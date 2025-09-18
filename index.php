<?php
/**
 * OpenSky Traveller Tools — Single-file PHP app
 * ------------------------------------------------------------
 * Features:
 * - Live map of aircraft in current view (bounded /states/all).
 * - Click a plane for details, show live track (/tracks?time=0).
 * - "Recent flights" by aircraft, and simple airport ARR/DEP lookup (requires auth).
 * - Server-side proxy (this file) performs OpenSky requests to avoid CORS,
 *   adds OAuth2 bearer token (or legacy Basic auth), caches, and rate-limit backoff.
 *
 * Docs referenced in implementation:
 * - REST base + /states/all + bbox params + fields: https://openskynetwork.github.io/opensky-api/rest.html
 * - Anonymous vs user limitations + OAuth2 client credentials flow: https://openskynetwork.github.io/opensky-api/rest.html
 * - Flights (all/aircraft/arrival/departure) + Tracks endpoint details & limits: https://openskynetwork.github.io/opensky-api/rest.html
 *
 * IMPORTANT:
 * - OpenSky API is research/non-commercial and credit-limited. Be respectful.
 * - Tracks endpoint is experimental and may be unavailable.
 * - Arrivals/Departures/Flights endpoints use batch-updated data (prev day or earlier).
 */

// -----------------------------------------------------------------------------
// Configuration: fill these or set as environment variables
// -----------------------------------------------------------------------------
$OPENSKY_CLIENT_ID     = getenv('OPENSKY_CLIENT_ID')     ?: ''; // e.g. 'your_client_id'
$OPENSKY_CLIENT_SECRET = getenv('OPENSKY_CLIENT_SECRET') ?: ''; // e.g. 'your_client_secret'

// Optional (legacy) Basic auth — deprecated for new accounts:
$OPENSKY_USERNAME      = getenv('OPENSKY_USERNAME')      ?: '';
$OPENSKY_PASSWORD      = getenv('OPENSKY_PASSWORD')      ?: '';

// App tuning
$CACHE_DIR             = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'opensky_cache';
$CACHE_TTL_STATES      = 8;    // seconds; states are ~10s granularity for anonymous users
$CACHE_TTL_OTHER       = 30;   // seconds for flights/arrivals/departures/tracks
$DEFAULT_REFRESH_SEC   = 15;   // JS polling (must be >=10s if anonymous)
$USER_AGENT            = 'OpenSkyTravellerTools/1.0 (+single-file-php)';

// Ensure cache dir exists
if (!is_dir($CACHE_DIR)) { @mkdir($CACHE_DIR, 0775, true); }

// -----------------------------------------------------------------------------
// Small utilities
// -----------------------------------------------------------------------------
function json_response($data, $code = 200) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_SLASHES);
    exit;
}

function parse_bool($v) { return filter_var($v, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE); }

function cache_key($prefix, $params) {
    ksort($params);
    return $prefix . '_' . substr(sha1(json_encode($params)), 0, 16) . '.json';
}

function cache_get($key, $ttl) {
    global $CACHE_DIR;
    $path = $CACHE_DIR . DIRECTORY_SEPARATOR . $key;
    if (is_file($path) && (time() - filemtime($path) <= $ttl)) {
        $raw = @file_get_contents($path);
        if ($raw !== false) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) return $decoded;
        }
    }
    return null;
}

function cache_set($key, $value) {
    global $CACHE_DIR;
    @file_put_contents($CACHE_DIR . DIRECTORY_SEPARATOR . $key, json_encode($value));
}

function have_oauth() {
    global $OPENSKY_CLIENT_ID, $OPENSKY_CLIENT_SECRET;
    return strlen($OPENSKY_CLIENT_ID) && strlen($OPENSKY_CLIENT_SECRET);
}
function have_basic() {
    global $OPENSKY_USERNAME, $OPENSKY_PASSWORD;
    return strlen($OPENSKY_USERNAME) && strlen($OPENSKY_PASSWORD);
}

