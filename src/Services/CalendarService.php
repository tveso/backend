<?php
/**
 * Date: 30/09/2018
 * Time: 18:19
 */

namespace App\Services;


use App\Auth\User;
use App\Auth\UserService;
use App\EntityManager;
use App\Form\CommentForm;
use MongoDB\BSON\ObjectId;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Exception\ValidatorException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CalendarService extends AbstractShowService
{
    /**
     * @var EntityManager
     */
    private $entityManager;
    /**
     * @var ValidatorInterface
     */
    private $validator;
    /**
     * @var UserService
     */
    private $userService;
    /**
     * @var User|string
     */
    private $user;

    public function __construct(EntityManager $entityManager,
                                ValidatorInterface $validator,
                                UserService $userService)
    {
        $this->entityManager = $entityManager;
        $this->validator = $validator;
        $this->userService = $userService;
        $this->user = $userService->getUser();
    }

    public function getFrom(string $date, bool $includeTvshows, bool $includeMovies, bool $onlyUserFollowed)
    {
        $result = ['episodes' => [], 'date' => $date, 'movies' => []];
        if($includeTvshows) {
            $result['episodes'] =  $this->getTvshowsFrom($date, $onlyUserFollowed);
        }
        if($includeMovies) {
            $result['movies'] = $this->getMoviesReleasedFrom($date);
        }

        return $result;
    }

    private function getTvshowsFrom(string $date, bool $onlyUserFollowed)
    {
        if($onlyUserFollowed) {
            return $this->getUserTvshowsFollowedFrom($date);
        }
        return $this->getAllEpisodesFrom($date);

    }

    public function getUserTvshowsFollowedFrom(string $date = null, string $minDate = null, string  $maxDate = null)
    {
        $query = [];
        $query[] = ['$match' => ['user' => new ObjectId($this->user->getId()),'type' => 'tvshow']];
        $query[] = ['$lookup' => ['from' => 'movies', 'localField' => 'show', 'foreignField' => '_id', 'as' => 'showDocument']];
        $query[] = ['$unwind' => ['path' => '$showDocument', 'preserveNullAndEmptyArrays' => false]];
        $query[] = ['$replaceRoot' => ['newRoot' => '$showDocument']];
        $query[] = ['$lookup' => [
            'from' => 'episodes',
            'localField' => 'id',
            'foreignField' => 'show_id',
            'as' => 'episodes'
        ]];
        $query[] = ['$unwind' => ['path' => '$episodes', 'preserveNullAndEmptyArrays' => false]];
        $query[] = ['$replaceRoot' => ['newRoot' => '$episodes']];
        if (!is_null($date)) {
            $query[] = ['$match' => ['air_date' => $date]];
        }
        if(!is_null($minDate) and !is_null($maxDate)) {
            $query[] = ['$match' => ['air_date' => ['$gte' => $minDate, '$lte' => $maxDate]]];
        }
        $userDataPipeline = array_merge($this->addEpisodeShowName(),$this->addUserRatingPipeLine($this->user->getId()),
            $this->addFollowPipeLine($this->user->getId()));
        $pipeline = array_merge($query, $userDataPipeline);
        $pipeline[] = ['$sort' => ['show.popularity' => -1]];
        $data = $this->entityManager->aggregate($pipeline, [], 'follows');
        return FindService::bsonArrayToArray($data);
    }

    private function getAllEpisodesFrom(string $date)
    {
        $query = [];
        $query[] = ['$match' => ['air_date' => $date]];
         $query[] = ['$project' => ['crew' => 0, 'cast' => 0, 'guest_stars' => 0]];
        $userDataPipeline = array_merge($this->addEpisodeShowName(),$this->addUserRatingPipeLine($this->user->getId()),
            $this->addFollowPipeLine($this->user->getId()));
        $pipeline = array_merge($query, $userDataPipeline);
        $pipeline[] = ['$sort' => ['show.popularity' => -1]];
        $data = $this->entityManager->aggregate($pipeline, [], 'episodes');
        return FindService::bsonArrayToArray($data);
    }

    private function getMoviesReleasedFrom(string $date)
    {
        $query = [];
        $query[] = ['$match' => ['release_date' => $date]];
        $userDataPipeline = array_merge($this->addUserRatingPipeLine($this->user->getId()),
            $this->addFollowPipeLine($this->user->getId()), $this->getProjection());
        $pipeline = array_merge($query, $userDataPipeline);
        $data = $this->entityManager->aggregate($pipeline, [], 'movies');
        return FindService::bsonArrayToArray($data);
    }

    public function getTvshowsEpisodesFrom(array $include, $minDate, $maxDate)
    {
        $query = [];
        $query[] = ['$match' => ['air_date' => ['$gte' => $minDate, '$lte' => $maxDate], 'show_id' => ['$in' => $include]]];
        $userDataPipeline = array_merge($this->addEpisodeShowName(),$this->addUserRatingPipeLine($this->user->getId()),
            $this->addFollowPipeLine($this->user->getId()));
        $pipeline = array_merge($query, $userDataPipeline);
        $pipeline[] = ['$sort' => ['show.popularity' => -1]];
        $data = $this->entityManager->aggregate($pipeline, [], 'episodes');

        return FindService::bsonArrayToArray($data);
    }


}