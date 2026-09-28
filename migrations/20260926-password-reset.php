<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once dirname(__DIR__).'/db.php';
$db=database();
$q=$db->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='password_version'");
if(!$q->fetchColumn())$db->exec('ALTER TABLE users ADD COLUMN password_version INT UNSIGNED NOT NULL DEFAULT 0');
$db->exec(file_get_contents(__DIR__.'/password-reset.sql'));
$q=$db->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='password_reset_tokens' AND COLUMN_NAME='code_hash'");
if(!$q->fetchColumn())$db->exec('ALTER TABLE password_reset_tokens ADD COLUMN code_hash VARCHAR(255) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL');
$q=$db->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='password_reset_tokens' AND COLUMN_NAME='attempts'");
if(!$q->fetchColumn())$db->exec('ALTER TABLE password_reset_tokens ADD COLUMN attempts TINYINT UNSIGNED NOT NULL DEFAULT 0');
echo "Password reset tables ready. Existing accounts preserved.\n";
