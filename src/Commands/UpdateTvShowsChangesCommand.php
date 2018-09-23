<?php
/**
 * Date: 23/09/2018
 * Time: 0:29
 */

namespace App\Commands;


use App\Jobs\UpdateTmdbJobs\UpdateTvShowsJob;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateTvShowsChangesCommand extends Command
{
    /**
     * @var UpdateTvShowsJob
     */
    private $updateTvShowsJob;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * UpdateTvShowsCommand constructor.
     * @param UpdateTvShowsJob $updateTvShowsJob
     */
    public function __construct(UpdateTvShowsJob $updateTvShowsJob, LoggerInterface $logger)
    {
        $this->updateTvShowsJob = $updateTvShowsJob;
        $this->logger = $logger;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('jobs:tvshows:update:changes')->addArgument('startdate', InputArgument::OPTIONAL,
            'Start Date')->addArgument('enddate', InputArgument::OPTIONAL,
            'End Date');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try{
            $startDate = $input->getArgument('startdate');
            $endDate = $input->getArgument('enddate');
            $this->updateTvShowsJob->updateByTMdbDates($startDate,$endDate);
        } catch (\Exception $e){
            $this->logger->error($e->getMessage());
        }

    }
}