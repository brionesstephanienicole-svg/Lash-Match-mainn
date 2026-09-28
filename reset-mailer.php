<?php
declare(strict_types=1);
require_once __DIR__.'/vendor/autoload.php';
function resetMailConfiguration(): array {
 $c=require __DIR__.'/mail-config.php';
 $local=in_array($c['host'],['127.0.0.1','localhost','::1'],true);
 if(!$c['host']||!filter_var($c['from_email'],FILTER_VALIDATE_EMAIL)||$c['port']<1||$c['port']>65535||
    !in_array($c['encryption'],['tls','ssl','none'],true)||(!$local&&($c['encryption']==='none'||!$c['username']||!$c['password']))) {
  throw new RuntimeException('Password reset email is not configured.');
 }
 return $c;
}
function sendResetPin(array $config,string $email,string $pin): void {
 $mail=new PHPMailer\PHPMailer\PHPMailer(true);
 $mail->isSMTP();$mail->Host=$config['host'];$mail->Port=$config['port'];
 $mail->SMTPAuth=$config['username']!=='';$mail->Username=$config['username'];$mail->Password=$config['password'];
 $mail->SMTPSecure=$config['encryption']==='none'?'':$config['encryption'];
 $mail->SMTPAutoTLS=$config['encryption']!=='none';$mail->Timeout=15;
 $mail->CharSet='UTF-8';$mail->Encoding='8bit';$mail->SMTPDebug=0;
 $mail->setFrom($config['from_email'],$config['from_name']);$mail->addAddress($email);
 $mail->Subject='Your LashMatch password reset PIN';
 $mail->Body="You requested a password reset for your LashMatch account.\r\n\r\nYour one-time PIN is: ".$pin."\r\n\r\nThe PIN expires in 10 minutes and can be used once. Your password stays unchanged until you submit a new one.\r\n\r\nIf you did not request this, ignore this email.\r\n\r\nLashMatch";
 $mail->send();
}
