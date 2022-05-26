<?php
namespace App\Command\Import;

use Symfony\Component\Console\Attribute\AsCommand;
use App\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Console\Helper\ProgressBar;

#[AsCommand(
    name: 'import:gemeentes',
    description: 'Loads data from Wikidata and writes data to Postgres',
    hidden: false
)]
class Gemeentes extends Command
{

  public static $tableName = 'gemeentes';

  protected function execute(InputInterface $input, OutputInterface $output): int
  {
    $client = HttpClient::create();
    $options = [
      'headers' => [
        'Accept' => 'application/json',
        'Content-type' => 'application/sparql-query'
      ],
      'body' => file_get_contents(APP_ROOT . '/var/sparql/gemeentes.rq')
    ];
    $output->write('Loading data from WikiData: ');
    $response = $client->request('POST', 'https://query.wikidata.org/sparql', $options)->toArray();
    $output->writeln('done, ' . count($response['results']['bindings']). ' gemeentes');
    $this->dbh->query('DELETE FROM ' . self::$tableName);
    $columns = $response['head']['vars'];
    $pgParams = $columns;
    array_walk($pgParams, function(&$column) {$column = ':' . $column;});
    //Some gemeentes have multiple coordinates, so we just ignore them
    $pgColumns = [];
    array_walk($columns, function(&$column) use (&$pgColumns) {$pgColumns[] = '"' . $column .'"';});
    $sql = "INSERT INTO ". self::$tableName. " (".implode(', ', $pgColumns).") VALUES(".implode(', ', $pgParams).") ON CONFLICT DO NOTHING";

    $statement = $this->dbh->prepare($sql);
    $progressBar = new ProgressBar($output, count($response['results']['bindings']));
    $progressBar->start();
    foreach ($response['results']['bindings'] as $row) {
      $params = [];
      foreach ($columns as $column) {
        $params[':'.$column] = @$row[$column]['value'];
      }
      $statement->execute($params);
      $progressBar->advance();
    }
    $progressBar->finish();
    $output->writeln('');

    $output->write('Tabel provincies & gemeentes_stat vullen ...');
    //create Provincies:
    $sql = <<<SQL
INSERT INTO provincies
SELECT 
	pve."PVE_NAAM",
	pve_vlag vlag,
	pve_lat lat,
	pve_lng lng,
	COUNT(*) count,
	SUM(CASE WHEN ong."AP3_CODE"='LET' THEN 1 ELSE 0 END) AS "LET",
	SUM(CASE WHEN ong."AP3_CODE"='DOD' THEN 1 ELSE 0 END) AS "DOD",
	SUM(CASE WHEN ong."AP3_CODE"='UMS' THEN 1 ELSE 0 END) AS "UMS"
FROM gemeentes pve
JOIN ongevallen ong ON ong."PVE_NAAM" = pve."PVE_NAAM"
GROUP BY 
	pve."PVE_NAAM", pve_vlag, pve_lat, pve_lng
SQL;
    $this->dbh->query('DELETE FROM provincies');
    $this->dbh->query($sql);

    $sql = <<<SQL
INSERT INTO gemeentes_stats
SELECT 
	gemeentes."GME_NAAM",
	gemeentes."PVE_NAAM",
	COUNT(*) count,
	SUM(CASE WHEN ongevallen."AP3_CODE"='LET' THEN 1 ELSE 0 END) AS "LET",
	SUM(CASE WHEN ongevallen."AP3_CODE"='DOD' THEN 1 ELSE 0 END) AS "DOD",
	SUM(CASE WHEN ongevallen."AP3_CODE"='UMS' THEN 1 ELSE 0 END) AS "UMS"
FROM gemeentes
JOIN ongevallen ON 
	ongevallen."GME_NAAM" = gemeentes."GME_NAAM"
	AND ongevallen."PVE_NAAM" = gemeentes."PVE_NAAM"
GROUP BY 
	gemeentes."GME_NAAM", 
	gemeentes."PVE_NAAM" 
SQL;

    $this->dbh->query('DELETE FROM gemeentes_stat');
    $this->dbh->query($sql);
    $output->writeln('');
    $output->writeln('Klaar, tip: draai het commando `cli.php app:orphins` om eventueel correcties uit te voeren');
    return Command::SUCCESS;
  }

}
