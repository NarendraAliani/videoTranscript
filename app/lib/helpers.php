<?php
declare(strict_types=1);
function e(mixed $v):string{return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function url(string $p=''):string{return rtrim((string)config('APP_URL',''),'/').'/'.ltrim($p,'/');}
function redirect(string $p):never{header('Location: '.url($p));exit;}
function db():PDO{return Database::connection();}
function flash(string $t,string $m):void{$_SESSION['_flash'][]=['type'=>$t,'message'=>$m];}
function flashes():array{$a=$_SESSION['_flash']??[];unset($_SESSION['_flash']);return $a;}
function csrf_token():string{return $_SESSION['_csrf']??=bin2hex(random_bytes(32));}
function csrf_field():string{return '<input type="hidden" name="_csrf" value="'.e(csrf_token()).'">';}
function verify_csrf():void{if(!isset($_POST['_csrf'])||!hash_equals(csrf_token(),(string)$_POST['_csrf'])){http_response_code(419);exit('Session expired. Refresh and try again.');}}
function require_post():void{if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);exit('Method not allowed');}}
function validate_media_url(string $u):bool{if(strlen($u)>2048||!filter_var($u,FILTER_VALIDATE_URL)||!in_array(strtolower((string)parse_url($u,PHP_URL_SCHEME)),['http','https'],true))return false;$h=strtolower((string)parse_url($u,PHP_URL_HOST));foreach(['youtube.com','youtu.be','tiktok.com','instagram.com','facebook.com','fb.watch','linkedin.com','x.com','twitter.com','twitch.tv','rumble.com','kick.com','spotify.com','podcasts.apple.com'] as $d)if($h===$d||str_ends_with($h,'.'.$d))return true;return false;}
function platform_from_url(string $u):string{$h=strtolower((string)parse_url($u,PHP_URL_HOST));foreach(['youtube'=>'youtube','youtu.be'=>'youtube','tiktok'=>'tiktok','instagram'=>'instagram','facebook'=>'facebook','fb.watch'=>'facebook','linkedin'=>'linkedin','twitter'=>'twitter','x.com'=>'twitter','twitch'=>'twitch','rumble'=>'rumble','kick'=>'kick','spotify'=>'podcast','apple.com'=>'podcast'] as $d=>$p)if(str_contains($h,$d))return $p;return 'unknown';}
function app_log(string $m):void{@file_put_contents(BASE_PATH.'/storage/app.log','['.date('c').'] '.$m.PHP_EOL,FILE_APPEND|LOCK_EX);}
function media_storage_dir():string{$configured=(string)config('UPLOAD_DIR','storage/uploads');if(str_starts_with($configured,'/')||preg_match('/^[A-Za-z]:[\\\\\\/]/',$configured))return rtrim($configured,'/\\\\');return BASE_PATH.'/'.trim($configured,'/');}
function media_file_path(string $path):string{if(str_starts_with($path,'/')||preg_match('/^[A-Za-z]:[\\\\\\/]/',$path))return $path;return BASE_PATH.'/'.ltrim($path,'/');}
function duration(int $s):string{return sprintf('%02d:%02d:%02d',intdiv($s,3600),intdiv($s%3600,60),$s%60);}
