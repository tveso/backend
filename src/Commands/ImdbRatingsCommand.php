<?php
/**
 * Date: 23/09/2018
 * Time: 0:29
 */

namespace App\Commands;


use App\Jobs\ImdbRatingsJob;
use App\Jobs\UpdateTmdbJobs\UpdateMoviesJob;
use App\Jobs\UpdateTmdbJobs\UpdateTvShowsJob;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImdbRatingsCommand extends Command
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
     * @var ImdbRatingsJob
     */
    private $imdbRatingsJob;

    /**
     * UpdateTvShowsCommand constructor.
     * @param UpdateMoviesJob $updateMoviesJob
     * @param LoggerInterface $logger
     */
    public function __construct(ImdbRatingsJob $imdbRatingsJob, LoggerInterface $logger)
    {
        $this->imdbRatingsJob = $imdbRatingsJob;
        $this->logger = $logger;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('jobs:imdb:ratings');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try{
            $this->imdbRatingsJob->action();
        } catch (\Exception $e){
            echo $e->getMessage()."\n";
            $this->logger->error($e->getMessage());
        }

    }
}