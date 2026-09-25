<?php
declare(strict_types=1);header('Content-Type: application/json');http_response_code(200);echo json_encode(['status'=>'ok','service'=>'skymart'],JSON_THROW_ON_ERROR);
