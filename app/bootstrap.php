<?php
declare(strict_types=1);define('BASE_PATH',dirname(__DIR__));
require_once BASE_PATH.'/app/lib/config.php';require_once BASE_PATH.'/app/lib/constants.php';require_once BASE_PATH.'/app/lib/helpers.php';require_once BASE_PATH.'/app/lib/database.php';require_once BASE_PATH.'/app/lib/auth.php';require_once BASE_PATH.'/app/lib/transcript_service.php';require_once BASE_PATH.'/app/lib/ai_service.php';require_once BASE_PATH.'/app/lib/jwt.php';
date_default_timezone_set((string)config('APP_TIMEZONE','Asia/Kolkata'));
if(session_status()!==PHP_SESSION_ACTIVE){session_name('vt_session');session_set_cookie_params(['httponly'=>true,'secure'=>!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off','samesite'=>'Lax','path'=>'/']);session_start();}
