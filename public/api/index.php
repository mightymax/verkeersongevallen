<?php
define('APP_ROOT', realpath(__DIR__ . '/../../'));
require APP_ROOT . '/vendor/autoload.php';
set_exception_handler(['App\Api\Response', 'error']);
if (count($_GET)!==1) throw new \RuntimeException("no mode detected");
$mode = array_keys($_GET)[0];
$api = App\Api::factory($mode)->setBounds(
  App\Bounds::fromJsonString(file_get_contents("php://input"))
)->srv();
