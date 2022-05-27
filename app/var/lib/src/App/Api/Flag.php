<?php
namespace App\Api;

use App\PDO;

class Flag
{
  private $dbh, $mode, $url;

  private const WIKIMEDIA_HOST = 'commons.wikimedia.org';

  public function __construct($mode = null, $url = null)
  {
    if (!defined('APP_ROOT')) throw new \Exception("missing APP_ROOT constant");
    $this->dbh = PDO::getInstance();
    if ($mode) $this->setMode($mode);
    if ($url) $this->setUrl($url);
  }

  private function getFlagFromDb($url): string | bool
  {
    if (!$this->mode) throw new Exception("class not ready for db queries, 'mode' must be set");
    if ($this->mode == 'PVE') {
      $statement = $this->dbh->prepare("SELECT pve_vlag vlag FROM provincies WHERE pve_vlag=:vlag");
    } else {
      $statement = $this->dbh->prepare("SELECT gme_vlag vlag FROM gemeentes WHERE gme_vlag=:vlag");
    }
    $statement->execute([':vlag' => $url]);
    return $statement->fetchColumn();
  }

  public function setMode(string $mode): self {
    if ($mode != 'PVE' && $mode != 'GME') throw new Exception("unkown mode");
    $this->mode = $mode;
    return $this;
  }

  public function setUrl(string $url, $trust = false): self {
    //this is when we manually set the URL from results that are alrady fetched from database:
    if (true === $trust && php_sapi_name() == 'cli') {
      //since the results in db are already urlencode, we need to reverse this:
      $this->url = parse_url(dirname($url) . '/' . rawurldecode(basename($url)));
      return $this;
    }
    $url = parse_url($url);
    if (!$url) throw new Exception("wrong url pattern");
    if ($url['host'] !== self::WIKIMEDIA_HOST) throw new Exception("wrong host");
    if (!$this->getFlagFromDb(self::unparse_url($url))) throw new Exception("unkown flag");
    $this->url = $url;
    return $this;
  }

  public function getUrl($asString = false): array | string
  {
    return true === $asString ? self::unparse_url($this->url) : $this->url;
  }

  public function exists(): bool
  { 
    if (!$this->url) throw new Exception("class not ready: 'url' must be set");
    return file_exists($this->cachePath(true));
  }

  public function srv()
  {
    $etag = $this->etag();
    Response::srv304($etag);
    $exists = $this->exists();
    if (!$exists) $this->download();
    $file = $this->cachePath(true);
    header("Last-Modified: " . gmdate('D, d M Y H:i:s ', mktime(0, 0, 0, 1, 1, 2021)) . 'GMT');
    header('Content-Type: ' . mime_content_type($file));
    header('Content-Disposition: inline; filename="'.basename($this->getUrl()['path']).'"');
    header('Content-Length: ' . filesize($file));
    header('X-Cache-Hit: ' . ($exists ? 'true' : 'false'));
    header("Etag: $etag"); 
    readfile($file);
    exit;
  }

  public function download(): string
  {
    if (!$this->url) throw new Exception("class not ready: 'url' must be set");
    $cacheInfo = $this->cachePath();
    if (!is_dir($cacheInfo['path'])) {
      if (!mkdir($cacheInfo['path'], 0700, true)) throw new Exception("failed to create flag cache dir");
    }
    ini_set( 'user_agent', 'MightyMax/1.0 (https://github.com/mightymax/ongevallen; markuitheiloo@gmail.com)' );
    $bytes = file_get_contents($this->getUrl(true));
    if (!$bytes) throw new Exception("failed to load flag from wikimiedia");

    $fp = fopen(implode('', $cacheInfo), 'wb');
    if (!$fp) throw new Exception("failed to create file pointer for flag");

    fwrite($fp, $bytes);
    fclose($fp);
    return $bytes;

  }

  public function etag(): string
  {
    return md5($this->getUrl(true));
  }

  protected function cachePath($asString = false): array | string
  {
    $path = APP_ROOT .'/var/cache/flags/' . $this->mode .'/';
    $file = $this->etag() . '.' . pathinfo($this->getUrl()['path'], PATHINFO_EXTENSION);
    return $asString ? $path . $file : ['path' => $path, 'file' => $file];
  }

  public static function unparse_url(array $parsed_url) 
  {
    $path = dirname($parsed_url['path']) . '/' . rawurlencode(basename($parsed_url['path']));
    $scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
    $host     = isset($parsed_url['host']) ? $parsed_url['host'] : '';
    return "$scheme$host$path";
  } 
}
