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
    Command, LockableTrait
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
    use LockableTrait;

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
        $crawler = new ConsoleCrawler($url, $output);

        if (!filter_var('http://gummisig.com/javascript:showallposts', FILTER_VALIDATE_URL)) {
            $output->writeln('<error>Entered url: ' . $url . ' is not valid. Stopping...</error>');
            return 0;
        }

        try {
            $output->writeln('<info>Start parsing urls at site ' . $crawler->getBaseUrl() . ' ...</info>');
            $crawler->crawlSite();
            if ($crawler->getFoundLinksCount() > 0) {
                $output->writeln('<info>Parsing links successfully completed. Found ' . $crawler->getFoundLinksCount() . ' links</info>');

                $output->writeln('<info>Start saving texts ...</info>');
                $progressBar = new ProgressBar($output, $crawler->getFoundLinksCount());
                $progressBar->start();
                foreach ($crawler->getFoundLinks() as $link) {
                    $textSaver = new TextSaver($link, $output);
                    $textSaver->save($crawler->getBaseHost());
                    $progressBar->advance(1);
                    $progressBar->setMessage($link, 'Saveing page: ');
                }
                $progressBar->finish();
                $output->writeln(PHP_EOL);
                $output->writeln('<info>Saving texts completed...</info>');
            } else {
                $output->writeln('<error>Parsing links completed. Found ' . $crawler->getFoundLinksCount() . ' links. Check base url.</error>');
            }
        } catch (Exception $exception) {
            $output->writeln('<error>' . $exception->getMessage() . '</error>');
        }

        return 0;
    }
}