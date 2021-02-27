<?php
namespace packages\Pick\commom;

use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ClientException;

class Crawler
{
    public $map = [];
    public $params  = [];
    public $promises = [];
    public $results  = [];
    public $range = [];
    public $baseUrl = "";

    private $proxyIp = [
            "http://154.197.27.252:8088",
            "http://154.197.27.252:8088",
            "http://154.197.27.252:8088",
            "http://154.197.27.252:8088"
    ];

    /**
     *
     * 设置请求地址
     *
     * @param string $url
     * @return $this
     */
    public function setUrl($url = ''){
        $this->baseUrl = $url;
        return $this;
    }

    /**
     *
     * 设置参数
     *
     * @param array $dates
     * @return obj
     */
    public function setParams(array $dates, array $param, $dateKey = '',$model = false)
    {
        foreach ($dates as $date) {
            $this->range[]  = $date;
            $param[$dateKey] = $date;

            if (!$model) {
                $this->params[] = "?".http_build_query($param);
            } else {
                $_param = '?';
                foreach ($param as $key => $value) {
                    $_param .= $key.'='.$value.'&';
                }
                $this->params[] = $_param;
            }
        }
        return $this;
    }

    /**
     *
     * 包装请求地址
     * @return obj
     */
    public function warpRequest()
    {
        if (empty($this->params)){
            $this->map[] = $this->baseUrl;
        }else{
            foreach ($this->params as $param) {
                $this->map[] = $this->baseUrl . $param;
            }
        }

        return $this;
    }

    /**
     *
     * 异步请求
     * @return obj
     *
     *  说明：配置代理的时，会出现代理那边http 不能请求https的情况
     */
    public function async()
    {
        foreach ($this->proxyIp as $ip) {
            try {
                foreach ($this->map as $index => $url) {
                    if (strpos($url , 'https') !== false){
                        $client = new \GuzzleHttp\Client($this->proxy());
                    }else{
                        $client = new \GuzzleHttp\Client($this->proxy(true, $ip));
                    }

                    //异步请求
                    $asyncRes = $client->getAsync($url);
                    if (empty($this->range)){
                        $this->promises[$index] = $asyncRes;
                    }else{
                        $this->promises[$this->range[$index]] = $asyncRes;
                    }
                }
                $this->results = \GuzzleHttp\Promise\unwrap($this->promises);
                break;
            }catch (\Exception $e){
                continue;
            }
        }

        return $this;
    }

    public function proxy($st = false, $ip = '')
    {
        $res = [
            'timeout' => 10
        ];

        if ($st == true){
            $res['proxy'] = $ip;
        }

        return $res;
    }
}
