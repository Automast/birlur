<?php
// OpenSky Flight Tracker - Comprehensive Traveler Tools
// Uses OpenSky Network API for real-time flight data
// Free API with rate limits: 400 credits/day for anonymous users

header('Content-Type: text/html; charset=UTF-8');

// Configuration
$opensky_base_url = 'https://opensky-network.org/api';
$cache_duration = 10; // Cache for 10 seconds to respect rate limits

// Helper function to make API calls with caching
function fetchOpenSkyData($endpoint, $params = []) {
    global $opensky_base_url, $cache_duration;
    
    $query = http_build_query($params);
    $url = $opensky_base_url . $endpoint . ($query ? '?' . $query : '');
    $cache_key = md5($url);
    $cache_file = sys_get_temp_dir() . '/opensky_' . $cache_key . '.json';
    
    // Check cache
    if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $cache_duration) {
        return json_decode(file_get_contents($cache_file), true);
    }
    
    // Fetch from API
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => [
                'User-Agent: OpenSky Flight Tracker/1.0'
            ],
            'timeout' => 10
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        return null;
    }
    
    // Cache the response
    file_put_contents($cache_file, $response);
    
    return json_decode($response, true);
}

// Handle AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['action']) {
        case 'states':
            $bbox = isset($_GET['bbox']) ? explode(',', $_GET['bbox']) : null;
            $params = [];
            if ($bbox && count($bbox) == 4) {
                $params = [
                    'lamin' => (float)$bbox[0],
                    'lomin' => (float)$bbox[1], 
                    'lamax' => (float)$bbox[2],
                    'lomax' => (float)$bbox[3]
                ];
            }
            echo json_encode(fetchOpenSkyData('/states/all', $params));
            exit;
            
        case 'flights_aircraft':
            if (isset($_GET['icao24'])) {
                $params = [
                    'icao24' => strtolower($_GET['icao24']),
                    'begin' => time() - 86400, // Last 24 hours
                    'end' => time()
                ];
                echo json_encode(fetchOpenSkyData('/flights/aircraft', $params));
            }
            exit;
            
        case 'airport_arrivals':
            if (isset($_GET['airport'])) {
                $params = [
                    'airport' => strtoupper($_GET['airport']),
                    'begin' => time() - 7200, // Last 2 hours
                    'end' => time()
                ];
                echo json_encode(fetchOpenSkyData('/flights/arrival', $params));
            }
            exit;
            
        case 'airport_departures':
            if (isset($_GET['airport'])) {
                $params = [
                    'airport' => strtoupper($_GET['airport']),
                    'begin' => time() - 7200, // Last 2 hours  
                    'end' => time()
                ];
                echo json_encode(fetchOpenSkyData('/flights/departure', $params));
            }
            exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OpenSky Flight Tracker - Traveler Tools</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }
        
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 1rem;
            text-align: center;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            color: #2c3e50;
            font-size: 2.5em;
            margin-bottom: 0.5rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }
        
        .header p {
            color: #7f8c8d;
            font-size: 1.1em;
        }
        
        .container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 1rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
        
        .tool-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .tool-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.3);
        }
        
        .tool-card h2 {
            color: #2c3e50;
            margin-bottom: 1rem;
            font-size: 1.5em;
            border-bottom: 2px solid #3498db;
            padding-bottom: 0.5rem;
        }
        
        .map-container {
            grid-column: 1 / -1;
            height: 500px;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        #map {
            height: 100%;
            width: 100%;
        }
        
        .input-group {
            margin-bottom: 1rem;
        }
        
        .input-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .input-group input, .input-group select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e8ed;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .input-group input:focus, .input-group select:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .btn {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.4);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #95a5a6, #7f8c8d);
            box-shadow: 0 4px 15px rgba(149, 165, 166, 0.3);
        }
        
        .results {
            max-height: 300px;
            overflow-y: auto;
            margin-top: 1rem;
        }
        
        .flight-item {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .flight-item:hover {
            background: #e3f2fd;
            border-color: #3498db;
            transform: translateX(5px);
        }
        
        .flight-callsign {
            font-weight: 700;
            color: #2c3e50;
            font-size: 1.1em;
        }
        
        .flight-details {
            color: #7f8c8d;
            font-size: 0.9em;
            margin-top: 0.3rem;
        }
        
        .status-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 0.5rem;
        }
        
        .status-airborne { background-color: #27ae60; }
        .status-ground { background-color: #e74c3c; }
        .status-unknown { background-color: #95a5a6; }
        
        .coords-display {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
            font-family: 'Courier New', monospace;
        }
        
        .loading {
            text-align: center;
            color: #3498db;
            font-style: italic;
            margin: 1rem 0;
        }
        
        .error {
            background: #ffe6e6;
            color: #c0392b;
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
            border-left: 4px solid #e74c3c;
        }
        
        .info-box {
            background: #e3f2fd;
            color: #1976d2;
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
            border-left: 4px solid #2196f3;
        }
        
        @media (max-width: 768px) {
            .container {
                grid-template-columns: 1fr;
                margin: 1rem;
                padding: 0 0.5rem;
            }
            
            .header h1 {
                font-size: 2em;
            }
            
            .map-container {
                height: 300px;
            }
        }
        
        .aircraft-icon {
            background: #3498db;
            border-radius: 50%;
            border: 2px solid white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.3);
        }
        
        .popup-content {
            max-width: 250px;
        }
        
        .popup-content h3 {
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        
        .popup-content p {
            margin: 0.2rem 0;
            font-size: 0.9em;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .stat-item {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
            border-left: 4px solid #3498db;
        }
        
        .stat-value {
            font-size: 1.5em;
            font-weight: 700;
            color: #2c3e50;
        }
        
        .stat-label {
            color: #7f8c8d;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>✈️ OpenSky Flight Tracker</h1>
        <p>Real-time flight data for travelers • Powered by OpenSky Network API</p>
    </div>
    
    <div class="container">
        <!-- Live Flight Map -->
        <div class="tool-card map-container">
            <div id="map"></div>
        </div>
        
        <!-- Flight Search -->
        <div class="tool-card">
            <h2>🔍 Flight Search</h2>
            <div class="input-group">
                <label for="search-input">Search by Callsign or ICAO24</label>
                <input type="text" id="search-input" placeholder="e.g., UAL123 or 3c6444" />
            </div>
            <button class="btn" onclick="searchFlight()">Search Flight</button>
            <div id="flight-results" class="results"></div>
        </div>
        
        <!-- Airport Information -->
        <div class="tool-card">
            <h2>🏢 Airport Board</h2>
            <div class="input-group">
                <label for="airport-input">Airport ICAO Code</label>
                <input type="text" id="airport-input" placeholder="e.g., KJFK, EGLL, EDDF" />
            </div>
            <div style="display: flex; gap: 0.5rem;">
                <button class="btn" onclick="getAirportArrivals()">Arrivals</button>
                <button class="btn btn-secondary" onclick="getAirportDepartures()">Departures</button>
            </div>
            <div id="airport-results" class="results"></div>
        </div>
        
        <!-- Overhead Traffic -->
        <div class="tool-card">
            <h2>📡 What's Flying Overhead</h2>
            <p>Click to detect your location and show nearby aircraft:</p>
            <button class="btn" onclick="detectLocation()">📍 Find My Location</button>
            <div id="location-coords" class="coords-display" style="display: none;"></div>
            <div id="overhead-results" class="results"></div>
        </div>
        
        <!-- Statistics -->
        <div class="tool-card">
            <h2>📊 Live Statistics</h2>
            <button class="btn" onclick="updateStats()">Refresh Stats</button>
            <div id="stats-container" class="stats-grid">
                <div class="stat-item">
                    <div class="stat-value" id="total-aircraft">-</div>
                    <div class="stat-label">Aircraft Tracked</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value" id="countries">-</div>
                    <div class="stat-label">Countries</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value" id="airborne">-</div>
                    <div class="stat-label">Airborne</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value" id="ground">-</div>
                    <div class="stat-label">On Ground</div>
                </div>
            </div>
        </div>
        
        <!-- API Information -->
        <div class="tool-card">
            <h2>ℹ️ API Information</h2>
            <div class="info-box">
                <p><strong>Data Source:</strong> OpenSky Network</p>
                <p><strong>Update Rate:</strong> Every 10 seconds (anonymous)</p>
                <p><strong>Rate Limit:</strong> 400 API credits per day</p>
                <p><strong>Coverage:</strong> Global ADS-B data</p>
            </div>
            <p style="font-size: 0.9em; color: #7f8c8d; margin-top: 1rem;">
                This tool is designed for research and non-commercial use. The OpenSky Network is a 
                community-driven project providing free access to real-world air traffic control data.
                <br><br>
                <strong>Features:</strong>
                • Live aircraft positions and tracking<br>
                • Airport arrival/departure boards<br>
                • Location-based overhead traffic detection<br>
                • Flight search by callsign or ICAO address<br>
                • Real-time statistics and analytics
            </p>
        </div>
    </div>
    
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Global variables
        let map;
        let aircraftMarkers = [];
        let userLocation = null;
        
        // Initialize the map
        function initMap() {
            map = L.map('map').setView([40.7128, -74.0060], 6); // Default to New York area
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);
            
            // Load initial aircraft data
            loadAircraftData();
            
            // Set up auto-refresh every 15 seconds (respect rate limits)
            setInterval(loadAircraftData, 15000);
        }
        
        // Load aircraft data from API
        async function loadAircraftData(bbox = null) {
            try {
                const url = bbox ? 
                    `?action=states&bbox=${bbox.join(',')}` : 
                    '?action=states';
                
                const response = await fetch(url);
                const data = await response.json();
                
                if (data && data.states) {
                    updateAircraftOnMap(data.states);
                    updateStatistics(data.states);
                }
            } catch (error) {
                console.error('Error loading aircraft data:', error);
            }
        }
        
        // Update aircraft markers on map
        function updateAircraftOnMap(states) {
            // Clear existing markers
            aircraftMarkers.forEach(marker => map.removeLayer(marker));
            aircraftMarkers = [];
            
            states.forEach(state => {
                const [icao24, callsign, origin_country, time_position, last_contact, 
                       longitude, latitude, baro_altitude, on_ground, velocity, 
                       true_track, vertical_rate] = state;
                
                if (latitude && longitude) {
                    const marker = L.circleMarker([latitude, longitude], {
                        radius: on_ground ? 4 : 6,
                        fillColor: on_ground ? '#e74c3c' : '#27ae60',
                        color: '#ffffff',
                        weight: 1,
                        opacity: 1,
                        fillOpacity: 0.8
                    });
                    
                    const popupContent = createAircraftPopup(state);
                    marker.bindPopup(popupContent);
                    
                    marker.addTo(map);
                    aircraftMarkers.push(marker);
                }
            });
        }
        
        // Create popup content for aircraft
        function createAircraftPopup(state) {
            const [icao24, callsign, origin_country, time_position, last_contact, 
                   longitude, latitude, baro_altitude, on_ground, velocity, 
                   true_track, vertical_rate] = state;
            
            const altitudeM = baro_altitude || 0;
            const altitudeFt = Math.round(altitudeM * 3.28084);
            const speedKmh = velocity ? Math.round(velocity * 3.6) : 0;
            const speedKts = velocity ? Math.round(velocity * 1.944) : 0;
            
            return `
                <div class="popup-content">
                    <h3>${callsign ? callsign.trim() : 'Unknown'}</h3>
                    <p><strong>ICAO24:</strong> ${icao24}</p>
                    <p><strong>Country:</strong> ${origin_country}</p>
                    <p><strong>Status:</strong> ${on_ground ? '🛬 On Ground' : '✈️ Airborne'}</p>
                    <p><strong>Altitude:</strong> ${altitudeFt} ft (${Math.round(altitudeM)} m)</p>
                    <p><strong>Speed:</strong> ${speedKts} kts (${speedKmh} km/h)</p>
                    ${true_track ? `<p><strong>Heading:</strong> ${Math.round(true_track)}°</p>` : ''}
                    ${vertical_rate ? `<p><strong>Climb Rate:</strong> ${Math.round(vertical_rate * 196.85)} ft/min</p>` : ''}
                </div>
            `;
        }
        
        // Update statistics
        function updateStatistics(states) {
            const total = states.length;
            const countries = new Set(states.map(s => s[2])).size;
            const airborne = states.filter(s => !s[8]).length;
            const ground = states.filter(s => s[8]).length;
            
            document.getElementById('total-aircraft').textContent = total;
            document.getElementById('countries').textContent = countries;
            document.getElementById('airborne').textContent = airborne;
            document.getElementById('ground').textContent = ground;
        }
        
        // Search for specific flight
        async function searchFlight() {
            const query = document.getElementById('search-input').value.trim();
            const resultsDiv = document.getElementById('flight-results');
            
            if (!query) {
                resultsDiv.innerHTML = '<div class="error">Please enter a callsign or ICAO24 address</div>';
                return;
            }
            
            resultsDiv.innerHTML = '<div class="loading">Searching flights...</div>';
            
            try {
                // Try to get current state first
                const statesResponse = await fetch('?action=states');
                const statesData = await statesResponse.json();
                
                if (statesData && statesData.states) {
                    const matchingAircraft = statesData.states.filter(state => {
                        const callsign = state[1] ? state[1].trim().toLowerCase() : '';
                        const icao24 = state[0].toLowerCase();
                        const searchLower = query.toLowerCase();
                        
                        return callsign.includes(searchLower) || icao24.includes(searchLower);
                    });
                    
                    if (matchingAircraft.length > 0) {
                        displayFlightResults(matchingAircraft, resultsDiv, 'current');
                    } else {
                        // Try historical search for ICAO24
                        if (query.match(/^[a-fA-F0-9]{6}$/)) {
                            const historicalResponse = await fetch(`?action=flights_aircraft&icao24=${query}`);
                            const historicalData = await historicalResponse.json();
                            
                            if (historicalData && historicalData.length > 0) {
                                displayHistoricalFlights(historicalData, resultsDiv);
                            } else {
                                resultsDiv.innerHTML = '<div class="error">No flights found for this search</div>';
                            }
                        } else {
                            resultsDiv.innerHTML = '<div class="error">No current flights found. Try an ICAO24 address for historical search.</div>';
                        }
                    }
                }
            } catch (error) {
                resultsDiv.innerHTML = '<div class="error">Error searching flights. Please try again.</div>';
                console.error('Search error:', error);
            }
        }
        
        // Display flight results
        function displayFlightResults(aircraft, container, type = 'current') {
            if (aircraft.length === 0) {
                container.innerHTML = '<div class="error">No flights found</div>';
                return;
            }
            
            let html = '';
            aircraft.forEach(state => {
                const [icao24, callsign, origin_country, time_position, last_contact, 
                       longitude, latitude, baro_altitude, on_ground, velocity] = state;
                
                const altitudeFt = baro_altitude ? Math.round(baro_altitude * 3.28084) : 0;
                const speedKts = velocity ? Math.round(velocity * 1.944) : 0;
                
                html += `
                    <div class="flight-item">
                        <div class="flight-callsign">
                            <span class="status-indicator ${on_ground ? 'status-ground' : 'status-airborne'}"></span>
                            ${callsign ? callsign.trim() : 'Unknown Callsign'}
                        </div>
                        <div class="flight-details">
                            ICAO24: ${icao24} • Country: ${origin_country}<br>
                            ${latitude && longitude ? `Position: ${latitude.toFixed(4)}, ${longitude.toFixed(4)}` : 'Position: Unknown'}<br>
                            Altitude: ${altitudeFt} ft • Speed: ${speedKts} kts
                            ${type === 'current' ? '' : '<br>Historical Flight'}
                        </div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }
        
        // Display historical flights
        function displayHistoricalFlights(flights, container) {
            if (flights.length === 0) {
                container.innerHTML = '<div class="error">No historical flights found</div>';
                return;
            }
            
            let html = '';
            flights.forEach(flight => {
                const departureTime = new Date(flight.firstSeen * 1000);
                const arrivalTime = new Date(flight.lastSeen * 1000);
                
                html += `
                    <div class="flight-item">
                        <div class="flight-callsign">
                            ${flight.callsign ? flight.callsign.trim() : 'Unknown Callsign'}
                        </div>
                        <div class="flight-details">
                            ICAO24: ${flight.icao24}<br>
                            Departure: ${flight.estDepartureAirport || 'Unknown'} • ${departureTime.toLocaleString()}<br>
                            Arrival: ${flight.estArrivalAirport || 'Unknown'} • ${arrivalTime.toLocaleString()}
                        </div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }
        
        // Get airport arrivals
        async function getAirportArrivals() {
            const airport = document.getElementById('airport-input').value.trim().toUpperCase();
            const resultsDiv = document.getElementById('airport-results');
            
            if (!airport) {
                resultsDiv.innerHTML = '<div class="error">Please enter an airport ICAO code</div>';
                return;
            }
            
            resultsDiv.innerHTML = '<div class="loading">Loading arrivals...</div>';
            
            try {
                const response = await fetch(`?action=airport_arrivals&airport=${airport}`);
                const data = await response.json();
                
                if (data && data.length > 0) {
                    displayAirportFlights(data, resultsDiv, 'arrivals');
                } else {
                    resultsDiv.innerHTML = '<div class="error">No recent arrivals found for this airport</div>';
                }
            } catch (error) {
                resultsDiv.innerHTML = '<div class="error">Error loading arrivals. Please check the airport code.</div>';
            }
        }
        
        // Get airport departures
        async function getAirportDepartures() {
            const airport = document.getElementById('airport-input').value.trim().toUpperCase();
            const resultsDiv = document.getElementById('airport-results');
            
            if (!airport) {
                resultsDiv.innerHTML = '<div class="error">Please enter an airport ICAO code</div>';
                return;
            }
            
            resultsDiv.innerHTML = '<div class="loading">Loading departures...</div>';
            
            try {
                const response = await fetch(`?action=airport_departures&airport=${airport}`);
                const data = await response.json();
                
                if (data && data.length > 0) {
                    displayAirportFlights(data, resultsDiv, 'departures');
                } else {
                    resultsDiv.innerHTML = '<div class="error">No recent departures found for this airport</div>';
                }
            } catch (error) {
                resultsDiv.innerHTML = '<div class="error">Error loading departures. Please check the airport code.</div>';
            }
        }
        
        // Display airport flights
        function displayAirportFlights(flights, container, type) {
            let html = `<h3>${type === 'arrivals' ? 'Recent Arrivals' : 'Recent Departures'}</h3>`;
            
            flights.forEach(flight => {
                const time = type === 'arrivals' ? 
                    new Date(flight.lastSeen * 1000) : 
                    new Date(flight.firstSeen * 1000);
                
                html += `
                    <div class="flight-item">
                        <div class="flight-callsign">
                            ${flight.callsign ? flight.callsign.trim() : 'Unknown'}
                        </div>
                        <div class="flight-details">
                            ICAO24: ${flight.icao24}<br>
                            ${type === 'arrivals' ? 'From' : 'To'}: ${
                                type === 'arrivals' ? 
                                (flight.estDepartureAirport || 'Unknown') : 
                                (flight.estArrivalAirport || 'Unknown')
                            }<br>
                            Time: ${time.toLocaleString()}
                        </div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }
        
        // Detect user location and show overhead traffic
        function detectLocation() {
            const coordsDiv = document.getElementById('location-coords');
            const resultsDiv = document.getElementById('overhead-results');
            
            if (!navigator.geolocation) {
                resultsDiv.innerHTML = '<div class="error">Geolocation is not supported by this browser</div>';
                return;
            }
            
            resultsDiv.innerHTML = '<div class="loading">Getting your location...</div>';
            
            navigator.geolocation.getCurrentPosition(
                position => {
                    const lat = position.coords.latitude;
                    const lon = position.coords.longitude;
                    userLocation = {lat, lon};
                    
                    coordsDiv.style.display = 'block';
                    coordsDiv.innerHTML = `📍 Your location: ${lat.toFixed(4)}, ${lon.toFixed(4)}`;
                    
                    // Update map center
                    map.setView([lat, lon], 9);
                    
                    // Add user location marker
                    L.marker([lat, lon], {
                        icon: L.divIcon({
                            html: '📍',
                            className: 'user-location-marker',
                            iconSize: [20, 20]
                        })
                    }).bindPopup('Your Location').addTo(map);
                    
                    // Load aircraft in the area
                    const bbox = [
                        lat - 0.5, lon - 0.5, // min lat, min lon
                        lat + 0.5, lon + 0.5  // max lat, max lon
                    ];
                    
                    loadAircraftData(bbox);
                    findOverheadTraffic(lat, lon);
                },
                error => {
                    resultsDiv.innerHTML = '<div class="error">Unable to get your location. Please enable location services.</div>';
                }
            );
        }
        
        // Find overhead traffic near user location
        async function findOverheadTraffic(lat, lon) {
            const resultsDiv = document.getElementById('overhead-results');
            resultsDiv.innerHTML = '<div class="loading">Finding overhead traffic...</div>';
            
            try {
                const bbox = [lat - 0.2, lon - 0.2, lat + 0.2, lon + 0.2];
                const response = await fetch(`?action=states&bbox=${bbox.join(',')}`);
                const data = await response.json();
                
                if (data && data.states && data.states.length > 0) {
                    // Calculate distances and sort by proximity
                    const aircraftWithDistance = data.states
                        .filter(state => state[5] && state[6]) // Has valid coordinates
                        .map(state => {
                            const distance = calculateDistance(lat, lon, state[6], state[5]);
                            return {...state, distance};
                        })
                        .sort((a, b) => a.distance - b.distance)
                        .slice(0, 10); // Show only closest 10 aircraft
                    
                    displayOverheadTraffic(aircraftWithDistance, resultsDiv);
                } else {
                    resultsDiv.innerHTML = '<div class="error">No aircraft found in your immediate area</div>';
                }
            } catch (error) {
                resultsDiv.innerHTML = '<div class="error">Error finding overhead traffic</div>';
            }
        }
        
        // Display overhead traffic
        function displayOverheadTraffic(aircraft, container) {
            let html = '<h3>Nearby Aircraft</h3>';
            
            aircraft.forEach(data => {
                const state = Array.isArray(data) ? data : [
                    data[0], data[1], data[2], data[3], data[4], data[5], data[6], 
                    data[7], data[8], data[9], data[10], data[11]
                ];
                const distance = data.distance;
                
                const [icao24, callsign, origin_country, , , longitude, latitude, 
                       baro_altitude, on_ground, velocity] = state;
                
                const altitudeFt = baro_altitude ? Math.round(baro_altitude * 3.28084) : 0;
                const speedKts = velocity ? Math.round(velocity * 1.944) : 0;
                
                html += `
                    <div class="flight-item">
                        <div class="flight-callsign">
                            <span class="status-indicator ${on_ground ? 'status-ground' : 'status-airborne'}"></span>
                            ${callsign ? callsign.trim() : 'Unknown'}
                        </div>
                        <div class="flight-details">
                            Distance: ${distance.toFixed(1)} km • Country: ${origin_country}<br>
                            Altitude: ${altitudeFt} ft • Speed: ${speedKts} kts<br>
                            ICAO24: ${icao24}
                        </div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }
        
        // Calculate distance between two coordinates (Haversine formula)
        function calculateDistance(lat1, lon1, lat2, lon2) {
            const R = 6371; // Earth's radius in kilometers
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLon = (lon2 - lon1) * Math.PI / 180;
            const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                      Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                      Math.sin(dLon/2) * Math.sin(dLon/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
            return R * c;
        }
        
        // Update statistics manually
        async function updateStats() {
            try {
                const response = await fetch('?action=states');
                const data = await response.json();
                
                if (data && data.states) {
                    updateStatistics(data.states);
                }
            } catch (error) {
                console.error('Error updating stats:', error);
            }
        }
        
        // Initialize everything when page loads
        document.addEventListener('DOMContentLoaded', function() {
            initMap();
            updateStats();
            
            // Add enter key support for search inputs
            document.getElementById('search-input').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') searchFlight();
            });
            
            document.getElementById('airport-input').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') getAirportArrivals();
            });
        });
    </script>
</body>
</html>
