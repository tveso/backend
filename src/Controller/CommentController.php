<?php


namespace App\Controller;


use App\Form\CommentForm;
use App\Services\CommentsService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;


/** @Route("/api/comment", name="comment_")
 */
class CommentController extends AbstractController
{
    /**
     * @var CommentsService
     */
    private $commentsService;


    /**
     * FollowController constructor.
     * @param CommentsService $commentsService
     */
    public function __construct(CommentsService $commentsService)
    {
        $this->commentsService = $commentsService;
    }

    /**
     * @Route("/add", name="add")
     * @ParamConverter("commentForm", converter="class")
     * @param CommentForm $commentForm
     * @Method({"POST"})
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function add(CommentForm $commentForm)
    {
        $id = $this->commentsService->add($commentForm);

        return $this->jsonResponse(["code"=> 200, "id"=> $id, "message"=> "Inserted comment"]);
    }

    /**
     * @Route("/{id}", name="getAll")
     * @param Request $request
     * @param string $id
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getComment(Request $request, string $id)
    {
        $page = intval(($request->query->get('page')) ?? 1);
        $data = $this->commentsService->getAll($id, $page);

        return $this->jsonResponseCached($data, $request);
    }
    /**
     * @Route("/{id}/delete", name="delete")
     * @param string $id
    $authChecker->isGranted('ROLE_ADMIN');
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function delete(string $id, Request $request)
    {
        $full = (!is_null($request->query->get('fullDelete'))) ? true : false;
        $this->commentsService->delete($id, $full);

        return $this->okResponse("Comment deleted");
    }
    /**
     * @Route("/{id}/like", name="like")
     * @param string $id
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function like(string $id)
    {
        $this->commentsService->like($id);
        return $this->okResponse();
    }
    /**
     * @Route("/{id}/dislike", name="dislike")
     * @param string $id
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function dislike(string $id)
    {
        $this->commentsService->dislike($id);
        return $this->okResponse();
    }
}