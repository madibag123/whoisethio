<?php
require 'db.php';



function get_server_by_tld($tld) {
    global $pdo;

    try {
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES,true);
        $stmt = $pdo->prepare("SELECT whoisServer FROM whois WHERE tld = ?");
        $stmt->execute([$tld]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt->closeCursor();

        return $row['whoisServer'] ?? null;
    } catch (PDOException $e) {
        echo json_encode([
            "error" => "Database Error",
            "message" => $e->getMessage()
        ]);
        return null;
    }
}

function parseWhoisDataNew($data) {
    $parsedData = [];
    $lines = explode("\n", $data);

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === "") {
            continue;
        }

        $parts = explode(":", $line, 2);
        $key = trim($parts[0]);
        $value = trim($parts[1] ?? "");

        if (array_key_exists($key, $parsedData)) {
            if (!is_array($parsedData[$key])) {
                $parsedData[$key] = [$parsedData[$key]];
            }
            $parsedData[$key][] = $value;
        } else {
            $parsedData[$key] = $value;
        }
    }

    return json_encode($parsedData, JSON_PRETTY_PRINT);
}

function logRequest($ip, $domain, $tld, $whoisServer) {
    global $pdo;

    try {
        
        $requestLogExists = $pdo->query("SHOW TABLES LIKE 'request_log'")->rowCount();
        if ($requestLogExists < 0) {
            die("Table do not exist.");
        }
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES,true);
        $stmt = $pdo->prepare("INSERT INTO request_log (ip, domain, tld, whoisServer) VALUES (?, ?, ?, ?)");
        $stmt->execute([$ip, $domain, $tld, $whoisServer]);
        $stmt->closeCursor();
    } catch (PDOException $e) {
        echo json_encode([
            "error" => "Database Error",
            "message" => $e->getMessage()
        ]);
    }
}

?>