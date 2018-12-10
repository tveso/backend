<?php
/**
 * Date: 17/10/2018
 * Time: 16:20
 */

namespace App\Tests\Services;


use App\Auth\UserService;
use App\Tests\AbstractTest;


class UserServiceTest extends AbstractTest
{


    /**
     * @throws \Exception
     */
    public function testUploadRandomPeopleAvatar()
    {
        /**
         * @var UserService $service
         */
        $service = self::$container->get('App\Auth\UserService');
        $service->setRandomPeopleAvatar('HolaQueTal.jpg');
        $this->assertTrue(true);
    }
}