<?php
/**
 * Date: 23/09/2018
 * Time: 0:29
 */

namespace App\Commands;


use App\Jobs\UpdateTmdbJobs\UpdateMoviesJob;
use App\Jobs\UpdateTmdbJobs\UpdatePeopleJob;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdatePeopleFromShowsCommand extends Command
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
     * @var UpdatePeopleJob
     */
    private $updatePeopleJob;

    /**
     * UpdateTvShowsCommand constructor.
     * @param UpdatePeopleJob $updatePeopleJob
     * @param LoggerInterface $logger
     */
    public function __construct(UpdatePeopleJob $updatePeopleJob, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->updatePeopleJob = $updatePeopleJob;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('jobs:people:latest');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try{
            $this->updatePeopleJob->updateFromLastPersonId();
        } catch (\Exception $e){
            echo $e->getMessage()."\n";
            $this->logger->error($e->getMessage());
        }

    }
}