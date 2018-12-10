<?php


namespace App\Controller;


use App\Auth\UserService;
use App\Services\RatingService;
use App\Services\UserFollowService;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Exception\ValidatorException;


/** @Route("/api/user", name="user_")
 */
class UserController extends AbstractController
{
    /**
     * @var UserService
     */
    private $userService;
    /**
     * @var UserFollowService
     */
    private $userFollowStatsService;

    /**
     * FollowController constructor.
     * @param UserService $userService
     * @param UserFollowService $userFollowStatsService
     */
    public function __construct(UserService $userService, UserFollowService $userFollowStatsService)
    {

        $this->userService = $userService;
        $this->userFollowStatsService = $userFollowStatsService;
    }


    /**
     * @Route("/avatar", name="rate", methods={"POST"})
     * @param Request $request
     * @return object|\Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function upload(Request $request)
    {
        $files = $request->files;
        if(sizeof($files->all())!== 1) {
            throw new ValidatorException();
        }
        $file = $files->get('file');
        $newPath = $this->userService->updateAvatar($file);

        return $this->jsonResponse(['new_path' => $newPath]);
    }

    /**
     * @Route("/{name}", name="find_by_name")
     * @param Request $request
     * @param string $name
     * @return object|\Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function findByName(Request $request, string $name)
    {
        $data = $this->userService->findByName($name);
        return $this->jsonResponse($data);
    }

    /**
     * @Route("/{name}/info", name="user_info")
     * @param Request $request
     * @param string $name
     * @return object|\Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function getUserProfileInfo(Request $request, string $name)
    {
        $data = $this->userFollowStatsService->getShowsAndEpisodeStats($name);
        $data['count'] = $this->userFollowStatsService->getCountFollowShowsInfo($name);
        return $this->json($data);
    }

}