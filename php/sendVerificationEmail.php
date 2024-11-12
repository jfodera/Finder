<?php
require_once '../vendor/autoload.php';
require_once '../db/db_connect.php';

function sendFoundNotification($email, $itemDetails) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();

    $transport = (new Swift_SmtpTransport($_ENV['SMTP_HOST'], $_ENV['SMTP_PORT'], 'tls'))
        ->setUsername($_ENV['SMTP_USER'])
        ->setPassword($_ENV['SMTP_PASS']);

    $mailer = new Swift_Mailer($transport);

    $message = (new Swift_Message('Your Lost Item Has Been Found'))
        ->setFrom([$_ENV['SMTP_USER'] => 'Finder'])
        ->setTo([$email])
        ->setBody(
            '<html>' .
            '<body>' .
            '<h1>Good News!</h1>' .
            '<p>Your lost item has been found. Here are the details:</p>' .
            '<p>' . htmlspecialchars($itemDetails) . '</p>' .
            '</body>' .
            '</html>',
            'text/html'
        );

    try {
        $result = $mailer->send($message);
        return true;
    } catch (Exception $e) {
        error_log("Failed to send found notification email: " . $e->getMessage());
        return false;
    }
}
?>