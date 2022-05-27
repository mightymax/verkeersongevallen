<?php
namespace App\Command\Import;

use App\Command;
use App\Command\Import\Gemeentes;
use App\Command\Import\Ongevallen;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'import:orphins',
    description: 'List Places that have no corresponding Ongeval or vice versa',
    hidden: false
)]
class Orphins extends Command
{
    var $tableName_ongevallen;
    var $tableName_gemeentes;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $g = $input->getOptions()['gemeentes'];
        $o = $input->getOption('ongevallen');
        $interactive = $input->getOption('interactive');
        $delete = $input->getOption('delete');
        $helper = $this->getHelper('question');
        if (!$g && !$o) {$g = true; $o = true;}

        if ($g) {
            $output->writeln('Plaatsnamen waarvan geen Ongevallen bekend zijn:');
            $statement = $this->plaatsen();
            if (!$statement->execute()) {
                $io->getErrorStyle()->error("kon resultaat niet niet ophalen uit de database");
            } else {
                while ($row = $statement->fetch()) {
                    $output->writeln("{$row['GME_NAAM']} ({$row['PVE_NAAM']})");
                    if ($interactive) {
                        if ($delete) {
                            $question = new ConfirmationQuestion('Wil je deze Gemeente verwijderen [j/n]? ', false, '/^(y|j)/i');
                            if ($helper->ask($input, $output, $question)) {
                                $this->delete($this->tableName_gemeentes, $row);
                            }
                        } else {
                            $question = new Question('Nieuwe naam voor de Gemeente `'.$row['GME_NAAM'].'`: ', $row['GME_NAAM']);
                            $GME_NAAM = $helper->ask($input, $output, $question);
                            if ($GME_NAAM && $GME_NAAM != $row['GME_NAAM']) {
                                if (!$this->updateGemeentenaam($this->tableName_ongevallen, $row, $GME_NAAM)) {
                                    $io->getErrorStyle()->error("kon Gemeentenaam niet aanpassen in de database");
                                }
                            }
                        }
                    } else if ($delete) {
                        $this->delete($this->tableName_gemeentes, $row);
                    }
                }
            }
        }

        if ($o) {
            $output->writeln('Ongevallen waarvan geen Plaatsnamen bekend zijn:');
            $statement = $this->ongevallen();
            if (!$statement->execute()) {
                $io->getErrorStyle()->error("kon resultaat niet ophalen uit de database");
            } else {
                while ($row = $statement->fetch()) {
                    $output->writeln("{$row['GME_NAAM']} ({$row['PVE_NAAM']}, {$row['count']} ong.)");
                    if ($interactive) {
                        if ($delete) {
                            $question = new ConfirmationQuestion('Wil je al deze Ongevallen ('.$row['count'].') verwijderen [j/n]? ', false, '/^(y|j)/i');
                            if ($helper->ask($input, $output, $question)) {
                                $this->delete($this->tableName_ongevallen, $row);
                            }
                        } else {
                            $question = new Question('Nieuwe naam voor de Gemeente `'.$row['GME_NAAM'].'`: ', $row['GME_NAAM']);
                            $GME_NAAM = $helper->ask($input, $output, $question);
                            if ($GME_NAAM && $GME_NAAM != $row['GME_NAAM']) {
                                if (!$this->updateGemeentenaam($this->tableName_ongevallen, $row, $GME_NAAM)) {
                                    $io->getErrorStyle()->error("kon Gemeentenaam niet aanpassen in de database");
                                }
                            }
                        }
                    } else if ($delete) {
                        $this->delete($this->tableName_ongevallen, $row);
                    }
                }
            }
        }
        return Command::SUCCESS;
    }

    protected function updateGemeentenaam(string $tablename, Array $row, string $GME_NAAM_nieuw): bool {
        $statement = $this->dbh->prepare("UPDATE {$tablename} SET \"GME_NAAM\"=:gme_naam_nieuw WHERE \"GME_NAAM\"=:gme_naam AND \"PVE_NAAM\"=:pve_naam");
        return $statement->execute([
            ':gme_naam_nieuw' => $GME_NAAM_nieuw,
            ':gme_naam' => $row['GME_NAAM'],
            ':pve_naam' => $row['PVE_NAAM']
        ]);
    }

    protected function delete(string $tablename, Array $row): bool {
        $statement = $this->dbh->prepare("DELETE FROM {$tablename} WHERE \"GME_NAAM\"=:gme_naam AND \"PVE_NAAM\"=:pve_naam");
        return $statement->execute([
            ':gme_naam' => $row['GME_NAAM'],
            ':pve_naam' => $row['PVE_NAAM']
        ]);
    }

    protected function configure(): void
    {
        $this->tableName_ongevallen = Ongevallen::$tableName;
        $this->tableName_gemeentes = Gemeentes::$tableName;

        $this->addOption('gemeentes', 'g', InputOption::VALUE_NONE, 'choose "gemeentes" as your target');
        $this->addOption('ongevallen', 'o', InputOption::VALUE_NONE, 'choose "ongevallen" as your target');
        $this->addOption('interactive', 'i', InputOption::VALUE_NONE, 'allow changes');
        $this->addOption('delete', null, InputOption::VALUE_NONE, 'delete orphins (!!)');
    }

    protected function plaatsen(): \PDOStatement|bool
    {
        $sql = <<<SQL
SELECT g."GME_NAAM", g."PVE_NAAM", COUNT(*) count
FROM {$this->tableName_gemeentes} g
LEFT JOIN {$this->tableName_ongevallen} o ON o."GME_NAAM"=g."GME_NAAM" AND o."PVE_NAAM"=g."PVE_NAAM"
WHERE o."GME_NAAM" IS NULL
GROUP BY g."GME_NAAM", g."PVE_NAAM"
ORDER BY g."PVE_NAAM", g."GME_NAAM"
SQL;
        $statement = $this->dbh->prepare($sql);
        $statement->setFetchMode(\PDO::FETCH_ASSOC);
        return $statement;
    }

    protected function ongevallen(): \PDOStatement|bool
    {
        $sql = <<<SQL
SELECT o."GME_NAAM", o."PVE_NAAM", COUNT(*) count
FROM {$this->tableName_ongevallen} o
LEFT JOIN {$this->tableName_gemeentes} g ON o."GME_NAAM"=g."GME_NAAM" AND o."PVE_NAAM"=g."PVE_NAAM"
WHERE g."GME_NAAM" IS NULL
GROUP BY o."GME_NAAM", o."PVE_NAAM"
ORDER BY o."PVE_NAAM", o."GME_NAAM"
SQL;
        $statement = $this->dbh->prepare($sql);
        $statement->setFetchMode(\PDO::FETCH_ASSOC);
        return $statement;
    }
}