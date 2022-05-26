<?php
namespace App\Command\Import;

use Symfony\Component\Console\Attribute\AsCommand;
use App\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\RD2WGS;

#[AsCommand(
    name: 'import:ongevallen',
    description: 'Loads data from textfile (Rijkswaterstaat ongevallen.txt)',
    hidden: false
)]
class Ongevallen extends Command
{

  public static $tableName = 'ongevallen';

  protected function execute(InputInterface $input, OutputInterface $output): int
  {
    $io = new SymfonyStyle($input, $output);
    $ongevallen = filter_var($input->getArgument('ongevallen'), FILTER_CALLBACK, ['options' => [$this, 'fp']]);
    if (Command::INVALID == $ongevallen) {
      $io->getErrorStyle()->error("'{$ongevallen}': failed to load file");
      return Command::INVALID;
    }

    $cOngevallen = 0;
    while($row = fgets($ongevallen)) $cOngevallen++;
    rewind($ongevallen);

    $cols = fgetcsv($ongevallen);
    //load column names from table:
    $sql = "SELECT column_name FROM information_schema.columns WHERE table_name = '".self::$tableName."'";
    $statement = $this->dbh->query($sql);
    $dbCols = [];
    $err = false;
    while ($col = $statement->fetchColumn()) {
      if (!in_array($col, $cols)) {
        $io->getErrorStyle()->warning("missing column '{$col}'");
        $err++;
      }
      $dbCols[] = $col;
    }
    if ($err) return Command::INVALID;

    $this->dbh->query('DELETE FROM ' . self::$tableName);

    $progressBar = new ProgressBar($output, $cOngevallen);
    $progressBar->start();
    $i = 0;
    $placeholders = [];
    $values = [];

    while($row = fgetcsv($ongevallen)) {
      $placeholder = [];
      $row = array_combine($cols, $row);
      foreach($dbCols as $col) {
        $placeholder[] = ":{$col}_{$i}";
        $values[":{$col}_{$i}"] = is_string($row[$col]) ? utf8_encode($row[$col]) : $row[$col];
      }
      $placeholders[] = '(' . implode(', ', $placeholder) .')';
      if (count($placeholders)>= 1000) $this->batchCreate($placeholders, $values, $progressBar);
      $i++;
    }

    if (count($placeholders)) $this->batchCreate($placeholders, $values, $progressBar);

    $progressBar->finish();
    $output->writeln('');
    $output->write('verwijderen niet gebruikte puntlocaties: ');
    $sql = <<<SQL
DELETE FROM puntlocaties WHERE "FK_VELD5" IN (
	SELECT 
		p."FK_VELD5" 
	FROM puntlocaties p
	LEFT JOIN ongevallen o ON 
		o."FK_VELD5"=p."FK_VELD5"
	WHERE 
		o."FK_VELD5" IS NULL
)
SQL;

    $this->dbh->query($sql);
    $output->writeln('klaar');
    $output->writeln('tip: draai het commando `cli.php app:orphins` om eventueel correcties uit te voeren');
    fclose($ongevallen);
    return Command::SUCCESS;
  }

  protected function configure(): void
  {
    $this->addArgument('ongevallen', InputArgument::REQUIRED, 'Databestand met ongevallen');
  }

  private function batchCreate(
    array &$placeholders, 
    array &$values, 
    ProgressBar $progressBar): void
  {
      $statement = $this->dbh->prepare("INSERT INTO ".self::$tableName." VALUES ".implode(', ', $placeholders));
      $statement->execute($values);
      $placeholders = [];
      $values = [];
      $progressBar->advance(count($placeholders));
  }

}