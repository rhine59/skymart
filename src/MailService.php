<?php
declare(strict_types=1);

final class MailService {
 public function sendPasswordReset(string $email,string $token): void {
  $base=rtrim((string)getenv('SKYMART_PUBLIC_URL'),'/');
  $from=(string)getenv('SKYMART_MAIL_FROM');
  if($base===''||$from===''){error_log('SkyMart mail not configured; password reset email not sent.');return;}
  $url=$base.'/reset-password.php?token='.rawurlencode($token);
  $subject='Reset your SkyMart password';
  $body="A password reset was requested for your SkyMart account.\n\nReset it here:\n".$url."\n\nThis link expires in 30 minutes. If you did not request this, ignore this message.\n";
  $headers=['From: '.$from,'Content-Type: text/plain; charset=UTF-8'];
  if(!mail($email,$subject,$body,implode("\r\n",$headers))) error_log('SkyMart password reset mail delivery failed.');
 }
}
