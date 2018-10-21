<?php
/**
 * Date: 23/09/2018
 * Time: 0:29
 */

namespace App\Commands;


use App\Jobs\UpdateTmdbJobs\UpdateMoviesJob;
use App\Jobs\UpdateTmdbJobs\UpdateTvShowsJob;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GetLatestTvshowsCommand extends Command
{


    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var UpdateTvShowsJob
     */
    private $updateTvShowsJob;

    /**
     * UpdateTvShowsCommand constructor.
     * @param UpdateTvShowsJob $updateTvShowsJob
     * @param LoggerInterface $logger
     */
    public function __construct(UpdateTvShowsJob $updateTvShowsJob, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->updateTvShowsJob = $updateTvShowsJob;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('jobs:tvshows:update:latests');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try{
            $this->updateTvShowsJob->getLatestTvshows();
        } catch (\Exception $e){
            echo $e->getMessage()."\n";
            $this->logger->error($e->__toString());
        }

    }
}