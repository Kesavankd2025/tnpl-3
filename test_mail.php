<?php
$_SERVER["REQUEST_METHOD"] = "POST";
$payload = [
    "name" => "Test",
    "email" => "test@example.com",
    "phone" => "1234567890",
    "message" => "Hello, this is a test"
];
file_put_contents("php://input", json_encode($payload));
ob_start();
include "send_mail.php";
$output = ob_get_clean();
echo "OUTPUT: " . $output;
