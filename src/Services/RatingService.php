<?php
/**
 * Date: 06/10/2018
 * Time: 20:55
 */

namespace App\Services;


use App\Auth\UserService;
use App\EntityManager;
use App\Form\RateForm;
use App\Util\DateUtil;
use MongoDB\Model\BSONDocument;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Exception\ValidatorException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RatingService implements Service
{

    /**
     * @var ValidatorInterface
     */
    private $validator;
    /**
     * @var EntityManager
     */
    private $entityManager;
    /**
     * @var UserService
     */
    private $userService;
    /**
     * @var \App\Auth\User|string
     */
    private $user;


    public function __construct(ValidatorInterface $validator, EntityManager $entityManager, UserService $userService)
    {
        $this->validator = $validator;
        $this->entityManager = $entityManager;
        $this->userService = $userService;
        $this->user = $userService->getUser();
    }

    /**
     * @param $id
     * @return array|null|object
     */
    public function getById($id) {
        return $this->entityManager->findOnebyId($id, 'ratings');
    }


    /**
     * @param RateForm $rateForm
     * @return int|null
     */
    public function rate(RateForm $rateForm) : ?BSONDocument
    {
        $formErrors = $this->validator->validate($rateForm);
        if(sizeof($formErrors)>0) {
            throw new ValidatorException();
        }

        $rate = ['user' => $this->user->getId(),
            'show' => $rateForm->getId(),
            'date' => DateUtil::getDateFormated(new \DateTime()),
            'rate' => $rateForm->getRating()];
        $rate = ['$set'=> $rate];

        $changes = $this->entityManager->update(['show'=> $rateForm->getId(), 'user' => $this->user->getId()], $rate,
            'ratings', ['upsert' => true]);

        return $this->getByShowAndUser($rateForm->getId(), $this->user->getId());
    }

    public function getByShowAndUser($showId, $userId) {
        return $this->entityManager->findOneBy(['show'=> $showId, 'user' => $userId], 'ratings');
    }

    public function delete($id) : bool {
        $result = $this->entityManager->delete(['_id' => $id], 'ratings');

        return $result->getDeletedCount()>0;
    }
}