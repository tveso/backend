<?php
/**
 * Date: 19/09/2018
 * Time: 17:59
 */

namespace App\Auth;


use App\EntityManager;
use App\Form\UserRegistrationForm;
use MongoDB\BSON\ObjectId;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;


class UserService
{

    /**
     * @var EntityManager
     */
    private $entityManager;
    /**
     * @var PasswordEncoderInterface
     */
    private $passwordEncoder;
    /**
     * @var User|string
     */
    private $user;
    /**
     * @var UserService
     */
    private $userService;

    /**
     * UserService constructor.
     * @param EntityManager $entityManager
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param UserService $userService
     */
    public function __construct(EntityManager $entityManager,
                                UserPasswordEncoderInterface $passwordEncoder,
                                UserService $userService)
    {
        $this->entityManager = $entityManager;
        $this->passwordEncoder = $passwordEncoder;
        $this->userService = $userService;
        $this->user = $userService->getUser();
    }

    /**
     * @param UserRegistrationForm $userRegistrationForm
     * @throws \Exception
     */
    public function register(UserRegistrationForm $userRegistrationForm)
    {
        if($this->userExists($userRegistrationForm)){
            throw new \Exception("Username is in used");
        }
        $username = strtolower($userRegistrationForm->getUsername());
        $user = new User($username, $userRegistrationForm->getPassword());
        $user->setEmail($userRegistrationForm->getEmail());
        $user->setRoles(["ROLE_USER"]);
        $password = $this->passwordEncoder->encodePassword($user, $user->getPassword());
        $user->setPassword($password);
        $userArr = User::toArray($user);
        $userArr["_id"] = (new ObjectId())->__toString();
        $this->entityManager->insert($userArr, 'users');
    }

    private function userExists(UserRegistrationForm $userRegistrationForm)
    {
        $username = $userRegistrationForm->getUsername();
        $email = $userRegistrationForm->getEmail();

        $query = ['$or'=> [['username'=> $username], ["email"=> $email]]];
        $search = $this->entityManager->findOneBy($query, 'users');
        if(is_null($search)){
            return false;
        }
        return true;
    }

    public function getUser()
    {
        return $this->user;
    }
}