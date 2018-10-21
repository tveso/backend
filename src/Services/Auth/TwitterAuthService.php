<?php
/**
 * Date: 11/10/2018
 * Time: 17:51
 */

namespace App\Services\Auth;


use Abraham\TwitterOAuth\TwitterOAuth;
use App\Auth\User;
use App\Auth\UserService;
use App\Entity\Entity;
use App\EntityManager;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\SessionStorageInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

class TwitterAuthService
{

    /**
     * @var TwitterOAuth
     */
    private $twitterOAuth;
    /**
     * @var SessionInterface
     */
    private $session;
    /**
     * @var EntityManager
     */
    private $entityManager;
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

    public function __construct(TwitterOAuth $twitterOAuth,
                                SessionInterface $session,
                                EntityManager $entityManager,
                                UserPasswordEncoderInterface $encoder,
                                UserService $userService,
                                TokenStorageInterface $tokenStorage,
                                AuthenticationManagerInterface $authenticationManager)
    {

        $this->twitterOAuth = $twitterOAuth;
        $this->session = $session;
        $this->entityManager = $entityManager;
        $this->encoder = $encoder;
        $this->userService = $userService;
        $this->tokenStorage = $tokenStorage;
        $this->authenticationManager = $authenticationManager;
    }


    /**
     * @param string $urlCallback
     * @return array
     * @throws \Abraham\TwitterOAuth\TwitterOAuthException
     */
    public function getRequestToken(string $urlCallback)
    {
        $token = $this->twitterOAuth->oauth('oauth/request_token',
                ['oauth_callback' => $urlCallback]);
        $this->session->set('oauth_token', $token['oauth_token']);
        $this->session->set('oauth_token_secret',$token['oauth_token_secret']);
        return $token;
    }

    /**
     * @param string $urlCallback
     * @return string
     * @throws \Abraham\TwitterOAuth\TwitterOAuthException
     */
    public function getAuthUrl(string $urlCallback)
    {
        $requestToken = $this->getRequestToken($urlCallback);
        return $this->twitterOAuth->url(
            'oauth/authorize', [
            'oauth_token' => $requestToken['oauth_token']
        ]);
    }

    /**
     * @param string $oauthToken
     * @param string $verifierToken
     * @return array
     * @throws \Abraham\TwitterOAuth\TwitterOAuthException
     */
    public function getAccessToken(string $verifierToken)
    {
        $this->twitterOAuth->setOauthToken($this->session->get('oauth_token'), $this->session->get('outh_token_secret'));
        $accessToken = $this->twitterOAuth->oauth("oauth/access_token", ["oauth_verifier" => $verifierToken]);
        $this->session->set('access_token', $accessToken);
        $this->twitterOAuth->setOauthToken($accessToken['oauth_token'], $accessToken['oauth_token_secret']);

        return $accessToken;
    }

    public function getUserData()
    {
        $user = $this->twitterOAuth->get('account/verify_credentials', ['include_email' => true]);
        return $user;
    }

    /**
     * @param $verifierToken
     * @return
     * @throws \Abraham\TwitterOAuth\TwitterOAuthException
     * @throws \Exception
     */
    public function logInOrSignup($verifierToken)
    {
        $this->getAccessToken($verifierToken);
        $twitterUser = $this->getUserData();
        $tokenId = $twitterUser->id;
        $email = $twitterUser->email;
        $entity = $this->findByToken($tokenId);
        if(is_null($entity)){
            $this->register($tokenId, $email);
        }
        return $this->login($tokenId, $email);
    }

    private function findByToken(string $token)
    {
        return $this->entityManager->findOneBy(["twitter_id" => $token], 'users');
    }

    /**
     * @param $token
     * @param $email
     * @return \MongoDB\Model\BSONDocument|null
     * @throws \Exception
     */
    private function register(string $token,string $email)
    {

        $password =  random_bytes(10);
        $username = explode("@", $email)[0];
        $user = new User($username, $password);
        $user->setEmail($email);
        $user->setTwitterId($token);
        $password = $this->encoder->encodePassword($user, $password);
        $user->setPassword($password);
        return $this->userService->register($user);
    }

    private function login(string $token, string $email)
    {
        $entity = $this->entityManager->findOneby(["twitter_id" => $token, "email" => $email], 'users');
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