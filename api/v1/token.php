<?php
require dirname(__DIR__,2).'/app/bootstrap.php';Auth::requireLogin();require_post();verify_csrf();try{header('Content-Type: application/json');echo json_encode(['token'=>JwtAuth::issue(Auth::id()),'token_type'=>'Bearer','expires_in'=>3600]);}catch(Throwable $e){json_response(['error'=>$e->getMessage()],500);}
