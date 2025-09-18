<?php
// Exchange Rate Checker - Comprehensive Traveler Tools
// Uses multiple free exchange rate APIs for reliability
// Free APIs with various endpoints for different functionalities

header('Content-Type: text/html; charset=UTF-8');

// Configuration - Multiple free APIs with no API key required
$primary_api = 'https://api.exchangerate.host';
$backup_api = 'https://api.frankfurter.app';
$currencies_api = 'https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@latest/v1';
$open_access_api = 'https://open.er-api.com/v6';
$cache_duration = 3600; // Cache for 1 hour to respect rate limits

// Popular travel currencies and their details
$popular_currencies = [
    'USD' => ['name' => 'US Dollar', 'symbol' => '$', 'flag' => '🇺🇸'],
    'EUR' => ['name' => 'Euro', 'symbol' => '€', 'flag' => '🇪🇺'],
    'GBP' => ['name' => 'British Pound', 'symbol' => '£', 'flag' => '🇬🇧'],
    'JPY' => ['name' => 'Japanese Yen', 'symbol' => '¥', 'flag' => '🇯🇵'],
    'AUD' => ['name' => 'Australian Dollar', 'symbol' => 'A$', 'flag' => '🇦🇺'],
    'CAD' => ['name' => 'Canadian Dollar', 'symbol' => 'C$', 'flag' => '🇨🇦'],
    'CHF' => ['name' => 'Swiss Franc', 'symbol' => 'CHF', 'flag' => '🇨🇭'],
    'CNY' => ['name' => 'Chinese Yuan', 'symbol' => '¥', 'flag' => '🇨🇳'],
    'SEK' => ['name' => 'Swedish Krona', 'symbol' => 'kr', 'flag' => '🇸🇪'],
    'NOK' => ['name' => 'Norwegian Krone', 'symbol' => 'kr', 'flag' => '🇳🇴'],
    'DKK' => ['name' => 'Danish Krone', 'symbol' => 'kr', 'flag' => '🇩🇰'],
    'NZD' => ['name' => 'New Zealand Dollar', 'symbol' => 'NZ$', 'flag' => '🇳🇿'],
    'SGD' => ['name' => 'Singapore Dollar', 'symbol' => 'S$', 'flag' => '🇸🇬'],
    'HKD' => ['name' => 'Hong Kong Dollar', 'symbol' => 'HK$', 'flag' => '🇭🇰'],
    'MXN' => ['name' => 'Mexican Peso', 'symbol' => '$', 'flag' => '🇲🇽'],
    'INR' => ['name' => 'Indian Rupee', 'symbol' => '₹', 'flag' => '🇮🇳'],
    'BRL' => ['name' => 'Brazilian Real', 'symbol' => 'R$', 'flag' => '🇧🇷'],
    'ZAR' => ['name' => 'South African Rand', 'symbol' => 'R', 'flag' => '🇿🇦'],
    'THB' => ['name' => 'Thai Baht', 'symbol' => '฿', 'flag' => '🇹🇭'],
    'KRW' => ['name' => 'South Korean Won', 'symbol' => '₩', 'flag' => '🇰🇷']
];

// Common tipping customs by country
$tipping_guide = [
    'US' => ['percent' => 18, 'description' => 'Standard 15-20% for restaurants, 10-15% for taxis'],
    'GB' => ['percent' => 10, 'description' => '10-15% for restaurants if service charge not included'],
    'AU' => ['percent' => 10, 'description' => 'Tipping not mandatory but 10% is appreciated'],
    'JP' => ['percent' => 0, 'description' => 'Tipping not customary and can be offensive'],
    'DE' => ['percent' => 10, 'description' => '10% is standard, round up to nearest Euro'],
    'FR' => ['percent' => 10, 'description' => 'Service included but 5-10% extra is polite'],
    'IT' => ['percent' => 10, 'description' => 'Round up or 10% for good service'],
    'ES' => ['percent' => 10, 'description' => 'Small tips appreciated, 5-10% for restaurants'],
    'CA' => ['percent' => 15, 'description' => '15-20% standard, similar to US'],
    'MX' => ['percent' => 15, 'description' => '10-15% for restaurants, round up for services']
];

