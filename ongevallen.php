<?php
// define('PLACENAME', 'Heiloo');
define('PLACENAME', false);
$from = @$argv[1] ? (int)$argv[1] : 2003;
if (@$argv[2]) $until = (int)$argv[2];
else $argv[2] = $until = @$argv[1] ? (int)$argv[1] : 2020;

$ongevallen = [];
$locaties = [];
for ($year = $from; $year <= $until; $year ++) {
  fwrite(STDERR, $year);
  $loadPLs = false;
  $fp = fopen($year. '/Ongevallengegevens/ongevallen.txt','r');
  $cols = fgetcsv($fp);
  print_r($cols);exit;
  while ($row =fgetcsv($fp)) {
    $row = array_combine($cols, $row);
    if (PLACENAME && $row['GME_NAAM']!=PLACENAME) continue;
    $loc = $row['FK_VELD5'];
    if (!isset($ongevallen[$loc])) {
      $ongevallen[$loc] = ['c' => 0];
      $loadPLs = true;
    }
    $ongevallen[$loc]['c']++;
    if (!PLACENAME) continue;
    if (!isset($ongevallen[$loc]['Y']))  $ongevallen[$loc]['Y'] = [];
    if (!isset($ongevallen[$loc]['Y'][$year])) $ongevallen[$loc]['Y'][$year] = 0;
    $ongevallen[$loc]['Y'][$year]++;

    foreach (['AP3_CODE', 'AOL_ID'] as $col) {
      if (!isset($ongevallen[$loc][$col])) 
        $ongevallen[$loc][$col] = [];
      if (!isset($ongevallen[$loc][$col][$row[$col]])) 
        $ongevallen[$loc][$col][$row[$col]] = 0;
      $ongevallen[$loc][$col][$row[$col]]++;

    }
  }
  fclose($fp);

  if ($loadPLs) {
    $fp = fopen($year. '/Netwerkgegevens/puntlocaties.txt','r');
    $cols = fgetcsv($fp);
    while($row = fgetcsv($fp)) {
      $loc =$row[0];
      if (isset($ongevallen[$loc])) {
        $wgs = rd2wgs($row[1], $row[2]);
        $ongevallen[$loc]['lat'] = round($wgs['lat'],5);
        $ongevallen[$loc]['lon'] = round($wgs['lon'], 5);
      }
    }
    fclose($fp);
  }
  fwrite(STDERR, " done\n");
}
if (PLACENAME) {
  $output = [];
  foreach ($ongevallen as $ongeval) if ($ongeval['lat'] && $ongeval['lon']) $output[] = $ongeval;
} else {
  $output = [];
  foreach ($ongevallen as $ongeval) if ($ongeval['lat'] && $ongeval['lon']) $output[] = [$ongeval['lat'], $ongeval['lon'], $ongeval['c']];
}
fwrite(STDOUT, json_encode($output)); 
fwrite(STDERR, count($ongevallen). " Ongevallen verwerkt\n");
exit;

$locs = [];
foreach ($ongevallen as $p) $locs[] = [$p['lat'],$p['lon'],$p['c']];
fwrite(STDOUT, json_encode($locs));
exit;

fwrite(STDOUT, "LOC,LAT,LON,COUNT\n");
foreach ($ongevallen as $l => $p) {
  fwrite(STDOUT, "{$l},{$p['lat']},{$p['lon']},{$p['c']}\n");
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
        'lat' => $Latitude ,
        'lon' => $Longitude
		);
}


