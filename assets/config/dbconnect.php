<?php
// ✅ Load environment variables from .env file
$env_path = __DIR__ . '/.env';

if (file_exists($env_path)) {
    $lines = file($env_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0 || !strpos($line, '=')) continue;
        putenv(trim($line));
    }
}

// ✅ Get DB credentials from environment
$host = getenv('DB_HOST');
$port = getenv('DB_PORT');
$dbname = getenv('DB_NAME');
$user = getenv('DB_USER');
$password = getenv('DB_PASS');

// ✅ PostgreSQL connection
try {
    $conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");

    if (!$conn) {
        throw new Exception("❌ Failed to connect to the database.");
    } else {
        //echo "✅ Connection successful!";
    }
} catch (Exception $e) {
    echo "Database Error: " . $e->getMessage();
}
?>
