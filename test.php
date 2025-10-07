<?php
$host = "turntable.proxy.rlwy.net";
$port = 33979;
$db   = "railway";
$user = "root";
$pass = "VSSsxWwulospGmcMpAZxvbAfzKDoyLpJ";

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Connected successfully!<br>";

$result = $conn->query("SHOW TABLES");
if ($result) {
    echo "Tables in database:<br>";
    while ($row = $result->fetch_array()) {
        echo $row[0] . "<br>";
    }
} else {
    echo "Error fetching tables: " . $conn->error;
}

$conn->close();
?>
