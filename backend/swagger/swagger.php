<?php
/**
 * Generates the OpenAPI (Swagger) JSON from the @OA annotations in the route files
 * and in openapi_setup.php. Swagger UI (index.html in this folder) loads it.
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Hide PHP deprecation notices from the library, they would break the JSON output
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

// TokenAnalyser reads the file text, so it also finds the @OA docblocks written above
// Flight::route(...) calls. The default analyser only reads docblocks of classes and functions.
$openapi = \OpenApi\Generator::scan([
    __DIR__ . '/openapi_setup.php',
    __DIR__ . '/../index.php',
    __DIR__ . '/../routes',
], ['analyser' => new \OpenApi\Analysers\TokenAnalyser()]);

// The API URL is calculated from this script's location, so the documentation works on
// localhost (/library-app/backend) and on the deployed server without changing anything.
// /library-app/backend/swagger/swagger.php -> /library-app/backend
$apiBasePath = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/swagger/swagger.php')), '/\\');
$openapi->servers = [new \OpenApi\Annotations\Server(['url' => $apiBasePath === '' ? '/' : $apiBasePath])];

header('Content-Type: application/json');
echo $openapi->toJson();
