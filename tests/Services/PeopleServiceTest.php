<?php
/**
 * Date: 17/10/2018
 * Time: 16:20
 */

namespace App\Tests\Services;


use App\Services\CacheProxyService;
use App\Services\PeopleService;
use App\Tests\AbstractTest;
use MongoDB\Model\BSONDocument;

class PeopleServiceTest extends AbstractTest
{


    public function testCacheTrait()
    {
        /** @var CacheProxyService $service */
        $service = self::$container->get('App\Services\CacheProxyService');
        $peopleService = self::$container->get('App\Services\PeopleService');
        $service->setService($peopleService);
        dump($service->daasd());
    }
}