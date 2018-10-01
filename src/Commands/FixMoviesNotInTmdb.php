<?php
/**
 * Date: 23/09/2018
 * Time: 0:29
 */

namespace App\Commands;


use App\Jobs\UpdateSearchFieldJob;
use App\Jobs\UpdateTmdbJobs\FixDataJob;
use App\Jobs\UpdateTmdbJobs\UpdateMoviesJob;
use App\Jobs\UpdateTmdbJobs\UpdateTvShowsJob;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FixMoviesNotInTmdb extends Command
{

    /**
     * @var FixDataJob
     */
    private $fixDataJob;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var UpdateSearchFieldJob
     */
    private $updateSearchFieldJob;

    /**
     * UpdateTvShowsCommand constructor.
     * @param FixDataJob $fixDataJob
     * @param LoggerInterface $logger
     */
    public function __construct(FixDataJob $fixDataJob, LoggerInterface $logger, UpdateSearchFieldJob $updateSearchFieldJob)
    {
        $this->logger = $logger;
        $this->fixDataJob = $fixDataJob;
        $this->updateSearchFieldJob = $updateSearchFieldJob;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('jobs:fix:notintmdb');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try{
            $this->fixDataJob->missingIds();
        } catch (\Exception $e){
            $this->logger->error($e);
        }

    }
}