// Helper function to make API calls with caching and multiple fallbacks
function fetchExchangeData($endpoint, $params = [], $api_type = 'primary') {
    global $primary_api, $backup_api, $currencies_api, $open_access_api, $cache_duration;
    
    $apis = [
        'primary' => $primary_api,
        'backup' => $backup_api,
        'currencies' => $currencies_api,
        'open' => $open_access_api
    ];
    
    $base_url = $apis[$api_type] ?? $primary_api;
    $query = http_build_query($params);
    $url = $base_url . $endpoint . ($query ? '?' . $query : '');
    $cache_key = md5($url);
    $cache_file = sys_get_temp_dir() . '/exchange_' . $cache_key . '.json';
    
    // Check cache
    if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $cache_duration) {
        return json_decode(file_get_contents($cache_file), true);
    }
    
    // Fetch from API
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => [
                'User-Agent: Exchange Rate Checker/1.0',
                'Accept: application/json'
            ],
            'timeout' => 10
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        // Try different APIs in sequence
        $fallback_order = ['backup', 'open', 'currencies'];
        foreach ($fallback_order as $fallback) {
            if ($fallback !== $api_type) {
                $fallback_result = fetchExchangeData($endpoint, $params, $fallback);
                if ($fallback_result) return $fallback_result;
            }
        
        // Load all available currencies and populate dropdowns
        async function loadAllCurrencies() {
            try {
                const response = await fetch('?action=currencies');
                const data = await response.json();
                
                if (data && data.currencies) {
                    allCurrencies = data.currencies;
                    populateAllDropdowns();
                    
                    // Set default values and add event listeners after population
                    document.getElementById('from-currency').value = 'USD';
                    document.getElementById('to-currency').value = 'EUR';
                    document.getElementById('budget-currency').value = 'USD';
                    document.getElementById('destination-currency').value = 'EUR';
                    document.getElementById('tip-currency').value = 'USD';
                    document.getElementById('trend-base').value = 'USD';
                    document.getElementById('trend-target').value = 'EUR';
                    document.getElementById('comparison-base').value = 'USD';
                    
                    // Add event listeners after dropdowns are populated
                    document.getElementById('from-currency').addEventListener('change', convertCurrency);
                    document.getElementById('to-currency').addEventListener('change', convertCurrency);
                    
                    // Update currency count in stats
                    const currencyCount = Object.keys(allCurrencies).length;
                    document.getElementById('supported-currencies').textContent = currencyCount;
                    document.getElementById('currency-count').textContent = currencyCount;
                    document.getElementById('total-currencies').textContent = currencyCount;
                }
            } catch (error) {
                console.error('Error loading currencies:', error);
                // Fallback to popular currencies if all else fails
                populateDropdownsWithPopular();
            }
        }
        
        // Populate all currency dropdowns with all available currencies
        function populateAllDropdowns() {
            const dropdowns = [
                'from-currency', 'to-currency', 'budget-currency', 
                'destination-currency', 'tip-currency', 'trend-base', 
                'trend-target', 'comparison-base'
            ];
            
            dropdowns.forEach(dropdownId => {
                populateDropdown(dropdownId);
            });
        }
        
        // Populate a specific dropdown
        function populateDropdown(dropdownId) {
            const dropdown = document.getElementById(dropdownId);
            if (!dropdown) return;
            
            // Clear existing options
            dropdown.innerHTML = '';
            
            // Add popular currencies first
            const popularSection = document.createElement('optgroup');
            popularSection.label = 'Popular Currencies';
            
            Object.keys(popularCurrencies).forEach(code => {
                if (allCurrencies[code]) {
                    const option = document.createElement('option');
                    option.value = code;
                    option.textContent = `${code} - ${allCurrencies[code].name}`;
                    popularSection.appendChild(option);
                }
            });
            
            dropdown.appendChild(popularSection);
            
            // Add separator
            const separator = document.createElement('optgroup');
            separator.label = 'All Currencies';
            
            // Add all other currencies
            Object.keys(allCurrencies).sort().forEach(code => {
                if (!popularCurrencies[code]) {
                    const option = document.createElement('option');
                    option.value = code;
                    option.textContent = `${code} - ${allCurrencies[code].name}`;
                    separator.appendChild(option);
                }
            });
            
            dropdown.appendChild(separator);
        }
        
        // Fallback function for popular currencies only
        function populateDropdownsWithPopular() {
            const dropdowns = [
                'from-currency', 'to-currency', 'budget-currency', 
                'destination-currency', 'tip-currency', 'trend-base', 
                'trend-target', 'comparison-base'
            ];
            
            dropdowns.forEach(dropdownId => {
                const dropdown = document.getElementById(dropdownId);
                if (!dropdown) return;
                
                dropdown.innerHTML = '';
                Object.keys(popularCurrencies).forEach(code => {
                    const option = document.createElement('option');
                    option.value = code;
                    option.textContent = `${code} - ${popularCurrencies[code].name}`;
                    dropdown.appendChild(option);
                });
            });
            
            // Set defaults
            document.getElementById('from-currency').value = 'USD';
            document.getElementById('to-currency').value = 'EUR';
            document.getElementById('budget-currency').value = 'USD';
            document.getElementById('destination-currency').value = 'EUR';
            document.getElementById('tip-currency').value = 'USD';
            document.getElementById('trend-base').value = 'USD';
            document.getElementById('trend-target').value = 'EUR';
            document.getElementById('comparison-base').value = 'USD';
            
            // Add event listeners
            document.getElementById('from-currency').addEventListener('change', convertCurrency);
            document.getElementById('to-currency').addEventListener('change', convertCurrency);
        }
        }
        return null;
    }
    
    // Cache the response
    file_put_contents($cache_file, $response);
    
    return json_decode($response, true);
}

// Get all available currencies from multiple free sources
function getAllCurrencies() {
    global $cache_duration;
    
    $cache_file = sys_get_temp_dir() . '/all_currencies.json';
    
    // Check cache first
    if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $cache_duration * 24) { // Cache for 24 hours
        return json_decode(file_get_contents($cache_file), true);
    }
    
    $all_currencies = [];
    
    // Try Frankfurter API first (free, no API key, comprehensive)
    $frankfurter_url = 'https://api.frankfurter.app/currencies';
    $response = @file_get_contents($frankfurter_url);
    if ($response) {
        $data = json_decode($response, true);
        if ($data) {
            foreach ($data as $code => $name) {
                $all_currencies[$code] = [
                    'name' => $name,
                    'code' => $code
                ];
            }
        }
    }
    
    // Fallback to Fawaz Ahmed's API (200+ currencies)
    if (empty($all_currencies)) {
        $fawaz_url = 'https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@latest/v1/currencies.json';
        $response = @file_get_contents($fawaz_url);
        if ($response) {
            $data = json_decode($response, true);
            if ($data) {
                foreach ($data as $code => $name) {
                    $all_currencies[strtoupper($code)] = [
                        'name' => ucwords($name),
                        'code' => strtoupper($code)
                    ];
                }
            }
        }
    }
    
    // Fallback to exchangerate.host symbols
    if (empty($all_currencies)) {
        $host_url = 'https://api.exchangerate.host/symbols';
        $response = @file_get_contents($host_url);
        if ($response) {
            $data = json_decode($response, true);
            if ($data && isset($data['symbols'])) {
                foreach ($data['symbols'] as $code => $info) {
                    $all_currencies[$code] = [
                        'name' => $info['description'] ?? $code,
                        'code' => $code
                    ];
                }
            }
        }
    }
    
    // If still empty, use our hardcoded popular currencies as fallback
    if (empty($all_currencies)) {
        global $popular_currencies;
        foreach ($popular_currencies as $code => $info) {
            $all_currencies[$code] = [
                'name' => $info['name'],
                'code' => $code
            ];
        }
    }
    
    // Sort currencies alphabetically
    ksort($all_currencies);
    
    // Cache the result
    file_put_contents($cache_file, json_encode($all_currencies));
    
    return $all_currencies;
}

