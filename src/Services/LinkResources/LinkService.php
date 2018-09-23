<?php
/**
 * Date: 01/08/2018
 * Time: 21:38
 */

namespace App\Services\LinkResources;



abstract class LinkService
{
    /** @Inject()
     * @var LinkClientService
     */
    protected $linkClientService;

    public function __construct(LinkClientService $linkClientService)
    {
        $this->linkClientService = $linkClientService;
    }

    /**
     * @param int $id
     * @return \HtmlParser\ParserDom|string
     * @throws \Exception
     */
    public function getAporteHtml($id)
    {
        return $this->linkClientService->get("$id");
    }

    /**
     * @param string $id
     * @return \HtmlParser\ParserDom|string
     * @throws \Exception
     */
    protected function getLinkHtml(string $id)
    {
        return $this->linkClientService->get("/aportes/$id");
    }

    /**
     * @param string $id
     * @return string
     * @throws \Exception
     */
    protected function getLinkUrl(string $id)
    {
        return $this->linkClientService->getRedirectUrl("/link/$id");
    }


}