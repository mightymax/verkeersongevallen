<?php
$query = <<<SPARQL
SELECT ?gmeLabel ?flag
WHERE {
  ?gme wdt:P31 wd:Q2039348; wdt:P131 ?prov; wdt:P41 ?flag .
  ?prov wdt:P31 wd:Q134390 .
  SERVICE wikibase:label { bd:serviceParam wikibase:language "nl" }
}
SPARQL;

ob_start();
passthru("curl -s -H 'Accept: application/json' " . escapeshellarg('https://query.wikidata.org/sparql?query=' . urlencode($query)));
$response = json_decode(ob_get_contents(), true);
ob_end_clean();

if (!$response) {
  fwrite(STDERR, "failed to load response\n");
  exit(1);
}

$dsn = "pgsql:host=localhost;port=5432;dbname=ongevallen;user=ongevallen;password=ongevallen";
$dbh = new PDO($dsn);
$statement = $dbh->prepare("UPDATE gemeentes SET flag=:flag WHERE \"GME_NAAM\" = :gme_naam");

foreach ($response['results']['bindings'] as $binding) {
  $params = [
    ':gme_naam' => $binding['gmeLabel']['value'],
    ':flag' => $binding['flag']['value'],
  ];
  $statement->execute($params);
  fwrite(STDERR, "{$binding['gmeLabel']['value']}: {$binding['flag']['value']}\n");
}