// -----------------------------------------------------------------------------
// OAuth2 token (client credentials) — cached to file until expiry (~30 min)
// -----------------------------------------------------------------------------
function get_oauth_token() {
    if (!have_oauth()) return null;

    $tokenCache = cache_get('oauth_token', 60); // short TTL; we embed expiry within payload
    if ($tokenCache && isset($tokenCache['access_token'], $tokenCache['expires_at']) && $tokenCache['expires_at'] > time()+30) {
        return $tokenCache['access_token'];
    }

    $url = 'https://auth.opensky-network.org/auth/realms/opensky-network/protocol/openid-connect/token';
    $postFields = http_build_query([
        'grant_type'    => 'client_credentials',
        'client_id'     => getenv('OPENSKY_CLIENT_ID') ?: '',
        'client_secret' => getenv('OPENSKY_CLIENT_SECRET') ?: '',
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $postFields,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => false,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
    ]);
    $resp = curl_exec($ch);
    $err  = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($resp === false || $code >= 400) {
        return null;
    }
    $data = json_decode($resp, true);
    if (!isset($data['access_token'])) return null;

    $expiresIn = isset($data['expires_in']) ? (int)$data['expires_in'] : 1800;
    $data['expires_at'] = time() + max(60, $expiresIn - 30); // small safety margin
    cache_set('oauth_token', $data);
    return $data['access_token'];
}

// -----------------------------------------------------------------------------
// OpenSky request wrapper with auth (OAuth2 preferred, Basic fallback), caching,
// and graceful handling of rate-limits (429) via backoff hint headers.
// -----------------------------------------------------------------------------
function opensky_request($endpoint, $query = [], $cachePrefix = 'r', $cacheTtl = 10) {
    global $USER_AGENT, $OPENSKY_USERNAME, $OPENSKY_PASSWORD;

    // Build URL
    $base = 'https://opensky-network.org/api';
    $url  = rtrim($base, '/') . '/' . ltrim($endpoint, '/');
    if (!empty($query)) {
        $url .= '?' . http_build_query($query);
    }

    // Cache
    $ckey  = cache_key($cachePrefix, ['u' => $url]);
    $cdata = cache_get($ckey, $cacheTtl);
    if ($cdata !== null) return $cdata;

    // Prepare cURL
    $headers = ['User-Agent: ' . $USER_AGENT];
    $token   = get_oauth_token();
    $auth    = null;
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    } elseif (have_basic()) {
        $auth = $OPENSKY_USERNAME . ':' . $OPENSKY_PASSWORD;
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_HTTPHEADER     => $headers,
    ]);
    if ($auth) curl_setopt($ch, CURLOPT_USERPWD, $auth);

    $response = curl_exec($ch);
    $errno    = curl_errno($ch);
    $err      = curl_error($ch);
    $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSz = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);

    if ($errno) {
        $out = ['ok' => false, 'status' => 0, 'error' => 'cURL error: ' . $err];
        cache_set($ckey, $out);
        return $out;
    }

    $rawHeaders = substr($response, 0, $headerSz);
    $rawBody    = substr($response, $headerSz);
    $data       = json_decode($rawBody, true);

    // Parse rate-limit headers for client info
    $rate = [
        'remaining' => header_value($rawHeaders, 'X-Rate-Limit-Remaining'),
        'retry'     => header_value($rawHeaders, 'X-Rate-Limit-Retry-After-Seconds'),
    ];

    if ($code === 429) {
        $retry = isset($rate['retry']) ? (int)$rate['retry'] : 30;
        $out = ['ok' => false, 'status' => 429, 'error' => "Rate limit reached. Retry after {$retry}s.", 'rate' => $rate];
        cache_set($ckey, $out);
        return $out;
    }

    if ($code >= 400) {
        $msg = is_array($data) ? json_encode($data) : trim($rawBody);
        $out = ['ok' => false, 'status' => $code, 'error' => $msg ?: "HTTP $code", 'rate' => $rate];
        cache_set($ckey, $out);
        return $out;
    }

    $out = ['ok' => true, 'status' => $code, 'data' => $data, 'rate' => $rate];
    cache_set($ckey, $out);
    return $out;
}

function header_value($headers, $name) {
    foreach (explode("\r\n", $headers) as $line) {
        if (stripos($line, $name . ':') === 0) {
            return trim(substr($line, strlen($name) + 1));
        }
    }
    return null;
}

