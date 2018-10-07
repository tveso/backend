<?php


namespace App\Services;


use App\Auth\UserService;
use App\EntityManager;
use App\Jobs\UpdateSearchFieldJob;
use App\Util\FindQueryBuilder;
use MongoDB\BSON\Regex;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;


class FindService extends AbstractShowService
{

    /** @Inject()
     * @var EntityManager
     */
    private $entityManager;
    /**
     * @var \App\Auth\User|string
     */
    private $user;
    /**
     * @var UserService
     */
    private $userService;

    /**
     * FindService constructor.
     * @param EntityManager $entityManager
     * @param UserService $userService
     */
    public function __construct(EntityManager $entityManager, UserService $userService)
    {
        $this->entityManager = $entityManager;
        $this->user = $userService->getUser();
        $this->userService = $userService;
    }

    public function filter(array $opts = [])
    {
        $opts['pipelines'][]['$project'] = FindQueryBuilder::getSimpleProject();
        $opts['pipelines'][] = $this->addUserRatingPipeLine($this->user->getId());

        return  $this->all($opts);
    }


    public function all(array $opts = [])
    {
        $qb = new FindQueryBuilder($opts);
        $pipeline = $qb->build();

        $collection = $this->entityManager->getCollection('movies');
        $searched = iterator_to_array($collection->aggregate($pipeline));

        return  $searched;
    }

    public function search(string $string, $limit = 100, $page = 1)
    {
        $results = $this->textSearch($string, $limit, $page);
        if(empty($results)){
            $results = $this->patternSearch($string,$limit,$page);
        }
        return $results;
    }



    public function patternSearch(string $string, $limit = 100, $page = 1)
    {
        $options = [];
        $string = UpdateSearchFieldJob::prepareString($string);
        $regexBody = new Regex("^$string",'im');
        $search = ["stitle"=> ['$regex' => $regexBody]];
        $options["sort"] = ["popularity" => -1, "rating.numVotes" => -1];
        $options["pipelines"]['$project'] = FindQueryBuilder::getSimpleProject();
        $options["skip"] = $skip = ($page-1)*$limit;
        $options["limit"] = $limit;
        $collection = $this->entityManager->getCollection('movies');

        return $collection->find($search,$options)->toArray();
    }




    private function textSearch($string, $limit, $page)
    {
        $options = [];
        $string =  UpdateSearchFieldJob::prepareString($string);
        $string = "\"$string\"";
        $search = ['$text' => ['$search'=>$string]];
        $options["sort"] = ["textScore" => -1, "rating.numVotes" => -1];
        $options["projection"] = ['score' => ['$meta' => "textScore"]] + FindQueryBuilder::getSimpleProject();
        $options["skip"] = $skip = ($page-1)*$limit;
        $options["limit"] = $limit;
        $collection = $this->entityManager->getCollection('movies');

        return $collection->find($search,$options)->toArray();
    }


}