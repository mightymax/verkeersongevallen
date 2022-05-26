<?php
namespace App\Api;

class Response
{
  public static function srv(callable $fun, $cacheId = null)
  {
    $body = $fun();
    if (!is_string($body)) $body = json_encode($body);
    header('Content-type: text/json');
    header('Content-length: ' . mb_strlen($body, '8bit'));
    echo $body;
    exit;

  }

  public static function error(\Throwable $exception)
  {
    $responseCode = $exception->getCode();
    switch ($exception->getCode()) {
      case 500:
        header("HTTP/1.1 500 Internal Server Error");
        break;
      default:
        header("HTTP/1.1 400 Bad Request");
        $responseCode = 400;
        break;
    }
    http_response_code($responseCode);
    header('Content-type: text/json');
    echo json_encode(['error' => ['message' => $exception->getMessage(), 'code' => $exception->getCode(), 'trace' => $exception->getTrace()]]);
    exit;    
  }
}