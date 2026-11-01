<?php
require dirname(__DIR__).'/app/bootstrap.php';
foreach(['PDO MySQL'=>extension_loaded('pdo_mysql'),'cURL'=>extension_loaded('curl'),'Fileinfo'=>extension_loaded('fileinfo'),'mbstring'=>extension_loaded('mbstring')] as $name=>$ok) echo ($ok?'[OK] ':'[MISSING] ').$name.PHP_EOL;
