<?php
$host = 'localhost';
$port = 21;
$user = 'Administrador';
$pass = 'cosita';

echo "Conectando a $host:$port...\n";
$timeout = 10;
$conn = @ftp_connect($host, $port, $timeout);
if (! $conn) {
    echo "ftp_connect failed\n";
    exit(1);
}

echo "ftp_connect OK\n";
$login = @ftp_login($conn, $user, $pass);
if (! $login) {
    echo "ftp_login failed (check username/password)\n";
    ftp_close($conn);
    exit(2);
}

echo "ftp_login OK\n";

// list root
$files = @ftp_nlist($conn, '.');
if ($files === false) {
    echo "ftp_nlist failed or empty\n";
} else {
    echo "Root listing (" . count($files) . "):\n";
    foreach ($files as $f) echo " - $f\n";
}

ftp_close($conn);
