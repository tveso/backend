<?php
/**
 * Date: 06/10/2018
 * Time: 21:24
 */

namespace App\Tests\Services;


use App\Form\RateForm;
use App\Services\RatingService;
use App\Tests\AbstractTest;
use MongoDB\Model\BSONDocument;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RatingServiceTest extends AbstractTest
{
    /**
     * @var BSONDocument $document
     * @var RatingService $service
     */
    public function testRatingAdded()
    {
        $this->logIn();
        /**
         * @var RatingService $service
         */
        $service = self::$container->get('App\Services\RatingService');
        $ratingForm = new RateForm();
        $ratingForm->setId('tt0000009');
        $ratingForm->setRating(5);
        $document = $service->rate($ratingForm);
        $service->delete($document["_id"]);
        $this->assertEquals($document["rate"], 5);
        $this->assertEquals($document["user"], $this->user->getId());
        $this->assertEquals($document["show"], 'tt0000009');
    }
}