<?php
/**
 * Date: 17/10/2018
 * Time: 4:35
 */

namespace App\Services;


use App\EntityManager;

class StatisticsService
{


    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {

        $this->entityManager = $entityManager;
    }

    
}