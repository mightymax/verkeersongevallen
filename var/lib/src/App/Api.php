<?php
namespace App;

use App\Api\Exception;
use App\Api\Module;
use App\Api\Response;

class Api 
{
  public static function factory(Bounds $bounds): Module 
  {
    if (!defined('APP_ROOT')) throw new \Exception("missing APP_ROOT constant");

    if (!Module::hasModule($bounds['mode'])) {
      throw new Exception("unkown module");
    }
    return (new Module($bounds['mode']))->setBounds($bounds);
  }

  public static function srvBoundsByGemeente($gme_naam)
  {
    $etag = md5(strtolower($gme_naam));

    Response::srvJson(function() use ($gme_naam) {
      $sql = 'SELECT "Y_COORD" lat, "X_COORD" lng FROM ongevallen o JOIN puntlocaties p ON p."FK_VELD5"=o."FK_VELD5" WHERE LOWER("GME_NAAM") = :gme_naam';
      $statement = PDO::getInstance()->prepare($sql);
      $statement->execute([':gme_naam' => strtolower($gme_naam)]);
      return $statement->fetchAll(PDO::FETCH_NUM);
      $coords = $statement->fetchAll(PDO::FETCH_ASSOC);
      if (count($coords) == 0) return false;
      $minlat = null;
      $minlon = null;
      $maxlat = null;
      $maxlon = null;
      for($i = 0; $i < count($coords);  $i++) {
        if (!$minlat || ($minlat > $coords[$i]['lat'])) $minlat = $coords[$i]['lat'];
        if (!$minlon || ($minlon > $coords[$i]['lng'])) $minlon = $coords[$i]['lng'];
        if (!$maxlat || ($maxlat < $coords[$i]['lat'])) $maxlat = $coords[$i]['lat'];
        if (!$maxlon || ($maxlon < $coords[$i]['lng'])) $maxlon = $coords[$i]['lng'];
      }
      return [
        [$minlat, $minlon],
        [$maxlat, $maxlon]
      ];
    }, $etag);
  }
}