<?php
/**
 * Date: 03/10/2018
 * Time: 14:50
 */

namespace App\Services;


use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

abstract class BasicSecurityService implements Service
{

    /**
     * @var AuthorizationCheckerInterface
     */
    protected $authChecker;
    protected $token;
    protected $user;

    public function __construct(AuthorizationCheckerInterface $authChecker, TokenStorageInterface $token)
    {
        $this->token = $token->getToken();
        $this->user = $token->getToken()->getUser();
        $this->authChecker = $authChecker;
    }

    public function userHasRole(string $role)
    {
        if(!$this->authChecker->isGranted($role)) {
            throw new AccessDeniedException('Unable to access this page!');
        }
    }
}