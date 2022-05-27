<?php
use App\Api\Flag;
define('APP_ROOT', realpath(__DIR__ . '/../../'));
require APP_ROOT . '/vendor/autoload.php';
set_exception_handler(['App\Api\Response', 'error']);

if (!@$_GET['url']) throw new \RuntimeException("geen vlag opgegeven");
if (!@$_GET['mode']) throw new \RuntimeException("geen gme/pve opgegeven");

$flag = (new Flag())
  ->setMode($_GET['mode'])
  ->setUrl($_GET['url'])
  ->srv();
