<?php
/**
 * Date: 06/10/2018
 * Time: 21:27
 */

namespace App\Tests;



use App\Auth\User;
use App\EntityManager;
use MongoDB\BSON\ObjectId;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\UserInterface;

abstract class AbstractTest extends WebTestCase
{

    /**
     * @var Client
     */
    protected $client = null;
    /**
     * @var EntityManager
     */
    protected $entityManager;
    /**
     * @var User
     */
    protected $user;


    protected function setUp()
    {
        $this->client = static::createClient();
        $this->entityManager = static::$kernel->getContainer()->get('entitymanager');
    }

    protected function logIn()
    {
        $session = $this->client->getContainer()->get('session');

        $firewallName = 'login';
        // if you don't define multiple connected firewalls, the context defaults to the firewall name
        // See https://symfony.com/doc/current/reference/configuration/security.html#firewall-context
        $firewallContext = 'login';

        $user = $this->createUser();
        $token = new UsernamePasswordToken($user, null, $firewallName, array('ROLE_ADMIN'));
        static::$kernel->getContainer()->get('security.token_storage')->setToken($token);

        $cookie = new Cookie($session->getName(), $session->getId());
        $this->client->getCookieJar()->set($cookie);
        $this->user = $user;
    }

    protected function createUser() : UserInterface
    {
        $user = new User('admin',null,['ROLE_ADMIN']);
        $user->setId(new ObjectId("5ba27a23d5276932a4002a9c"));

        return $user;
    }
}