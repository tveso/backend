<?php
/**
 * Date: 10/10/2018
 * Time: 0:04
 */

namespace App\Services\Auth;


use App\Auth\User;
use App\Auth\UserService;
use App\EntityManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

class GoogleAuthService
{
    /**
     * @var EntityManager
     */
    private $entityManager;
    /**
     * @var \Google_Client
     */
    private $client;
    /**
     * @var UserPasswordEncoderInterface
     */
    private $encoder;
    /**
     * @var UserService
     */
    private $userService;
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;
    /**
     * @var AuthenticationManagerInterface
     */
    private $authenticationManager;


    /**
     * GoogleAuthService constructor.
     * @param EntityManager $entityManager
     * @param \Google_Client $client
     * @param UserPasswordEncoderInterface $encoder
     * @param UserService $userService
     * @param TokenStorageInterface $tokenStorage
     * @param AuthenticationManagerInterface $authenticationManager
     */
    public function __construct(EntityManager $entityManager, \Google_Client $client,
                                UserPasswordEncoderInterface $encoder,
                                UserService $userService, TokenStorageInterface $tokenStorage,
                                AuthenticationManagerInterface $authenticationManager)
    {
        $this->entityManager = $entityManager;
        $this->client = $client;
        $this->encoder = $encoder;
        $this->userService = $userService;
        $this->tokenStorage = $tokenStorage;
        $this->authenticationManager = $authenticationManager;
    }

    /**
     * @param string $token
     * @return User|array|\MongoDB\Model\BSONDocument|null
     * @throws \Exception
     */
    public function loginOrSignUp(string $token)
    {
        $data = $this->verifyToken($token);
        $tokenId = $data['sub'];
        $email = $data['email'];
        $entity = $this->findByToken($tokenId);
        if(is_null($entity)){
            $this->register($tokenId, $email);
        }
        return $this->login($tokenId, $email);
    }

    private function findByToken(string $token)
    {
        return $this->entityManager->findOneBy(["google_id" => $token], 'users');
    }

    private function verifyToken($token)
    {
        $payload = $this->client->verifyIdToken($token);
        if(!$payload) {
            throw new BadCredentialsException($payload);
        }
        return $payload;
    }

    /**
     * @param $token
     * @param $email
     * @return \MongoDB\Model\BSONDocument|null
     * @throws \Exception
     */
    private function register($token, $email)
    {

        $password =  random_bytes(10);
        $username = explode("@", $email)[0];
        $user = new User($username, $password);
        $user->setEmail($email);
        $user->setGoogleId($token);
        $password = $this->encoder->encodePassword($user, $password);
        $user->setPassword($password);
        return $this->userService->register($user);
    }

    private function login(string $token, string $email)
    {
        return $this->userService->login(["google_id" => $token, "email" => $email]);
    }
}
