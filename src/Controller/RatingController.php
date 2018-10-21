<?php


namespace App\Controller;


use App\Form\RateForm;
use App\Form\UserRegistrationForm;
use App\Services\RatingService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;


/** @Route("/api/rating", name="rating_")
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
     * @Route("/rate", name="rate", methods={"POST"})
     * @param RateForm $rateForm
     * @return object|\Symfony\Component\HttpFoundation\JsonResponse
     * @ParamConverter("rateForm", converter="class")
     */
    public function rate(RateForm $rateForm)
    {
        $this->ratingService->rate($rateForm);
        return $this->okResponse();
    }
}