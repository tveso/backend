<?php


namespace App\Controller;


use App\Form\CommentForm;
use App\Services\CommentsService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Routing\Annotation\Route;


/** @Route("/api/comment", name="comment_")
 *  @Cache(expires="+3600 seconds")
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
        $this->commentsService->add($commentForm);

        return $this->okResponse();
    }

    /**
     * @Route("/{id}", name="getAll")
     * @param string $id
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function get(string $id)
    {
        $data = $this->commentsService->getAll($id);

        return $this->jsonResponse($data);
    }
}