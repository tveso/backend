<?php
/**
 * Date: 23/09/2018
 * Time: 0:29
 */

namespace App\Commands;


use App\Jobs\UpdateTmdbJobs\UpdateMoviesJob;
use App\Jobs\UpdateTmdbJobs\UpdateNowPlayingMoviesJob;
use App\Jobs\UpdateTmdbJobs\UpdateTvShowsJob;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateNowPlayingCommand extends Command
{

    /**
     * @var UpdateNowPlayingMoviesJob;
     */
    private $updateNowPlayingMoviesJob;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * UpdateTvShowsCommand constructor.
     * @param UpdateNowPlayingMoviesJob $updateNowPlayingMoviesJob
     * @param LoggerInterface $logger
     */
    public function __construct(UpdateNowPlayingMoviesJob $updateNowPlayingMoviesJob, LoggerInterface $logger)
    {
        $this->updateNowPlayingMoviesJob = $updateNowPlayingMoviesJob;
        $this->logger = $logger;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('jobs:movies:update:nowplaying');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try{
            $this->updateNowPlayingMoviesJob->update();
        } catch (\Exception $e){
            $this->logger->error($e->getMessage());
        }

    }
}