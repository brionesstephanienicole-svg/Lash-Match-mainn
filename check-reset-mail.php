<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__.'/reset-mailer.php';
try {
    $config = resetMailConfiguration();
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $config['host'];
    $mail->Port = $config['port'];
    $mail->SMTPAuth = $config['username'] !== '';
    $mail->Username = $config['username'];
    $mail->Password = $config['password'];
    $mail->SMTPSecure = $config['encryption'] === 'none' ? '' : $config['encryption'];
    $mail->SMTPAutoTLS = $config['encryption'] !== 'none';
    $mail->Timeout = 15;
    $mail->SMTPDebug = 0;
    if (!$mail->smtpConnect()) throw new RuntimeException('SMTP connection failed.');
    $mail->smtpClose();
    echo "SMTP connection and authentication passed. No email was sent.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "SMTP check failed. Check the local SMTP credentials, PHP TLS certificates, and connectivity.\n");
    exit(1);
}
