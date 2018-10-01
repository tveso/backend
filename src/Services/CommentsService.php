<?php
/**
 * Date: 30/09/2018
 * Time: 18:19
 */

namespace App\Services;


use App\Auth\User;
use App\EntityManager;
use App\Form\CommentForm;
use MongoDB\BSON\ObjectId;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CommentsService
{
    /**
     * @var EntityManager
     */
    private $entityManager;
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;
    /**
     * @var object|string
     */
    private $user;
    /**
     * @var ValidatorInterface
     */
    private $validator;

    public function __construct(EntityManager $entityManager, TokenStorageInterface $tokenStorage, ValidatorInterface $validator)
    {
        $this->entityManager = $entityManager;
        $this->tokenStorage = $tokenStorage;
        $this->user = $tokenStorage->getToken()->getUser();
        $this->validator = $validator;
    }

    /**
     * @param CommentForm $commentForm
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

        $this->entityManager->insert($comment, 'comments');

    }

    /**
     * @throws \Exception
     */
    private function checkAuthUser()
    {
        if(!$this->user instanceof User){
            throw new \Exception('Method not allowed');
        }
    }

    /**
     * @param CommentForm $commentForm
     * @throws \Exception
     */
    private function checkValidation(CommentForm $commentForm)
    {
        if(!$this->validator->validate($commentForm)){
            throw new \Exception('Comment is not valid');
        }
    }

    public function getAll(string $id)
    {
        $array = [];
        $array[] = [
            '$graphLookup' =>
                [
                    "from" => "comments",
                    "startWith" => '$_id',
                    "connectFromField" => 'parent',
                    'connectToField' => '_id',
                    'as' => 'parents',
                    'maxDepth' => 5
                ],
        ];
        $array[] = [
            '$match' => ["parents.parent" => $id]
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
            '$project' =>
            [
                "_id" => 1,
                "text" => 1,
                "date" => 1,
                "parent"=> 1,
                "like"=> 1,
                "dislikes"=>1,
                "author._id"=> 1,
                "author.username"=>1,
            ]
        ];
        $data = $this->entityManager->aggregate($array, [], 'comments');


        return iterator_to_array($data);
    }


}