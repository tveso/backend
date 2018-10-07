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

class CommentsService
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

    /**
     * @param CommentForm $commentForm
     * @return mixed
     * @throws \Exception
     */
    public function add(CommentForm $commentForm)
    {
        $this->checkAuthUser();
        $this->checkValidation($commentForm);
        $comment = [];
        $comment['text'] = htmlentities($commentForm->getText());
        $comment['parent'] = $commentForm->getParent();
        $comment['date'] = (new \DateTime())->getTimestamp();
        $comment["likes"] = [];
        $comment["dislikes"] = [];
        $comment["_id"] = (new ObjectId())->__toString();
        $comment["author"] = $this->user->getId();

        return $this->entityManager->insert($comment, 'comments')->getInsertedId();

    }

    /**
     * @throws \Exception
     */
    private function checkAuthUser()
    {
        if(!$this->user instanceof User){
            throw new ValidatorException();
        }
    }

    /**
     * @param CommentForm $commentForm
     * @throws \Exception
     */
    private function checkValidation(CommentForm $commentForm)
    {
        $errors = $this->validator->validate($commentForm);
        if(count($errors) > 0){
            throw new ValidatorException();
        }
    }

    public function getAll(string $id, int $page = 1)
    {
        $userId = $this->user->getId();
        $limit = 100;
        $skip = ($page-1)*$limit;
        $array = [];
        $array[] = [
            '$graphLookup' =>
                [
                    "from" => "comments",
                    "startWith" => '$_id',
                    "connectFromField" => 'parent',
                    'connectToField' => '_id',
                    'as' => 'parents',
                    'maxDepth' => 4
                ],
        ];
        $array[] = [
            '$match' => ["parents.parent" => $id,
            ],
        ];
        $array[] = [
            '$lookup' =>
                [
                    'from' => 'users',
                        'localField' => 'author',
                        'foreignField' => '_id',
                    'as' => 'author'
                ]
        ];
        $array[] = [
            '$unwind' => '$author'
        ];
        $array[] = [
            '$addFields' => [
                "userLike" => ['$in' => [$userId,'$likes']],
                "userDislike" => ['$in' => [$userId, '$dislikes']]
            ]
        ];
        $array[] = [
            '$project' =>
            [
                "_id" => 1,
                "text" => 1,
                "date" => 1,
                "parent"=> 1,
                "like"=> 1,
                "likes"=>[ '$size'=>'$likes'],
                "dislikes" => ['$size'=>'$dislikes'],
                "puntuation" => ['$subtract'=> [['$size'=> '$likes'],['$size'=> '$dislikes']]],
                "author._id"=> 1,
                "author.username"=>1,
                "userLike" => 1,
                "userDislike" => 1
            ]
        ];
        $array[] = ['$skip' => $skip];
        $array[] =['$limit'=> $limit];
        $data = $this->entityManager->aggregate($array, [], 'comments');


        return iterator_to_array($data);
    }

    public function dislike(string $id)
    {
        $comment = $this->entityManager->findOnebyId($id, 'comments');
        if(is_null($comment)){
            throw new \InvalidArgumentException();
        }
        $update = [];
        $userId = $this->user->getId();
        $likes = iterator_to_array($comment["likes"]);
        $dislikes = iterator_to_array($comment["dislikes"]);
        $liked = in_array($userId, $likes);
        $alreadyDisliked = in_array($userId, $dislikes);
        if($liked  or (!$alreadyDisliked && !$liked)) {
            $update["likes"] = array_diff($likes, [$userId]);
            $update["dislikes"][] = $userId;
        }
        if($alreadyDisliked) {
            $update["dislikes"] = array_diff($dislikes, [$userId]);
        }
        $result = ['$set'=> $update];
        $queryResult = $this->entityManager->update(["_id"=> $id], $result, 'comments');

        return $queryResult->getModifiedCount();
    }

    public function like(string $id)
    {
        $comment = $this->entityManager->findOnebyId($id, 'comments');
        if(is_null($comment)) {
            throw new \InvalidArgumentException();
        }
        $comment = $comment->getArrayCopy();
        $update = [];
        $userId = $this->user->getId();
        $likes = iterator_to_array($comment["likes"]);
        $dislikes = iterator_to_array($comment["dislikes"]);
        $alreadyLiked = in_array($userId, $likes);
        $disliked = in_array($userId, $dislikes);
        if($alreadyLiked) {
            $update["likes"] = array_diff($likes, [$userId]);
        }
        if($disliked or (!$alreadyLiked && !$disliked)) {
            $update["likes"][] = $userId;
            $update["dislikes"] = array_diff($dislikes, [$userId]);
        }
        $result = ['$set'=> $update];

        $queryResult = $this->entityManager->update(["_id"=> $id], $result, 'comments');

        return $queryResult->getModifiedCount();
    }

    public function delete(string $id, bool $fullDelete = false)
    {
        $this->userHasRole('ROLE_ADMIN');
        if($fullDelete){
            return $this->entityManager->delete(["_id"=> $id],'comments');
        }
        return $this->entityManager->update(["_id"=> $id],['$set'=> ['deleted' => true]], 'comments');

    }


}