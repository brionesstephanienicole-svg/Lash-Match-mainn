<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require dirname(__DIR__).'/db.php';
$db=database();$id=null;$cookie=tempnam(sys_get_temp_dir(),'reset-check-');$csrf='';
$email='reset-check-'.bin2hex(random_bytes(8)).'@example.com';
$password=bin2hex(random_bytes(16));
function api(string $action,?array $body=null): array {
 global $cookie,$csrf;
 $ch=curl_init((getenv('LASHMATCH_TEST_URL') ?: 'http://localhost:8080').'/api.php?action='.$action);
 curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_COOKIEFILE=>$cookie,CURLOPT_COOKIEJAR=>$cookie,CURLOPT_TIMEOUT=>20]);
 if($body!==null)curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>json_encode($body),CURLOPT_HTTPHEADER=>['Content-Type: application/json','X-CSRF-Token: '.$csrf]]);
 $raw=curl_exec($ch);if($raw===false)throw new RuntimeException(curl_error($ch));
 $status=curl_getinfo($ch,CURLINFO_RESPONSE_CODE);curl_close($ch);
 $data=json_decode($raw,true,512,JSON_THROW_ON_ERROR);$csrf=$data['csrf']??$csrf;return [$status,$data];
}
function verify(bool $ok,string $label): void {if(!$ok)throw new RuntimeException($label);echo "PASS $label\n";}
try {
 $db->prepare('INSERT INTO users(name,email,password_hash) VALUES(?,?,?)')->execute(['Reset test',$email,password_hash(bin2hex(random_bytes(16)),PASSWORD_DEFAULT)]);
 $id=(int)$db->lastInsertId();
 $db->prepare('INSERT INTO password_reset_tokens(token_hash,user_id,password_version,code_hash,expires_at) VALUES(?,?,0,?,DATE_ADD(NOW(),INTERVAL 10 MINUTE))')->execute([hash('sha256',random_bytes(32)),$id,password_hash('123456',PASSWORD_DEFAULT)]);
 api('session');
 verify(api('request_password_reset',['email'=>'missing-'.$email])[0]===422,'unregistered email rejected without sending a PIN');
 $db->prepare('INSERT INTO password_reset_requests(ip_hash,email_hash) VALUES(?,?)')->execute([hash('sha256','reset-test'),hash('sha256',$email)]);
 [$status,$cooldown]=api('request_password_reset',['email'=>$email]);
 verify($status===429&&$cooldown['retry_after']>0&&$cooldown['retry_after']<=60,'server enforces one-minute resend cooldown');
 $body=['email'=>$email,'pin'=>'000000','password'=>$password,'password_confirmation'=>$password];
 verify(api('verify_password_reset_pin',$body)[0]===422,'incorrect PIN rejected');
 $body['pin']='123456';
 verify(api('reset_password',$body)[0]===422,'password change requires separate PIN verification');
 [$status,$verified]=api('verify_password_reset_pin',['email'=>$email,'pin'=>'123456']);
 verify($status===200&&strlen($verified['token'])===64,'correct PIN grants reset token');
 $q=$db->prepare('SELECT password_version FROM users WHERE id=?');$q->execute([$id]);
 verify((int)$q->fetchColumn()===0,'PIN verification does not change password');
 verify(api('verify_password_reset_pin',['email'=>$email,'pin'=>'123456'])[0]===422,'verified PIN cannot be reused');
 $body=['token'=>$verified['token'],'password'=>$password,'password_confirmation'=>$password];
 verify(api('reset_password',$body)[0]===200,'verified token changes password');
 api('session');
 verify(api('reset_password',$body)[0]===422,'used reset token rejected');
 $q=$db->prepare('SELECT password_hash,password_version FROM users WHERE id=?');$q->execute([$id]);$u=$q->fetch();
 verify(password_verify($password,$u['password_hash'])&&(int)$u['password_version']===1,'new password hashed and old sessions invalidated');
 verify(api('login',['email'=>$email,'password'=>$password])[0]===200,'login accepts new password');
} finally {
 if($id!==null)$db->prepare('DELETE FROM users WHERE id=?')->execute([$id]);
 $db->prepare('DELETE FROM password_reset_requests WHERE email_hash IN (?,?)')->execute([hash('sha256',$email),hash('sha256','missing-'.$email)]);
 if(is_file($cookie))unlink($cookie);
}
