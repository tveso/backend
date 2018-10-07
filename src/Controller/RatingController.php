<?php


namespace App\Controller;


use App\Form\RateForm;
use App\Form\UserRegistrationForm;
use App\Services\RatingService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;


/** @Route("/api/rating", name="rating_")
 *  @Cache(expires="+3600 seconds")
 */
class RatingController extends AbstractController
{
    /**
     * @var RatingService
     */
    private $ratingService;


    /**
     * FollowController constructor.
     * @param RatingService $ratingService
     */
    public function __construct(RatingService $ratingService)
    {

        $this->ratingService = $ratingService;
    }

    /**
     * @Route("/{id}/rate", name="follow", methods={"POST"})
     * @param UserRegistrationForm $userRegistrationForm
     * @return object|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function rate(RateForm $userRegistrationForm)
    {
        $this->ratingService->rate($userRegistrationForm);
        return $this->okResponse();
    }
}