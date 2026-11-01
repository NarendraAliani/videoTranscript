<?php
require dirname(__DIR__,2).'/app/bootstrap.php';$u=JwtAuth::requireUser();json_response(['user'=>['id'=>(int)$u['id'],'name'=>$u['name'],'email'=>$u['email'],'role'=>$u['role']]]);
