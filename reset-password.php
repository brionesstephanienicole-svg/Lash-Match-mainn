<?php
header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; connect-src 'self'; form-action 'self'; base-uri 'none'; frame-ancestors 'none'");
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="referrer" content="no-referrer"><title>Choose a new password | LashMatch</title><link rel="stylesheet" href="password-recovery.css"><script src="password-recovery.js" defer></script></head>
<body data-mode="reset"><main><a class="brand" href="landingpage.html">LashMatch</a><section class="card"><h1>Choose a new password</h1><p>Confirm your email using the link we sent, then set a new password.</p>
<form hidden><label for="new-password">New password</label><input id="new-password" name="password" type="password" autocomplete="new-password" minlength="12" maxlength="72" required><small>Use at least 12 characters (maximum 72 bytes).</small><label for="confirm-password">Confirm new password</label><input id="confirm-password" name="password_confirmation" type="password" autocomplete="new-password" minlength="12" maxlength="72" required><button type="submit">Change password</button></form>
<div id="success" class="success" hidden><p>Your password has been changed. Sign in with your new password.</p></div><p id="recovery-message" role="status" aria-live="polite"></p>
<a class="back" href="forgot-password.php">Request a new reset link</a><a class="back" href="login.html">Back to login</a><noscript><p>Enable JavaScript to use password recovery.</p></noscript></section></main></body></html>
