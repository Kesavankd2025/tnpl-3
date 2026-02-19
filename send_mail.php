<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// If you are using Composer (Recommended)
require 'vendor/autoload.php';

// CORS Headers
header("Access-Control-Allow-Origin: *"); // Allow all origins (for development)
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header('Content-Type: application/json');

// Check if request is POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Get raw POST data (since we'll send JSON from JS)
    $input = json_decode(file_get_contents('php://input'), true);

    $name = $input['name'] ?? '';
    // $company = $input['company'] ?? ''; // Not in current form
    // $segment = $input['segments'] ?? ''; // Not in current form
    $email = $input['email'] ?? '';
    $mobile = $input['phone'] ?? ''; // Mapped from 'phone' in JS payload
    // $city = $input['city'] ?? ''; // Not in current form
    $requirements = $input['suggestion'] ?? ''; // Mapped from 'suggestion' in JS payload
    $product = $input['product'] ?? '';

    if (empty($name) || empty($email)) {
        echo json_encode(["status" => "error", "message" => "Name and Email are required."]);
        exit;
    }

    $mail = new PHPMailer(true);

    try {
        //Server settings
        // $mail->SMTPDebug = 2;                      //Enable verbose debug output
        $mail->isSMTP();                                            //Send using SMTP
        $mail->Host       = 'smtp.gmail.com';                       //Set the SMTP server to send through
        $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
        $mail->Username   = 'enquiry.arunaandco@gmail.com';               //SMTP username 
        $mail->Password   = 'xdblchojmccgbqcj';               //SMTP password (Use App Password, not Gmail password)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         //Enable TLS encryption
        $mail->Port       = 587;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

        //Recipients
        $mail->setFrom('enquiry.arunaandco@gmail.com', 'Aruna & Co. Website');
        $mail->addAddress('enquiry.arunaandco@gmail.com');    //Add a recipient

        //Content
        $mail->isHTML(true);                                  //Set email format to HTML
        $mail->Subject = 'New Product Download Request - Aruna & Co.';
        
        $mailBody = "
            <h3>New Booking Request Recieved</h3>
            <p><strong>Name:</strong> $name</p>
            <p><strong>Email:</strong> $email</p>
            <p><strong>Mobile:</strong> $mobile</p>
            <p><strong>Product:</strong> $product</p>
            <p><strong>Requirements:</strong> $requirements</p>
        ";
        
        $mail->Body    = $mailBody;
        $mail->AltBody = strip_tags($mailBody);

        $mail->send();
        echo json_encode(["status" => "success", "message" => "Message has been sent successfully."]);
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => "Message could not be sent. Mailer Error: {$mail->ErrorInfo}"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid Request Method"]);
}
?>
