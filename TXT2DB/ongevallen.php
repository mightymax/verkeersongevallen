<?php
$fp = fopen(__DIR__ .'/../01-01-2011_31-12-2020/Netwerkgegevens/puntlocaties.txt', 'r');
$cols = fgetcsv($fp);
$puntlocaties = [];
while ($row = fgetcsv($fp)) {
  $puntlocaties[$row[0]] = rd2wgs($row[1],$row[2]);
}
$puntlocaties['FK_VELD5'] = ['lat','lng'];
$c = 0;
$fp = fopen(__DIR__ .'/../01-01-2011_31-12-2020/Ongevallengegevens/ongevallen.txt', 'r');
while ($row = fgetcsv($fp)) {
  fwrite(STDOUT, implode(',',
  [
    $row[0],
    $row[6],
    $row[10],
    $row[21],
    $row[23],
    $row[25],
    $row[26],
    // $row[51],
    $row[53],
    $row[54],
    $row[55],
    implode(', ', $puntlocaties[$row[51]])
  ])."\n");
  $c++;
}


function rd2wgs ($x, $y){
	
    // Calculate WGS84 coördinates
    $dX = ($x - 155000) * pow(10, - 5);
    $dY = ($y - 463000) * pow(10, - 5);
    $SomN = (3235.65389 * $dY) + (- 32.58297 * pow($dX, 2)) + (- 0.2475 *
         pow($dY, 2)) + (- 0.84978 * pow($dX, 2) *
         $dY) + (- 0.0655 * pow($dY, 3)) + (- 0.01709 *
         pow($dX, 2) * pow($dY, 2)) + (- 0.00738 *
         $dX) + (0.0053 * pow($dX, 4)) + (- 0.00039 *
         pow($dX, 2) * pow($dY, 3)) + (0.00033 * pow(
            $dX, 4) * $dY) + (- 0.00012 *
         $dX * $dY);
    $SomE = (5260.52916 * $dX) + (105.94684 * $dX * $dY) + (2.45656 *
         $dX * pow($dY, 2)) + (- 0.81885 * pow(
            $dX, 3)) + (0.05594 *
         $dX * pow($dY, 3)) + (- 0.05607 * pow(
            $dX, 3) * $dY) + (0.01199 *
         $dY) + (- 0.00256 * pow($dX, 3) * pow(
            $dY, 2)) + (0.00128 *
         $dX * pow($dY, 4)) + (0.00022 * pow($dY,
            2)) + (- 0.00022 * pow(
            $dX, 2)) + (0.00026 *
         pow($dX, 5));
 
    $Latitude = 52.15517 + ($SomN / 3600);
    $Longitude = 5.387206 + ($SomE / 3600);
 
    return array(
        'lat' => round($Latitude, 6) ,
        'lon' => round($Longitude, 6)
		);
}
