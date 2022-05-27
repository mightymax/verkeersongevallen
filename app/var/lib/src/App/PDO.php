<?php
namespace App;

class PDO extends \PDO
{
  public static function getInstance(): \PDO
  {
    if (isset($_ENV['DATABASE_URL'])) {
      $dsn = $_ENV['DATABASE_URL'];
    } else {
      if (!defined('APP_ROOT')) throw new \Exception("missing APP_ROOT constant");
      $config = @parse_ini_file(APP_ROOT . '/var/config.ini');
      if (!$config) throw new \Exception("failed to parse config.ini");
      if (!isset($config['dsn'])) throw new \Exception("missing 'dsn' entry in config.ini");
      $dsn = $config['dsn'];
    }

    try {
      return new self($dsn);
    } catch (\Exception $e) {
      throw new \Exception("error in database connection: " . $e->getMessage());
    }
  }
}