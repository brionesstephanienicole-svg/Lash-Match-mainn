<?php
declare(strict_types=1);
$config=[
 'host'=>getenv('LASHMATCH_SMTP_HOST') ?: '',
 'port'=>(int)(getenv('LASHMATCH_SMTP_PORT') ?: 587),
 'username'=>getenv('LASHMATCH_SMTP_USER') ?: '',
 'password'=>getenv('LASHMATCH_SMTP_PASSWORD') ?: '',
 'encryption'=>getenv('LASHMATCH_SMTP_ENCRYPTION') ?: 'tls',
 'from_email'=>getenv('LASHMATCH_MAIL_FROM') ?: '',
 'from_name'=>'LashMatch',
 'base_url'=>getenv('LASHMATCH_BASE_URL') ?: 'http://localhost/LashMatch-main/LashMatch-main',
];
// Local SMTP credentials are excluded from source control.
if(is_file(__DIR__.'/mail-config.local.php'))$config=array_replace($config,require __DIR__.'/mail-config.local.php');
return $config;
