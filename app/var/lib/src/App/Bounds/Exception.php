<?php
namespace App\Bounds;
class Exception extends \RuntimeException {

  public function __construct(string $message = "an error has occured for Bounds", int $code = 400)
  {
    parent::__construct($message, $code);
  }
}
