<?php
/**
 * Date: 16/10/2018
 * Time: 3:03
 */

namespace App\Services;


use App\Auth\UserService;

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


    public function setUserDataIntoShows($shows, array $pipelines = [])
    {
        $ids = $this->getIds($shows);
        $query['pipelines']= [
            ['$match'=> ['_id' => ['$in' => $ids]]]
        ];
        $query['pipelines'] = array_merge($query['pipelines'], $pipelines,
            $this->addUserRatingPipeLine($this->user->getId()),
                $this->addFollowPipeLine($this->user->getId()),
            $this->addLimitPipeline());

        return $this->findService->all($query);
    }

    public function filter(array $opts = [])
    {
        $page = ($opts["page"]) ?? 1;
        $limit = min(($opts["limit"]) ?? 30,100);
        $sort = ($opts["sort"]) ?? 'popularity';
        $opts['pipelines']= array_merge($this->addSortPipeline($sort), $this->addLimitPipeline($limit, $page), $this->getProjection());
        $opts['pipe_order'] = ['$match' => 6, '$sort' => 4,'$project' => 3];
        $data = $this->findService->all($opts, 'movies');
        $data = $this->setUserDataIntoShows($data, array_merge($this->getProjection(),$this->addSortPipeline($sort)));
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
}