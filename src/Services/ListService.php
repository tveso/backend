<?php
/**
 * Date: 18/12/2018
 * Time: 21:18
 */

namespace App\Services;


use App\Auth\UserService;
use App\EntityManager;
use App\Form\ListForm;
use App\Pipelines\FollowPipeline;
use App\Pipelines\ListPipeline;
use App\Pipelines\PipelineFactory;
use InvalidArgumentException;
use MongoDB\BSON\ObjectId;
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

    public function all($opts = [])
    {
        if (!isset($opts['page'])) {
            $opts['page'] = 1;
        }
        $opts['pipelines'] = $this->listPipeline->pipe([], 'resourcesCount','user', 'movies', 'tvshows');
        $opts['pipelines'] = $this->followPipeline->pipe($opts['pipelines'], ['follow', [$this->user->getId()]]);
        $data = $this->findService->all($opts, 'lists');
        $data = $this->mergeResults($data);
        return $data;
    }

    public function userLists($opts = [])
    {
        $opts['pipelines'] = [['$match' => ['user' => new ObjectId($this->user->getId())]]];
        $data = $this->all($opts);

        return $data;
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

    private function findOne($id)
    {
        if ($id instanceof ObjectId) {
            $id = $id->__toString();
        }
        $query[] = ['$match' => ['_id' => new ObjectId($id)]];
        $data = $this->entityManager->aggregate($query, [], 'lists');
        $data = FindService::bsonArrayToArray($data);
        if (empty($data)) {
            throw new InvalidArgumentException();
        }

        return $data[0];
    }

    public function create(ListForm $listForm)
    {
        $list = json_decode(json_encode($listForm), 1);
        $this->validateLengthOfResources($listForm);
        $list['created_at'] = (new \DateTime())->format('Y-m-d');
        $list['user'] = new ObjectId($this->user->getId());
        $list['type'] = 'list';

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

    public function edit(ListForm $listForm)
    {
        $this->checkUserOwnList($listForm->getId());
        $list = json_decode(json_encode($listForm), 1);
        $list['updated_at'] = (new \DateTime())->format('Y-m-d');
        $list['user'] = new ObjectId($this->user->getId());
        $list['_id'] = new ObjectId();

        $this->entityManager->update(['_id'=> new ObjectId($listForm->getId())], ['$set' =>$list], 'lists');
    }

    private function checkUserOwnList($getId)
    {
        $list = $this->entityManager->findOneBy(['_id' => new ObjectId($getId)], 'lists');
        if (!is_null($list)) {
            throw new BadRequestHttpException();
        }
        $user = $list['user'];

        return $user->toString() === $this->user->getId();
    }

    public function delete(string $id)
    {
        $this->checkUserOwnList($id);

        $this->entityManager->delete(['_id' => new ObjectId($id)], 'lists');
    }

    private  function mergeResult($value)
    {
        $result = $value;
        foreach ($value['episodes'] as $key=>$episode) {
            $result['episodes'][$key] = ['_id' => $episode['_id'],
                'name' => $episode['name'], 'image' => $episode['season_poster_path']];
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
    public function getShows(string $id, array $opts = [], string $type = 'movies')
    {
        $userId = $this->user->getId();
        $list = $this->findOne($id);
        $query = [['$match' => ['_id' => ['$in' => $list[$type]]]]];
        $pipelineFactory = new PipelineFactory([]);
        $pipelineFactory->add('common', ['filter', [$opts]])
            ->add('movie', 'project')->add('common', ['rating', [$userId]], ['follow', [$userId]]);
        $postFilerPipelines = $pipelineFactory->getPipeline();
        $pipelines = array_merge($query, $postFilerPipelines);
        $data = $this->entityManager->aggregate($pipelines, [] , 'movies');
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
        $list = $this->findOne($id);
        $query = [['$match' => ['_id' => ['$in' => $list['people']]]]];
        $pipelineFactory = new PipelineFactory([]);
        $pipelineFactory->add('common', ['filter', [$opts]])
            ->add('people', 'project');
        $postFilerPipelines = $pipelineFactory->getPipeline();
        $pipelines = array_merge($query, $postFilerPipelines);
        $data = $this->entityManager->aggregate($pipelines, [] , 'people');
        $data = FindService::bsonArrayToArray($data);
        return  $data;
    }




}