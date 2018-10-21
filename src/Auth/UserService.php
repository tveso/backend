<?php
/**
 * Date: 19/09/2018
 * Time: 17:59
 */

namespace App\Auth;


use App\EntityManager;
use App\Form\UserRegistrationForm;
use MongoDB\BSON\ObjectId;
use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;


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
     * @var TokenStorageInterface
     */
    private $tokenStorage;
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authChecker;
    /**
     * @var AuthenticationManagerInterface
     */
    private $authenticationManager;

    /**
     * UserService constructor.
     * @param EntityManager $entityManager
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param TokenStorageInterface $tokenStorage
     * @param AuthorizationCheckerInterface $authChecker
     */
    public function __construct(EntityManager $entityManager,
                                UserPasswordEncoderInterface $passwordEncoder,
                                TokenStorageInterface $tokenStorage,
                                AuthorizationCheckerInterface $authChecker,
                                AuthenticationManagerInterface $authenticationManager)
    {
        $this->entityManager = $entityManager;
        $this->passwordEncoder = $passwordEncoder;
        if(!is_null($tokenStorage->getToken())){
            $this->user = $tokenStorage->getToken()->getUser();
        }
        $this->tokenStorage = $tokenStorage;
        $this->authChecker = $authChecker;
        $this->authenticationManager = $authenticationManager;
    }

    /**
     * @param UserRegistrationForm $userRegistrationForm
     * @throws \Exception
     */
    public function registerFromFrom(UserRegistrationForm $userRegistrationForm)
    {
        $username = strtolower($userRegistrationForm->getUsername());
        $user = new User($username, $userRegistrationForm->getPassword());
        $user->setRoles(["ROLE_USER"]);
        $password = $this->passwordEncoder->encodePassword($user, $user->getPassword());
        $user->setPassword($password);
        $user->setEmail($userRegistrationForm->getEmail());

        return $this->register($user);
    }

    /**
     * @param User $user
     * @return array|object
     * @throws \Exception
     */
    public function register(User $user)
    {
        if($this->userExists($user)){
            throw new \Exception("Username is in used");
        }
        $userArr = User::toArray($user);
        $userArr["_id"] = new ObjectId();
        $userArr["hash"] = $this->getHash($user["username"]);
        $this->entityManager->insert($userArr, 'users');

        return $this->entityManager->findOneBy(["_id"=> $userArr["_id"]], 'users');
    }

    private function userExists(User $user)
    {
        $username = $user->getUsername();
        $email = $user->getEmail();

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

    public function userHasRole(string $role)
    {
        if(!$this->authChecker->isGranted($role)) {
            throw new AccessDeniedException('Unable to access this page!');
        }
    }

    private function getHash($username)
    {
        $users = $this->entityManager->find(["username" => $username], 'users');
        $length = sizeof($users->toArray());
        return $length+1;
    }

    public function login(array $query)
    {
        $entity = $this->entityManager->findOneby($query, 'users');
        if(is_null($entity)) {
            throw new BadCredentialsException();
        }
        $user = User::fromArray(iterator_to_array($entity));
        $token = new UsernamePasswordToken($user, null, 'login', $user->getRoles());
        $authenticatedToken = $this->authenticationManager->authenticate($token);
        $this->tokenStorage->setToken($authenticatedToken);

        return $user;
    }

}