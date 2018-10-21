<?php
/**
 * Date: 28/09/2018
 * Time: 18:05
 */

namespace App\Services;


use App\Auth\User;
use App\Auth\UserService;
use App\Entity\Movie;
use App\Entity\TvShow;
use App\EntityManager;
use MongoDB\BSON\ObjectId;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class FollowService
{
    /**
     * @var FindService
     */
    private $findService;
    /**
     * @var EntityManager
     */
    private $entityManager;
    /**
     * @var User|string
     */
    private $user;
    /**
     * @var UserService
     */
    private $userService;


    /**
     * TvShowService constructor.
     * @param FindService $findService
     * @param EntityManager $entityManager
     * @param UserService $userService
     */
    public function __construct(FindService $findService, EntityManager $entityManager, UserService $userService)
    {
        $this->findService = $findService;
        $this->entityManager = $entityManager;
        $this->user = $userService->getUser();
        $this->userService = $userService;
    }

    /**
     * @param string $id
     * @param string $type
     * @param null|string $mode
     * @return bool
     * @throws \Exception
     */
    public function follow(string $id, ?string $mode)
    {
        $type = $this->checkShowExists($id);
        if($type === 'movie') {
            $this->checkValidMode($mode, Movie::FOLLOW_MODES);
        }
        if($type === 'tvshow') {
            $this->checkValidMode($mode, TvShow::FOLLOW_MODES);
        }
        $mode = strtolower($mode);
        $values = $this->updateShowFollowState($id, $mode);

        return $values;
    }


    public function findShow(array $entity, string $id )
    {

    }

    /**
     * @param string $mode
     * @param array $availableModes
     * @throws \Exception
     */
    private function checkValidMode(string $mode, array $availableModes = [])
    {
        $mode = strtolower($mode);
        if(!in_array($mode, $availableModes) and $mode !== 'cancel'){
            throw new \Exception("'$mode' is not a valid mode");
        }
    }

    /**
     * @param string $id
     * @param string $mode
     * @return
     * @throws \Exception
     */
    private function checkShowExists(string $id)
    {
        $entities = $this->entityManager->find(["_id" => $id], 'movies')->toArray();
        if(empty($entities)){
            throw new \Exception("$id tvshow was not found");
        }

        return $entities[0]['type'];
    }

    private function updateShowFollowState(string $id, string $mode)
    {
        $data = $this->entityManager->findOneBy(['user'=> $this->user->getId(), 'show' => $id], 'follows');
        if($mode === 'cancel') {
            if(is_null($data)) {
                return 0;
            }
            return $this->entityManager->delete(["_id"=> $data["_id"]],'follows')->getDeletedCount();
        }
        $data["updated_at"] = (new \DateTime())->getTimestamp();
        $data["mode"] = $mode;
        $data["show"] = $id;
        $data["user"] = $this->user->getId();
        if(!isset($data["_id"])){
            $data["_id"] = new ObjectId();
        }
        $updated = $this->entityManager->replace($data, 'follows');

        return $updated->getModifiedCount();
    }


}