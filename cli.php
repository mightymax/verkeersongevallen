#!/usr/bin/env php
<?php
chdir(__DIR__);
define('APP_ROOT', realpath(__DIR__));
require './vendor/autoload.php';

use Symfony\Component\Console\Application;
use App\Command\Import\Gemeentes;
use App\Command\Import\Ongevallen;
use App\Command\Import\Orphins;
use App\Command\Import\Puntlocaties;

$dbh = App\PDO::getInstance();
$application = new Application();
$application->add(new Gemeentes($dbh));
$application->add(new Ongevallen($dbh));
$application->add(new Puntlocaties($dbh));
$application->add(new Orphins($dbh));

$application->run();