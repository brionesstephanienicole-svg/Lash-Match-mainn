<?php
// Copy to mail-config.local.php and fill in your SMTP provider's settings.
// Do not commit mail-config.local.php or paste credentials into chat.
return [
 'host'=>'smtp.gmail.com',
 'port'=>587,
 'username'=>'your-email@gmail.com',
 'password'=>'YOUR_GOOGLE_APP_PASSWORD',
 'encryption'=>'tls',
 'from_email'=>'your-email@gmail.com',
 'from_name'=>'LashMatch',
 'base_url'=>'http://localhost/LashMatch-main/LashMatch-main',
];
