<?php

namespace packages\Pick\Service;


use packages\Pick\commom\Crawler;
use packages\Pick\Models\Jobs;
use App\Exceptions\BusinessException;

class JobsService
{
    private $expertsUrl = 'https://hongcai.163.com/api/web/expert/detail/';                    //专家信息请求地址
    private $articleUrl = 'https://hongcai.163.com/api/web/thread/expert/###/0/100';           //专家文章列表
    private $articleInfoUrl = 'https://hongcai.163.com/api/web/thread/query/###/0';            //文章详情


    /**
     * 专家任务数据处理插入
     * @param $class_code
     * @param $experts
     * @param array $unCode
     * @return array
     */
    public function exStore($class_code, $experts, &$unCode = [], $jobsData)
    {
        $data = [];
        $useridMap = [];
        foreach ($experts as $item){
            $useridMap[] = $item->userId;
            //任务表的标识
            $un = md5($item->nickname . $item->userId);
            //如果有相同的专家或者任务表里面有相同的专家任务就不用插入了
            if (isset($unCode[$un]) || isset($jobsData[$un])){
                continue;
            }

            $unCode[$un] = $un;
            $data[] = $this->__exStore($class_code, $item);
        }

        Jobs::insert($data);
        return $useridMap;
    }
    public function __exStore($class_code, $experts)
    {
        return [
            'job_type' => 0,
            'status'   => 0,
            '_un_code'  => md5($experts->nickname . $experts->userId),   //姓名 + 红彩那边的用户id
            'url'      => $this->expertsUrl . $experts->userId,
            'class_code' => $class_code,
            'created_at' => date('Y-m-d H:i:s')
        ];
    }

    public function arStore($class_code, $uid, $jobsArData)
    {
        $url = str_replace('###', $uid, $this->articleUrl);
        $res = (new Crawler())->setUrl($url)->warpRequest()->async();


        $data = [];
        foreach ($res->results as $item){
            $articleList = (array) json_decode($item->getBody()->getContents());

            if (empty($articleList)){
                throw new BusinessException($class_code . "The list of collected $uid articles is empty",1071);
            }

            foreach ($articleList['data'] as $article){

                //任务表的标识
                $un = md5($article->threadTitle . $article->threadId);
                if (isset($jobsArData[$un])){
                    continue;
                }

                $data[] = $this->__arStore($class_code, $article);
            }
        }

        Jobs::insert($data);
    }
    public function __arStore($class_code, $article)
    {
        $url = str_replace('###', $article->threadId, $this->articleInfoUrl);
        return [
            'url'      => $url,
            'job_type' => 1,
            'status'   => 0,
            '_un_code'  => md5($article->threadTitle . $article->threadId),   //文章标题 + 文章id
            'class_code' => $class_code,
            'created_at' => date('Y-m-d H:i:s')
        ];
    }

    //更新成功状态
    public function sucess(string $un_code)
    {
        Jobs::query()->where('_un_code', $un_code)->update(['status'=>1]);
    }
    //更新失败状态
    public function failure(string $un_code,string $recv)
    {
        Jobs::query()->where('_un_code', $un_code)->update(['status'=>3,'recv'=>$recv]);
    }
}
