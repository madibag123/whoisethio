<?php

require_once 'db.php';

function backupTable($table) {
    global $pdo;

    try {
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES,true);
        $stmt = $pdo->prepare("SELECT * FROM $table");
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $file = fopen("backup_$table.csv", "w");

        if ($file === false) {
            die("Error creating backup file.");
        }

        $header = false;
        foreach ($data as $row) {
            if (!$header) {
                fputcsv($file, array_keys($row));
                $header = true;
            }
            fputcsv($file, $row);
        }

        fclose($file);

        echo "Backup created successfully.";
    } catch (PDOException $e) {
        die("Error backing up table: " . $e->getMessage());
    }
}

function whoisDb() {
    global $pdo;

    $raw_data = file_get_contents('clean_tlds.json');

    $tlds = json_decode($raw_data, true);
    
    try {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $whoisExists = $pdo->query("SHOW TABLES LIKE 'whois'")->rowCount();

        $forced = isset($_GET['force']) ? $_GET['force'] : false;
        if ($whoisExists > 0 && !$forced) {
            die("Table already exists.");
        }
        $stmt = "CREATE TABLE IF NOT EXISTS whois (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tld VARCHAR(30) NOT NULL,
            whoisServer VARCHAR(100) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        backupTable('whois');
        $pdo->exec("DROP TABLE IF EXISTS whois");
        $pdo->exec($stmt);
    
        $pdo->beginTransaction();
        foreach ($tlds as $tld => $server) {
            $stmt = $pdo->prepare("INSERT INTO whois (tld, whoisServer) VALUES (?, ?)");
            $stmt->execute([$tld, $server]);
        }
        $pdo->commit();
    
        echo "Table created successfully.";
    } catch (PDOException $e) {
        die("Error creating table: " . $e->getMessage());
    }

}

function requestLogDb() {
    global $pdo;
    try {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $requestLogExists = $pdo->query("SHOW TABLES LIKE 'request_log'")->rowCount();

        $forced = isset($_GET['force']) ? $_GET['force'] : false;
        if ($requestLogExists > 0 && !$forced) {
            die("Table already exists.");
        }
        backupTable('request_log');
        $pdo->exec("DROP TABLE IF EXISTS request_log");
        $stmt = "CREATE TABLE IF NOT EXISTS request_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip VARCHAR(30) NOT NULL,
            domain VARCHAR(255) NOT NULL,
            tld VARCHAR(30) NOT NULL,
            whoisServer VARCHAR(100) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $pdo->exec($stmt);
    
        echo "Table created successfully.";
    } catch (PDOException $e) {
        die("Error creating table: " . $e->getMessage());
    }
}

if (isset($_GET['whois'])) {
    whoisDb();
}
if (isset($_GET['request_log'])) {
    requestLogDb();
}


?>
