<?php
$dsn = "pgsql:host=localhost;port=5432;dbname=ongevallen;user=ongevallen;password=ongevallen";
$dbh = new PDO($dsn);
chdir(dirname(__FILE__));
$tables = [
  // 'aardongevallen',
  // 'wegsituaties',
  // 'aflopen',
  // 'ongevallen',
  'gemeentes'
];

$nullable = [
  'AOL_ID', 'WSE_ID', 'MAXSNELHD'
];
foreach (array_reverse($tables) as $table) {
  $dbh->query("DELETE FROM {$table}");
}

foreach ($tables as $table) {
  $c = (int)exec("wc -l data/{$table}.csv");
  $fp = fopen("data/{$table}.csv", 'r');
  $cols = fgetcsv($fp);
  $values = [];
  foreach ($cols as $k=>$val) $values[] = ':v'.$k;

  $statement = $dbh->prepare("INSERT INTO {$table} VALUES(".implode(',', $values).")");
  $i = 1;
  while ($row = fgetcsv($fp)) {
    if ($table == 'ongevallen') {
      $row = array_combine($cols, $row);
      $row['GME_NAAM'] = utf8_encode($row['GME_NAAM']);
      foreach ($nullable as $colname) if ($row[$colname] == '') $row[$colname] = null;
      $row = array_values($row);
    }
    fwrite(STDERR, sprintf("[%07d/%07d] ", $i, $c).implode(", ", $row));
    try {
      $statement->execute(array_combine($values, $row));
    } catch(PDOException $e) {
      fwrite(STDERR, "** ERR **");
    }
    fwrite(STDERR, "\n");
    $i++;
  }
  fclose($fp);
}

// $dbh->query("UPDATE ongevallen SET \"BEBKOM\"=null WHERE \"BEBKOM\"='  '");