// -----------------------------------------------------------------------------
// API endpoints (proxied by this file)
// -----------------------------------------------------------------------------
if (isset($_GET['action'])) {
    $action = $_GET['action'];

    switch ($action) {
        case 'states':
            // Required: a bounded area to keep credit usage low (lamin, lamax, lomin, lomax)
            $lamin    = isset($_GET['lamin']) ? floatval($_GET['lamin']) : null;
            $lamax    = isset($_GET['lamax']) ? floatval($_GET['lamax']) : null;
            $lomin    = isset($_GET['lomin']) ? floatval($_GET['lomin']) : null;
            $lomax    = isset($_GET['lomax']) ? floatval($_GET['lomax']) : null;
            $extended = isset($_GET['extended']) ? intval($_GET['extended']) : 1;

            $params = ['extended' => $extended];
            // Only add bbox if fully present
            if ($lamin !== null && $lamax !== null && $lomin !== null && $lomax !== null) {
                $params['lamin'] = $lamin; $params['lamax'] = $lamax;
                $params['lomin'] = $lomin; $params['lomax'] = $lomax;
            }

            $resp = opensky_request('/states/all', $params, 'states', $CACHE_TTL_STATES);
            json_response($resp, $resp['ok'] ? 200 : 502);
            break;

        case 'tracks':
            // Tracks are experimental; time=0 ⇒ live track if any flight ongoing
            $icao24 = isset($_GET['icao24']) ? strtolower(trim($_GET['icao24'])) : '';
            $time   = isset($_GET['time']) ? intval($_GET['time']) : 0;
            if (!$icao24) json_response(['ok' => false, 'error' => 'Missing icao24'], 400);
            $resp = opensky_request('/tracks', ['icao24' => $icao24, 'time' => $time], 'tracks', $CACHE_TTL_OTHER);
            json_response($resp, $resp['ok'] ? 200 : 502);
            break;

        case 'flights_aircraft':
            $icao24 = isset($_GET['icao24']) ? strtolower(trim($_GET['icao24'])) : '';
            $begin  = isset($_GET['begin']) ? intval($_GET['begin']) : (time() - 48*3600); // last 48h
            $end    = isset($_GET['end'])   ? intval($_GET['end'])   : time();
            if (!$icao24) json_response(['ok' => false, 'error' => 'Missing icao24'], 400);
            // Endpoint strict rules: <= 2 days window; data updated next day
            $resp = opensky_request('/flights/aircraft', ['icao24' => $icao24, 'begin' => $begin, 'end' => $end], 'fl_ac', $CACHE_TTL_OTHER);
            json_response($resp, $resp['ok'] ? 200 : 502);
            break;

        case 'arrivals':
            $airport = isset($_GET['airport']) ? strtoupper(trim($_GET['airport'])) : '';
            $begin   = isset($_GET['begin']) ? intval($_GET['begin']) : (time() - 2*24*3600);
            $end     = isset($_GET['end'])   ? intval($_GET['end'])   : time();
            if (!$airport) json_response(['ok' => false, 'error' => 'Missing airport (ICAO)'], 400);
            $resp = opensky_request('/flights/arrival', ['airport' => $airport, 'begin' => $begin, 'end' => $end], 'arr', $CACHE_TTL_OTHER);
            json_response($resp, $resp['ok'] ? 200 : 502);
            break;

        case 'departures':
            $airport = isset($_GET['airport']) ? strtoupper(trim($_GET['airport'])) : '';
            $begin   = isset($_GET['begin']) ? intval($_GET['begin']) : (time() - 48*3600);
            $end     = isset($_GET['end'])   ? intval($_GET['end'])   : time();
            if (!$airport) json_response(['ok' => false, 'error' => 'Missing airport (ICAO)'], 400);
            $resp = opensky_request('/flights/departure', ['airport' => $airport, 'begin' => $begin, 'end' => $end], 'dep', $CACHE_TTL_OTHER);
            json_response($resp, $resp['ok'] ? 200 : 502);
            break;

        case 'auth_status':
            // Expose whether authenticated features are available (for UI)
            $mode = have_oauth() ? 'oauth2' : (have_basic() ? 'basic' : 'anonymous');
            json_response(['ok' => true, 'mode' => $mode]);
            break;

        default:
            json_response(['ok' => false, 'error' => 'Unknown action'], 400);
    }
}

