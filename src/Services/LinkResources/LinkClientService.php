<?php


namespace App\Services\LinkResources;




use HtmlParser\ParserDom;

class LinkClientService
{


    private $url = "https://www.plusdede.com";

    public function __construct()
    {
        $this->opts = array(
            'http'=>array(
                'method'=>"GET",
                'header'=>
                    "cookie: __cfduid=d44fb535a30a58760fb49f20e517193371529263620; plusdede-sess=eyJpdiI6ImR1WThIclRKbjd1ckxcL1wvQTlCK0YyZz09IiwidmFsdWUiOiJKTkh3M1dBQ3VpVnJRQ2RXeVpXcVoxTWVTKys0emt5YzRSY0NGQThtTndoeU1uRFdNbzdOM1FzME9sN1E3Vm5qIiwibWFjIjoiYzcwOGJkYTczYzYwMWZlNGUyZmE2ZjIwODg4MTYzYjc4NzhkN2ExNzRhMDc5NjM0YWQ1ODAxMmM4Y2VjN2JmZCJ9; PHPSESSID=v6g8v64k74ccimoq9vua136751; popshown=1; popshown2=1; XSRF-TOKEN=eyJpdiI6Ikx4ZkN3U1BJRWJqUHR3eUUxaStRY2c9PSIsInZhbHVlIjoidXhiZ1lEWVRBOUJ5YythY1YraE9sNllWQ1wvbExmMGRZUUVzUFF5UFFXNFpVTW1PRjhiYjdSYTRGelwvUEZXbkUrRnI4dEVrSkRjUW9Yd1NXMDZqXC8zcnc9PSIsIm1hYyI6ImVkZTE5M2IzNzY1OTBlZjdlYzA4ZmRkOWE0MDBmNmY1NmJjYWE2ZDZmMTM5ZGY3MmNlMzg0ZDE3YjY2NjY2MjUifQ%3D%3D; cakephp_session=eyJpdiI6Ikt0R2JXSzNNb0VKWFBvUThuWk1WZHc9PSIsInZhbHVlIjoiTWVwT0sza04zQXBVVG1xMndzOGswV2pzOFBHcDhZZ3dEaFd5NEFvVHNHUTVVUWlrc1BvaEdEdnRiUWt3U2ViTGFrRURycUZjT3JMNytQUlVQQ0RpSGc9PSIsIm1hYyI6IjlmYjkzNDQ4ZGE1MWM2ZWNhYTY2NDE5MjUwMDIzNzVkZDlkNDMyN2ZhYzViZDk3YmI4MWU2NzJkNmFhMmI2MjYifQ%3D%3D"
            )
        );
    }

    /**
     * @param string $url
     * @return string
     * @throws \Exception
     */
    public function get(string $url='/'): ParserDom
    {
        $opts = stream_context_create($this->opts);
        $response = file_get_contents("{$this->url}$url", false, $opts);
        return new ParserDom($response);
    }

    public function getRedirectUrl(string $url ='/'): string
    {
        $opts = stream_context_create($this->opts);
        $response = file_get_contents("{$this->url}$url", false, $opts);
        $headers = $this->parseHeaders($http_response_header);
        $location = $headers["Location"];
        if(is_array($location)) {
            return $location[0];
        }
        return $location;
    }

    public function parseHeaders($headers){
            $raw_headers = implode("\n", $headers);
            $headers = array();
            $key = '';

            foreach(explode("\n", $raw_headers) as $i => $h) {
                $h = explode(':', $h, 2);

                if (isset($h[1])) {
                    if (!isset($headers[$h[0]]))
                        $headers[$h[0]] = trim($h[1]);
                    elseif (is_array($headers[$h[0]])) {
                        $headers[$h[0]] = array_merge($headers[$h[0]], array(trim($h[1])));
                    }
                    else {
                        $headers[$h[0]] = array_merge(array($headers[$h[0]]), array(trim($h[1])));
                    }

                    $key = $h[0];
                }
                else {
                    if (substr($h[0], 0, 1) == "\t")
                        $headers[$key] .= "\r\n\t".trim($h[0]);
                    elseif (!$key)
                        $headers[0] = trim($h[0]);
                }
            }

            return $headers;
    }


}