// Handle AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['action']) {
        case 'latest_rates':
            $base = isset($_GET['base']) ? strtoupper($_GET['base']) : 'USD';
            $symbols = isset($_GET['symbols']) ? strtoupper($_GET['symbols']) : '';
            $params = ['base' => $base];
            if ($symbols) {
                $params['symbols'] = $symbols;
            }
            echo json_encode(fetchExchangeData('/latest', $params));
            exit;
            
        case 'convert':
            $from = strtoupper($_GET['from'] ?? 'USD');
            $to = strtoupper($_GET['to'] ?? 'EUR');
            $amount = floatval($_GET['amount'] ?? 1);
            $params = ['from' => $from, 'to' => $to, 'amount' => $amount];
            echo json_encode(fetchExchangeData('/convert', $params));
            exit;
            
        case 'historical':
            $date = $_GET['date'] ?? date('Y-m-d', strtotime('-1 day'));
            $base = strtoupper($_GET['base'] ?? 'USD');
            $symbols = isset($_GET['symbols']) ? strtoupper($_GET['symbols']) : '';
            $params = ['base' => $base];
            if ($symbols) {
                $params['symbols'] = $symbols;
            }
            echo json_encode(fetchExchangeData("/$date", $params));
            exit;
            
        case 'timeseries':
            $start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-7 days'));
            $end_date = $_GET['end_date'] ?? date('Y-m-d');
            $base = strtoupper($_GET['base'] ?? 'USD');
            $symbols = strtoupper($_GET['symbols'] ?? 'EUR,GBP,JPY');
            $params = [
                'start_date' => $start_date,
                'end_date' => $end_date,
                'base' => $base,
                'symbols' => $symbols
            ];
            echo json_encode(fetchExchangeData('/timeseries', $params));
            exit;
            
        case 'fluctuation':
            $start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-1 day'));
            $end_date = $_GET['end_date'] ?? date('Y-m-d');
            $base = strtoupper($_GET['base'] ?? 'USD');
            $symbols = strtoupper($_GET['symbols'] ?? 'EUR,GBP,JPY');
            $params = [
                'start_date' => $start_date,
                'end_date' => $end_date,
                'base' => $base,
                'symbols' => $symbols
            ];
            echo json_encode(fetchExchangeData('/fluctuation', $params));
            exit;
            
        case 'currencies':
            echo json_encode(['currencies' => getAllCurrencies()]);
            exit;
            
        case 'all_symbols':
            // Alternative endpoint for all currency symbols
            $currencies = getAllCurrencies();
            $symbols = [];
            foreach ($currencies as $code => $info) {
                $symbols[$code] = $info['name'];
            }
            echo json_encode(['symbols' => $symbols]);
            exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exchange Rate Checker - Traveler Tools</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
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
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .wide-card {
            grid-column: 1 / -1;
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
        
        .input-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
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
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.4);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #95a5a6, #7f8c8d);
            box-shadow: 0 4px 15px rgba(149, 165, 166, 0.3);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #27ae60, #229954);
            box-shadow: 0 4px 15px rgba(39, 174, 96, 0.3);
        }
        
        .results {
            max-height: 400px;
            overflow-y: auto;
            margin-top: 1rem;
        }
        
        .currency-item {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: between;
        }
        
        .currency-item:hover {
            background: #e3f2fd;
            border-color: #3498db;
            transform: translateX(5px);
        }
        
        .currency-info {
            flex-grow: 1;
        }
        
        .currency-code {
            font-weight: 700;
            color: #2c3e50;
            font-size: 1.1em;
        }
        
        .currency-name {
            color: #7f8c8d;
            font-size: 0.9em;
        }
        
        .currency-rate {
            font-size: 1.2em;
            font-weight: 700;
            color: #27ae60;
        }
        
        .rate-change {
            font-size: 0.9em;
            font-weight: 600;
        }
        
        .rate-up { color: #27ae60; }
        .rate-down { color: #e74c3c; }
        .rate-same { color: #95a5a6; }
        
        .conversion-result {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            text-align: center;
            margin-top: 1rem;
            box-shadow: 0 8px 25px rgba(52, 152, 219, 0.3);
        }
        
        .conversion-amount {
            font-size: 2.5em;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .conversion-details {
            opacity: 0.9;
            font-size: 1.1em;
        }
        
        .popular-currencies {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 0.5rem;
            margin-top: 1rem;
        }
        
        .currency-button {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 0.75rem 0.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            font-size: 0.9em;
        }
        
        .currency-button:hover, .currency-button.active {
            background: #3498db;
            color: white;
            border-color: #3498db;
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
        
        .chart-container {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .trend-item {
            display: flex;
            align-items: center;
            justify-content: between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
        }
        
        .trend-date {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .trend-rate {
            font-weight: 700;
        }
        
        .budget-breakdown {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
        }
        
        .budget-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .budget-item:last-child {
            border-bottom: none;
            font-weight: 700;
            font-size: 1.1em;
            color: #2c3e50;
        }
        
        .tip-result {
            background: linear-gradient(135deg, #27ae60, #229954);
            color: white;
            padding: 1.5rem;
            border-radius: 10px;
            margin-top: 1rem;
            text-align: center;
        }
        
        .country-guide {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .country-item {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            border-left: 4px solid #3498db;
        }
        
        .country-flag {
            font-size: 1.5em;
            margin-right: 0.5rem;
        }
        
        .country-name {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .country-currency {
            color: #7f8c8d;
            font-size: 0.9em;
            margin-top: 0.3rem;
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
            
            .input-row {
                grid-template-columns: 1fr;
            }
            
            .popular-currencies {
                grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>💱 Exchange Rate Checker</h1>
        <p>Complete currency tools for travelers • Real-time rates from multiple sources</p>
    </div>
    
    <div class="container">
        <!-- Live Currency Converter -->
        <div class="tool-card">
            <h2><i class="fas fa-exchange-alt"></i>Currency Converter</h2>
            <div class="input-row">
                <div class="input-group">
                    <label for="from-currency">From</label>
                    <select id="from-currency">
                        <option value="">Loading currencies...</option>
                    </select>
                </div>
                <div class="input-group">
                    <label for="to-currency">To</label>
                    <select id="to-currency">
                        <option value="">Loading currencies...</option>
                    </select>
                </div>
            </div>
            <div class="input-group">
                <label for="amount">Amount</label>
                <input type="number" id="amount" value="100" min="0" step="0.01" />
            </div>
            <button class="btn" onclick="convertCurrency()">
                <i class="fas fa-calculator"></i> Convert
            </button>
            <div id="conversion-result"></div>
        </div>
        
        <!-- Popular Exchange Rates -->
        <div class="tool-card">
            <h2><i class="fas fa-chart-line"></i>Popular Rates</h2>
            <p>Select base currency (from <span id="total-currencies">200+</span> available):</p>
            <div class="popular-currencies">
                <?php foreach (array_slice($popular_currencies, 0, 8) as $code => $info): ?>
                <div class="currency-button" onclick="setBaseCurrency('<?php echo $code; ?>')">
                    <div><?php echo $info['flag']; ?></div>
                    <div><?php echo $code; ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <div id="popular-rates" class="results"></div>
        </div>
        
        <!-- Travel Budget Calculator -->
        <div class="tool-card">
            <h2><i class="fas fa-wallet"></i>Travel Budget Calculator</h2>
            <div class="input-row">
                <div class="input-group">
                    <label for="budget-amount">Budget Amount</label>
                    <input type="number" id="budget-amount" value="1000" min="0" step="10" />
                </div>
                <div class="input-group">
                    <label for="budget-currency">Budget Currency</label>
                    <select id="budget-currency">
                        <option value="">Loading currencies...</option>
                    </select>
                </div>
            </div>
            <div class="input-group">
                <label for="destination-currency">Destination Currency</label>
                <select id="destination-currency">
                    <option value="">Loading currencies...</option>
                </select>
            </div>
            <div class="input-group">
                <label for="trip-days">Trip Duration (days)</label>
                <input type="number" id="trip-days" value="7" min="1" max="365" />
            </div>
            <button class="btn btn-success" onclick="calculateBudget()">
                <i class="fas fa-calculator"></i> Calculate Budget
            </button>
            <div id="budget-result"></div>
        </div>
        
        <!-- Tipping Calculator -->
        <div class="tool-card">
            <h2><i class="fas fa-hand-holding-usd"></i>Tipping Calculator</h2>
            <div class="input-row">
                <div class="input-group">
                    <label for="bill-amount">Bill Amount</label>
                    <input type="number" id="bill-amount" value="50" min="0" step="0.01" />
                </div>
                <div class="input-group">
                    <label for="tip-currency">Currency</label>
                    <select id="tip-currency">
                        <option value="">Loading currencies...</option>
                    </select>
                </div>
            </div>
            <div class="input-group">
                <label for="tip-country">Country/Region</label>
                <select id="tip-country">
                    <option value="US">United States (18%)</option>
                    <option value="GB">United Kingdom (10%)</option>
                    <option value="AU">Australia (10%)</option>
                    <option value="JP">Japan (0% - Not customary)</option>
                    <option value="DE">Germany (10%)</option>
                    <option value="FR">France (10%)</option>
                    <option value="IT">Italy (10%)</option>
                    <option value="ES">Spain (10%)</option>
                    <option value="CA">Canada (15%)</option>
                    <option value="MX">Mexico (15%)</option>
                </select>
            </div>
            <button class="btn" onclick="calculateTip()">
                <i class="fas fa-percentage"></i> Calculate Tip
            </button>
            <div id="tip-result"></div>
        </div>
        
        <!-- Rate History & Trends -->
        <div class="tool-card wide-card">
            <h2><i class="fas fa-history"></i>Rate History & Trends (7 Days)</h2>
            <div class="input-row">
                <div class="input-group">
                    <label for="trend-base">Base Currency</label>
                    <select id="trend-base">
                        <option value="">Loading currencies...</option>
                    </select>
                </div>
                <div class="input-group">
                    <label for="trend-target">Target Currency</label>
                    <select id="trend-target">
                        <option value="">Loading currencies...</option>
                    </select>
                </div>
            </div>
            <button class="btn" onclick="loadTrends()">
                <i class="fas fa-chart-area"></i> Load Trends
            </button>
            <div id="trends-result"></div>
        </div>
        
        <!-- Multi-Currency Comparison -->
        <div class="tool-card">
            <h2><i class="fas fa-balance-scale"></i>Multi-Currency Comparison</h2>
            <div class="input-group">
                <label for="comparison-base">Compare Against</label>
                <select id="comparison-base">
                    <option value="">Loading currencies...</option>
                </select>
            </div>
            <div class="input-group">
                <label for="comparison-amount">Amount</label>
                <input type="number" id="comparison-amount" value="100" min="0" step="0.01" />
            </div>
            <button class="btn btn-secondary" onclick="compareMultiple()">
                <i class="fas fa-coins"></i> Compare Rates
            </button>
            <div id="comparison-result" class="results"></div>
        </div>
        
        <!-- Cash vs Card Calculator -->
        <div class="tool-card">
            <h2><i class="fas fa-credit-card"></i>Cash vs Card Calculator</h2>
            <div class="input-group">
                <label for="transaction-amount">Transaction Amount</label>
                <input type="number" id="transaction-amount" value="100" min="0" step="0.01" />
            </div>
            <div class="input-row">
                <div class="input-group">
                    <label for="card-fee">Card Foreign Fee (%)</label>
                    <input type="number" id="card-fee" value="2.5" min="0" max="10" step="0.1" />
                </div>
                <div class="input-group">
                    <label for="atm-fee">ATM Withdrawal Fee</label>
                    <input type="number" id="atm-fee" value="5" min="0" step="0.5" />
                </div>
            </div>
            <button class="btn" onclick="compareCashCard()">
                <i class="fas fa-calculator"></i> Compare Costs
            </button>
            <div id="cash-card-result"></div>
        </div>
        
        <!-- Country Currency Guide -->
        <div class="tool-card wide-card">
            <h2><i class="fas fa-globe"></i>Travel Destinations & Currencies</h2>
            <div class="info-box">
                <strong>Popular Travel Destinations:</strong> Quick reference for major currencies used around the world
            </div>
            <div class="country-guide">
                <?php 
                $destinations = [
                    'US' => ['name' => 'United States', 'currency' => 'USD', 'flag' => '🇺🇸'],
                    'GB' => ['name' => 'United Kingdom', 'currency' => 'GBP', 'flag' => '🇬🇧'],
                    'DE' => ['name' => 'Germany', 'currency' => 'EUR', 'flag' => '🇩🇪'],
                    'JP' => ['name' => 'Japan', 'currency' => 'JPY', 'flag' => '🇯🇵'],
                    'AU' => ['name' => 'Australia', 'currency' => 'AUD', 'flag' => '🇦🇺'],
                    'CA' => ['name' => 'Canada', 'currency' => 'CAD', 'flag' => '🇨🇦'],
                    'CH' => ['name' => 'Switzerland', 'currency' => 'CHF', 'flag' => '🇨🇭'],
                    'TH' => ['name' => 'Thailand', 'currency' => 'THB', 'flag' => '🇹🇭'],
                    'SG' => ['name' => 'Singapore', 'currency' => 'SGD', 'flag' => '🇸🇬'],
                    'MX' => ['name' => 'Mexico', 'currency' => 'MXN', 'flag' => '🇲🇽'],
                    'IN' => ['name' => 'India', 'currency' => 'INR', 'flag' => '🇮🇳'],
                    'BR' => ['name' => 'Brazil', 'currency' => 'BRL', 'flag' => '🇧🇷']
                ];
                foreach ($destinations as $code => $info): ?>
                <div class="country-item">
                    <div>
                        <span class="country-flag"><?php echo $info['flag']; ?></span>
                        <span class="country-name"><?php echo $info['name']; ?></span>
                    </div>
                    <div class="country-currency">Currency: <?php echo $info['currency']; ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- API Information -->
        <div class="tool-card">
            <h2><i class="fas fa-info-circle"></i>API Information</h2>
            <div class="info-box">
                <p><strong>Data Sources:</strong> Frankfurter, ExchangeRate.host, Fawaz API & ExchangeRate-API</p>
                <p><strong>Update Frequency:</strong> Hourly updates (some real-time)</p>
                <p><strong>API Keys:</strong> Not required - completely free!</p>
                <p><strong>Rate Limits:</strong> Generous limits with smart caching</p>
                <p><strong>Coverage:</strong> <span id="currency-count">200+</span> currencies worldwide</p>
            </div>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-value" id="supported-currencies">200+</div>
                    <div class="stat-label">Currencies Supported</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value" id="last-update">-</div>
                    <div class="stat-label">Last Update</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value" id="api-status">Online</div>
                    <div class="stat-label">API Status</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">Free</div>
                    <div class="stat-label">API Keys Required</div>
                </div>
            </div>
            <p style="font-size: 0.9em; color: #7f8c8d; margin-top: 1rem;">
                <strong>Multiple Free Data Sources:</strong><br>
                • <strong>Frankfurter:</strong> Open-source, ECB data, no limits<br>
                • <strong>ExchangeRate.host:</strong> Real-time rates, 170+ currencies<br>
                • <strong>Fawaz Currency API:</strong> 200+ currencies, no rate limits<br>
                • <strong>ExchangeRate-API:</strong> Backup source, reliable data<br><br>
                
                <strong>Advanced Features:</strong><br>
                • Auto-discovery of all available currencies<br>
                • Smart fallback between multiple APIs<br>
                • Intelligent caching for performance<br>
                • Real-time currency conversion<br>
                • Historical rate analysis & trends<br>
                • Travel budget planning tools<br>
                • Country-specific tipping guides<br>
                • Multi-currency comparison<br>
                • Cash vs card cost analysis<br>
                • Mobile-responsive design
            </p>
        </div>
    </div>
    
    <script>
        // Global variables
        let currentRates = {};
        let allCurrencies = {};
        let popularCurrencies = <?php echo json_encode($popular_currencies); ?>;
        let tippingGuide = <?php echo json_encode($tipping_guide); ?>;
        
        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            loadAllCurrencies();
            loadPopularRates('USD');
            updateLastUpdate();
            
            // Add event listeners for real-time conversion
            document.getElementById('amount').addEventListener('input', debounce(convertCurrency, 500));
        });
        
        // Debounce function to limit API calls
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
        
        // Convert currency
        async function convertCurrency() {
            const from = document.getElementById('from-currency').value;
            const to = document.getElementById('to-currency').value;
            const amount = parseFloat(document.getElementById('amount').value) || 0;
            const resultDiv = document.getElementById('conversion-result');
            
            if (amount <= 0) {
                resultDiv.innerHTML = '';
                return;
            }
            
            resultDiv.innerHTML = '<div class="loading">Converting...</div>';
            
            try {
                const response = await fetch(`?action=convert&from=${from}&to=${to}&amount=${amount}`);
                const data = await response.json();
                
                if (data && data.success !== false && data.result) {
                    const rate = data.info ? data.info.rate : (data.result / amount);
                    resultDiv.innerHTML = `
                        <div class="conversion-result">
                            <div class="conversion-amount">
                                ${formatCurrency(data.result, to)}
                            </div>
                            <div class="conversion-details">
                                ${amount} ${from} = ${data.result.toFixed(2)} ${to}<br>
                                Rate: 1 ${from} = ${rate.toFixed(4)} ${to}
                            </div>
                        </div>
                    `;
                } else {
                    // Fallback: calculate from current rates
                    await loadCurrentRate(from, to, amount);
                }
            } catch (error) {
                resultDiv.innerHTML = '<div class="error">Error converting currency. Please try again.</div>';
                console.error('Conversion error:', error);
            }
        }
        
        // Fallback conversion calculation
        async function loadCurrentRate(from, to, amount) {
            try {
                const response = await fetch(`?action=latest_rates&base=${from}&symbols=${to}`);
                const data = await response.json();
                const resultDiv = document.getElementById('conversion-result');
                
                if (data && data.rates && data.rates[to]) {
                    const rate = data.rates[to];
                    const result = amount * rate;
                    
                    resultDiv.innerHTML = `
                        <div class="conversion-result">
                            <div class="conversion-amount">
                                ${formatCurrency(result, to)}
                            </div>
                            <div class="conversion-details">
                                ${amount} ${from} = ${result.toFixed(2)} ${to}<br>
                                Rate: 1 ${from} = ${rate.toFixed(4)} ${to}
                            </div>
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = '<div class="error">Unable to get current rates</div>';
                }
            } catch (error) {
                document.getElementById('conversion-result').innerHTML = 
                    '<div class="error">Error loading rates</div>';
            }
        }
        
        // Load popular rates for a base currency
        async function loadPopularRates(baseCurrency) {
            const resultDiv = document.getElementById('popular-rates');
            resultDiv.innerHTML = '<div class="loading">Loading rates...</div>';
            
            // Update active currency button
            document.querySelectorAll('.currency-button').forEach(btn => {
                btn.classList.remove('active');
                if (btn.textContent.includes(baseCurrency)) {
                    btn.classList.add('active');
                }
            });
            
            try {
                const popularCodes = ['USD', 'EUR', 'GBP', 'JPY', 'AUD', 'CAD', 'CHF', 'CNY'];
                const symbols = popularCodes.filter(code => code !== baseCurrency).join(',');
                
                const response = await fetch(`?action=latest_rates&base=${baseCurrency}&symbols=${symbols}`);
                const data = await response.json();
                
                if (data && data.rates) {
                    currentRates = data.rates;
                    displayPopularRates(data.rates, baseCurrency);
                } else {
                    resultDiv.innerHTML = '<div class="error">Unable to load rates</div>';
                }
            } catch (error) {
                resultDiv.innerHTML = '<div class="error">Error loading rates</div>';
                console.error('Rates error:', error);
            }
        }
        
        // Display popular rates
        function displayPopularRates(rates, baseCurrency) {
            const resultDiv = document.getElementById('popular-rates');
            let html = '';
            
            for (const [currency, rate] of Object.entries(rates)) {
                const currencyInfo = popularCurrencies[currency];
                if (currencyInfo) {
                    html += `
                        <div class="currency-item">
                            <div class="currency-info">
                                <div class="currency-code">
                                    ${currencyInfo.flag} ${currency}
                                </div>
                                <div class="currency-name">${currencyInfo.name}</div>
                            </div>
                            <div class="currency-rate">
                                ${rate.toFixed(4)}
                            </div>
                        </div>
                    `;
                }
            }
            
            resultDiv.innerHTML = html;
        }
        
        // Set base currency for popular rates
        function setBaseCurrency(currency) {
            loadPopularRates(currency);
        }
        
        // Calculate travel budget
        async function calculateBudget() {
            const budgetAmount = parseFloat(document.getElementById('budget-amount').value) || 0;
            const budgetCurrency = document.getElementById('budget-currency').value;
            const destinationCurrency = document.getElementById('destination-currency').value;
            const tripDays = parseInt(document.getElementById('trip-days').value) || 1;
            const resultDiv = document.getElementById('budget-result');
            
            if (budgetAmount <= 0) {
                resultDiv.innerHTML = '<div class="error">Please enter a valid budget amount</div>';
                return;
            }
            
            resultDiv.innerHTML = '<div class="loading">Calculating budget...</div>';
            
            try {
                const response = await fetch(`?action=convert&from=${budgetCurrency}&to=${destinationCurrency}&amount=${budgetAmount}`);
                const data = await response.json();
                
                if (data && data.result) {
                    const totalBudget = data.result;
                    const dailyBudget = totalBudget / tripDays;
                    
                    resultDiv.innerHTML = `
                        <div class="budget-breakdown">
                            <h4>Budget Breakdown</h4>
                            <div class="budget-item">
                                <span>Total Budget:</span>
                                <span>${formatCurrency(totalBudget, destinationCurrency)}</span>
                            </div>
                            <div class="budget-item">
                                <span>Daily Budget:</span>
                                <span>${formatCurrency(dailyBudget, destinationCurrency)}</span>
                            </div>
                            <div class="budget-item">
                                <span>Accommodation (40%):</span>
                                <span>${formatCurrency(dailyBudget * 0.4, destinationCurrency)}</span>
                            </div>
                            <div class="budget-item">
                                <span>Food & Dining (30%):</span>
                                <span>${formatCurrency(dailyBudget * 0.3, destinationCurrency)}</span>
                            </div>
                            <div class="budget-item">
                                <span>Activities (20%):</span>
                                <span>${formatCurrency(dailyBudget * 0.2, destinationCurrency)}</span>
                            </div>
                            <div class="budget-item">
                                <span>Miscellaneous (10%):</span>
                                <span>${formatCurrency(dailyBudget * 0.1, destinationCurrency)}</span>
                            </div>
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = '<div class="error">Unable to calculate budget</div>';
                }
            } catch (error) {
                resultDiv.innerHTML = '<div class="error">Error calculating budget</div>';
                console.error('Budget error:', error);
            }
        }
        
        // Calculate tip
        function calculateTip() {
            const billAmount = parseFloat(document.getElementById('bill-amount').value) || 0;
            const currency = document.getElementById('tip-currency').value;
            const country = document.getElementById('tip-country').value;
            const resultDiv = document.getElementById('tip-result');
            
            if (billAmount <= 0) {
                resultDiv.innerHTML = '<div class="error">Please enter a valid bill amount</div>';
                return;
            }
            
            const tipInfo = tippingGuide[country];
            if (!tipInfo) {
                resultDiv.innerHTML = '<div class="error">Tipping information not available for this country</div>';
                return;
            }
            
            const tipPercentage = tipInfo.percent;
            const tipAmount = billAmount * (tipPercentage / 100);
            const totalAmount = billAmount + tipAmount;
            
            resultDiv.innerHTML = `
                <div class="tip-result">
                    <h4>Tipping Calculation</h4>
                    <p><strong>Recommended Tip:</strong> ${formatCurrency(tipAmount, currency)} (${tipPercentage}%)</p>
                    <p><strong>Total Amount:</strong> ${formatCurrency(totalAmount, currency)}</p>
                    <p style="margin-top: 1rem; font-size: 0.9em;">${tipInfo.description}</p>
                </div>
            `;
        }
        
        // Load trends
        async function loadTrends() {
            const base = document.getElementById('trend-base').value;
            const target = document.getElementById('trend-target').value;
            const resultDiv = document.getElementById('trends-result');
            
            resultDiv.innerHTML = '<div class="loading">Loading trend data...</div>';
            
            try {
                const endDate = new Date();
                const startDate = new Date();
                startDate.setDate(startDate.getDate() - 7);
                
                const response = await fetch(
                    `?action=timeseries&base=${base}&symbols=${target}` +
                    `&start_date=${formatDate(startDate)}&end_date=${formatDate(endDate)}`
                );
                const data = await response.json();
                
                if (data && data.rates) {
                    displayTrends(data.rates, base, target);
                } else {
                    resultDiv.innerHTML = '<div class="error">Unable to load trend data</div>';
                }
            } catch (error) {
                resultDiv.innerHTML = '<div class="error">Error loading trends</div>';
                console.error('Trends error:', error);
            }
        }
        
        // Display trends
        function displayTrends(rates, base, target) {
            const resultDiv = document.getElementById('trends-result');
            let html = '<div class="chart-container"><h4>7-Day Rate History</h4>';
            
            const sortedDates = Object.keys(rates).sort();
            let previousRate = null;
            
            sortedDates.forEach(date => {
                const rate = rates[date][target];
                let changeClass = 'rate-same';
                let changeIcon = '→';
                
                if (previousRate !== null) {
                    if (rate > previousRate) {
                        changeClass = 'rate-up';
                        changeIcon = '↗';
                    } else if (rate < previousRate) {
                        changeClass = 'rate-down';
                        changeIcon = '↘';
                    }
                }
                
                html += `
                    <div class="trend-item">
                        <div class="trend-date">${formatDateDisplay(date)}</div>
                        <div class="trend-rate ${changeClass}">
                            ${changeIcon} ${rate.toFixed(4)} ${target}
                        </div>
                    </div>
                `;
                
                previousRate = rate;
            });
            
            // Calculate trend summary
            const firstRate = rates[sortedDates[0]][target];
            const lastRate = rates[sortedDates[sortedDates.length - 1]][target];
            const change = ((lastRate - firstRate) / firstRate * 100);
            const changeClass = change > 0 ? 'rate-up' : (change < 0 ? 'rate-down' : 'rate-same');
            
            html += `
                <div style="margin-top: 1rem; padding: 1rem; background: #f8f9fa; border-radius: 8px;">
                    <strong>7-Day Summary:</strong><br>
                    <span class="${changeClass}">
                        ${change > 0 ? '+' : ''}${change.toFixed(2)}% change
                        (${firstRate.toFixed(4)} → ${lastRate.toFixed(4)})
                    </span>
                </div>
            `;
            
            html += '</div>';
            resultDiv.innerHTML = html;
        }
        
        // Compare multiple currencies
        async function compareMultiple() {
            const base = document.getElementById('comparison-base').value;
            const amount = parseFloat(document.getElementById('comparison-amount').value) || 1;
            const resultDiv = document.getElementById('comparison-result');
            
            resultDiv.innerHTML = '<div class="loading">Loading comparison...</div>';
            
            try {
                const currencies = ['EUR', 'GBP', 'JPY', 'AUD', 'CAD', 'CHF', 'CNY', 'SEK'];
                const symbols = currencies.filter(c => c !== base).join(',');
                
                const response = await fetch(`?action=latest_rates&base=${base}&symbols=${symbols}`);
                const data = await response.json();
                
                if (data && data.rates) {
                    displayComparison(data.rates, base, amount);
                } else {
                    resultDiv.innerHTML = '<div class="error">Unable to load comparison data</div>';
                }
            } catch (error) {
                resultDiv.innerHTML = '<div class="error">Error loading comparison</div>';
                console.error('Comparison error:', error);
            }
        }
        
        // Display comparison
        function displayComparison(rates, base, amount) {
            const resultDiv = document.getElementById('comparison-result');
            let html = `<h4>${amount} ${base} converts to:</h4>`;
            
            // Sort by converted amount (descending)
            const sortedRates = Object.entries(rates).sort((a, b) => b[1] * amount - a[1] * amount);
            
            sortedRates.forEach(([currency, rate]) => {
                const converted = amount * rate;
                const currencyInfo = popularCurrencies[currency];
                
                html += `
                    <div class="currency-item">
                        <div class="currency-info">
                            <div class="currency-code">
                                ${currencyInfo ? currencyInfo.flag + ' ' : ''}${currency}
                            </div>
                            <div class="currency-name">
                                ${currencyInfo ? currencyInfo.name : currency}
                            </div>
                        </div>
                        <div class="currency-rate">
                            ${formatCurrency(converted, currency)}
                        </div>
                    </div>
                `;
            });
            
            resultDiv.innerHTML = html;
        }
        
        // Compare cash vs card
        function compareCashCard() {
            const amount = parseFloat(document.getElementById('transaction-amount').value) || 0;
            const cardFee = parseFloat(document.getElementById('card-fee').value) || 0;
            const atmFee = parseFloat(document.getElementById('atm-fee').value) || 0;
            const resultDiv = document.getElementById('cash-card-result');
            
            if (amount <= 0) {
                resultDiv.innerHTML = '<div class="error">Please enter a valid transaction amount</div>';
                return;
            }
            
            const cardCost = amount * (1 + cardFee / 100);
            const cashCost = amount + atmFee;
            const difference = Math.abs(cardCost - cashCost);
            const cheaper = cardCost < cashCost ? 'Card' : 'Cash';
            const savings = difference;
            
            resultDiv.innerHTML = `
                <div class="budget-breakdown">
                    <h4>Cost Comparison</h4>
                    <div class="budget-item">
                        <span>💳 Card Payment:</span>
                        <span>$${cardCost.toFixed(2)}</span>
                    </div>
                    <div class="budget-item">
                        <span>💰 Cash (ATM):</span>
                        <span>$${cashCost.toFixed(2)}</span>
                    </div>
                    <div class="budget-item" style="color: #27ae60;">
                        <span><strong>Recommended:</strong></span>
                        <span><strong>${cheaper}</strong></span>
                    </div>
                    <div style="margin-top: 1rem; padding: 1rem; background: #e8f5e8; border-radius: 8px; color: #27ae60;">
                        <strong>You save $${savings.toFixed(2)} by using ${cheaper.toLowerCase()}</strong>
                    </div>
                </div>
            `;
        }
        
        // Utility functions
        function formatCurrency(amount, currency) {
            const currencyInfo = popularCurrencies[currency];
            const symbol = currencyInfo ? currencyInfo.symbol : currency;
            
            if (['JPY', 'KRW'].includes(currency)) {
                return `${symbol}${Math.round(amount).toLocaleString()}`;
            }
            
            return `${symbol}${amount.toFixed(2)}`;
        }
        
        function formatDate(date) {
            return date.toISOString().split('T')[0];
        }
        
        function formatDateDisplay(dateStr) {
            const date = new Date(dateStr);
            return date.toLocaleDateString('en-US', { 
                month: 'short', 
                day: 'numeric' 
            });
        }
        
        function updateLastUpdate() {
            const now = new Date();
            document.getElementById('last-update').textContent = 
                now.toLocaleTimeString('en-US', { 
                    hour: '2-digit', 
                    minute: '2-digit' 
                });
        }
        
        // Auto-refresh rates every 5 minutes
        setInterval(() => {
            const activeBase = document.querySelector('.currency-button.active');
            if (activeBase) {
                const currency = activeBase.textContent.trim().split('\n')[1];
                loadPopularRates(currency);
            }
            updateLastUpdate();
        }, 300000);
    </script>
</body>
</html>
