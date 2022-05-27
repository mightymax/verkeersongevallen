<?php
namespace App\Command\Import;

use App\Api\Flag;
use Symfony\Component\Console\Attribute\AsCommand;
use App\Command;
use App\PDO;
use Symfony\Component\Console\Cursor;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(
    name: 'import:flags',
    description: 'Loads flags from Wikidata (cache)',
    hidden: false
)]
class Flags extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $force = $input->getOptions()['force'];

        $statement = $this->dbh->prepare('SELECT COUNT(*) FROM gemeentes WHERE NOT gme_vlag IS NULL');
        $statement->execute();
        $countGME = $statement->fetchColumn();

        $statement = $this->dbh->prepare('SELECT COUNT(DISTINCT(pve_vlag)) FROM gemeentes');
        $statement->execute();
        $countPVE = $statement->fetchColumn();

        $sql = <<<SQL
SELECT "mode", naam, url FROM (
	SELECT 'PVE' "mode", CONCAT('Provincie ', "PVE_NAAM") naam, pve_vlag url 
	FROM gemeentes
	GROUP BY "PVE_NAAM", pve_vlag
	UNION
	SELECT 'GME' "mode", CONCAT("GME_NAAM", ' (', "PVE_NAAM", ')') naam, gme_vlag url 
	FROM gemeentes WHERE NOT gme_vlag IS NULL
) results
ORDER BY "mode" DESC, naam ASC
SQL;
      $statement = $this->dbh->prepare($sql);
      $statement->execute();
      ProgressBar::setFormatDefinition('custom', ' %current%/%max% -- %message% (%file%)');
      $progressBar = new ProgressBar($output, $countGME + $countPVE);
      $progressBar->setMessage("downloading flags from Wikimedia");
      $progressBar->start();
      $progressBar->setFormat('custom');
      while($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        //this is a bit silly, since we already fetched the URL from the database
        //but since cache is only generated very rarely, let's use simplicity over efficiency
        $flag = (new Flag())->setMode($row['mode'])->setUrl($row['url'], true);
        $progressBar->setMessage($row['naam']);
        $progressBar->setMessage(basename(rawurldecode($flag->getUrl()['path'])), 'file');
        if (!$flag->exists() || $force) {
          $flag->download();
        }
        $progressBar->advance();
      }
      $progressBar->finish();
      return Command::SUCCESS;
    }

    protected function configure(): void
    {
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'load flag even if it exists');
    }
}