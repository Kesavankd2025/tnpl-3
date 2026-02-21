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

function envOrDefault(string $key, string $default): string {
    $value = getenv($key);
    if ($value === false || $value === '') {
        return $default;
    }
    return $value;
}

function logMailerError(string $message): void {
    $logLine = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
    @file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . 'mail_error.log', $logLine, FILE_APPEND);
}

// Check if request is POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Accept JSON, x-www-form-urlencoded and multipart payloads.
    $rawBody = file_get_contents('php://input');
    $input = json_decode($rawBody, true);
    if (!is_array($input)) {
        $input = $_POST;
    }
    if (!is_array($input) || count($input) === 0) {
        $parsedBody = [];
        parse_str($rawBody, $parsedBody);
        if (is_array($parsedBody) && count($parsedBody) > 0) {
            $input = $parsedBody;
        }
    }
    if (!is_array($input)) {
        logMailerError("Invalid payload. Raw body: " . substr((string)$rawBody, 0, 500));
        echo json_encode(["status" => "error", "message" => "Invalid request payload."]);
        exit;
    }

    $name = trim($input['name'] ?? '');
    $email = trim($input['email'] ?? '');
    $mobile = trim($input['phone'] ?? '');
    $message = trim($input['message'] ?? '');
    $requirements = trim($input['suggestion'] ?? $message);
    $product = trim($input['product'] ?? '');
    $source = trim($input['source'] ?? 'website-enquiry');
    $page = trim($input['page'] ?? '');

    if ($name === '' || ($email === '' && $mobile === '')) {
        echo json_encode(["status" => "error", "message" => "Name and either phone or email are required."]);
        exit;
    }

    $mail = new PHPMailer(true);

    try {
        $smtpHost = envOrDefault('ARUNA_SMTP_HOST', 'smtp.gmail.com');
        $smtpPort = (int) envOrDefault('ARUNA_SMTP_PORT', '587');
        $smtpUser = envOrDefault('ARUNA_SMTP_USER', 'enquiry.arunaandco@gmail.com');
        $smtpPass = envOrDefault('ARUNA_SMTP_PASS', 'xdbl choj mccg bqcj');
        $smtpFrom = envOrDefault('ARUNA_SMTP_FROM', $smtpUser);
        $smtpTo = envOrDefault('ARUNA_SMTP_TO', 'enquiry.arunaandco@gmail.com');

        $mail->CharSet    = 'UTF-8';
        $mail->Timeout    = 20;
        $mail->isHTML(true);
        $mail->Subject = 'New Enquiry - Aruna & Co. (' . $source . ')';
        
        $mailBody = "
            <h3>New Website Enquiry Received</h3>
            <p><strong>Name:</strong> $name</p>
            <p><strong>Email:</strong> $email</p>
            <p><strong>Mobile:</strong> $mobile</p>
            <p><strong>Product:</strong> $product</p>
            <p><strong>Requirements:</strong> $requirements</p>
            <p><strong>Source:</strong> $source</p>
            <p><strong>Page:</strong> $page</p>
        ";
        
        $mail->Body = $mailBody;
        $mail->AltBody = strip_tags($mailBody);

        $sendAttempt = function ($security, $port) use ($mail, $smtpHost, $smtpUser, $smtpPass, $smtpFrom, $smtpTo, $email, $name) {
            // Reset recipients/replyTo between retries.
            $mail->clearAllRecipients();
            $mail->clearReplyTos();

            $mail->isSMTP();
            $mail->Host = $smtpHost;
            $mail->SMTPAuth = true;
            $mail->Username = $smtpUser;
            $mail->Password = $smtpPass;
            $mail->SMTPSecure = $security;
            $mail->Port = $port;
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ],
            ];

            $mail->setFrom($smtpFrom, 'Aruna & Co. Website');
            $mail->addAddress($smtpTo);
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $mail->addReplyTo($email, $name);
            }

            return $mail->send();
        };

        $sent = false;
        $attemptErrors = [];

        // Attempt 1: STARTTLS (587) unless overridden via env.
        try {
            $sent = $sendAttempt(PHPMailer::ENCRYPTION_STARTTLS, $smtpPort);
        } catch (Exception $eTls) {
            $attemptErrors[] = 'TLS:' . $mail->ErrorInfo;
        }

        // Attempt 2: SMTPS (465) fallback.
        if (!$sent) {
            try {
                $sent = $sendAttempt(PHPMailer::ENCRYPTION_SMTPS, 465);
            } catch (Exception $eSsl) {
                $attemptErrors[] = 'SSL:' . $mail->ErrorInfo;
            }
        }

        if ($sent) {
            echo json_encode(["status" => "success", "message" => "Message has been sent successfully."]);
        } else {
            $joinedErrors = implode(' | ', $attemptErrors);
            logMailerError("Mailer send failed. {$joinedErrors} | source={$source} | page={$page} | name={$name}");
            echo json_encode(["status" => "error", "message" => "Message could not be sent. Please verify SMTP settings/app password."]);
        }
    } catch (Exception $e) {
        $errorText = "Mailer Error: " . ($mail->ErrorInfo ?: $e->getMessage());
        logMailerError($errorText . " | source=" . $source . " | page=" . $page . " | name=" . $name);
        echo json_encode(["status" => "error", "message" => "Message could not be sent. Please check SMTP credentials or app password."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid Request Method"]);
}
?>
