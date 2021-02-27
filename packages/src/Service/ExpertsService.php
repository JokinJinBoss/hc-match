<?php
namespace packages\Pick\Service;

use CloudLive\Hc\TransForm\ExpertsForm;
use Illuminate\Support\Facades\Log;
use packages\Pick\commom\Crawler;
use packages\Pick\Models\Experts;
use packages\Pick\Models\MatchCate;

class ExpertsService
{
    /**
     * 专家数据没有就插入，有就修改
     * @param $job
     */
    public function store($job)
    {
        $crl = (new Crawler())->setUrl($job->url)->warpRequest()->async();
        try {
            //注:如果确定只有1个请求时，不要使用foreach循环
            foreach ($crl->results as $item) {
                $data = (array) json_decode($item->getBody()->getContents());
                $res = $this->_store($data['data'], $job->class_code);
                Experts::updateOrCreate(['_alias' => $job->_un_code], $res);
            }

         }catch (\Exception $e){
            Log::channel('daily')->info('专家数据插入跟新失败：' . json_encode($data['data']) . '---------error:' . $e->getMessage());
            throw new \Exception($e->getMessage(), 0);
        }
    }

    public function _store($data, $classCode)
    {

        $goodAt = '';
        foreach ($data->leagueMatchStats as $val){
            $goodAt .= $val->leagueMatchName . ',';
        }
        $goodAt = substr($goodAt, 0, -1);

        if ($data->hitRate == 1){
            $hitRate = sprintf("%1\$.2f", $data->hitRate / 100);
        }else{
            $hitRate = $data->hitRate;
        }

        return [
            'nickname' => $data->nickname,
            '_alias'    => md5($data->nickname . $data->userId),
            'avatar'   => $data->avatar,
            'slogan'   => $data->slogan,
            'desc'     => $data->desc,
            'max_win'  => $data->maxWin,
            'hit_rate' => $hitRate,
            'best_win' => $data->bAllRate,
            'good_at' => $goodAt,
            'isrobot' => 1,
            'thread_count' => $data->planCount,
            'class_code' => $classCode,
            'created_at' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * 专家列表
     * @param $request
     * @return array|Response
     */
    public static function list($request)
    {
        if (config('api.review_switch') == 'off'){
            $expertsData = Experts::where('class_code', $request->class_code)->paginate($request->per_page);
        }else{
            $expertsData = Experts::where('class_code', $request->class_code)->where('check_status', 1)->paginate($request->per_page);
        }

        if ($expertsData->isEmpty()){
            return [];
        }

        return ExpertsForm::list($expertsData);
    }

    /**
     * 专家分类
     * @return mixed
     */
    public static function type()
    {
        $classData = MatchCate::select('title', 'code')->where('status', 1)->get()->toArray();
        return $classData;
    }

    /**
     * 专家详情
     * @param $request
     * @return mixed
     */
    public static function info($request)
    {
        $expertsData = Experts::select('avatar', 'best_win', 'hit_rate', 'max_win', 'nickname', 'slogan', 'thread_count', 'desc', 'good_at')->find($request->user_id)->toArray();

        return $expertsData;
    }

    public static function home($request)
    {
        if (config('api.review_switch') == 'off'){
            $expertsData = Experts::where($request->req)->orderBy('hit_rate', 'desc')->get();
        }else{
            $expertsData = Experts::where($request->req)->where('check_status', 1)->orderBy('hit_rate', 'desc')->get();
        }

        $res = ExpertsForm::home($expertsData, $request->req, $request->page);

        return $res;
    }

}
