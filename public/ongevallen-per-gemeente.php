<?php
define('YOU_MAY_INCLUDE_ME', true);
$data = file_get_contents("php://input");
if (!$data) error();
$data = @json_decode($data, true, 4,  JSON_INVALID_UTF8_IGNORE | JSON_BIGINT_AS_STRING );
if (null == $data) error();

if (
  !(int)@$data['zoom']
  || ! isset($data['bounds'])
  || ! isset($data['bounds']['_southWest'])
  || ! isset($data['bounds']['_northEast'])
  || ! is_array($data['bounds']['_southWest'])
  || ! is_array($data['bounds']['_northEast'])
  || count($data['bounds']['_southWest']) !== 2
  || count($data['bounds']['_northEast']) !== 2
  || ! isset($data['bounds']['_southWest']['lat'])
  || ! isset($data['bounds']['_southWest']['lng'])
  || ! isset($data['bounds']['_northEast']['lat'])
  || ! isset($data['bounds']['_northEast']['lng'])
  || ! is_float($data['bounds']['_southWest']['lat'])
  || ! is_float($data['bounds']['_southWest']['lng'])
  || ! is_float($data['bounds']['_northEast']['lat'])
  || ! is_float($data['bounds']['_northEast']['lng'])
) error();


$dbh = require __DIR__ . '/Db.php';

$sql = <<<SQL
SELECT 
	gemeentes."GME_NAAM",
	gemeentes.lat,
	gemeentes.lng,
	gemeentes.flag,
	COUNT(*) count,
	SUM(CASE WHEN ongevallen."AP3_CODE"='LET' THEN 1 ELSE 0 END) AS "LET",
	SUM(CASE WHEN ongevallen."AP3_CODE"='DOD' THEN 1 ELSE 0 END) AS "DOD",
	SUM(CASE WHEN ongevallen."AP3_CODE"='UMS' THEN 1 ELSE 0 END) AS "UMS"
FROM gemeentes
JOIN ongevallen ON ongevallen."GME_NAAM" = gemeentes."GME_NAAM"
WHERE 
  point(gemeentes.lat, gemeentes.lng) <@ box(point(:sw_lat, :sw_lng), point(:ne_lat, :ne_lng)) 
GROUP BY 
	gemeentes."GME_NAAM", 
	gemeentes.lat,
	gemeentes.lng,
	gemeentes.flag
SQL;

$queryParams = [
	':sw_lat' => $data['bounds']['_southWest']['lat'],
	':sw_lng' => $data['bounds']['_southWest']['lng'],
	':ne_lat' => $data['bounds']['_northEast']['lat'],
	':ne_lng' => $data['bounds']['_northEast']['lng'],
];
$statement = $dbh->prepare($sql);
$statement->setFetchMode(PDO::FETCH_ASSOC);
$statement->execute($queryParams);
header('Content-type: application/json; charset=UTF-8');
echo json_encode($statement->fetchAll());
