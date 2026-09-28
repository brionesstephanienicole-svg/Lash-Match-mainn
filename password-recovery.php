<?php
declare(strict_types=1);
if(!function_exists('respond')){http_response_code(404);exit;}
$db=database();
if($action==='request_password_reset') {
 $email=strtolower(field($data,'email'));
 if(!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($email)>254)respond(['error'=>'Enter a valid email address.'],422);
 require_once __DIR__.'/reset-mailer.php';
 try{$config=resetMailConfiguration();}catch(Throwable $e){respond(['error'=>'Password reset email is not available yet. Please contact the administrator.'],503);}
 $ip=hash('sha256',$_SERVER['REMOTE_ADDR']??'unknown');$emailHash=hash('sha256',$email);
 $db->exec('DELETE FROM password_reset_requests WHERE created_at<DATE_SUB(NOW(),INTERVAL 1 DAY)');
 $db->exec('DELETE FROM password_reset_tokens WHERE expires_at<=NOW()');
 $q=$db->prepare('SELECT COUNT(*) FROM password_reset_requests WHERE ip_hash=? AND created_at>DATE_SUB(NOW(),INTERVAL 15 MINUTE)');$q->execute([$ip]);
 if((int)$q->fetchColumn()>=10)respond(['error'=>'Too many reset requests. Please try again in 15 minutes.'],429);
 $q=$db->prepare('SELECT COUNT(*) FROM password_reset_requests WHERE email_hash=? AND created_at>DATE_SUB(NOW(),INTERVAL 1 HOUR)');$q->execute([$emailHash]);$limited=(int)$q->fetchColumn()>=3;
 if($limited)respond(['error'=>'Too many codes requested. Please try again in one hour.'],429);
 $q=$db->prepare('SELECT GREATEST(0,60-TIMESTAMPDIFF(SECOND,MAX(created_at),NOW())) FROM password_reset_requests WHERE email_hash=?');$q->execute([$emailHash]);$retry=(int)$q->fetchColumn();
 if($retry>0)respond(['error'=>'Please wait before requesting another code.','retry_after'=>$retry],429);
 $message=['message'=>'A one-time PIN has been sent. Check your inbox and spam folder. The PIN expires in 10 minutes.','retry_after'=>60];
 $q=$db->prepare('SELECT id,email,password_version FROM users WHERE email=? AND is_active=1');$q->execute([$email]);$user=$q->fetch();
 if(!$user){
  $db->prepare('INSERT INTO password_reset_requests(ip_hash,email_hash) VALUES(?,?)')->execute([$ip,$emailHash]);
  respond(['error'=>'No active account exists with that email. Please sign up first.'],422);
 }
 if($user){
  $pin=str_pad((string)random_int(0,999999),6,'0',STR_PAD_LEFT);
  $hash=hash('sha256',bin2hex(random_bytes(32)));
  $codeHash=password_hash($pin,PASSWORD_DEFAULT);
  $db->beginTransaction();
  try{
   $q=$db->prepare('SELECT id FROM users WHERE id=? FOR UPDATE');$q->execute([$user['id']]);
   $q=$db->prepare('SELECT GREATEST(0,60-TIMESTAMPDIFF(SECOND,MAX(created_at),NOW())) FROM password_reset_requests WHERE email_hash=?');$q->execute([$emailHash]);$retry=(int)$q->fetchColumn();
   if($retry>0){$db->rollBack();respond(['error'=>'Please wait before requesting another code.','retry_after'=>$retry],429);}
   $db->prepare('INSERT INTO password_reset_requests(ip_hash,email_hash) VALUES(?,?)')->execute([$ip,$emailHash]);
   $db->prepare('DELETE FROM password_reset_tokens WHERE user_id=?')->execute([$user['id']]);
   $db->prepare('INSERT INTO password_reset_tokens(token_hash,user_id,password_version,code_hash,expires_at) VALUES(?,?,?,?,DATE_ADD(NOW(),INTERVAL 10 MINUTE))')->execute([$hash,$user['id'],$user['password_version'],$codeHash]);
   $db->commit();
  }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
  try{sendResetPin($config,$user['email'],$pin);}
  catch(Throwable $e){
   $db->prepare('DELETE FROM password_reset_tokens WHERE token_hash=?')->execute([$hash]);
   error_log('LashMatch password reset: SMTP delivery failed. Check SMTP configuration and connectivity.');
   respond(['error'=>'Unable to send the PIN. Please try again in one minute.','retry_after'=>60],503);
  }
 }
 respond($message);
}
$token=field($data,'token');
$invalidLink=['error'=>'This reset link is invalid, expired, or already used. Request a new link.'];
$lookup='SELECT t.user_id FROM password_reset_tokens t JOIN users u ON u.id=t.user_id WHERE t.token_hash=? AND t.code_hash IS NULL AND t.expires_at>NOW() AND u.is_active=1 AND t.password_version=u.password_version';
if($action==='validate_password_reset'){
 if(!preg_match('/^[a-f0-9]{64}$/D',$token))respond($invalidLink,422);
 $q=$db->prepare($lookup);$q->execute([hash('sha256',$token)]);if(!$q->fetch())respond($invalidLink,422);
 respond(['valid'=>true]);
}
if($action==='verify_password_reset_pin'){
 $email=strtolower(field($data,'email'));$pin=field($data,'pin');
 $invalid=['error'=>'The PIN is invalid, expired, or already used. Request a new PIN.'];
 if(!filter_var($email,FILTER_VALIDATE_EMAIL)||!preg_match('/^[0-9]{6}$/D',$pin))respond($invalid,422);
 $db->beginTransaction();
 try{
  $q=$db->prepare('SELECT id,password_version FROM users WHERE email=? AND is_active=1 FOR UPDATE');$q->execute([$email]);$user=$q->fetch();$entry=false;
  if($user){
   $q=$db->prepare('SELECT token_hash,code_hash,attempts FROM password_reset_tokens WHERE user_id=? AND password_version=? AND code_hash IS NOT NULL AND expires_at>NOW() ORDER BY created_at DESC LIMIT 1 FOR UPDATE');$q->execute([$user['id'],$user['password_version']]);$entry=$q->fetch();
  }
  if(!$entry||(int)$entry['attempts']>=5||!password_verify($pin,$entry['code_hash'])){
   if($entry&&(int)$entry['attempts']<5)$db->prepare('UPDATE password_reset_tokens SET attempts=attempts+1 WHERE token_hash=?')->execute([$entry['token_hash']]);
   $db->commit();respond($invalid,422);
  }
  $verifiedToken=bin2hex(random_bytes(32));
  $db->prepare('UPDATE password_reset_tokens SET token_hash=?,code_hash=NULL WHERE token_hash=?')->execute([hash('sha256',$verifiedToken),$entry['token_hash']]);
  $db->commit();respond(['token'=>$verifiedToken,'message'=>'PIN verified. Create your new password.']);
 }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
}
if($token==='')respond(['error'=>'Verify your one-time PIN before creating a new password.'],422);
$password=$data['password']??null;$confirmation=$data['password_confirmation']??null;
if(!is_string($password)||strlen($password)<12||strlen($password)>72)respond(['error'=>'Use a password between 12 and 72 bytes.'],422);
if(!is_string($confirmation)||!hash_equals($password,$confirmation))respond(['error'=>'The passwords do not match.'],422);
$newHash=password_hash($password,PASSWORD_DEFAULT);
if($token!==''){
 if(!preg_match('/^[a-f0-9]{64}$/D',$token))respond($invalidLink,422);
 $hash=hash('sha256',$token);
 try{
  $db->beginTransaction();
  $q=$db->prepare($lookup.' FOR UPDATE');$q->execute([$hash]);$row=$q->fetch();
  if(!$row){$db->rollBack();respond($invalidLink,422);}
  $db->prepare('UPDATE users SET password_hash=?,password_version=password_version+1 WHERE id=?')->execute([$newHash,$row['user_id']]);
  $db->prepare('DELETE FROM password_reset_tokens WHERE user_id=?')->execute([$row['user_id']]);
  $db->commit();
 }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
}

unset($_SESSION['user'],$_SESSION['password_version']);session_regenerate_id(true);$_SESSION['csrf']=bin2hex(random_bytes(32));
respond(['success'=>true,'message'=>'Your password has been changed. Sign in with your new password.']);
