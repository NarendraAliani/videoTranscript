<?php
declare(strict_types=1);
final class JwtAuth{
private static function b64(string $v):string{return rtrim(strtr(base64_encode($v),'+/','-_'),'=');}
static function issue(int $userId):string{$secret=(string)config('JWT_SECRET','');if(strlen($secret)<32)throw new RuntimeException('Set JWT_SECRET to a random secret of at least 32 characters.');$head=self::b64(json_encode(['typ'=>'JWT','alg'=>'HS256']));$body=self::b64(json_encode(['iss'=>url(),'sub'=>$userId,'iat'=>time(),'exp'=>time()+3600]));$sig=self::b64(hash_hmac('sha256',$head.'.'.$body,$secret,true));return $head.'.'.$body.'.'.$sig;}
static function verify(string $token):?int{$secret=(string)config('JWT_SECRET','');$p=explode('.',$token);if(count($p)!==3||strlen($secret)<32)return null;$sig=self::b64(hash_hmac('sha256',$p[0].'.'.$p[1],$secret,true));if(!hash_equals($sig,$p[2]))return null;$data=json_decode(base64_decode(strtr($p[1],'-_','+/')),true);if(!is_array($data)||($data['exp']??0)<time()||($data['iss']??'')!==url())return null;return (int)($data['sub']??0) ?: null;}
static function requireUser():array{$h=$_SERVER['HTTP_AUTHORIZATION']??'';if(!preg_match('/^Bearer\s+(.+)$/i',$h,$m))json_response(['error'=>'Bearer token required'],401);$id=self::verify(trim($m[1]));if(!$id)json_response(['error'=>'Invalid or expired token'],401);$s=db()->prepare("SELECT id,name,email,role,status FROM users WHERE id=? AND status='active'");$s->execute([$id]);$u=$s->fetch();if(!$u)json_response(['error'=>'Account unavailable'],401);return $u;}
}
