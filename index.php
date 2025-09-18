<?php
// Exchange Rate Checker - Comprehensive Traveler Tools
// Uses multiple FREE APIs without API keys required
// Primary: fawazahmed0/currency-api (200+ currencies, no limits)
// Backup: exchangerate.host, open.er-api.com

header('Content-Type: text/html; charset=UTF-8');

// Configuration - All FREE APIs, no API keys required
$currency_apis = [
    'primary' => 'https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@latest/v1',
    'backup1' => 'https://api.exchangerate.host',
    'backup2' => 'https://open.er-api.com/v6'
];

$cache_duration = 3600; // Cache for 1 hour

// Comprehensive fallback currency list (ISO 4217 codes)
$all_currencies = [
    'AED' => ['name' => 'United Arab Emirates Dirham', 'symbol' => 'د.إ', 'flag' => '🇦🇪'],
    'AFN' => ['name' => 'Afghan Afghani', 'symbol' => '؋', 'flag' => '🇦🇫'],
    'ALL' => ['name' => 'Albanian Lek', 'symbol' => 'L', 'flag' => '🇦🇱'],
    'AMD' => ['name' => 'Armenian Dram', 'symbol' => '֏', 'flag' => '🇦🇲'],
    'ANG' => ['name' => 'Netherlands Antillean Guilder', 'symbol' => 'ƒ', 'flag' => '🇳🇱'],
    'AOA' => ['name' => 'Angolan Kwanza', 'symbol' => 'Kz', 'flag' => '🇦🇴'],
    'ARS' => ['name' => 'Argentine Peso', 'symbol' => '$', 'flag' => '🇦🇷'],
    'AUD' => ['name' => 'Australian Dollar', 'symbol' => 'A$', 'flag' => '🇦🇺'],
    'AWG' => ['name' => 'Aruban Florin', 'symbol' => 'ƒ', 'flag' => '🇦🇼'],
    'AZN' => ['name' => 'Azerbaijani Manat', 'symbol' => '₼', 'flag' => '🇦🇿'],
    'BAM' => ['name' => 'Bosnia-Herzegovina Convertible Mark', 'symbol' => 'KM', 'flag' => '🇧🇦'],
    'BBD' => ['name' => 'Barbadian Dollar', 'symbol' => '$', 'flag' => '🇧🇧'],
    'BDT' => ['name' => 'Bangladeshi Taka', 'symbol' => '৳', 'flag' => '🇧🇩'],
    'BGN' => ['name' => 'Bulgarian Lev', 'symbol' => 'лв', 'flag' => '🇧🇬'],
    'BHD' => ['name' => 'Bahraini Dinar', 'symbol' => 'ب.د', 'flag' => '🇧🇭'],
    'BIF' => ['name' => 'Burundian Franc', 'symbol' => 'Fr', 'flag' => '🇧🇮'],
    'BMD' => ['name' => 'Bermudan Dollar', 'symbol' => '$', 'flag' => '🇧🇲'],
    'BND' => ['name' => 'Brunei Dollar', 'symbol' => '$', 'flag' => '🇧🇳'],
    'BOB' => ['name' => 'Bolivian Boliviano', 'symbol' => 'Bs.', 'flag' => '🇧🇴'],
    'BRL' => ['name' => 'Brazilian Real', 'symbol' => 'R$', 'flag' => '🇧🇷'],
    'BSD' => ['name' => 'Bahamian Dollar', 'symbol' => '$', 'flag' => '🇧🇸'],
    'BTC' => ['name' => 'Bitcoin', 'symbol' => '₿', 'flag' => '🟡'],
    'BTN' => ['name' => 'Bhutanese Ngultrum', 'symbol' => 'Nu.', 'flag' => '🇧🇹'],
    'BWP' => ['name' => 'Botswanan Pula', 'symbol' => 'P', 'flag' => '🇧🇼'],
    'BYN' => ['name' => 'Belarusian Ruble', 'symbol' => 'Br', 'flag' => '🇧🇾'],
    'BZD' => ['name' => 'Belize Dollar', 'symbol' => 'BZ$', 'flag' => '🇧🇿'],
    'CAD' => ['name' => 'Canadian Dollar', 'symbol' => 'C$', 'flag' => '🇨🇦'],
    'CDF' => ['name' => 'Congolese Franc', 'symbol' => 'Fr', 'flag' => '🇨🇩'],
    'CHF' => ['name' => 'Swiss Franc', 'symbol' => 'CHF', 'flag' => '🇨🇭'],
    'CLP' => ['name' => 'Chilean Peso', 'symbol' => '$', 'flag' => '🇨🇱'],
    'CNY' => ['name' => 'Chinese Yuan', 'symbol' => '¥', 'flag' => '🇨🇳'],
    'COP' => ['name' => 'Colombian Peso', 'symbol' => '$', 'flag' => '🇨🇴'],
    'CRC' => ['name' => 'Costa Rican Colón', 'symbol' => '₡', 'flag' => '🇨🇷'],
    'CUC' => ['name' => 'Cuban Convertible Peso', 'symbol' => '$', 'flag' => '🇨🇺'],
    'CUP' => ['name' => 'Cuban Peso', 'symbol' => '$', 'flag' => '🇨🇺'],
    'CVE' => ['name' => 'Cape Verdean Escudo', 'symbol' => '$', 'flag' => '🇨🇻'],
    'CZK' => ['name' => 'Czech Koruna', 'symbol' => 'Kč', 'flag' => '🇨🇿'],
    'DJF' => ['name' => 'Djiboutian Franc', 'symbol' => 'Fr', 'flag' => '🇩🇯'],
    'DKK' => ['name' => 'Danish Krone', 'symbol' => 'kr', 'flag' => '🇩🇰'],
    'DOP' => ['name' => 'Dominican Peso', 'symbol' => 'RD$', 'flag' => '🇩🇴'],
    'DZD' => ['name' => 'Algerian Dinar', 'symbol' => 'د.ج', 'flag' => '🇩🇿'],
    'EGP' => ['name' => 'Egyptian Pound', 'symbol' => '£', 'flag' => '🇪🇬'],
    'ERN' => ['name' => 'Eritrean Nakfa', 'symbol' => 'Nfk', 'flag' => '🇪🇷'],
    'ETB' => ['name' => 'Ethiopian Birr', 'symbol' => 'Br', 'flag' => '🇪🇹'],
    'EUR' => ['name' => 'Euro', 'symbol' => '€', 'flag' => '🇪🇺'],
    'FJD' => ['name' => 'Fijian Dollar', 'symbol' => '$', 'flag' => '🇫🇯'],
    'FKP' => ['name' => 'Falkland Islands Pound', 'symbol' => '£', 'flag' => '🇫🇰'],
    'GBP' => ['name' => 'British Pound Sterling', 'symbol' => '£', 'flag' => '🇬🇧'],
    'GEL' => ['name' => 'Georgian Lari', 'symbol' => '₾', 'flag' => '🇬🇪'],
    'GGP' => ['name' => 'Guernsey Pound', 'symbol' => '£', 'flag' => '🇬🇬'],
    'GHS' => ['name' => 'Ghanaian Cedi', 'symbol' => '₵', 'flag' => '🇬🇭'],
    'GIP' => ['name' => 'Gibraltar Pound', 'symbol' => '£', 'flag' => '🇬🇮'],
    'GMD' => ['name' => 'Gambian Dalasi', 'symbol' => 'D', 'flag' => '🇬🇲'],
    'GNF' => ['name' => 'Guinean Franc', 'symbol' => 'Fr', 'flag' => '🇬🇳'],
    'GTQ' => ['name' => 'Guatemalan Quetzal', 'symbol' => 'Q', 'flag' => '🇬🇹'],
    'GYD' => ['name' => 'Guyanaese Dollar', 'symbol' => '$', 'flag' => '🇬🇾'],
    'HKD' => ['name' => 'Hong Kong Dollar', 'symbol' => 'HK$', 'flag' => '🇭🇰'],
    'HNL' => ['name' => 'Honduran Lempira', 'symbol' => 'L', 'flag' => '🇭🇳'],
    'HRK' => ['name' => 'Croatian Kuna', 'symbol' => 'kn', 'flag' => '🇭🇷'],
    'HTG' => ['name' => 'Haitian Gourde', 'symbol' => 'G', 'flag' => '🇭🇹'],
    'HUF' => ['name' => 'Hungarian Forint', 'symbol' => 'Ft', 'flag' => '🇭🇺'],
    'IDR' => ['name' => 'Indonesian Rupiah', 'symbol' => 'Rp', 'flag' => '🇮🇩'],
    'ILS' => ['name' => 'Israeli New Sheqel', 'symbol' => '₪', 'flag' => '🇮🇱'],
    'IMP' => ['name' => 'Manx pound', 'symbol' => '£', 'flag' => '🇮🇲'],
    'INR' => ['name' => 'Indian Rupee', 'symbol' => '₹', 'flag' => '🇮🇳'],
    'IQD' => ['name' => 'Iraqi Dinar', 'symbol' => 'ع.د', 'flag' => '🇮🇶'],
    'IRR' => ['name' => 'Iranian Rial', 'symbol' => '﷼', 'flag' => '🇮🇷'],
    'ISK' => ['name' => 'Icelandic Króna', 'symbol' => 'kr', 'flag' => '🇮🇸'],
    'JEP' => ['name' => 'Jersey Pound', 'symbol' => '£', 'flag' => '🇯🇪'],
    'JMD' => ['name' => 'Jamaican Dollar', 'symbol' => 'J$', 'flag' => '🇯🇲'],
    'JOD' => ['name' => 'Jordanian Dinar', 'symbol' => 'د.ا', 'flag' => '🇯🇴'],
    'JPY' => ['name' => 'Japanese Yen', 'symbol' => '¥', 'flag' => '🇯🇵'],
    'KES' => ['name' => 'Kenyan Shilling', 'symbol' => 'Sh', 'flag' => '🇰🇪'],
    'KGS' => ['name' => 'Kyrgystani Som', 'symbol' => 'с', 'flag' => '🇰🇬'],
    'KHR' => ['name' => 'Cambodian Riel', 'symbol' => '៛', 'flag' => '🇰🇭'],
    'KMF' => ['name' => 'Comorian Franc', 'symbol' => 'Fr', 'flag' => '🇰🇲'],
    'KPW' => ['name' => 'North Korean Won', 'symbol' => '₩', 'flag' => '🇰🇵'],
    'KRW' => ['name' => 'South Korean Won', 'symbol' => '₩', 'flag' => '🇰🇷'],
    'KWD' => ['name' => 'Kuwaiti Dinar', 'symbol' => 'د.ك', 'flag' => '🇰🇼'],
    'KYD' => ['name' => 'Cayman Islands Dollar', 'symbol' => '$', 'flag' => '🇰🇾'],
    'KZT' => ['name' => 'Kazakhstani Tenge', 'symbol' => '₸', 'flag' => '🇰🇿'],
    'LAK' => ['name' => 'Laotian Kip', 'symbol' => '₭', 'flag' => '🇱🇦'],
    'LBP' => ['name' => 'Lebanese Pound', 'symbol' => 'ل.ل', 'flag' => '🇱🇧'],
    'LKR' => ['name' => 'Sri Lankan Rupee', 'symbol' => 'Rs', 'flag' => '🇱🇰'],
    'LRD' => ['name' => 'Liberian Dollar', 'symbol' => '$', 'flag' => '🇱🇷'],
    'LSL' => ['name' => 'Lesotho Loti', 'symbol' => 'L', 'flag' => '🇱🇸'],
    'LYD' => ['name' => 'Libyan Dinar', 'symbol' => 'ل.د', 'flag' => '🇱🇾'],
    'MAD' => ['name' => 'Moroccan Dirham', 'symbol' => 'د.م.', 'flag' => '🇲🇦'],
    'MDL' => ['name' => 'Moldovan Leu', 'symbol' => 'L', 'flag' => '🇲🇩'],
    'MGA' => ['name' => 'Malagasy Ariary', 'symbol' => 'Ar', 'flag' => '🇲🇬'],
    'MKD' => ['name' => 'Macedonian Denar', 'symbol' => 'ден', 'flag' => '🇲🇰'],
    'MMK' => ['name' => 'Myanma Kyat', 'symbol' => 'Ks', 'flag' => '🇲🇲'],
    'MNT' => ['name' => 'Mongolian Tugrik', 'symbol' => '₮', 'flag' => '🇲🇳'],
    'MOP' => ['name' => 'Macanese Pataca', 'symbol' => 'P', 'flag' => '🇲🇴'],
    'MRU' => ['name' => 'Mauritanian Ouguiya', 'symbol' => 'UM', 'flag' => '🇲🇷'],
    'MUR' => ['name' => 'Mauritian Rupee', 'symbol' => '₨', 'flag' => '🇲🇺'],
    'MVR' => ['name' => 'Maldivian Rufiyaa', 'symbol' => '.ރ', 'flag' => '🇲🇻'],
    'MWK' => ['name' => 'Malawian Kwacha', 'symbol' => 'MK', 'flag' => '🇲🇼'],
    'MXN' => ['name' => 'Mexican Peso', 'symbol' => '$', 'flag' => '🇲🇽'],
    'MYR' => ['name' => 'Malaysian Ringgit', 'symbol' => 'RM', 'flag' => '🇲🇾'],
    'MZN' => ['name' => 'Mozambican Metical', 'symbol' => 'MT', 'flag' => '🇲🇿'],
    'NAD' => ['name' => 'Namibian Dollar', 'symbol' => '$', 'flag' => '🇳🇦'],
    'NGN' => ['name' => 'Nigerian Naira', 'symbol' => '₦', 'flag' => '🇳🇬'],
    'NIO' => ['name' => 'Nicaraguan Córdoba', 'symbol' => 'C$', 'flag' => '🇳🇮'],
    'NOK' => ['name' => 'Norwegian Krone', 'symbol' => 'kr', 'flag' => '🇳🇴'],
    'NPR' => ['name' => 'Nepalese Rupee', 'symbol' => '₨', 'flag' => '🇳🇵'],
    'NZD' => ['name' => 'New Zealand Dollar', 'symbol' => 'NZ$', 'flag' => '🇳🇿'],
    'OMR' => ['name' => 'Omani Rial', 'symbol' => 'ر.ع.', 'flag' => '🇴🇲'],
    'PAB' => ['name' => 'Panamanian Balboa', 'symbol' => 'B/.', 'flag' => '🇵🇦'],
    'PEN' => ['name' => 'Peruvian Nuevo Sol', 'symbol' => 'S/.', 'flag' => '🇵🇪'],
    'PGK' => ['name' => 'Papua New Guinean Kina', 'symbol' => 'K', 'flag' => '🇵🇬'],
    'PHP' => ['name' => 'Philippine Peso', 'symbol' => '₱', 'flag' => '🇵🇭'],
    'PKR' => ['name' => 'Pakistani Rupee', 'symbol' => '₨', 'flag' => '🇵🇰'],
    'PLN' => ['name' => 'Polish Zloty', 'symbol' => 'zł', 'flag' => '🇵🇱'],
    'PYG' => ['name' => 'Paraguayan Guarani', 'symbol' => '₲', 'flag' => '🇵🇾'],
    'QAR' => ['name' => 'Qatari Rial', 'symbol' => 'ر.ق', 'flag' => '🇶🇦'],
    'RON' => ['name' => 'Romanian Leu', 'symbol' => 'lei', 'flag' => '🇷🇴'],
    'RSD' => ['name' => 'Serbian Dinar', 'symbol' => 'дин.', 'flag' => '🇷🇸'],
    'RUB' => ['name' => 'Russian Ruble', 'symbol' => '₽', 'flag' => '🇷🇺'],
    'RWF' => ['name' => 'Rwandan Franc', 'symbol' => 'Fr', 'flag' => '🇷🇼'],
    'SAR' => ['name' => 'Saudi Riyal', 'symbol' => 'ر.س', 'flag' => '🇸🇦'],
    'SBD' => ['name' => 'Solomon Islands Dollar', 'symbol' => '$', 'flag' => '🇸🇧'],
    'SCR' => ['name' => 'Seychellois Rupee', 'symbol' => '₨', 'flag' => '🇸🇨'],
    'SDG' => ['name' => 'Sudanese Pound', 'symbol' => 'ج.س.', 'flag' => '🇸🇩'],
    'SEK' => ['name' => 'Swedish Krona', 'symbol' => 'kr', 'flag' => '🇸🇪'],
    'SGD' => ['name' => 'Singapore Dollar', 'symbol' => 'S$', 'flag' => '🇸🇬'],
    'SHP' => ['name' => 'Saint Helena Pound', 'symbol' => '£', 'flag' => '🇸🇭'],
    'SLE' => ['name' => 'Sierra Leonean Leone', 'symbol' => 'Le', 'flag' => '🇸🇱'],
    'SOS' => ['name' => 'Somali Shilling', 'symbol' => 'Sh', 'flag' => '🇸🇴'],
    'SRD' => ['name' => 'Surinamese Dollar', 'symbol' => '$', 'flag' => '🇸🇷'],
    'STD' => ['name' => 'São Tomé and Príncipe Dobra', 'symbol' => 'Db', 'flag' => '🇸🇹'],
    'SVC' => ['name' => 'Salvadoran Colón', 'symbol' => '₡', 'flag' => '🇸🇻'],
    'SYP' => ['name' => 'Syrian Pound', 'symbol' => '£S', 'flag' => '🇸🇾'],
    'SZL' => ['name' => 'Swazi Lilangeni', 'symbol' => 'L', 'flag' => '🇸🇿'],
    'THB' => ['name' => 'Thai Baht', 'symbol' => '฿', 'flag' => '🇹🇭'],
    'TJS' => ['name' => 'Tajikistani Somoni', 'symbol' => 'ЅМ', 'flag' => '🇹🇯'],
    'TMT' => ['name' => 'Turkmenistani Manat', 'symbol' => 'm', 'flag' => '🇹🇲'],
    'TND' => ['name' => 'Tunisian Dinar', 'symbol' => 'د.ت', 'flag' => '🇹🇳'],
    'TOP' => ['name' => 'Tongan Paʻanga', 'symbol' => 'T$', 'flag' => '🇹🇴'],
    'TRY' => ['name' => 'Turkish Lira', 'symbol' => '₺', 'flag' => '🇹🇷'],
    'TTD' => ['name' => 'Trinidad and Tobago Dollar', 'symbol' => 'TT$', 'flag' => '🇹🇹'],
    'TVD' => ['name' => 'Tuvaluan Dollar', 'symbol' => '$', 'flag' => '🇹🇻'],
    'TWD' => ['name' => 'New Taiwan Dollar', 'symbol' => 'NT$', 'flag' => '🇹🇼'],
    'TZS' => ['name' => 'Tanzanian Shilling', 'symbol' => 'Sh', 'flag' => '🇹🇿'],
    'UAH' => ['name' => 'Ukrainian Hryvnia', 'symbol' => '₴', 'flag' => '🇺🇦'],
    'UGX' => ['name' => 'Ugandan Shilling', 'symbol' => 'Sh', 'flag' => '🇺🇬'],
    'USD' => ['name' => 'US Dollar', 'symbol' => '$', 'flag' => '🇺🇸'],
    'UYU' => ['name' => 'Uruguayan Peso', 'symbol' => '$U', 'flag' => '🇺🇾'],
    'UYW' => ['name' => 'Unidad Previsional', 'symbol' => 'UP', 'flag' => '🇺🇾'],
    'UZS' => ['name' => 'Uzbekistan Som', 'symbol' => 'som', 'flag' => '🇺🇿'],
    'VED' => ['name' => 'Venezuelan Bolívar Digital', 'symbol' => 'Bs.D.', 'flag' => '🇻🇪'],
    'VES' => ['name' => 'Venezuelan Bolívar Soberano', 'symbol' => 'Bs.S.', 'flag' => '🇻🇪'],
    'VND' => ['name' => 'Vietnamese Dong', 'symbol' => '₫', 'flag' => '🇻🇳'],
    'VUV' => ['name' => 'Vanuatu Vatu', 'symbol' => 'Vt', 'flag' => '🇻🇺'],
    'WST' => ['name' => 'Samoan Tala', 'symbol' => 'T', 'flag' => '🇼🇸'],
    'XAF' => ['name' => 'CFA Franc BEAC', 'symbol' => 'Fr', 'flag' => '🌍'],
    'XAG' => ['name' => 'Silver Ounce', 'symbol' => 'oz', 'flag' => '🥈'],
    'XAU' => ['name' => 'Gold Ounce', 'symbol' => 'oz', 'flag' => '🥇'],
    'XCD' => ['name' => 'East Caribbean Dollar', 'symbol' => '$', 'flag' => '🏝️'],
    'XDR' => ['name' => 'Special Drawing Rights', 'symbol' => 'SDR', 'flag' => '🏦'],
    'XOF' => ['name' => 'CFA Franc BCEAO', 'symbol' => 'Fr', 'flag' => '🌍'],
    'XPD' => ['name' => 'Palladium Ounce', 'symbol' => 'oz', 'flag' => '⚪'],
    'XPF' => ['name' => 'CFP Franc', 'symbol' => 'Fr', 'flag' => '🇫🇷'],
    'XPT' => ['name' => 'Platinum Ounce', 'symbol' => 'oz', 'flag' => '🔘'],
    'YER' => ['name' => 'Yemeni Rial', 'symbol' => '﷼', 'flag' => '🇾🇪'],
    'ZAR' => ['name' => 'South African Rand', 'symbol' => 'R', 'flag' => '🇿🇦'],
    'ZMW' => ['name' => 'Zambian Kwacha', 'symbol' => 'ZK', 'flag' => '🇿🇲'],
    'ZWL' => ['name' => 'Zimbabwean Dollar', 'symbol' => '$', 'flag' => '🇿🇼']
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
    'MX' => ['percent' => 15, 'description' => '10-15% for restaurants, round up for services'],
    'TH' => ['percent' => 5, 'description' => '5-10% for restaurants, round up for services'],
    'SG' => ['percent' => 0, 'description' => 'Service charge often included, small tips appreciated'],
    'IN' => ['percent' => 10, 'description' => '10% for restaurants, round up for taxis'],
    'BR' => ['percent' => 10, 'description' => '10% service charge usually included'],
    'ZA' => ['percent' => 15, 'description' => '10-15% for restaurants and services']
];

