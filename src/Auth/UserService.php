<?php
/**
 * Date: 19/09/2018
 * Time: 17:59
 */

namespace App\Auth;


use App\Auth\Exceptions\UserRegistrationException;
use App\EntityManager;
use App\Form\UserRegistrationForm;
use App\Services\FindService;
use App\Services\ImageService;
use MongoDB\BSON\ObjectId;
use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Validator\Exception\ValidatorException;


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
     * @var ImageService
     */
    private $imageService;
    /**
     * @var FindService
     */
    private $findService;

    /**
     * UserService constructor.
     * @param EntityManager $entityManager
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param TokenStorageInterface $tokenStorage
     * @param AuthorizationCheckerInterface $authChecker
     * @param AuthenticationManagerInterface $authenticationManager
     * @param ImageService $imageService
     */
    public function __construct(EntityManager $entityManager, UserPasswordEncoderInterface $passwordEncoder,
                                TokenStorageInterface $tokenStorage,
                                AuthorizationCheckerInterface $authChecker,
                                AuthenticationManagerInterface $authenticationManager,
                                ImageService $imageService)
    {
        $this->entityManager = $entityManager;
        $this->passwordEncoder = $passwordEncoder;
        if(!is_null($tokenStorage->getToken())){
            $this->user = $tokenStorage->getToken()->getUser();
        }
        $this->tokenStorage = $tokenStorage;
        $this->authChecker = $authChecker;
        $this->authenticationManager = $authenticationManager;
        $this->imageService = $imageService;
    }

    /**
     * @param UserRegistrationForm $userRegistrationForm
     * @return array|object
     * @throws UserRegistrationException
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
     * @throws UserRegistrationException
     * @throws \Exception
     */
    public function register(User $user)
    {
        $this->userExists($user);
        $userArr = User::toArray($user);
        $userArr["_id"] = new ObjectId();
        $userArr["avatar"] = md5($user->getUsername().'avatar').'.jpg';
        $userArr["hash"] = $this->getHash($user["username"]);
        $this->entityManager->insert($userArr, 'users');
        $this->setRandomPeopleAvatar($userArr["avatar"]);

        return $this->entityManager->findOneBy(["_id"=> $userArr["_id"]], 'users');
    }

    /**
     * @param User $user
     * @throws UserRegistrationException
     */
    private function userExists(User $user)
    {
        $username = $user->getUsername();
        $email = $user->getEmail();
        $exception = new UserRegistrationException();
        $emailSearch = $this->entityManager->findOneBy(["email"=> $email], 'users');
        $usernameSearch = $this->entityManager->findOneBy(['username' => $username], 'users');
        if(!is_null($emailSearch)){
            $exception->addError('La dirección de email está en uso.');
        }
        if(is_null($usernameSearch)) {
            $exception->addError('El nombre de usuario está en uso.');
        }
        if(is_null($usernameSearch) or is_null($emailSearch)) {
            throw $exception;
        }
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

    /**
     * @param File $file
     * @return string
     * @throws \Exception
     */
    public function updateAvatar(File $file)
    {
        if(!$this->imageService->checkDimensions($file, [400,400])) {
            throw new ValidatorException();
        }
        $extension = $file->guessExtension();
        $fileName = md5($this->user->getUsername()."avatar").".".$extension;
        $filePath = $file->getRealPath();
        $this->imageService->upload($filePath, $fileName);
        $this->entityManager->update(['_id' => $this->user->getId()], ['$set'=> ['avatar'=> $fileName]],'users');

        return $fileName;
    }

    /**
     * @param string $avatar
     * @throws \Exception
     */
    public function setRandomPeopleAvatar(string $avatar)
    {
        $person = $this->findRandomPersonWithProfileImage();
        $uri = "https://image.tmdb.org/t/p/w500".$person['profile_path'];
        $imageContent = file_get_contents($uri);

        $this->imageService->uploadFromBody($avatar, $imageContent, 'image/jpg');
    }


    private function findRandomPersonWithProfileImage()
    {
        $pipeline = [['$match' => ['profile_path' => ['$ne' => null]]], ['$sample' => ['size' => 1]]];
        $person = $this->entityManager->aggregate($pipeline, [], 'people');
        $person = iterator_to_array($person);

        return $person[0];
    }

    /**
     * @param string $name
     * @return User
     */
    public function findByName(string $name): User
    {
        $name = strtolower($name);
        $user = $this->entityManager->findOneBy(['username' => $name], 'users');
        if(is_null($user)){
            throw new \InvalidArgumentException();
        }
        $user['id'] = $user["_id"];
        $user = User::fromArray($user);
        return $user;
    }


}