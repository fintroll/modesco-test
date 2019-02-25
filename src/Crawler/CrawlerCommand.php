<?php
/**
 * Created by PhpStorm.
 * User: Work
 * Date: 23.02.2019
 * Time: 1:36
 */

namespace Console\Crawler;

use Exception;
use Symfony\Component\Console\Command\{
    Command
};
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\{
    InputArgument, InputInterface
};
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CrawlerCommand
 * @package Console\Crawler
 */
class CrawlerCommand extends Command
{

    protected function configure()
    {
        $this->setName('parse')
            ->setDescription('Test exercise for middle PHP developer position at Modesco company')
            ->setHelp('Parsing a site')
            ->addArgument('url', InputArgument::REQUIRED, 'Pass the url.')
            ->addArgument('depth', InputArgument::OPTIONAL, 'Depth of parsing');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln([
            'Web crawler',
            '============',
            '',
        ]);

        $url = $input->getArgument('url');
        $depth = $input->getArgument('depth');

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $output->writeln('<error>Entered url: ' . $url . ' is not valid. Stopping...</error>');
            return 0;
        }

        $crawler = new ConsoleCrawler($url, $output, $depth);
        try {
            $timeStart = microtime(true);
            $output->writeln('<info>Start parsing urls at site ' . $crawler->getBaseUrl() . ' ...</info>');
            $crawler->crawlSite();
            $totalLinksCount = $crawler->getFoundLinksCount();
            $savedLinksCount = 0;
            $failedLinks = [];
            if ($totalLinksCount > 0) {
                $output->writeln('<info>Parsing links successfully completed. Found ' . $crawler->getFoundLinksCount() . ' links</info>');
                $output->writeln('<info>Start saving texts ...</info>');
                $progressBar = new ProgressBar($output, $crawler->getFoundLinksCount());
                $progressBar->start();
                foreach ($crawler->getFoundLinks() as $link) {
                    $textSaver = new TextSaver($link, $output);
                    if ($textSaver->save($crawler->getBaseHost())) {
                        $savedLinksCount++;
                    } else {
                        $failedLinks[$link] = $textSaver->getErrorMessage();
                    }
                    $progressBar->advance(1);
                    $progressBar->setMessage($link, 'Saving page: ');
                }
                $timeEnd = microtime(true);
                $scriptExecutionTime = $timeEnd - $timeStart;
                $progressBar->finish();
                $output->writeln(PHP_EOL);
                $output->writeln('<info>Saving texts completed.</info>');
                $output->writeln('<info>Command execution time: ' . gmdate("H:i:s", (int)$scriptExecutionTime) . '</info>');
                $output->writeln('<info>Successfully processed links: ' . $savedLinksCount . '</info>');
                if ($output->isVerbose()) {
                    $output->writeln('<info>Failed links: </info>');
                    foreach ($failedLinks as $failedLink => $error) {
                        $output->writeln($failedLink . ' : ' . $error);
                    }
                }
            } else {
                $output->writeln('<error>Parsing links completed. Found ' . $totalLinksCount . ' links. Check base url.</error>');
            }
        } catch (Exception $exception) {
            $output->writeln('<error>' . $exception->getMessage() . '</error>');
        }

        return 0;
    }
}