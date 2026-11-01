<?php
declare(strict_types=1);$f=BASE_PATH.'/.env';if(is_file($f)){foreach(file($f,FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES)?:[] as $l){$l=trim($l);if(!$l||str_starts_with($l,'#')||!str_contains($l,'='))continue;[$k,$v]=explode('=',$l,2);$v=trim($v);if(strlen($v)>1&&(($v[0]==='"'&&str_ends_with($v,'"'))||($v[0]==="'"&&str_ends_with($v,"'"))))$v=substr($v,1,-1);if(getenv(trim($k))===false)putenv(trim($k).'='.$v);}}
function config(string $k,mixed $d=null):mixed{$v=getenv($k);return $v===false||$v===''?$d:($v==='true'?true:($v==='false'?false:$v));}