// Helper function to make API calls with multiple fallbacks
function fetchExchangeData($endpoint, $params = [], $api_type = 'primary') {
    global $currency_apis, $cache_duration;
    
    $cache_key = md5($endpoint . serialize($params) . $api_type);
    $cache_file = sys_get_temp_dir() . '/exchange_' . $cache_key . '.json';
    
    // Check cache first
    if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $cache_duration) {
        return json_decode(file_get_contents($cache_file), true);
    }
    
    // Try different APIs based on endpoint
    $attempts = [];
    
    if ($api_type === 'primary' || $api_type === 'currencies') {
        // Use fawazahmed0 API for currencies and rates
        if ($endpoint === '/currencies') {
            $attempts[] = $currency_apis['primary'] . '/currencies.json';
        } else {
            $base = isset($params['base']) ? strtolower($params['base']) : 'usd';
            $attempts[] = $currency_apis['primary'] . '/currencies/' . $base . '.json';
        }
    }
    
    // Add backup APIs
    if ($endpoint !== '/currencies') {
        $query = http_build_query($params);
        $attempts[] = $currency_apis['backup1'] . $endpoint . ($query ? '?' . $query : '');
        
        if (strpos($endpoint, 'convert') === false && strpos($endpoint, 'timeseries') === false) {
            $attempts[] = $currency_apis['backup2'] . $endpoint;
        }
    }
    
    // Try each API
    foreach ($attempts as $url) {
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
        if ($response !== false) {
            $data = json_decode($response, true);
            if ($data) {
                // Cache successful response
                file_put_contents($cache_file, $response);
                return $data;
            }
        }
    }
    
    return null;
}

