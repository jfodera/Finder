<?php 
include 'header.php'; 
require_once '../vendor/autoload.php';

$statusMsg = '';
$statusClass = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();

    try {
        $transport = (new Swift_SmtpTransport($_ENV['SMTP_HOST'], $_ENV['SMTP_PORT'], 'tls'))
            ->setUsername($_ENV['SMTP_USER'])
            ->setPassword($_ENV['SMTP_PASS']);

        $mailer = new Swift_Mailer($transport);

        $name = $_POST['full_name'];
        $emailFrom = $_POST['email'];
        $userMessage = $_POST['message'];

        $message = (new Swift_Message('New Contact Form Message'))
            ->setFrom([$_ENV['SMTP_USER'] => 'Finder Contact Form'])
            ->setReplyTo([$emailFrom => $name])
            ->setTo([$_ENV['SMTP_USER']])
            ->setBody(
                '<html>' .
                '<body>' .
                '<h2>New Contact Form Message</h2>' .
                '<p><strong>From:</strong> ' . htmlspecialchars($name) . '</p>' .
                '<p><strong>Email:</strong> ' . htmlspecialchars($emailFrom) . '</p>' .
                '<p><strong>Message:</strong></p>' .
                '<p>' . htmlspecialchars($userMessage) . '</p>' .
                '</body>' .
                '</html>',
                'text/html'
            );

        $result = $mailer->send($message);
        if ($result) {
            $statusMsg = "Thank you! Your message has been sent successfully.";
            $statusClass = "success";
        }

    } catch (Exception $e) {
        error_log("Failed to send contact form email: " . $e->getMessage());
        $statusMsg = "Sorry, there was an error sending your message. Please try again.";
        $statusClass = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Finder - Contact</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <div class="contact">
        <div class="content">
            <h2>Contact Us</h2>
        </div>
        
        <?php if ($statusMsg): ?>
            <div class="alert <?php echo $statusClass; ?>">
                <?php echo $statusMsg; ?>
            </div>
        <?php endif; ?>

        <div class="contact_container">
            <div class="contactInfo">
                <div class="box">
                    <div class="icon"><i class="fa-solid fa-location-pin"></i></div>
                    <div class="text">
                        <h3>Address</h3>
                        <p>15th Street & Sage Avenue,<br>
                            Troy, New York<br> 12180, US</p>
                    </div>
                </div>

                <div class="box">
                    <div class="icon"><i class="fa-solid fa-phone"></i></div>
                    <div class="text">
                        <h3>Phone</h3>
                        <p>518-276-6656</p>
                    </div>
                </div>

                <div class="box">
                    <div class="icon"><i class="fa-solid fa-envelope"></i></div>
                    <div class="text">
                        <h3>Email</h3>
                        <p>finderitws@gmail.com</p>
                    </div>
                </div>
            </div>
            
            <div class="contactForm">
                <form method="POST">
                    <h2>Send Message</h2>
                    <div class="inputBox">
                        <input type="text" name="full_name" required="required">
                        <span>Full Name</span>
                    </div>

                    <div class="inputBox">
                        <input type="email" name="email" required="required">
                        <span>Email</span>
                    </div>

                    <div class="inputBox">
                        <textarea name="message" required="required"></textarea>
                        <span>Type your message...</span>
                    </div>

                    <div class="inputBox">
                        <input type="submit" value="Send Message">
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php include 'background-under.php'; ?>
</body>
</html>
