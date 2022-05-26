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
    name: 'import:puntlocaties',
    description: 'Loads data from textfile (Rijkswaterstaat puntlocaties.txt)',
    hidden: false
)]
class Puntlocaties extends Command
{

  public static $tableName = 'puntlocaties';

  protected function execute(InputInterface $input, OutputInterface $output): int
  {
    $io = new SymfonyStyle($input, $output);
    $puntlocaties = filter_var($input->getArgument('puntlocaties'), FILTER_CALLBACK, ['options' => [$this, 'fp']]);
    if (Command::INVALID == $puntlocaties) {
      $io->getErrorStyle()->error("'{$puntlocaties}': failed to load file");
      return Command::INVALID;
    }

    $cPuntloacties = 0;
    while($row = fgets($puntlocaties)) $cPuntloacties++;
    rewind($puntlocaties);

    $cols = fgetcsv($puntlocaties);
    if(count(array_diff(['FK_VELD5', 'X_COORD', 'Y_COORD'], $cols))) {
      $io->getErrorStyle()->error("unexpected columns in puntlocaties");
      return Command::INVALID;
    }

    $progressBar = new ProgressBar($output, $cPuntloacties);
    $progressBar->start();

    $this->dbh->query('DELETE FROM puntlocaties');
    
    $placeholders = [];
    $values = [];
    $i = 0;
    while($row = fgetcsv($puntlocaties)) {
      $latlng = RD2WGS::convert($row[1], $row[2]);
      $placeholders[] = "(:FK_VELD5_{$i}, :X_COORD_{$i}, :Y_COORD_{$i})";
      $values[':FK_VELD5_'.$i] = $row[0];
      $values[':X_COORD_'.$i] = $latlng['lat'];
      $values[':Y_COORD_'.$i] = $latlng['lng'];
      if (count($values)>= 1000) $this->batchCreate($placeholders, $values, $progressBar);
      $i++;
    }
    if (count($values)) $this->batchCreate($placeholders, $values, $progressBar);

    $progressBar->finish();
    $output->writeln('');
    fclose($puntlocaties);
    return Command::SUCCESS;
  }

  private function batchCreate(array &$placeholders, array &$values, ProgressBar $progressBar): void
  {
      $statement = $this->dbh->prepare("INSERT INTO ".self::$tableName." VALUES ".implode(', ', $placeholders));
      $statement->execute($values);
      $placeholders = [];
      $values = [];
      $progressBar->advance(count($placeholders));
  }

  protected function configure(): void
    {
        $this->addArgument('puntlocaties', InputArgument::REQUIRED, 'Databestand met puntlocaties');
    }
}