// -----------------------------------------------------------------------------
// HTML UI below (same file). Uses Leaflet via CDN and fetches this file's
// proxy endpoints (?action=...).
// -----------------------------------------------------------------------------
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>OpenSky Traveller Tools — Live Aircraft & Flights</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <!-- Leaflet -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
  <script defer src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
          integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
  <style>
    :root {
      --bg: #0b1020;
      --card: #111831;
      --muted: #7f8bb2;
      --accent: #7dd3fc;
      --ok: #22c55e;
      --warn: #f59e0b;
      --err: #ef4444;
      --text: #e5e7eb;
    }
    * { box-sizing: border-box; }
    body {
      margin: 0; font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
      background: linear-gradient(180deg, #0b1020, #0e1430 60%, #0b1020);
      color: var(--text);
    }
    header {
      padding: 16px 20px; position: sticky; top: 0; z-index: 1000;
      background: rgba(11,16,32,0.9); backdrop-filter: blur(6px); border-bottom: 1px solid #1d274d;
    }
    .title { font-size: 20px; font-weight: 700; letter-spacing: .2px; }
    .subtitle { color: var(--muted); font-size: 13px; margin-top: 4px; }
    .container { padding: 12px 20px 20px 20px; display: grid; grid-template-columns: 1fr 360px; gap: 16px; }
    @media (max-width: 1100px) { .container { grid-template-columns: 1fr; } }
    #map { width: 100%; height: 72vh; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,.25); border: 1px solid #223064; }
    .card {
      background: var(--card); border: 1px solid #223064; border-radius: 16px; padding: 12px; box-shadow: 0 10px 30px rgba(0,0,0,.25);
    }
    .row { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
    .row > * { flex: 1; }
    .controls label { font-size: 12px; color: var(--muted); display: block; margin-bottom: 6px;}
    .controls input, .controls select, .controls button {
      width: 100%; padding: 10px 12px; border-radius: 12px; border: 1px solid #2a3a6b; background: #0c1430; color: var(--text);
    }
    .controls button { cursor: pointer; transition: transform .05s ease; font-weight: 600; }
    .controls button:active { transform: translateY(1px); }
    .pill { display:inline-flex; gap:8px; align-items:center; padding:6px 10px; border:1px solid #2a3a6b; border-radius:999px; font-size:12px; color:var(--muted); }
    .badge { padding:4px 8px; border-radius: 999px; font-size:11px; border:1px solid #2a3a6b; color: var(--muted); margin-left: 8px; }
    .muted { color: var(--muted); }
    .grid { display:grid; grid-template-columns: repeat(2, 1fr); gap:8px; }
    .grid > div { background:#0c1430; border:1px solid #2a3a6b; border-radius:12px; padding:8px; font-size:13px; }
    .list { max-height: 280px; overflow:auto; padding-right:2px;}
    .list-item { padding:8px; border-bottom: 1px solid #233166; font-size: 13px;}
    .warn { color: var(--warn); }
    .ok { color: var(--ok); }
    .err { color: var(--err); }
    footer { padding: 18px 20px; color: var(--muted); font-size: 12px; text-align: center;}
    a { color: var(--accent); text-decoration: none; }
    .rotate { transform-origin: center; }
  </style>
</head>
<body>
  <header>
    <div class="title">OpenSky Traveller Tools</div>
    <div class="subtitle">Live aircraft map, quick flight lookups, and tracks — powered by OpenSky (research/non-commercial).</div>
  </header>

  <div class="container">
    <div>
      <div class="card" style="margin-bottom:12px;">
        <div class="row" style="gap:10px; align-items:stretch;">
          <div class="pill" id="authPill">Auth: <strong id="authMode">checking…</strong></div>
          <div class="pill">Refresh: <strong id="refreshLbl"></strong></div>
          <div class="pill">Credits left: <strong id="creditsLeft">?</strong></div>
          <div style="flex:2; min-width: 220px;">
            <label>Auto-refresh (seconds)</label>
            <input id="refreshSec" type="number" min="10" step="1" value="<?php echo htmlspecialchars((string)$DEFAULT_REFRESH_SEC, ENT_QUOTES); ?>">
          </div>
          <div style="flex:1">
            <label>&nbsp;</label>
            <button id="useMyLocation">📍 Center on my location</button>
          </div>
          <div style="flex:1">
            <label>&nbsp;</label>
            <button id="reloadNow">🔄 Reload now</button>
          </div>
        </div>
      </div>

      <div id="map"></div>
    </div>

    <aside class="card">
      <div class="controls">
        <div class="row">
          <div>
            <label>Airport Arrivals (ICAO, e.g. DNMM)</label>
            <input id="arrIcao" placeholder="DNMM">
          </div>
          <div>
            <label>&nbsp;</label>
            <button id="btnArr">Fetch Arrivals</button>
          </div>
        </div>
        <div class="row" style="margin-top:8px;">
          <div>
            <label>Airport Departures (ICAO, e.g. DNMM)</label>
            <input id="depIcao" placeholder="DNMM">
          </div>
          <div>
            <label>&nbsp;</label>
            <button id="btnDep">Fetch Departures</button>
          </div>
        </div>
      </div>

      <div class="card" style="margin-top:12px;">
        <div class="row" style="justify-content:space-between;">
          <div><strong>Selected aircraft</strong> <span id="selIcao" class="badge">—</span></div>
          <div class="muted" style="font-size:12px;">Click a plane on the map</div>
        </div>
        <div class="grid" style="margin-top:8px;">
          <div><div class="muted">Callsign</div><div id="f_callsign">—</div></div>
          <div><div class="muted">Country</div><div id="f_country">—</div></div>
          <div><div class="muted">Altitude</div><div id="f_alt">—</div></div>
          <div><div class="muted">Speed</div><div id="f_spd">—</div></div>
          <div><div class="muted">Track</div><div id="f_track">—</div></div>
          <div><div class="muted">Last contact</div><div id="f_last">—</div></div>
        </div>
        <div class="row" style="margin-top:10px;">
          <button id="btnTrack">Show Live Track</button>
          <button id="btnRecent">Recent Flights</button>
        </div>
        <div id="infoMsg" class="muted" style="margin-top:8px;font-size:12px;">Tracks are experimental; flights update daily.</div>
      </div>

      <div class="card" style="margin-top:12px;">
        <strong>Results</strong>
        <div id="results" class="list"></div>
      </div>

      <div class="card" style="margin-top:12px;">
        <strong>Tips</strong>
        <div class="muted" style="font-size:12px; margin-top:6px;">
          • Keep the map zoomed to your area to use fewer credits. <br>
          • Anonymous mode has ~10s time resolution and no historical states. <br>
          • For airport lookups and recent flights, add OAuth2 credentials on the server. <br>
          • Non-commercial, rate-limited: be kind to the API. 💙
        </div>
      </div>
    </aside>
  </div>

  <footer>
    Built with ❤️ on Leaflet & OpenSky. This tool fetches only public ADS-B data and is intended for research/non-commercial use.
  </footer>

  <script>
    // Helpers
    const $ = sel => document.querySelector(sel);
    const fmt = {
      ts: s => s ? new Date(s*1000).toLocaleString() : '—',
      alt: m => (m==null ? '—' : Math.round(m*3.28084).toLocaleString() + ' ft'),
      spd: ms => (ms==null ? '—' : Math.round(ms*1.94384) + ' kt'),
      deg: d  => (d==null ? '—' : Math.round(d) + '°'),
      callsign: c => (c || '—').trim(),
    };

    // State
    let map, layerGroup, refreshTimer=null, selected=null, authMode='anonymous';
    const authPill = $('#authPill'), creditsLeft = $('#creditsLeft');

    // Map init
    function initMap() {
      map = L.map('map', { zoomControl:true, minZoom: 2, worldCopyJump:true }).setView([6.5244, 3.3792], 6); // Lagos-ish default
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 12, attribution: '&copy; OpenStreetMap contributors'
      }).addTo(map);
      layerGroup = L.layerGroup().addTo(map);
      map.on('moveend', () => loadStates());
    }

    // Fetch auth status (so UI can warn/enable)
    async function fetchAuthStatus() {
      try {
        const r = await fetch('?action=auth_status');
        const j = await r.json();
        if (j.ok) {
          authMode = j.mode;
          $('#authMode').textContent = authMode;
          if (authMode === 'anonymous') {
            authPill.classList.remove('ok'); authPill.classList.add('warn');
          } else {
            authPill.classList.remove('warn'); authPill.classList.add('ok');
          }
        }
      } catch (e) {}
    }

    // Load states for current bounds (extended=1 to get category)
    async function loadStates() {
      if (!map) return;
      const b = map.getBounds();
      const url = `?action=states&lamin=${b.getSouth()}&lamax=${b.getNorth()}&lomin=${b.getWest()}&lomax=${b.getEast()}&extended=1`;
      try {
        const r = await fetch(url);
        const j = await r.json();
        // Rate-limit info (if present)
        if (j && j.rate && j.rate.remaining) creditsLeft.textContent = j.rate.remaining; else creditsLeft.textContent = '?';
        if (!j.ok) {
          console.warn('states error', j);
          return;
        }
        renderStates(j.data);
      } catch (e) {
        console.error(e);
      }
    }

    function renderStates(data) {
      layerGroup.clearLayers();
      if (!data || !data.states || !Array.isArray(data.states)) return;
      const icon = L.divIcon({className:'plane', html:'✈️', iconSize:[24,24], iconAnchor:[12,12]});

      data.states.forEach(s => {
        // fields defined in the docs: [icao24, callsign, origin_country, time_position, last_contact, lon, lat, baro_altitude, on_ground, velocity, true_track, vertical_rate, sensors, geo_altitude, squawk, spi, position_source, category]
        const [icao24, callsign, country, tpos, last, lon, lat, baro_alt, on_ground, vel, track] = s;
        if (lat==null || lon==null) return;

        const marker = L.marker([lat, lon], { icon });
        const rot = (track==null ? 0 : track);
        marker._icon.style.transform = `rotate(${rot}deg)`;
        marker.on('click', () => {
          selected = { icao24, callsign, country, baro_alt, vel, track, last };
          updateSelected();
        });
        marker.addTo(layerGroup);
      });
    }

    function updateSelected() {
      if (!selected) return;
      $('#selIcao').textContent = selected.icao24 || '—';
      $('#f_callsign').textContent = fmt.callsign(selected.callsign);
      $('#f_country').textContent  = selected.country || '—';
      $('#f_alt').textContent      = fmt.alt(selected.baro_alt);
      $('#f_spd').textContent      = fmt.spd(selected.vel);
      $('#f_track').textContent    = fmt.deg(selected.track);
      $('#f_last').textContent     = selected.last ? new Date(selected.last*1000).toLocaleTimeString() : '—';
    }

    // Live track (time=0)
    async function showTrack() {
      if (!selected) return notify('Select an aircraft first.');
      const url = `?action=tracks&icao24=${encodeURIComponent(selected.icao24)}&time=0`;
      const res = $('#results'); res.innerHTML = '';
      try {
        const r = await fetch(url); const j = await r.json();
        if (!j.ok) return notify('Track unavailable (endpoint is experimental or no ongoing flight).');
        const path = (j.data && j.data.path) ? j.data.path : [];
        if (!path.length) return notify('No track points.');
        const latlngs = path.map(p => [p[1], p[2]]).filter(v => v[0]!=null && v[1]!=null);
        if (latlngs.length) {
          L.polyline(latlngs, {weight:3, opacity:.9}).addTo(layerGroup);
          map.fitBounds(L.latLngBounds(latlngs), {padding:[30,30]});
        }
        notify(`Track points: ${latlngs.length}`);
      } catch (e) { notify('Error fetching track.'); }
    }

    // Recent flights for aircraft
    async function recentFlights() {
      if (!selected) return notify('Select an aircraft first.');
      if (authMode === 'anonymous') return notify('Recent flights require server auth (OAuth2 or legacy).');
      const now = Math.floor(Date.now()/1000), begin = now - 48*3600, end = now;
      const url = `?action=flights_aircraft&icao24=${encodeURIComponent(selected.icao24)}&begin=${begin}&end=${end}`;
      const res = $('#results'); res.innerHTML = '';
      try {
        const r = await fetch(url); const j = await r.json();
        if (!j.ok) return notify('No recent flights (remember: flights update after the day ends).');
        const flights = j.data || [];
        if (!flights.length) return notify('No flights for this window.');
        res.innerHTML = flights.map(f => `
          <div class="list-item">
            <div><strong>${f.callsign || '—'}</strong> <span class="muted">${f.icao24}</span></div>
            <div class="muted">${f.estDepartureAirport || '?'} → ${f.estArrivalAirport || '?'}</div>
            <div class="muted">${fmt.ts(f.firstSeen)} → ${fmt.ts(f.lastSeen)}</div>
          </div>
        `).join('');
      } catch (e) { notify('Error fetching recent flights.'); }
    }

    // Airport arrivals
    async function fetchArrivals() {
      const icao = $('#arrIcao').value.trim().toUpperCase();
      if (!icao) return notify('Enter an airport ICAO (e.g., DNMM).');
      if (authMode === 'anonymous') return notify('Arrivals require server auth (OAuth2 or legacy).');
      const now = Math.floor(Date.now()/1000), begin = now - 48*3600, end = now - 60; // batch-updated
      const url = `?action=arrivals&airport=${encodeURIComponent(icao)}&begin=${begin}&end=${end}`;
      return renderAirport(url, `Arrivals for ${icao}`);
    }

    // Airport departures
    async function fetchDepartures() {
      const icao = $('#depIcao').value.trim().toUpperCase();
      if (!icao) return notify('Enter an airport ICAO (e.g., DNMM).');
      if (authMode === 'anonymous') return notify('Departures require server auth (OAuth2 or legacy).');
      const now = Math.floor(Date.now()/1000), begin = now - 48*3600, end = now - 60;
      const url = `?action=departures&airport=${encodeURIComponent(icao)}&begin=${begin}&end=${end}`;
      return renderAirport(url, `Departures for ${icao}`);
    }

    async function renderAirport(url, title) {
      const res = $('#results'); res.innerHTML = '';
      try {
        const r = await fetch(url); const j = await r.json();
        if (!j.ok) return notify('No results (remember: airport data is batch-updated).');
        const flights = j.data || [];
        if (!flights.length) return notify('No results for that window.');
        res.innerHTML = `<div class="list-item"><strong>${title}</strong></div>` + flights.map(f => `
          <div class="list-item">
            <div><strong>${f.callsign || '—'}</strong> <span class="muted">${f.icao24}</span></div>
            <div class="muted">${f.estDepartureAirport || '?'} → ${f.estArrivalAirport || '?'}</div>
            <div class="muted">${fmt.ts(f.firstSeen)} → ${fmt.ts(f.lastSeen)}</div>
          </div>
        `).join('');
      } catch (e) { notify('Error fetching airport data.'); }
    }

    function notify(msg) {
      const res = $('#results');
      res.innerHTML = `<div class="list-item">${msg}</div>`;
    }

    // UI wiring
    window.addEventListener('DOMContentLoaded', async () => {
      initMap();
      await fetchAuthStatus();
      $('#refreshLbl').textContent = document.querySelector('#refreshSec').value + 's';
      loadStates();

      $('#reloadNow').addEventListener('click', loadStates);
      $('#refreshSec').addEventListener('change', () => {
        const n = Math.max(10, parseInt($('#refreshSec').value || '10', 10));
        $('#refreshSec').value = n;
        $('#refreshLbl').textContent = n + 's';
        if (refreshTimer) clearInterval(refreshTimer);
        refreshTimer = setInterval(loadStates, n*1000);
      });
      // Start initial timer
      refreshTimer = setInterval(loadStates, Math.max(10, parseInt($('#refreshSec').value, 10))*1000);

      $('#useMyLocation').addEventListener('click', () => {
        if (!navigator.geolocation) return alert('Geolocation unsupported.');
        navigator.geolocation.getCurrentPosition(pos => {
          const {latitude, longitude} = pos.coords;
          map.setView([latitude, longitude], 9);
        }, () => alert('Unable to get location.'));
      });

      $('#btnTrack').addEventListener('click', showTrack);
      $('#btnRecent').addEventListener('click', recentFlights);
      $('#btnArr').addEventListener('click', fetchArrivals);
      $('#btnDep').addEventListener('click', fetchDepartures);
    });
  </script>
</body>
</html>
