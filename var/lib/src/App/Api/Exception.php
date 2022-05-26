<?php
namespace App\Api;
class Exception extends \RuntimeException {

  public function __construct(string $message = "an Api error has occured", int $code = 400)
  {
    parent::__construct($message, $code);
  }
}
