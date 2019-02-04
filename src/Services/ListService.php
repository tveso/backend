<?php
/**
 * Date: 18/12/2018
 * Time: 21:18
 */

namespace App\Services;


use App\Auth\UserService;
use App\EntityManager;
use App\Form\ListForm;
use App\Jobs\UpdateSearchFieldJob;
use App\Pipelines\FollowPipeline;
use App\Pipelines\ListPipeline;
use App\Pipelines\PipelineFactory;
use InvalidArgumentException;
use MongoDB\BSON\ObjectId;
use MongoDB\Model\BSONArray;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Exception\ValidatorException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ListService extends AbstractShowService
{

    /**
     * @var EntityManager
     */
    private $entityManager;
    /**
     * @var FindService
     */
    private $findService;
    /**
     * @var UserService
     */
    private $userService;
    private $user;

    private $limits = ['movies' => 100, 'tvshows' => 100, 'episodes' => 100, 'people' => 100];
    /**
     * @var ValidatorInterface
     */
    private $validator;
    /**
     * @var ListPipeline
     */
    private $listPipeline;
    /**
     * @var FollowPipeline
     */
    private $followPipeline;

    public function __construct(EntityManager $entityManager, FindService $findService, UserService $userService,
                                ValidatorInterface $validator, ListPipeline $listPipeline, FollowPipeline $followPipeline)
    {
        $this->entityManager = $entityManager;
        $this->findService = $findService;
        $this->userService = $userService;
        $this->user = $this->userService->getUser();
        $this->validator = $validator;
        $this->listPipeline = $listPipeline;
        $this->followPipeline = $followPipeline;
    }

    /**
     * @param array $opts
     * @return mixed
     * @throws \Exception
     */
    public function all($opts = [])
    {
        if (!isset($opts['page'])) {
            $opts['page'] = 1;
        }
        $pf = new PipelineFactory();
        $pf->add('common', ['filter', [$opts]], 'nondeleted')->add('list', 'resourcesCount', 'user', 'movies', 'tvshows',
            'episodes', 'people')->add('follow', ['follow', [$this->user->getId()]]);
        $pipeline = $pf->getPipeline();

        $data = $this->entityManager->aggregate($pipeline, [], 'lists');
        $data = FindService::bsonArrayToArray($data);
        $data = $this->mergeResults($data);
        return $data;
    }

    public function delete($id)
    {
        $this->checkUserOwnList($id);
        $updated = ['$set' => ['deleted' => true, 'deleted_at' => (new \DateTime())->getTimestamp()]];
        $this->entityManager->update(['_id' => new ObjectId($id)], $updated, 'lists');
    }

    /**
     * @param array $opts
     * @return mixed|\Traversable
     * @throws \Exception
     */
    public function loggedUserLists($opts = [])
    {
        return $this->userLists($this->user->getId(), $opts);
    }

    /**
     * @param $id
     * @param array $opts
     * @return mixed|\Traversable
     * @throws \Exception
     */
    public function userLists($id, $opts = [])
    {
        $query = [['$match' => ['user' => new ObjectId($id)]]];
        $query = new PipelineFactory($query);
        $query->add('common', ['filter', [$opts]])
            ->add('list', 'resourcesCount', 'user','tvshows','episodes','people', 'movies');
        $data = $this->entityManager->aggregate($query->getPipeline(), [], 'lists');
        $data = FindService::bsonArrayToArray($data);
        $data = $this->mergeResults($data);

        return $data;
    }

    private function updateSearchField($entity)
    {
        $name = $entity['title'];
        $name = UpdateSearchFieldJob::prepareString($name);
        for($i=1; $i<=strlen($name); $i++){
            $substr = utf8_encode(substr($name, 0, $i));
            $entity['search_title'][] = $substr;
        }

        return $entity;
    }

    public function get($id)
    {
        if ($id instanceof ObjectId) {
            $id = $id->__toString();
        }
        $query[] = ['$match' => ['_id' => new ObjectId($id)]];
        $query = $this->listPipeline->pipe($query, 'resourcesCount','user', 'movies', 'tvshows', 'episodes', 'people');
        $data = $this->entityManager->aggregate($query, [], 'lists');
        $data = FindService::bsonArrayToArray($data);
        if (empty($data)) {
            throw new InvalidArgumentException();
        }
        $data = $this->mergeResult($data[0]);

        return $data;
    }

    public function create(ListForm $listForm)
    {
        $list = json_decode(json_encode($listForm), 1);
        $this->validateLengthOfResources($listForm);
        $list['created_at'] = (new \DateTime())->format('Y-m-d');
        $list['user'] = new ObjectId($this->user->getId());
        $list['type'] = 'list';
        $list = $this->updateSearchField($list);

        $inserted = $this->entityManager->insert($list, 'lists');
        return $this->get($inserted->getInsertedId());
    }
    private function validateLengthOfResources(ListForm $listForm)
    {
        $errors = $this->validator->validate($listForm);
        if (count($errors) > 0) {
            throw new ValidatorException();
        }
    }

    public function edit($id, ListForm $listForm)
    {
        $this->checkUserOwnList($id);
        $list = json_decode(json_encode($listForm), 1);
        $list['updated_at'] = (new \DateTime())->format('Y-m-d');
        $list = $this->updateSearchField($list);
        $inserted = $this->entityManager->update(['_id'=> new ObjectId($id)], ['$set' =>$list], 'lists');
        return $this->get(new ObjectId($id));
    }

    public function addToList($resourceId, $listId, $type = 'movies')
    {
        $this->checkUserOwnList($listId);
        $list = $this->entityManager->findOneBy(['_id' => new ObjectId($listId)], 'lists');
        if (is_null($list)) {
            throw new BadRequestHttpException();
        }
        if (!array_key_exists($type, $list )) {
            throw new BadRequestHttpException();
        }
        if (!($list[$type] instanceof BSONArray)){
            throw new BadRequestHttpException();
        }
        $id = $this->processId($resourceId, $type);
        $list[$type][] =$id;
        $list[$type] = iterator_to_array($list[$type]);
        $list[$type] = array_unique($list[$type]);
        $this->entityManager->update(['_id' => new ObjectId($listId)], ['$set' => [$type => $list[$type]]], 'lists');

        return $list;
    }

    /**
     * @param $getId
     */
    private function checkUserOwnList($getId)
    {
        if (in_array('ROLE_ADMIN',$this->user->getRoles())){
            return;
        }
        $list = $this->entityManager->findOneBy(['_id' => new ObjectId($getId)], 'lists');
        if (is_null($list)) {
            throw new BadRequestHttpException();
        }
        $user = $list['user'];
        if(!($user->__toString() === $this->user->getId()->__toString())) {
            throw new BadRequestHttpException();
        }
    }


    private  function mergeResult($value)
    {
        $result = $value;
        foreach ($value['episodes'] as $key=>$episode) {
            $result['episodes'][$key] = ['_id' => $episode['_id'],
                'name' => $episode['name'], 'image' => $episode['season_poster_path'],'show' => $episode['show'],
                'season_number' => $episode['season_number'], 'episode_number' => $episode['episode_number']];
        }
        foreach ($value['movies'] as $key=>$movie) {
            $result['movies'][$key] = ['_id' => $movie['_id'],
                'title' => $movie['title'], 'image' => $movie['poster_path']];
        }
        foreach ($value['tvshows'] as $key=>$tvshow) {
            $result['tvshows'][$key] = ['_id' => $tvshow['_id'],
                'name' => $tvshow['name'], 'image' => $tvshow['poster_path'], 'duration' => $tvshow['duration']];
        }
        foreach ($value['people'] as $key=>$people) {
            $result['people'][$key] = ['_id' => $people['_id'],
                'name' => $people['name'], 'image' => $people['profile_path']];
        }

        return $result;
    }

    private function mergeResults($data)
    {
        foreach ($data as $k=>$value) {
           $data[$k] = $this->mergeResult($value);
        }

        return $data;
    }

    /**
     * @param string $id
     * @param array $opts
     * @param string $type
     * @return mixed
     * @throws \Exception
     */
    public function getShows(string $id, array $opts = [], $type ='movies')
    {
        $userId = $this->user->getId();
        $query[] = [
            '$match' => ['_id' => new ObjectId($id)]
        ];
        $query[] = [
            '$lookup' => [
                'from' => 'movies',
                'foreignField' => '_id',
                'localField' => $type,
                'as' => 'movies'
            ]
        ];
        $query[] = [
            '$unwind' => ['path' => '$movies', 'preserveNullAndEmptyArrays' => false]
        ];
        $query[] = [
            '$replaceRoot' => ['newRoot' => '$movies']
        ];
        $pipelineFactory = new PipelineFactory([]);
        $pipelineFactory->add('common', ['filter', [$opts]])
            ->add('movie', 'project')->add('common', ['rating', [$userId]], ['follow', [$userId]]);
        $postFilerPipelines = $pipelineFactory->getPipeline();
        $pipelines = array_merge($query, $postFilerPipelines);
        $data = $this->entityManager->aggregate($pipelines, [] , 'lists');
        $data = FindService::bsonArrayToArray($data);
        return  $data;
    }

    /**
     * @param string $id
     * @param array $opts
     * @param string $type
     * @return mixed
     * @throws \Exception
     */
    public function getPeople(string $id, array $opts = [])
    {
        $query[] = [
            '$match' => ['_id' => new ObjectId($id)]
        ];
        $query[] = [
            '$lookup' => [
                'from' => 'people',
                'foreignField' => '_id',
                'localField' => 'people',
                'as' => 'people'
            ]
        ];
        $query[] = [
            '$unwind' => ['path' => '$people', 'preserveNullAndEmptyArrays' => false]
        ];
        $query[] = [
            '$replaceRoot' => ['newRoot' => '$people']
        ];
        $pipelineFactory = new PipelineFactory([]);
        $pipelineFactory->add('common', ['filter', [$opts]])
            ->add('people', 'project');
        $postFilerPipelines = $pipelineFactory->getPipeline();
        $pipelines = array_merge($query, $postFilerPipelines);
        $data = $this->entityManager->aggregate($pipelines, [] , 'lists');
        $data = FindService::bsonArrayToArray($data);
        return  $data;
    }

    /**
     * @param string $id
     * @param array $opts
     * @return mixed|\Traversable
     * @throws \Exception
     */
    public function getEpisodes(string $id, array $opts = [])
    {
        $query[] = [
            '$match' => ['_id' => new ObjectId($id)]
        ];
        $query[] = [
            '$lookup' => [
                'from' => 'episodes',
                'foreignField' => '_id',
                'localField' => 'episodes',
                'as' => 'episodes'
            ]
        ];
        $query[] = [
            '$unwind' => ['path' => '$episodes', 'preserveNullAndEmptyArrays' => false]
        ];
        $query[] = [
            '$replaceRoot' => ['newRoot' => '$episodes']
        ];
        $pipelineFactory = new PipelineFactory([]);
        $pipelineFactory->add('common', ['filter', [$opts]], ['follow', [$this->user->getId()]]);
        $postFilerPipelines = $pipelineFactory->getPipeline();
        $pipelines = array_merge($query, $postFilerPipelines);
        $data = $this->entityManager->aggregate($pipelines, [] , 'lists');
        $data = FindService::bsonArrayToArray($data);
        return  $data;
    }

    private function processId($resourceId, string $type)
    {
        if ($type === 'people' or $type==='episodes') {
            $resourceId = intval($resourceId);
        }

        return $resourceId;
    }


}