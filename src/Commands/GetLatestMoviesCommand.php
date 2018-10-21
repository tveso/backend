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

class GetLatestMoviesCommand extends Command
{

    /**
     * @var UpdateMoviesJob
     */
    private $updateMoviesJob;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * UpdateTvShowsCommand constructor.
     * @param UpdateMoviesJob $updateMoviesJob
     * @param LoggerInterface $logger
     */
    public function __construct(UpdateMoviesJob $updateMoviesJob, LoggerInterface $logger)
    {
        $this->updateMoviesJob = $updateMoviesJob;
        $this->logger = $logger;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('jobs:movies:update:latests');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try{
            $this->updateMoviesJob->getLatestMovies();
        } catch (\Exception $e){
            echo $e->getMessage()."\n";
            $this->logger->error($e->getMessage());
        }

    }
}