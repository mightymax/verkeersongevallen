<?php
define('YOU_MAY_INCLUDE_ME', true);
error_reporting(E_ALL);
ini_set('display_errors', 1);
$cacheFile = __DIR__ . '/cache/ongevallen-per-provincie.json';
if (!file_exists($cacheFile)) {
  $dbh = require __DIR__ . '/Db.php';

  $sql = <<<SQL
SELECT 
	provincies."PVE_CODE",
	provincies."PVE_OMS",
	provincies.lat,
	provincies.lng,
	COUNT(*) count,
	SUM(CASE WHEN ongevallen."AP3_CODE"='LET' THEN 1 ELSE 0 END) AS "LET",
	SUM(CASE WHEN ongevallen."AP3_CODE"='DOD' THEN 1 ELSE 0 END) AS "DOD",
	SUM(CASE WHEN ongevallen."AP3_CODE"='UMS' THEN 1 ELSE 0 END) AS "UMS"
FROM provincies
JOIN ongevallen ON ongevallen."PVE_CODE" = provincies."PVE_CODE"
GROUP BY 
	provincies."PVE_CODE", 
	provincies.lat,
	provincies.lng,
	provincies."PVE_OMS"
SQL;

  $statement = $dbh->prepare($sql);
  $statement->setFetchMode(PDO::FETCH_ASSOC);
  $statement->execute();
  $fp = fopen($cacheFile, 'w');
  fwrite($fp, json_encode($statement->fetchAll()));
  fclose($fp);
}
header('Content-type: application/json; charset=UTF-8');
readfile($cacheFile);
exit;
