<?php
/**
 * Date: 04/10/2018
 * Time: 18:19
 */

namespace App\Commands\SearchFieldCommands;


use App\Jobs\UpdateSearchFieldJob;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateSearchFieldCommand extends Command
{
    /**
     * @var UpdateSearchFieldJob
     */
    private $updateSearchFieldJob;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * UpdateTvShowsCommand constructor.
     * @param UpdateSearchFieldJob $updateSearchFieldJob
     */
    public function __construct(UpdateSearchFieldJob $updateSearchFieldJob, LoggerInterface $logger)
    {
        $this->updateSearchFieldJob = $updateSearchFieldJob;
        parent::__construct();
        $this->logger = $logger;
    }

    protected function configure()
    {
        $this->setName('jobs:searchfield:update');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try{
            $this->updateSearchFieldJob->updateSearchFieldByLanguages(['ja']);
        } catch (\Exception $e){
            $this->logger->error($e);
        }

    }
}