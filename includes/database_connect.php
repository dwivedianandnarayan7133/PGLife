<?php
$conn = @mysqli_connect("127.0.0.1", "root", "", "pglife", 3307);

if (!$conn) {
    $conn = @mysqli_connect("127.0.0.1", "root", "", "pglife");
}

if (!$conn) {
    $conn = @mysqli_connect("127.0.0.1", "root", "Sachin01!", "pglife");
}

if (!$conn) {
    $conn = @mysqli_connect("127.0.0.1", "root", "password", "pglife");
}

if (!$conn) {
    echo "Failed to connect to MySQL! Please contact the admin.";
    return;
}