// Handle AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['action']) {
        case 'currencies':
            // Get all available currencies
            $data = fetchExchangeData('/currencies', [], 'currencies');
            if ($data) {
                echo json_encode($data);
            } else {
                // Fallback to built-in currency list
                echo json_encode($all_currencies);
            }
            exit;
            
        case 'latest_rates':
            $base = isset($_GET['base']) ? strtolower($_GET['base']) : 'usd';
            $data = fetchExchangeData('/latest', ['base' => $base], 'primary');
            
            if ($data) {
                // Handle different API response formats
                if (isset($data[$base])) {
                    // fawazahmed0 format
                    echo json_encode(['rates' => $data[$base], 'base' => strtoupper($base)]);
                } else {
                    echo json_encode($data);
                }
            } else {
                echo json_encode(['error' => 'Unable to fetch rates']);
            }
            exit;
            
        case 'convert':
            $from = strtolower($_GET['from'] ?? 'usd');
            $to = strtolower($_GET['to'] ?? 'eur');
            $amount = floatval($_GET['amount'] ?? 1);
            
            // Get rates from base currency
            $data = fetchExchangeData('/latest', ['base' => $from], 'primary');
            
            if ($data && isset($data[$from][$to])) {
                $rate = $data[$from][$to];
                $result = $amount * $rate;
                echo json_encode([
                    'result' => $result,
                    'rate' => $rate,
                    'from' => strtoupper($from),
                    'to' => strtoupper($to),
                    'amount' => $amount
                ]);
            } else {
                echo json_encode(['error' => 'Unable to convert']);
            }
            exit;
            
        case 'historical':
            $date = $_GET['date'] ?? date('Y-m-d', strtotime('-1 day'));
            $base = strtolower($_GET['base'] ?? 'usd');
            
            // Try fawazahmed0 historical endpoint
            $url_date = str_replace('-', '-', $date);
            $cache_key = md5("historical_${base}_${date}");
            $cache_file = sys_get_temp_dir() . '/exchange_' . $cache_key . '.json';
            
            if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $cache_duration) {
                echo file_get_contents($cache_file);
                exit;
            }
            
            $historical_url = "https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@{$date}/v1/currencies/{$base}.json";
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'timeout' => 10
                ]
            ]);
            
            $response = @file_get_contents($historical_url, false, $context);
            if ($response) {
                file_put_contents($cache_file, $response);
                echo $response;
            } else {
                echo json_encode(['error' => 'Historical data not available']);
            }
            exit;
            
        case 'timeseries':
            // For timeseries, we'll get data for each day in range
            $start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-7 days'));
            $end_date = $_GET['end_date'] ?? date('Y-m-d');
            $base = strtolower($_GET['base'] ?? 'usd');
            $symbols = explode(',', strtolower($_GET['symbols'] ?? 'eur,gbp,jpy'));
            
            $start = new DateTime($start_date);
            $end = new DateTime($end_date);
            $interval = new DateInterval('P1D');
            $period = new DatePeriod($start, $interval, $end);
            
            $timeseries_data = [];
            
            foreach ($period as $date) {
                $date_str = $date->format('Y-m-d');
                $historical_data = fetchExchangeData('/historical', ['date' => $date_str, 'base' => $base]);
                
                if ($historical_data && isset($historical_data[$base])) {
                    $day_rates = [];
                    foreach ($symbols as $symbol) {
                        if (isset($historical_data[$base][$symbol])) {
                            $day_rates[$symbol] = $historical_data[$base][$symbol];
                        }
                    }
                    if (!empty($day_rates)) {
                        $timeseries_data[$date_str] = $day_rates;
                    }
                }
            }
            
            echo json_encode(['rates' => $timeseries_data, 'base' => strtoupper($base)]);
            exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exchange Rate Checker - Complete Currency Tools</title>
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
        
        .trend-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
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
        
        .rate-up { color: #27ae60; }
        .rate-down { color: #e74c3c; }
        .rate-same { color: #95a5a6; }
        
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
        <p>ALL world currencies • 200+ currencies • Multiple free APIs • No API key required</p>
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
            <p>Select base currency:</p>
            <div class="popular-currencies" id="popular-currency-buttons">
                <div class="currency-button active" onclick="setBaseCurrency('USD')">
                    <div>🇺🇸</div>
                    <div>USD</div>
                </div>
                <div class="currency-button" onclick="setBaseCurrency('EUR')">
                    <div>🇪🇺</div>
                    <div>EUR</div>
                </div>
                <div class="currency-button" onclick="setBaseCurrency('GBP')">
                    <div>🇬🇧</div>
                    <div>GBP</div>
                </div>
                <div class="currency-button" onclick="setBaseCurrency('JPY')">
                    <div>🇯🇵</div>
                    <div>JPY</div>
                </div>
                <div class="currency-button" onclick="setBaseCurrency('AUD')">
                    <div>🇦🇺</div>
                    <div>AUD</div>
                </div>
                <div class="currency-button" onclick="setBaseCurrency('CAD')">
                    <div>🇨🇦</div>
                    <div>CAD</div>
                </div>
                <div class="currency-button" onclick="setBaseCurrency('CHF')">
                    <div>🇨🇭</div>
                    <div>CHF</div>
                </div>
                <div class="currency-button" onclick="setBaseCurrency('CNY')">
                    <div>🇨🇳</div>
                    <div>CNY</div>
                </div>
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
                        <option value="">Loading...</option>
                    </select>
                </div>
            </div>
            <div class="input-group">
                <label for="destination-currency">Destination Currency</label>
                <select id="destination-currency">
                    <option value="">Loading...</option>
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
                        <option value="">Loading...</option>
                    </select>
                </div>
            </div>
            <div class="input-group">
                <label for="tip-country">Country/Region</label>
                <select id="tip-country">
                    <option value="US">🇺🇸 United States (18%)</option>
                    <option value="GB">🇬🇧 United Kingdom (10%)</option>
                    <option value="AU">🇦🇺 Australia (10%)</option>
                    <option value="JP">🇯🇵 Japan (0% - Not customary)</option>
                    <option value="DE">🇩🇪 Germany (10%)</option>
                    <option value="FR">🇫🇷 France (10%)</option>
                    <option value="IT">🇮🇹 Italy (10%)</option>
                    <option value="ES">🇪🇸 Spain (10%)</option>
                    <option value="CA">🇨🇦 Canada (15%)</option>
                    <option value="MX">🇲🇽 Mexico (15%)</option>
                    <option value="TH">🇹🇭 Thailand (5%)</option>
                    <option value="SG">🇸🇬 Singapore (0%)</option>
                    <option value="IN">🇮🇳 India (10%)</option>
                    <option value="BR">🇧🇷 Brazil (10%)</option>
                    <option value="ZA">🇿🇦 South Africa (15%)</option>
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
                        <option value="">Loading...</option>
                    </select>
                </div>
                <div class="input-group">
                    <label for="trend-target">Target Currency</label>
                    <select id="trend-target">
                        <option value="">Loading...</option>
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
                    <option value="">Loading...</option>
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
        
        <!-- API Information -->
        <div class="tool-card">
            <h2><i class="fas fa-info-circle"></i>API Information</h2>
            <div class="info-box">
                <p><strong>Data Sources:</strong> fawazahmed0/currency-api, ExchangeRate.host</p>
                <p><strong>No API Key Required:</strong> Completely free to use</p>
                <p><strong>Coverage:</strong> 200+ currencies including crypto</p>
                <p><strong>Rate Limits:</strong> None on primary API</p>
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
                    <div class="stat-label">API Key Required</div>
                </div>
            </div>
            <p style="font-size: 0.9em; color: #7f8c8d; margin-top: 1rem;">
                <strong>Features:</strong><br>
                • Complete currency coverage (200+ currencies)<br>
                • Real-time & historical exchange rates<br>
                • No API key or registration required<br>
                • Multiple backup data sources<br>
                • Cryptocurrency support (BTC, ETH, etc.)<br>
                • Precious metals (Gold, Silver, Platinum)<br>
                • Travel budget planning & tipping guides<br>
                • Responsive design for all devices
            </p>
        </div>
    </div>
    
    <script>
        // Global variables
        let allCurrencies = <?php echo json_encode($all_currencies); ?>;
        let currentRates = {};
        let tippingGuide = <?php echo json_encode($tipping_guide); ?>;
        
        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            loadAllCurrencies();
            loadPopularRates('USD');
            updateLastUpdate();
            
            // Add event listeners
            document.getElementById('amount').addEventListener('input', debounce(convertCurrency, 500));
            document.getElementById('from-currency').addEventListener('change', convertCurrency);
            document.getElementById('to-currency').addEventListener('change', convertCurrency);
        });
        
        // Load all currencies from API
        async function loadAllCurrencies() {
            try {
                const response = await fetch('?action=currencies');
                const data = await response.json();
                
                let currencies = allCurrencies; // Use fallback
                
                // Check if we got currency data from API
                if (data && typeof data === 'object') {
                    // Handle different API response formats
                    if (Object.keys(data).length > 50) {  // Assume it's currency data if many entries
                        currencies = {};
                        Object.entries(data).forEach(([code, name]) => {
                            if (typeof name === 'string') {
                                currencies[code.toUpperCase()] = {
                                    name: name,
                                    symbol: allCurrencies[code.toUpperCase()]?.symbol || code.toUpperCase(),
                                    flag: allCurrencies[code.toUpperCase()]?.flag || '🌍'
                                };
                            }
                        });
                    }
                }
                
                populateCurrencyDropdowns(currencies);
                document.getElementById('supported-currencies').textContent = Object.keys(currencies).length;
                
            } catch (error) {
                console.error('Error loading currencies:', error);
                populateCurrencyDropdowns(allCurrencies);
            }
        }
        
        // Populate all currency dropdowns
        function populateCurrencyDropdowns(currencies) {
            const dropdowns = [
                'from-currency', 'to-currency', 'budget-currency', 
                'destination-currency', 'tip-currency', 'trend-base', 
                'trend-target', 'comparison-base'
            ];
            
            // Sort currencies by code
            const sortedCurrencies = Object.entries(currencies).sort((a, b) => a[0].localeCompare(b[0]));
            
            dropdowns.forEach(id => {
                const select = document.getElementById(id);
                select.innerHTML = '';
                
                sortedCurrencies.forEach(([code, info]) => {
                    const option = document.createElement('option');
                    option.value = code;
                    option.textContent = `${info.flag || '🌍'} ${code} - ${info.name}`;
                    select.appendChild(option);
                });
            });
            
            // Set default values
            document.getElementById('from-currency').value = 'USD';
            document.getElementById('to-currency').value = 'EUR';
            document.getElementById('budget-currency').value = 'USD';
            document.getElementById('destination-currency').value = 'EUR';
            document.getElementById('tip-currency').value = 'USD';
            document.getElementById('trend-base').value = 'USD';
            document.getElementById('trend-target').value = 'EUR';
            document.getElementById('comparison-base').value = 'USD';
        }
        
        // Debounce function
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
            
            if (!from || !to || amount <= 0) {
                resultDiv.innerHTML = '';
                return;
            }
            
            resultDiv.innerHTML = '<div class="loading">Converting...</div>';
            
            try {
                const response = await fetch(`?action=convert&from=${from}&to=${to}&amount=${amount}`);
                const data = await response.json();
                
                if (data && data.result && !data.error) {
                    resultDiv.innerHTML = `
                        <div class="conversion-result">
                            <div class="conversion-amount">
                                ${formatCurrency(data.result, to)}
                            </div>
                            <div class="conversion-details">
                                ${amount} ${from} = ${data.result.toFixed(4)} ${to}<br>
                                Rate: 1 ${from} = ${data.rate.toFixed(6)} ${to}
                            </div>
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = '<div class="error">Unable to convert. Please try again.</div>';
                }
            } catch (error) {
                resultDiv.innerHTML = '<div class="error">Error converting currency. Please try again.</div>';
                console.error('Conversion error:', error);
            }
        }
        
        // Load popular rates
        async function loadPopularRates(baseCurrency) {
            const resultDiv = document.getElementById('popular-rates');
            resultDiv.innerHTML = '<div class="loading">Loading rates...</div>';
            
            // Update active button
            document.querySelectorAll('.currency-button').forEach(btn => {
                btn.classList.remove('active');
                if (btn.textContent.includes(baseCurrency)) {
                    btn.classList.add('active');
                }
            });
            
            try {
                const response = await fetch(`?action=latest_rates&base=${baseCurrency}`);
                const data = await response.json();
                
                if (data && data.rates) {
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
            const popularCodes = ['USD', 'EUR', 'GBP', 'JPY', 'AUD', 'CAD', 'CHF', 'CNY', 'SEK', 'NOK', 'DKK', 'NZD', 'SGD', 'HKD'];
            
            let html = '';
            
            popularCodes.forEach(code => {
                if (code !== baseCurrency && rates[code.toLowerCase()]) {
                    const rate = rates[code.toLowerCase()];
                    const currencyInfo = allCurrencies[code];
                    
                    html += `
                        <div class="currency-item">
                            <div class="currency-info">
                                <div class="currency-code">
                                    ${currencyInfo?.flag || '🌍'} ${code}
                                </div>
                                <div class="currency-name">${currencyInfo?.name || code}</div>
                            </div>
                            <div class="currency-rate">
                                ${rate.toFixed(4)}
                            </div>
                        </div>
                    `;
                }
            });
            
            resultDiv.innerHTML = html || '<div class="error">No rates available</div>';
        }
        
        // Set base currency
        function setBaseCurrency(currency) {
            loadPopularRates(currency);
        }
        
        // Calculate budget
        async function calculateBudget() {
            const budgetAmount = parseFloat(document.getElementById('budget-amount').value) || 0;
            const budgetCurrency = document.getElementById('budget-currency').value;
            const destinationCurrency = document.getElementById('destination-currency').value;
            const tripDays = parseInt(document.getElementById('trip-days').value) || 1;
            const resultDiv = document.getElementById('budget-result');
            
            if (budgetAmount <= 0 || !budgetCurrency || !destinationCurrency) {
                resultDiv.innerHTML = '<div class="error">Please fill all fields</div>';
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
            
            if (billAmount <= 0 || !currency) {
                resultDiv.innerHTML = '<div class="error">Please enter valid amounts</div>';
                return;
            }
            
            const tipInfo = tippingGuide[country];
            if (!tipInfo) {
                resultDiv.innerHTML = '<div class="error">Tipping information not available</div>';
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
            
            if (!base || !target) {
                resultDiv.innerHTML = '<div class="error">Please select currencies</div>';
                return;
            }
            
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
                
                if (data && data.rates && Object.keys(data.rates).length > 0) {
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
            let html = '<div style="background: white; padding: 1rem; border-radius: 8px;"><h4>7-Day Rate History</h4>';
            
            const sortedDates = Object.keys(rates).sort();
            let previousRate = null;
            
            if (sortedDates.length === 0) {
                html += '<p>No historical data available for this period.</p></div>';
                resultDiv.innerHTML = html;
                return;
            }
            
            sortedDates.forEach(date => {
                const rate = rates[date][target.toLowerCase()];
                if (!rate) return;
                
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
            if (sortedDates.length >= 2) {
                const firstRate = rates[sortedDates[0]][target.toLowerCase()];
                const lastRate = rates[sortedDates[sortedDates.length - 1]][target.toLowerCase()];
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
            }
            
            html += '</div>';
            resultDiv.innerHTML = html;
        }
        
        // Compare multiple currencies
        async function compareMultiple() {
            const base = document.getElementById('comparison-base').value;
            const amount = parseFloat(document.getElementById('comparison-amount').value) || 1;
            const resultDiv = document.getElementById('comparison-result');
            
            if (!base) {
                resultDiv.innerHTML = '<div class="error">Please select a base currency</div>';
                return;
            }
            
            resultDiv.innerHTML = '<div class="loading">Loading comparison...</div>';
            
            try {
                const response = await fetch(`?action=latest_rates&base=${base}`);
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
            
            // Get top currencies to compare
            const popularCodes = ['EUR', 'GBP', 'JPY', 'AUD', 'CAD', 'CHF', 'CNY', 'SEK'];
            const availableRates = [];
            
            popularCodes.forEach(code => {
                if (rates[code.toLowerCase()]) {
                    availableRates.push([code, rates[code.toLowerCase()]]);
                }
            });
            
            // Sort by converted amount (descending)
            availableRates.sort((a, b) => b[1] * amount - a[1] * amount);
            
            availableRates.forEach(([currency, rate]) => {
                const converted = amount * rate;
                const currencyInfo = allCurrencies[currency];
                
                html += `
                    <div class="currency-item">
                        <div class="currency-info">
                            <div class="currency-code">
                                ${currencyInfo?.flag || '🌍'} ${currency}
                            </div>
                            <div class="currency-name">
                                ${currencyInfo?.name || currency}
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
                        <strong>You save $${difference.toFixed(2)} by using ${cheaper.toLowerCase()}</strong>
                    </div>
                </div>
            `;
        }
        
        // Utility functions
        function formatCurrency(amount, currency) {
            const currencyInfo = allCurrencies[currency];
            const symbol = currencyInfo?.symbol || currency;
            
            if (['JPY', 'KRW', 'IDR', 'VND'].includes(currency)) {
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
        
        // Auto-refresh every 10 minutes
        setInterval(() => {
            const activeBase = document.querySelector('.currency-button.active');
            if (activeBase) {
                const currency = activeBase.textContent.trim().split('\n')[1];
                if (currency) loadPopularRates(currency);
            }
            updateLastUpdate();
        }, 600000);
    </script>
</body>
</html>
