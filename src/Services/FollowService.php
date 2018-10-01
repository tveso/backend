<?php
/**
 * Date: 28/09/2018
 * Time: 18:05
 */

namespace App\Services;


use App\Entity\Movie;
use App\Entity\TvShow;
use App\EntityManager;
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
     * @var object|string
     */
    private $user;


    /**
     * TvShowService constructor.
     * @param FindService $findService
     * @param EntityManager $entityManager
     * @param TokenStorageInterface $token
     */
    public function __construct(FindService $findService, EntityManager $entityManager, TokenStorageInterface $token)
    {
        $this->findService = $findService;
        $this->entityManager = $entityManager;
        $this->user = $token->getToken()->getUser();
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
        $userId = $this->user->getId();
        $userFollowModes = $this->user->get("{$type}s")->get('following');
        $values = $this->updateShowFollowState($userFollowModes->getData(), $id, $mode);
        $updates = $this->entityManager
            ->update(["_id"=> $userId], ['$set' => ["data.{$type}s.following" => $values]], 'users');

        return $updates->getModifiedCount()>0;
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

    private function updateShowFollowState(array $shows, string $id, string $mode)
    {
        if($mode === 'cancel') {
            unset($shows[$id]);

            return $shows;
        }
        $shows[$id] = $mode;

        return $shows;
    }


}