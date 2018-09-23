<?php
/**
 * Date: 11/08/2018
 * Time: 21:42
 */

namespace App\Services\LinkResources;


use HtmlParser\ParserDom;

class AporteBuilder
{

    /**
     * @var ParserDom
     */
    private $dom;

    public function __construct(ParserDom $dom)
    {
        $this->dom = $dom;
    }

    public function build() : array
    {
        $result = [];
        $result['host'] = $this->getHost();
        $result['language'] = $this->getLanguage();
        $result['quality'] = $this->getQuality();
        $result['resource'] = $this->getResource();

        return $result;
    }

    public function getHost()
    {
        $host = $this->dom->find(".host",0);
        if(is_null($host)) return "";
        $host = $host->find("img",0);
        if(is_null($host)) return $host;
        $name = $host->src;
        $host = str_replace("https://cdn1.plusdede.com/images/hosts/", "", $name);
        $host = str_replace(".png",  "", $host);
        $host = ucfirst($host);

        return $host;
    }

    public function getLanguage()
    {
        $result = ["voice" => null, "text" => null];
        $languages = $this->dom->find(".language",0);
        if(is_null($languages)){
            return [];
        }
        $languages = $languages->find("img");
        $size = count($languages);
        switch ($size){
            case 1:
                $result["voice"] = $this->parseLanguage($languages[0]);
                break;
            case 2:
                $result["voice"] =  $this->parseLanguage($languages[0]);
                $result["text"] =  $this->parseLanguage($languages[1]);
                break;
            default:
                break;
        }
        if(sizeof($this->dom->find(".language",0)->find(".latino"))){
            $result["voice"] = "latinspanish";
        }

        return $result;
    }

    public function getQuality()
    {
        $quality = $this->dom->find(".videoquality", 0);
        if(is_null($quality)) return "";
        $result = str_replace("\n", "",$quality->plaintext);
        $result = str_replace(" ", "", $result);
        return $result;
    }

    public function getResource()
    {
        $resource = $this->dom->href;
        $resource = str_replace("https://www.plusdede.com/aportes/", "", $resource);

        return $resource;
    }

    private function parseLanguage(ParserDom $lang)
    {
        $result = '';
        $src = $lang->src;
        $result = str_replace("https://cdn1.plusdede.com/images/flags/","",$src);
        $result = str_replace('.png', '', $result);
        $result = ucfirst($result);

        return $result;
    }
}