<?php
namespace App\Api;

use App\Bounds;

class Module 
{

  private const PVE = 'PVE';
  private const GME = 'GME';
  private const ONG = 'ONG';

  protected $queryTemplateFile;

  /**
   * @var $_bounds Bounds
   */
  protected $_bounds;

  protected $_module;

  public function __construct(string $module)
  {
    $this->_module = $module;
  }

  public static function hasModule($module)
  {
    return defined('self::' . strtoupper($module));
  }

  public function setBounds(Bounds $bounds): self
  {
    $this->_bounds = $bounds;
    return $this;
  }

  public function srv()
  {
    $etag = md5($this->_module == self::ONG ? $this->_bounds->etag() : $this->_module);
    Response::srvJson(function() {
      $sql = $this->getSqlString();
      $dbh = \App\PDO::getInstance();
      $statement = $dbh->prepare($sql);
      $statement->setFetchMode(\PDO::FETCH_ASSOC);
      $statement->execute($this->_bounds->getPgOptions());
      return $statement->fetchAll();
    }, $etag);
  }

  public function getSqlString(): string
  {
    $templatePath = APP_ROOT . '/var/queries/' . $this->_module . '.sql';
    $sql = @file_get_contents($templatePath);
    if (!$sql) {
      throw new Exception("failed to load SQL template", 500);
    }
    return $sql;
  }

}