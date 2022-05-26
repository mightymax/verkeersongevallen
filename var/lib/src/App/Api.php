<?php
namespace App;

use App\Api\Exception;
use App\Api\Module;

class Api 
{
  public static function factory($moduleName): Module 
  {
    if (!defined('APP_ROOT')) throw new \Exception("missing APP_ROOT constant");

    if (!Module::hasModule($moduleName)) {
      throw new Exception("unkown module");
    }
    return new Module($moduleName);
  }
}