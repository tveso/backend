<?php
/**
 * Date: 18/09/2018
 * Time: 3:24
 */

namespace App\Auth;


use App\Entity\Entity;
use App\EntityManager;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserProvider implements UserProviderInterface
{

    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Loads the user for the given username.
     *
     * This method must throw UsernameNotFoundException if the user is not
     * found.
     *
     * @param string $username The username
     *
     * @return UserInterface
     *
     * @throws UsernameNotFoundException if the user is not found
     */
    public function loadUserByUsername($username) : UserInterface
    {
        $userDao = $this->fetchUser($username);

        return $userDao;
    }

    /**
     * Refreshes the user.
     *
     * It is up to the implementation to decide if the user data should be
     * totally reloaded (e.g. from the database), or if the UserInterface
     * object can just be merged into some internal array of users / identity
     * map.
     *
     * @param UserInterface $user
     * @return UserInterface
     *
     */
    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(
                sprintf('Instances of "%s" are not supported.', get_class($user))
            );
        }

        $username = $user->getUsername();

        return $this->fetchUser($username);
    }

    /**
     * Whether this provider supports the given user class.
     *
     * @param string $class
     *
     * @return bool
     */
    public function supportsClass($class)
    {
        return User::class === $class;
    }

    private function fetchUser($username)
    {
        $username = strtolower($username);
        $user = $this->entityManager->findOneBy(["username"=> $username], 'users');
        if(is_null($user)){
            throw new UsernameNotFoundException();
        }
        $user = $user->getArrayCopy();
        $roles = $user["roles"]->getArrayCopy();
        $userDao = new User($user["username"], $user["password"], $roles, $user['enabled'], $user['accountNonExpired'],
            $user['credentialsNonExpired'], $user['accountNonLocked'], $user['email'], []);
        $userDao->setId($user["_id"]);
        $userDao->setAvatar($user['avatar']);

        return $userDao;
    }
}