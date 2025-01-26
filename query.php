<?php

require 'utils.php';

// Enable query buffering


header('Content-Type: application/json');



// Function to parse WHOIS data
function parseWhoisData($whoisText) {
    $pattern = '/^([\w\s]+):\s*(.*)$/m';
    $parsedData = [];

    if (preg_match_all($pattern, $whoisText, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $key = trim(str_replace(' ', '_', $match[1]));
            $value = trim($match[2]);

            // Handle multi-valued fields
            if (isset($parsedData[$key])) {
                if (!is_array($parsedData[$key])) {
                    $parsedData[$key] = [$parsedData[$key]];
                }
                $parsedData[$key][] = $value;
            } else {
                $parsedData[$key] = $value;
            }
        }
    }

    return json_encode($parsedData, JSON_PRETTY_PRINT);
}

function whoisQuery($domain, $server = null, $key = null) {
    $whoisServers = [
        "et" => "whois.ethiotelecom.et",
        "default" => "whois.iana.org"
    ];


    // Determine WHOIS server
    if ($server) {
        $whoisServer = $server;
    } elseif ($key) {
        $whoisServer = get_server_by_tld($key) ?? $whoisServers['default'];
    } else {
        $tld = strtolower(pathinfo($domain, PATHINFO_EXTENSION));

        if ($tld === 'et') {
            $whoisServer = $whoisServers['et'];
        } else {
            $bytld = get_server_by_tld($tld);

            $whoisServer = $bytld ?? $whoisServers['default'];
        }
    }

    // Connect to WHOIS server
    try {
        $socket = @fsockopen($whoisServer, 43, $errno, $errstr, 10);
        if (!$socket) {
            if(!$server) {
                return whoisQuery($domain, $whoisServers['default']);
            }
            echo json_encode([
                "error" => "WHOIS Error",
                "message" => "Unable to connect to WHOIS server: $errstr ($errno)"
            ]);
            exit;
        }

        // Send domain query
        fwrite($socket, $domain . "\r\n");

        // Read response
        $response = "";
        while (!feof($socket)) {
            $response .= fgets($socket, 4096);
        }
        fclose($socket);

        if($response == null) {
            echo json_encode([
                "error" => "WHOIS Error",
                "message" => "No response from WHOIS server."
            ]);
            exit;
        }

        return [$response, $whoisServer];

    } catch (Exception $e) {
        echo json_encode([
            "error" => "WHOIS Error",
            "message" => $e->getMessage()
        ]);
        exit;
    }
}

// Restrict access based on GeoPlugin
$geoPluginData = unserialize(file_get_contents('http://www.geoplugin.net/php.gp?ip=' . $_SERVER['REMOTE_ADDR']));
$allowedCountry = 'ET'; // Ethiopia
$allowedIPs = ['192.168.1.100', '203.0.113.5', '::1'];

if($geoPluginData === false) {
    echo json_encode([
        "error" => "GeoPlugin Error",
        "message" => "Could not fetch GeoPlugin data."
    ]);
    exit;
}

// Check country and IP
if ($geoPluginData['geoplugin_countryCode'] !== $allowedCountry && !in_array($_SERVER['REMOTE_ADDR'], $allowedIPs) && !isset($_GET['secret_key']) ) {
    
    echo json_encode([
        "error" => "Access Denied",
        "message" => "Your location (" . $geoPluginData['geoplugin_countryName'] . ") or IP (" . $_SERVER['REMOTE_ADDR'] . ") is not authorized to access this resource."
    ]);
    exit;
}
if (isset($_GET['secret_key']) ) {
    $key = $_GET['secret_key'];
    if ($key !== '9006619417107927') {
        echo json_encode([
            "error" => "Access Denied",
            "message" => "Invalid secret key."
        ]);
        exit;
    }
}

// log request


function validateDomain($domain) {
    if(!filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)){
        return false;
    }
    return true;
}

// Fetch the domain from the query string
if (isset($_GET['domain'])) {
    
    $domain = htmlspecialchars($_GET['domain']); // Sanitize input
    if (!validateDomain($domain)) {
        echo json_encode([
            "error" => "Invalid Domain",
            "message" => "Please provide a valid domain name."
        ]);
        exit;
    }
    list($result, $whoisServer) = whoisQuery($domain);
    $parsedData = parseWhoisData($result);

    $ip = $_SERVER['REMOTE_ADDR'];
    $domain = $_GET['domain'];
    $tld = pathinfo($domain, PATHINFO_EXTENSION);
    logRequest($ip, $domain, $tld, $whoisServer);
   
    echo $parsedData;
} else {
    echo json_encode([
        "error" => "Missing Domain",
        "message" => "Please provide a domain name in the query string."
    ]);
}
?>
