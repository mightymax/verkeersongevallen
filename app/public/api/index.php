<?php

use App\Api;
use App\Api\Exception;
use App\Api\Flag;

define('APP_ROOT', realpath(__DIR__ . '/../..'));
require APP_ROOT . '/vendor/autoload.php';
set_exception_handler(['App\Api\Response', 'error']);

if (isset($_GET['GME'])) {
  if (!Api::srvBoundsByGemeente($_GET['GME'])) throw new Exception("placename not found", 404);
} else if (isset($_GET['vlag'])) {
  if (!@$_GET['mode']) throw new \RuntimeException("geen gme/pve opgegeven");
  (new Flag())->setMode($_GET['mode'])->setUrl($_GET['vlag'])->srv();
} else if (isset($_GET['bounds'])) {
  App\Api::factory(App\Bounds::fromJsonString($_GET['bounds']))->srv();
}
throw new \RuntimeException("I have no clue what you want from me ;-(");
