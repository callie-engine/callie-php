<?php
/*
|--------------------------------------------------------------------------
| ENTRY POINT
|--------------------------------------------------------------------------
|
| You generally DO NOT need to edit this file.
| This file bootstraps the framework, loads your .env config,
| and processes the request.
|
| Go to routes.php to start adding your API logic!
|
*/

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: OPTIONS,GET,POST,PUT,DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/core/Callie.php';

// Initialize the Framework
use Callie\Callie;

$app = new Callie();
$app->run();

