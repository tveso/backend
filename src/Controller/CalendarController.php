<?php


namespace App\Controller;


use App\Services\CalendarService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Exception\ValidatorException;


/** @Route("/api/calendar", name="calendar_")
 */
class CalendarController extends AbstractController
{

    /**
     * @var CalendarService
     */
    private $calendarService;

    /**
     * CalendarController constructor.
     * @param CalendarService $calendarService
     */
    public function __construct(CalendarService $calendarService)
    {
        $this->calendarService = $calendarService;
    }

    /**
     * @Route("/bydate", name="bydate")
     * @param Request $request
     * @return object|\Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function bydate(Request $request)
    {
        $includeMovies = $request->query->get('include_movies') ?? true;
        $includeTvshows = $request->query->get('include_tvshows') ?? true;
        $onlyUserFollowed = $request->query->get('only_user_followed') ?? false;
        $includeMovies = ($includeMovies === 'false' or !$includeMovies) ? false : true;
        $includeTvshows = ($includeTvshows === 'false' or !$includeTvshows) ? false : true;
        $onlyUserFollowed = ($onlyUserFollowed === 'false' or !$onlyUserFollowed) ? false : true;
        $date = $request->query->get('date') ?? (new \DateTime())->format('Y-m-d');
        try{
            new \DateTime($date);
        } catch (\Exception $e) {
            throw new ValidatorException();
        }
        $data = $this->calendarService->getFrom($date, $includeTvshows, $includeMovies, $onlyUserFollowed);

        return $this->jsonResponse($data);
    }


    /**
     * @Route("/episodes", name="episodes")
     * @param Request $request
     * @return object|\Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function getEpisodesBetweenDates(Request $request)
    {
        $minDate = $request->query->get('min_date') ;
        $maxDate = $request->query->get('max_date') ;
        if (is_null($minDate) or is_null($maxDate)) {
            throw new ValidatorException();
        }
        $data = $this->calendarService->getUserTvshowsFollowedFrom(null, $minDate, $maxDate);

        return $this->jsonResponse($data);
    }



}