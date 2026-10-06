<?php
declare(strict_types=1);
$path=(string)($_SERVER['PATH_INFO']??'');
if(!preg_match('#^/(\d+)/([a-f0-9]{48})(-thumb)?\.jpg$#',$path,$m)){http_response_code(404);exit;}
$root=(string)(getenv('SKYMART_UPLOAD_ROOT')?:'/var/lib/skymart/uploads');$file=$root.'/'.$m[1].'/'.$m[2].$m[3].'.jpg';
if(!is_file($file)){http_response_code(404);exit;}
header('Content-Type: image/jpeg');header('Cache-Control: public, max-age=31536000, immutable');header('Content-Length: '.filesize($file));readfile($file);
