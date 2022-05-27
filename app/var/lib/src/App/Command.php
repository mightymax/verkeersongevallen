<?php

namespace App;

use Symfony\Component\Console\Command\Command as Base;
use Symfony\Component\Console\Exception\LogicException;

class Command extends Base
{
    protected $dbh;

    public function __construct(\PDO $dbh, string $name = null) {
      $this->dbh = $dbh;
      if (!defined('APP_ROOT')) {
        throw new LogicException('missing defined constant "APP_ROOT"');
      }
      return parent::__construct($name);
    }

  protected function fp(string $path)
  {
    if (!is_file($path))  return Command::INVALID;
    if (!is_readable($path))  return Command::INVALID;
    $fp = @fopen($path, 'r');
    if (!$fp) return Command::FAILURE;
    return $fp;
  }
}