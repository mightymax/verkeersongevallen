<?php
namespace App;

class PDO extends \PDO
{
  public static function getInstance(): \PDO
  {
    if (!defined('APP_ROOT')) throw new \Exception("missing APP_ROOT constant");
    $config = @parse_ini_file(APP_ROOT . '/var/config.ini');
    if (!$config) throw new \Exception("failed to parse config.ini");
    if (!isset($config['dsn'])) throw new \Exception("missing 'dsn' entry in config.ini");
    try {
      return new self($config['dsn']);
    } catch (\Exception $e) {
      throw new \Exception("Faild to make database connection");
    }
  }
}