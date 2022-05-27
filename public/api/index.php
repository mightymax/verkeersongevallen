<?php

use App\Api;
use App\Api\Exception;

define('APP_ROOT', realpath(__DIR__ . '/../../'));
require APP_ROOT . '/vendor/autoload.php';
set_exception_handler(['App\Api\Response', 'error']);

if (isset($_GET['GME'])) {
  if (!Api::srvBoundsByGemeente($_GET['GME'])) throw new Exception("placename not found", 404);
}

if (!isset($_GET['bounds'])) throw new \RuntimeException("no bounds detected");
$bounds = App\Bounds::fromJsonString($_GET['bounds']);
$api = App\Api::factory(App\Bounds::fromJsonString($_GET['bounds']))->srv();
