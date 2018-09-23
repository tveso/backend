<?php
/**
 * Date: 01/08/2018
 * Time: 21:09
 */

namespace App\Services\LinkResources;


use HtmlParser\ParserDom;

class TvShowLinksService extends LinkService
{


    /**
     * @param string $oname
     * @param int $seasonNumber
     * @param int $episodeNumber
     * @param int|null $showNumber
     * @return array
     * @throws \Exception
     */
    public function getSeasonEpisodeLinks(string $name, int $seasonNumber, int $episodeNumber, int $showNumber = null)
    {
        try{
            $name = $this->formatName($name, $showNumber);
            $dom = $this->getTvshowHtml($name);
            $resource = $this->getSeasonEpisodeResource($dom,$seasonNumber,$episodeNumber);
            if($resource === null) {
                return $this->getSeasonEpisodeLinks($name, $seasonNumber, $episodeNumber, 1);
            }
            $aportes = $this->getAportes($resource);
            if(sizeof($aportes)===0 and $showNumber === null){
                return $this->getSeasonEpisodeLinks($name, $seasonNumber, $episodeNumber, 1);
            }
            return $aportes;
        } catch (\Exception $e){
            return [];
        }
    }

    public function getSeasonEpisodeResource(ParserDom $dom, int $seasonNumber, int $episodeNumber)
    {
        $season = $dom->find("[data-season=$seasonNumber]", 1);
        $episode = $season->find("li", $episodeNumber);
        if($episode === null){
            return null;
        }
        $episode = $episode->find("a",0);
        return $episode->getAttr("data-href");

    }


    private function formatName(string $title, int $showNumber = null) : string
    {
        $title = strtolower($title);
        $title = str_replace(" ", "-", $title);
        if(!is_null($showNumber)){
            $title = "$title-$showNumber";
        }

        return $title;
    }

    /**
     * @param $name
     * @return ParserDom
     * @throws \Exception
     */
    private function getTvShowHtml($name)
    {
        return $this->linkClientService->get("/serie/$name");
    }

    /**
     * @param $resource
     * @throws \Exception
     */
    private function getAportes($resource)
    {
        $result = [];
        $dom = $this->getAporteHtml($resource);
        $links = $dom->find("#online",0);
        $links = $links->find("a");
        foreach ($links as $link){
            $result[] = $this->buildAporte($link);
        }

        return $result;
    }

    private function buildAporte($link)
    {
        $builder = new AporteBuilder($link);

        return $builder->build();
    }

    /**
     * @param string $resource
     * @return string
     * @throws \Exception
     */
    public function getLink(string $resource)
    {
        $dom = $this->getLinkHtml("$resource");
        $button = $dom->find(".visit-buttons", 0);
        $a = $button->find("a", 0);
        $link = str_replace("/link/", "", $a->href);

        return $this->getLinkUrl($link);
    }

}