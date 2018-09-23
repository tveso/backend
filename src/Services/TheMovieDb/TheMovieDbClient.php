<?php
/**
 * Date: 09/07/2018
 * Time: 21:43
 */

namespace App\Services\TheMovieDb;


use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

class TheMovieDbClient
{

    private $uri;
    private $client;
    private $apikey;
    private $language;

    public function __construct(Client $client, string $apikey)
    {
        $this->uri = "https://api.themoviedb.org/3/";
        $this->client = $client;
        $this->apikey = $apikey;
        $this->language = 'es-ES';
    }

    /**
     * @return string
     */
    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * @param string $language
     * @return TheMovieDbClient
     */
    public function setLanguage(string $language): TheMovieDbClient
    {
        $this->language = $language;
        return $this;
    }



    private function buildUri(string $resource, array $params = [])
    {
        $params['api_key'] = $this->apikey;
        $params["language"] = $this->language;
        $paramsStr = http_build_query($params,"?");
        return  sprintf("%s%s?%s",$this->uri,$resource,$paramsStr);
    }

    public function request(string $resource, array $params = []) : string
    {
        $url =  $this->buildUri($resource,$params);
        $request = $this->client->get($url);
        $this->handleRateLimit($request);

        return $request->getBody()->getContents();
    }

    public function asyncRequest(string $resource, \Closure $callback, array $params = [])
    {
        $url =  $this->buildUri($resource,$params);
        $request = $this->client->requestAsync('GET',$url);
        $request->then($callback);
    }

    /**
     * @param Response $request
     */
    private function handleRateLimit(Response $request)
    {
        $ts = new \DateTime();
        $ts->setTimestamp(intval($request->getHeader('X-RateLimit-Reset')[0]));
        $requestRemaining = intval($request->getHeader('X-RateLimit-Remaining')[0]);
        $now = new \DateTime('now');
        $interval = $ts->diff($now)->s;
        if($requestRemaining === 0 ){
            sleep(10);
            return;
        }
        sleep(($interval/$requestRemaining)+0.1);
    }
}