<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require dirname(__DIR__).'/private/bootstrap.php';
$db=id_db_identity();
$counts=[];
$counts['codes']=$db->exec('DELETE FROM identity_codes WHERE expires_at<DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 DAY) OR used_at<DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 DAY)');
$counts['sessions']=$db->exec('DELETE FROM identity_sessions WHERE expires_at<DATE_SUB(UTC_TIMESTAMP(), INTERVAL 7 DAY) OR revoked_at<DATE_SUB(UTC_TIMESTAMP(),INTERVAL 7 DAY)');
$counts['limits']=$db->exec('DELETE FROM identity_login_limits WHERE window_started<DATE_SUB(UTC_TIMESTAMP(), INTERVAL 7 DAY)');
echo "Limpieza del acceso central: ".json_encode($counts,JSON_UNESCAPED_UNICODE)."\n";
