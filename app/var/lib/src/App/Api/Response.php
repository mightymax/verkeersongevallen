<?php
namespace App\Api;

use App\PDO;

class Response
{
  public static function srvJson(callable $fun, $etag = null)
  {
    if ($etag) {
      self::srv304($etag);
    }
    $body = $fun();
    if (!is_string($body)) $body = json_encode($body);
    if ($etag) {
      $cachePath = APP_ROOT . '/var/cache/geo/' . chunk_split($etag, 8, '/');
      $cacheFile = $cachePath . $etag . '.phps';
      if (file_exists($cacheFile)) {
        $body = file_get_contents($cacheFile);
        header('X-Cache-Hit: true');
      } else {
        $body = $fun();
        if (!is_string($body)) $body = json_encode($body);
        if (!is_dir($cachePath) && !mkdir($cachePath, 0700, true)) 
          throw new Exception("failed to create flag cache dir");
        if (!$fp = @fopen($cacheFile, 'w')) throw new Exception("failed to create cache file");
        fwrite($fp, $body);
        fclose($fp);
        header('X-Cache-Hit: false');
      }
      header('Content-type: application/json');
      header("Last-Modified: " . gmdate('D, d M Y H:i:s ', mktime(0, 0, 0, 1, 1, 2021)) . 'GMT');
      header('Content-Length: ' . filesize($cacheFile));
      header('Etag: ' . $etag);
      echo $body;
    } else {
      $body = $fun();
      if (!is_string($body)) $body = json_encode($body);
      header('Content-type: application/json');
      header('Content-length: ' . mb_strlen($body, '8bit'));
      echo $body;
    }
    exit;
  }

  public static function error(\Throwable $exception)
  {
    $responseCode = $exception->getCode();
    switch ($exception->getCode()) {
      case 500:
        header("HTTP/1.1 500 Internal Server Error");
        break;
      case 404:
        header("HTTP/1.1 404 Not Found");
        break;
      default:
        header("HTTP/1.1 400 Bad Request");
        $responseCode = 400;
        break;
    }
    http_response_code($responseCode);
    header('Content-type: text/json');
    echo json_encode([
      'error' => [
        'message' => $exception->getMessage(), 
        'code' => $exception->getCode()
      ], 
      'exception' => [
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'trace' => $exception->getTrace(),
      ],
      // 'env' => $_ENV
    ]);
    exit;    
  }

  public static function srv304($etag, $timestamp = null)
  {
    if (null === $timestamp) $timestamp = mktime(0, 0, 0, 1, 1, 2021);
    $tsstring = gmdate('D, d M Y H:i:s ', $timestamp) . 'GMT';
    $if_modified_since = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? $_SERVER['HTTP_IF_MODIFIED_SINCE'] : false;
    $if_none_match = isset($_SERVER['HTTP_IF_NONE_MATCH']) ? $_SERVER['HTTP_IF_NONE_MATCH'] : false;
    if ((($if_none_match && $if_none_match == $etag) || (!$if_none_match)) &&
        ($if_modified_since && $if_modified_since == $tsstring))
    {
        header('HTTP/1.1 304 Not Modified', true, 304);
        exit();
    }    
    return false;
  }
}