<?php
/**
 * travelhint.info — Landing Page (Single File PHP)
 *
 * Goals:
 * - Clear, policy-safe positioning (we do NOT sell flights, tickets, or travel services).
 * - Useful original content & tools (informational only).
 * - Clean UX, fast, mobile-first, accessible.
 *
 * Notes:
 * - Blog images use Unsplash "featured" endpoints (free for commercial use under Unsplash License).
 * - Tools are client-side; free/public APIs used where possible (no keys needed).
 * - If you later add APIs with keys, hide keys server-side or via a proxy—never expose in client JS.
 *
 * PHP is used mainly to set basic server-side values if needed; the page is primarily static for speed.
 */
date_default_timezone_set('UTC');
$year = date('Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>TravelHint — Smarter Travel, No Sales Pitch</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="description" content="TravelHint.info offers free travel guides & tools — travel advisories, currency conversion, and a live aircraft tracker — no booking, no sales. We are not a travel agency." />
  <link rel="canonical" href="https://travelhint.info/" />
  <!-- Leaflet (map for Live Flight Tracker) -->
  <link
    rel="stylesheet"
    href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
    crossorigin=""
  />
  <style>
    :root{
      --bg:#0b1320; --fg:#f6f7fb; --muted:#9aa4b2; --brand:#4f8cff; --ok:#18b26b; --warn:#efb034; --danger:#e74c3c;
      --card:#121a2b; --stroke:#1e2a44; --chip:#1a2338; --link:#79aaff;
    }
    *{box-sizing:border-box}
    html,body{margin:0;padding:0;background:var(--bg);color:var(--fg);font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Cantarell,Noto Sans,sans-serif;line-height:1.55}
    a{color:var(--link);text-decoration:none}
    a:hover{text-decoration:underline}
    .container{max-width:1180px;margin:0 auto;padding:0 16px}
    header.topbar{border-bottom:1px solid var(--stroke);background:linear-gradient(180deg,#0d1730 0%, #0b1320 100%)}
    .bar{display:flex;align-items:center;justify-content:space-between;padding:10px 0}
    .brand{display:flex;align-items:center;gap:10px;font-weight:700;letter-spacing:.3px}
    .brand svg{display:block}
    nav a{margin:0 10px;color:var(--fg);opacity:.9}
    nav a:hover{opacity:1}
    .disclaimer{font-size:.85rem;color:var(--muted)}
    .hero{padding:48px 0 24px;border-bottom:1px solid var(--stroke)}
    .hero h1{margin:0 0 10px;font-size:clamp(28px,4vw,44px);line-height:1.15}
    .hero p{margin:0 0 16px;color:#dce3ee;max-width:60ch}
    .chip{display:inline-flex;align-items:center;gap:8px;font-size:.85rem;color:#d5def0;background:var(--chip);border:1px solid var(--stroke);padding:6px 10px;border-radius:999px}
    .cta-row{display:flex;gap:12px;flex-wrap:wrap;margin-top:14px}
    .btn{padding:10px 14px;border-radius:10px;border:1px solid var(--stroke);background:#112040;color:var(--fg);font-weight:600}
    .btn:hover{background:#12264f}
    .btn.secondary{background:transparent}
    .section{padding:36px 0;border-bottom:1px solid var(--stroke)}
    .section h2{margin:0 0 14px;font-size:clamp(22px,3vw,28px)}
    .grid{display:grid;gap:16px}
    @media(min-width:900px){.grid.cols-3{grid-template-columns:repeat(3,1fr)}}
    .card{background:var(--card);border:1px solid var(--stroke);border-radius:14px;padding:16px}
    .card h3{margin:0 0 8px}
    .card p{margin:0;color:#d6deea}
    .tool-body{margin-top:12px}
    .tool-row{display:flex;gap:10px;flex-wrap:wrap}
    .input, select{background:#0b1426;border:1px solid var(--stroke);border-radius:10px;color:var(--fg);padding:10px;width:100%}
    .label{font-size:.9rem;color:#c3ccdb;margin:.25rem 0 .4rem}
    .pill{display:inline-block;padding:6px 10px;border-radius:999px;background:#0f1a30;border:1px solid var(--stroke);font-size:.8rem;color:#d0d9ea}
    .small{font-size:.85rem;color:#b9c3d4}
    .muted{color:var(--muted)}
    .list{list-style:none;padding:0;margin:0}
    .list li{padding:6px 0;border-bottom:1px dashed var(--stroke)}
    .list li:last-child{border-bottom:none}
    .kpi{display:flex;gap:12px;flex-wrap:wrap;margin:10px 0}
    .kpi .box{padding:10px 12px;border-radius:10px;background:#0f1930;border:1px solid var(--stroke)}
    .flex{display:flex;gap:12px;align-items:center;flex-wrap:wrap}
    .map{height:380px;border-radius:12px;overflow:hidden;border:1px solid var(--stroke)}
    .blog-grid{display:grid;gap:16px}
    @media(min-width:800px){.blog-grid{grid-template-columns:repeat(3,1fr)}}
    .blog-card{background:var(--card);border:1px solid var(--stroke);border-radius:12px;overflow:hidden;display:flex;flex-direction:column}
    .blog-card img{width:100%;height:180px;object-fit:cover;display:block}
    .blog-card .pad{padding:14px}
    footer{padding:28px 0;color:#cbd5e1}
    .footgrid{display:grid;gap:16px}
    @media(min-width:800px){.footgrid{grid-template-columns:2fr 1fr 1fr}}
    .fineprint{font-size:.8rem;color:#9fb0c7}
    .divider{height:1px;background:var(--stroke);margin:16px 0}
    .sr-only{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);border:0}
    /* Sparkline canvas */
    canvas.spark{width:100%;height:72px;background:#0a1426;border:1px solid var(--stroke);border-radius:10px}
  </style>
</head>
<body>
  <!-- Topbar with brand, nav, and compliance-forward disclaimer -->
  <header class="topbar">
    <div class="container bar">
      <div class="brand" aria-label="TravelHint brand">
        <!-- Compass/Globe icon -->
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <circle cx="12" cy="12" r="10" stroke="#79aaff" stroke-width="2"/>
          <path d="M12 2a16 16 0 000 20a16 16 0 000-20Z" fill="none" stroke="#79aaff" stroke-width="1.2"/>
          <path d="M2 12h20M12 2v20" stroke="#79aaff" stroke-width="1.2"/>
        </svg>
        <span>TravelHint.info</span>
      </div>
      <nav aria-label="Main">
        <a href="#home">Home</a>
        <a href="#tools">Tools</a>
        <a href="#blog">Blog</a>
        <a href="mailto:mail@travelhint.info">Contact</a>
      </nav>
    </div>
    <div class="container" style="padding:6px 0 12px;">
      <div class="disclaimer">
        <strong>Transparency:</strong> TravelHint.info is an <em>independent information website</em>. We are <strong>not</strong> a travel agency and we <strong>do not sell</strong> flights, tickets, hotels, insurance, or any travel services. Our content and tools are free and educational. Always confirm details with official sources before you travel.
      </div>
    </div>
  </header>

  <main id="home">
    <!-- HERO -->
    <section class="hero container" aria-label="Hero">
      <span class="chip">Free tools & guides • No booking • No upsells</span>
      <h1>Smarter Travel, Zero Sales Pitch</h1>
      <p>
        Plan with confidence using our free tools: country advisories & essentials, a travel-friendly currency converter with trends,
        and a live aircraft map for avgeek fun. Learn, compare, and prepare — then book on your preferred airline or OTA.
      </p>
      <div class="cta-row">
        <a class="btn" href="#tools">Explore Tools</a>
        <a class="btn secondary" href="#blog">Read New Articles</a>
      </div>
      <div class="kpi">
        <div class="box"><span class="small">No booking here</span></div>
        <div class="box"><span class="small">Original content & tools</span></div>
        <div class="box"><span class="small">Policy-safe destination</span></div>
      </div>
    </section>

    <!-- TOOLS -->
    <section id="tools" class="section container" aria-label="Tools">
      <h2>Tools for Travelers</h2>
      <div class="grid cols-3">
        <!-- Travel Advisor -->
        <article class="card" aria-labelledby="ta-title">
          <div class="flex">
            <!-- Globe icon -->
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <circle cx="12" cy="12" r="10" stroke="#4f8cff" stroke-width="2"/>
              <path d="M2 12h20M12 2v20" stroke="#4f8cff" stroke-width="1.3"/>
              <path d="M12 2a16 16 0 000 20" stroke="#4f8cff" stroke-width=".9"/>
            </svg>
            <h3 id="ta-title" style="margin:0">Travel Advisor</h3>
          </div>
          <p class="small">Country basics at a glance — capital, currency, local time, today’s weather, and upcoming public holidays.</p>
          <div class="tool-body">
            <label for="ta-country" class="label">Choose a country</label>
            <div class="tool-row">
              <input id="ta-country" class="input" placeholder="e.g., United States, Nigeria, Japan" />
              <button class="btn" id="ta-run">Check</button>
            </div>
            <div id="ta-result" class="small" style="margin-top:10px"></div>
            <p class="muted" style="margin-top:8px">
              Data: REST Countries, WorldTimeAPI, Nager.Date (holidays), Open-Meteo weather (no API keys required).
            </p>
          </div>
        </article>

        <!-- Travel Exchange Rate Checker -->
        <article class="card" aria-labelledby="fx-title">
          <div class="flex">
            <!-- Calculator icon -->
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <rect x="3" y="3" width="18" height="18" rx="2" stroke="#18b26b" stroke-width="2"/>
              <rect x="7" y="7" width="10" height="4" rx="1" stroke="#18b26b" stroke-width="1.3"/>
              <rect x="7" y="13" width="4" height="4" rx="1" stroke="#18b26b" stroke-width="1.3"/>
              <rect x="13" y="13" width="4" height="4" rx="1" stroke="#18b26b" stroke-width="1.3"/>
            </svg>
            <h3 id="fx-title" style="margin:0">Travel Exchange Rate Checker</h3>
          </div>
          <p class="small">Convert amounts, compare currencies, view 7-day trends, and estimate card fees vs. cash.</p>
          <div class="tool-body">
            <div class="tool-row">
              <div style="flex:1;min-width:120px">
                <label class="label" for="fx-amount">Amount</label>
                <input id="fx-amount" class="input" type="number" value="100" min="0" step="0.01" />
              </div>
              <div style="flex:1;min-width:140px">
                <label class="label" for="fx-base">From</label>
                <select id="fx-base" class="input"></select>
              </div>
              <div style="flex:1;min-width:140px">
                <label class="label" for="fx-quote">To</label>
                <select id="fx-quote" class="input"></select>
              </div>
              <button class="btn" id="fx-run" style="align-self:flex-end">Convert</button>
            </div>
            <div class="kpi" id="fx-kpi" role="status" aria-live="polite"></div>
            <div>
              <label class="label" for="fx-fee">Estimate card fee % (cash vs card)</label>
              <input id="fx-fee" class="input" type="range" min="0" max="5" step="0.25" value="3" />
              <div class="small">Estimated fee: <span id="fx-fee-val">3%</span></div>
            </div>
            <div style="margin-top:10px">
              <label class="label">7-day trend (<span id="fx-trend-label"></span>)</label>
              <canvas id="fx-spark" class="spark" height="72"></canvas>
            </div>
            <details style="margin-top:10px">
              <summary class="small">Tip guide (quick reference)</summary>
              <ul class="list small">
                <li><strong>USA/Canada:</strong> 15–20% restaurants (pre-tax), $2–$5 per bag for porters.</li>
                <li><strong>Japan:</strong> No tipping culture; service charge often included.</li>
                <li><strong>EU (varies):</strong> Often included; round up or 5–10% for great service.</li>
                <li><strong>Nigeria:</strong> 5–10% in restaurants if service not included.</li>
              </ul>
            </details>
            <p class="muted" style="margin-top:8px">Rates: Frankfurter (ECB reference). No API key required.</p>
          </div>
        </article>

        <!-- Live Flight Tracker (informational only) -->
        <article class="card" aria-labelledby="flt-title">
          <div class="flex">
            <!-- Plane icon -->
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M2 16l7-2 5 6 2-1-3-7 7-2 1-2-9 1-3-7-2 1 1 8-6 3z" stroke="#efb034" stroke-width="1.4" fill="none"/>
            </svg>
            <h3 id="flt-title" style="margin:0">Live Flight Tracker</h3>
          </div>
          <p class="small">See aircraft on a map, filter by callsign/ICAO24, and watch nearby air traffic. <strong>For information & avgeek fun only.</strong></p>
          <div class="tool-body">
            <div class="tool-row">
              <input id="flt-search" class="input" placeholder="Search callsign or ICAO24 (e.g., KLM, ADBF21)" />
              <button class="btn" id="flt-run">Refresh</button>
            </div>
            <div id="map" class="map" role="application" aria-label="Live aircraft map"></div>
            <p class="muted" style="margin-top:8px">Data: OpenSky Network (live ADS-B). Anonymous requests are rate-limited and intended for research/non-commercial use.</p>
          </div>
        </article>
      </div>
    </section>

    <!-- ABOUT / TRUST -->
    <section class="section container" aria-label="About TravelHint">
      <h2>What We Do (and Don’t)</h2>
      <div class="grid" style="gap:10px">
        <div class="card">
          <h3>Independent, Educational Content</h3>
          <p>TravelHint shares practical travel know-how and free tools so you can plan smarter.
             We publish original guides, checklists, and utilities that help you compare options before booking on the airline or OTA you trust.</p>
        </div>
        <div class="card">
          <h3>Not a Seller, Not an Agency</h3>
          <p>We don’t sell or broker flights, and we don’t run a call center. No “book now” buttons, no hidden fees, no paid phone lines—just information and tools.</p>
        </div>
        <div class="card">
          <h3>Accuracy & Sources</h3>
          <p>Always verify time-sensitive items (visas, entry rules, schedules) with official sources. Our tools pull open data from reputable APIs and show when live data is unavailable.</p>
        </div>
      </div>
    </section>

    <!-- BLOG -->
    <section id="blog" class="section container" aria-label="Blog">
      <h2>Latest from the Blog</h2>
      <div class="blog-grid">
        <!-- Newest -->
        <article class="blog-card">
          <img src="https://source.unsplash.com/featured/800x600/?city,street,travel" alt="City street travel scene" loading="lazy" />
          <div class="pad">
            <h3 style="margin:0 0 6px">10 Underrated Cities You Should Visit Before Everyone Else Does</h3>
            <p class="small" style="margin:0 0 10px">Fresh picks for surprising food, culture, and weekend vibes.</p>
            <a class="pill" href="/underrated-cities">Read</a>
          </div>
        </article>
        <!-- Second -->
        <article class="blog-card">
          <img src="https://source.unsplash.com/featured/800x600/?travel,flatlay,gadgets" alt="Travel gadgets flat lay" loading="lazy" />
          <div class="pad">
            <h3 style="margin:0 0 6px">7 Travel Gadgets That Make Your Trips Easier</h3>
            <p class="small" style="margin:0 0 10px">Compact, useful gear that earns its space in your bag.</p>
            <a class="pill" href="/gadget-lists">Read</a>
          </div>
        </article>
        <!-- Oldest -->
        <article class="blog-card">
          <img src="https://source.unsplash.com/featured/800x600/?flight,booking,laptop" alt="Laptop with flight search on screen" loading="lazy" />
          <div class="pad">
            <h3 style="margin:0 0 6px">Top 7 Websites to Book Flights Safely in 2025</h3>
            <p class="small" style="margin:0 0 10px">Pros, cons & hidden fees — learn before you book elsewhere.</p>
            <a class="pill" href="/website-lists">Read</a>
          </div>
        </article>
      </div>
      <p class="muted small" style="margin-top:10px">
        Images via Unsplash free license (commercial use allowed, attribution appreciated).
      </p>
    </section>
  </main>

  <!-- FOOTER -->
  <footer>
    <div class="container footgrid">
      <div>
        <h3 style="margin:0 0 8px">TravelHint.info</h3>
        <p class="fineprint">
          © <?= $year ?> TravelHint. Free travel info & tools. Not a travel agency and not affiliated with airlines or booking sites.
          For questions: <a href="mailto:mail@travelhint.info">mail@travelhint.info</a>
        </p>
        <div class="divider"></div>
        <p class="fineprint">
          Data sources used on this page: REST Countries, WorldTimeAPI, Nager.Date, Open-Meteo, Frankfurter (ECB rates), OpenSky Network, OpenStreetMap/Leaflet, Unsplash images.
        </p>
      </div>
      <div>
        <h4>Menu</h4>
        <ul class="list">
          <li><a href="#home">Home</a></li>
          <li><a href="#tools">Tools</a></li>
          <li><a href="#blog">Blog</a></li>
          <li><a href="mailto:mail@travelhint.info">Contact</a></li>
        </ul>
      </div>
      <div>
        <h4>Legal</h4>
        <ul class="list">
          <li><a href="/privacy">Privacy Policy</a></li>
          <li><a href="/terms">Terms &amp; Conditions</a></li>
          <li><a href="https://unsplash.com/license" rel="noopener" target="_blank">Unsplash License</a></li>
        </ul>
      </div>
    </div>
    <div class="container" style="margin-top:10px">
      <p class="fineprint">
        <strong>Editorial & Ads Disclosure:</strong> We don’t sell flights or operate a booking service. Our pages are designed to be useful destinations with original content and clear intent.
        If we ever include affiliate links in the future, they will be disclosed and placed alongside genuine comparisons and guides — not as “bridge” pages.
      </p>
    </div>
  </footer>

  <!-- JS: Tools Logic -->
  <script
    src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
    crossorigin=""
  ></script>
  <script>
    // ---------- UTIL ----------
    const $ = sel => document.querySelector(sel);
    const fmt = (n, d=2) => Number(n).toLocaleString(undefined, {maximumFractionDigits:d});
    const todayISO = () => new Date().toISOString().slice(0,10);

    // ---------- TRAVEL ADVISOR ----------
    async function travelAdvisor(countryName){
      const out = $('#ta-result');
      out.innerHTML = '<span class="small">Loading…</span>';
      try{
        // 1) Country basics
        const cRes = await fetch(`https://restcountries.com/v3.1/name/${encodeURIComponent(countryName)}?fields=name,cca2,capital,capitalInfo,currencies,languages,timezones,idd,flags,region,population`);
        if(!cRes.ok) throw new Error('Country not found.');
        const data = await cRes.json();
        const c = data[0];
        // Extracts
        const cca2 = c.cca2;
        const capital = (c.capital && c.capital[0]) || '—';
        const tz = (c.timezones && c.timezones[0]) || null;
        const cur = c.currencies ? Object.keys(c.currencies)[0] : '—';
        const langList = c.languages ? Object.values(c.languages).slice(0,3).join(', ') : '—';
        const lat = (c.capitalInfo && c.capitalInfo.latlng) ? c.capitalInfo.latlng[0] : (c.latlng ? c.latlng[0] : null);
        const lon = (c.capitalInfo && c.capitalInfo.latlng) ? c.capitalInfo.latlng[1] : (c.latlng ? c.latlng[1] : null);

        // 2) Local time
        let timeStr = '—';
        if(tz){
          const tRes = await fetch(`https://worldtimeapi.org/api/timezone/${encodeURIComponent(tz)}`);
          if(tRes.ok){
            const t = await tRes.json();
            timeStr = new Date(t.datetime).toLocaleString();
          }
        }

        // 3) Weather (Open-Meteo, no key)
        let weatherStr = '—';
        if(lat!=null && lon!=null){
          const wRes = await fetch(`https://api.open-meteo.com/v1/forecast?latitude=${lat}&longitude=${lon}&current_weather=true&timezone=auto`);
          if(wRes.ok){
            const w = await wRes.json();
            if(w.current_weather){
              weatherStr = `${w.current_weather.temperature}°C, wind ${w.current_weather.windspeed} km/h`;
            }
          }
        }

        // 4) Next public holidays (Nager.Date)
        let holiHTML = '<li class="muted">No upcoming holidays found.</li>';
        const hRes = await fetch(`https://date.nager.at/api/v3/NextPublicHolidays/${cca2}`);
        if(hRes.ok){
          const hs = await hRes.json();
          if(Array.isArray(hs) && hs.length){
            holiHTML = hs.slice(0,5).map(h=>`<li><strong>${h.date}</strong> — ${h.localName} (${h.name})</li>`).join('');
          }
        }

        out.innerHTML = `
          <div class="grid" style="gap:10px">
            <div class="card">
              <div class="flex"><img src="${c.flags?.png || ''}" alt="" width="24" height="16" style="border:1px solid var(--stroke)"/> <strong>${c.name?.common || countryName}</strong></div>
              <p class="small muted" style="margin-top:6px">Region: ${c.region || '—'} · Languages: ${langList}</p>
            </div>
            <div class="card">
              <div class="label">Capital</div>
              <div class="pill">${capital}</div>
              <div class="label" style="margin-top:8px">Local time</div>
              <div class="pill">${timeStr}</div>
            </div>
            <div class="card">
              <div class="label">Currency</div>
              <div class="pill">${cur}</div>
              <div class="label" style="margin-top:8px">Weather in ${capital}</div>
              <div class="pill">${weatherStr}</div>
            </div>
            <div class="card" style="grid-column:1/-1">
              <div class="label">Upcoming public holidays</div>
              <ul class="list small">${holiHTML}</ul>
            </div>
          </div>
        `;
      }catch(err){
        out.innerHTML = `<span class="small" style="color:var(--danger)">Oops: ${err.message}</span>`;
      }
    }
    $('#ta-run').addEventListener('click', ()=>{
      const name = ($('#ta-country').value || '').trim();
      if(!name){ alert('Type a country name.'); return; }
      travelAdvisor(name);
    });

    // ---------- EXCHANGE RATE CHECKER ----------
    const fxState = { currencies:{}, base:'USD', quote:'EUR', amount:100, trendDays:7, lastSeries:[] };

    async function fxLoadCurrencies(){
      const res = await fetch('https://api.frankfurter.app/currencies');
      const data = await res.json();
      fxState.currencies = data;
      const baseSel = $('#fx-base'), quoteSel = $('#fx-quote');
      const opts = Object.entries(data).map(([code,name]) => `<option value="${code}">${code} — ${name}</option>`).join('');
      baseSel.innerHTML = opts;
      quoteSel.innerHTML = opts;
      baseSel.value = fxState.base;
      quoteSel.value = fxState.quote;
    }
    function fxDrawSpark(values){
      const cv = $('#fx-spark'); const ctx = cv.getContext('2d');
      const w = cv.width = cv.clientWidth; const h = cv.height = cv.clientHeight;
      ctx.clearRect(0,0,w,h);
      if(!values.length){ return; }
      const min = Math.min(...values), max = Math.max(...values);
      const pad = 8;
      ctx.lineWidth = 2; ctx.strokeStyle = '#79aaff';
      ctx.beginPath();
      values.forEach((v,i)=>{
        const x = pad + (i*(w-2*pad)/(values.length-1));
        const y = h - pad - ((v - min) / (max - min || 1))*(h-2*pad);
        if(i===0) ctx.moveTo(x,y); else ctx.lineTo(x,y);
      });
      ctx.stroke();
      // Fill
      ctx.lineTo(w-pad,h-pad); ctx.lineTo(pad,h-pad); ctx.closePath();
      ctx.fillStyle = 'rgba(79,140,255,0.15)';
      ctx.fill();
    }
    async function fxConvert(){
      const amount = parseFloat($('#fx-amount').value||'0')||0;
      const base = $('#fx-base').value; const quote = $('#fx-quote').value;
      fxState.amount = amount; fxState.base = base; fxState.quote = quote;

      const r = await fetch(`https://api.frankfurter.app/latest?from=${base}&to=${quote}`);
      const j = await r.json();
      const rate = j.rates?.[quote];
      const converted = rate ? amount * rate : 0;

      // KPI boxes
      const feePct = parseFloat($('#fx-fee').value||'0');
      const cardCost = converted * (1 + feePct/100);
      const kpi = $('#fx-kpi');
      kpi.innerHTML = `
        <div class="box">1 ${base} ≈ <strong>${fmt(rate, 6)}</strong> ${quote}</div>
        <div class="box">${fmt(amount)} ${base} ≈ <strong>${fmt(converted, 2)}</strong> ${quote}</div>
        <div class="box">With ${feePct}% card fee: <strong>${fmt(cardCost,2)}</strong> ${quote}</div>
      `;

      // 7-day trend
      const end = new Date();
      const start = new Date(); start.setDate(end.getDate()-6);
      const sISO = start.toISOString().slice(0,10), eISO = end.toISOString().slice(0,10);
      $('#fx-trend-label').textContent = `${base}→${quote}`;
      const t = await fetch(`https://api.frankfurter.app/${sISO}..${eISO}?from=${base}&to=${quote}`);
      const tj = await t.json();
      const arr = Object.keys(tj.rates).sort().map(d => tj.rates[d][quote]);
      fxState.lastSeries = arr;
      fxDrawSpark(arr);
    }
    $('#fx-run').addEventListener('click', fxConvert);
    $('#fx-fee').addEventListener('input', e => {
      $('#fx-fee-val').textContent = `${e.target.value}%`;
      if(fxState.lastSeries.length) fxConvert();
    });

    // ---------- LIVE FLIGHT TRACKER ----------
    let map, markersLayer;
    function initMap(){
      map = L.map('map',{zoomControl:true}).setView([20,0], 2);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 8,
        attribution: '&copy; OpenStreetMap contributors'
      }).addTo(map);
      markersLayer = L.layerGroup().addTo(map);

      // Try geolocation to center
      if(navigator.geolocation){
        navigator.geolocation.getCurrentPosition(
          pos => map.setView([pos.coords.latitude, pos.coords.longitude], 6),
          ()=>{}
        );
      }
    }
    async function loadFlights(){
      if(!map) return;
      const b = map.getBounds();
      const params = new URLSearchParams({
        lamin: b.getSouth(), lomin: b.getWest(), lamax: b.getNorth(), lomax: b.getEast()
      });
      const url = `https://opensky-network.org/api/states/all?${params.toString()}`;
      const res = await fetch(url);
      if(!res.ok){ console.warn('OpenSky rate limit or error'); return; }
      const data = await res.json();
      const q = ($('#flt-search').value || '').trim().toUpperCase();

      markersLayer.clearLayers();
      if(!data || !data.states) return;

      data.states.forEach(s=>{
        // s indices: 0:icao24, 1:callsign, 2:origin_country, 5:longitude, 6:latitude, 7:baro_altitude, 8:on_ground, 9:velocity
        const icao24 = s[0]?.toUpperCase() || '';
        const callsign = (s[1]||'').trim().toUpperCase();
        const lat = s[6], lon = s[5];
        if(lat==null || lon==null) return;
        if(q && !(icao24.includes(q) || callsign.includes(q))) return;

        const marker = L.circleMarker([lat,lon],{
          radius: 4, color:'#efb034', weight:1, fill:true, fillColor:'#efb034', fillOpacity:.8
        }).bindTooltip(`
          <div><strong>${callsign || '—'}</strong></div>
          <div class="small">ICAO24: ${icao24}</div>
          <div class="small">From: ${s[2] || '—'}</div>
          <div class="small">Alt: ${s[7]!=null? Math.round(s[7])+' m':'—'} | Spd: ${s[9]!=null? Math.round(s[9])+' m/s':'—'}</div>
        `);
        markersLayer.addLayer(marker);
      });
    }
    let fltTimer = null;
    function startFlightLoop(){
      loadFlights();
      if(fltTimer) clearInterval(fltTimer);
      // Respectful polling: 15s to help avoid hitting OpenSky anonymous limits
      fltTimer = setInterval(loadFlights, 15000);
    }
    $('#flt-run').addEventListener('click', loadFlights);

    // ---------- INIT ON LOAD ----------
    window.addEventListener('DOMContentLoaded', async ()=>{
      initMap();
      startFlightLoop();
      await fxLoadCurrencies();
      $('#fx-base').value = 'USD';
      $('#fx-quote').value = 'EUR';
      fxConvert();

      // Preload Travel Advisor example
      const exampleCountry = 'United States';
      $('#ta-country').value = exampleCountry;
      travelAdvisor(exampleCountry);
    });
  </script>
</body>
</html>
