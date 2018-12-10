<?php
/**
 * Date: 16/10/2018
 * Time: 3:03
 */

namespace App\Services;


use App\Auth\UserService;
use Symfony\Component\Stopwatch\Stopwatch;

class ShowService extends AbstractShowService
{

    /**
     * @var FindService
     */
    private $findService;
    /**
     * @var \App\Auth\User|object|string
     */
    private $user;
    /**
     * @var UserService
     */
    private $userService;

    public function __construct(FindService $findService, UserService $userService)
    {

        $this->findService = $findService;
        $this->userService = $userService;
        $this->user = $userService->getUser();
    }


    public function setUserDataIntoShows($shows, array $pipelines = [], $addLimit = true)
    {
        $ids = $this->getIds($shows);
        $query['pipelines']= [
            ['$match'=> ['_id' => ['$in' => $ids]]]
        ];
        $query['pipelines'] = array_merge($query['pipelines'], $pipelines,
            $this->addUserRatingPipeLine($this->user->getId()),
                $this->addFollowPipeLine($this->user->getId()));
        if($addLimit) {
            $query['pipelines'] = array_merge($query['pipelines'], $this->addLimitPipeline());
        }
        return $this->findService->all($query);

    }

    public function filter(array $opts = [])
    {
        $page = ($opts["page"]) ?? 1;
        $limit = min(($opts["limit"]) ?? 30,100);
        unset($opts['limit']);
        $sort = ($opts["sort"]) ?? 'popularity';
        $opts['pipelines']= array_merge($this->addSortPipeline($sort), $this->addLimitPipeline($limit, $page),
            $this->getProjection(),$this->addUserRatingPipeLine($this->user->getId()),
            $this->addFollowPipeLine($this->user->getId()));
        $opts['pipe_order'] = ['$match' => 6, '$sort' => 4,'$project' => 3];
        $data = $this->findService->all($opts, 'movies');

        return  $data;
    }

    private function getIds($shows)
    {
        if(!is_array($shows)){
            $shows = iterator_to_array($shows);
        }
        $result = array_map(function($a){
            return $a["_id"];
        }, $shows);

        return array_unique($result);
    }

    /**
     * @param $ids
     * @return false|mixed
     */
    public function getManyById($ids)
    {
        $query['pipelines'] = [['$match' => ['_id'=> ['$in'=> $ids]]]];

        return $this->findService->allCached($ids);
    }

    public function setUserDataPipeline($data)
    {
        return  array_merge($data, $this->addUserRatingPipeLine($this->user->getId()),
            $this->addFollowPipeLine($this->user->getId()));
    }
}