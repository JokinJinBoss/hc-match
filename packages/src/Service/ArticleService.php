<?php
namespace packages\Pick\Service;


use Illuminate\Support\Facades\Log;
use packages\Pick\commom\Base;
use packages\Pick\commom\Crawler;
use packages\Pick\Models\Article;
use packages\Pick\Models\Experts;
use packages\Pick\Models\Plays;
use packages\Pick\TransForm\ArticleForm;

class ArticleService
{
    public function store($job)
    {
        $crl = (new Crawler())->setUrl($job->url)->warpRequest()->async();
        try {

            foreach ($crl->results as $item) {

                $data = (array) json_decode($item->getBody()->getContents());


                //玩法数据处理
                $playId = $this->contesPlay($data['data']);
                $res = $this->_store($data['data']);

                $res['plays'] = explode(',',$playId);
                $res['created_at'] = date('Y-m-d H:i:s');

                Article::updateOrCreate(['title'=>$res['title']],$res);

            }
        }catch (\Exception $e){
            Log::channel('daily')->info( '---------error:' . $e->getMessage() . '文章数据插入跟新失败：' . json_encode($data['data']));
            throw new \Exception($e->getMessage(), 0);
        }
    }

    public function _store($data)
    {
        if (isset($data->hitRateValue)){
            $hitRateValue = $data->hitRateValue;
            $status = 1;
        }else{
            if ($data->canPurchase ==true){
                $hitRateValue = '未开始';
                $status = 0;
            }else{
                if (isset($data->isWin) && $data->isWin == 1){
                    $hitRateValue = '红';
                    $status = 2;
                }else{
                    $hitRateValue = '黑';
                    $status = 3;
                }
            }
        }

        return [
            'title'   => $data->title,
            'content' => $data->content ?? '',
            'iswin'   => $data->isWin ?? 0,
            'expert_alias' => md5($data->expertData->nickname . $data->expertData->userId),
            'created_at'   => date('Y-m-d H:i:s'),
            'hit_rate_value' => $hitRateValue,
            'rate_status' => $status
        ];
    }

    /**
     * 插入玩法数据并返回玩法的ID
     * @param $data
     * @return false|string
     */
    public function contesPlay($data)
    {
        $playsMap = [];
        $playId = '';
        foreach ($data->matchList as $k => $match){
            $info = [];
            foreach ($match->playVoList as $play){
                foreach ($play->itemVoList as $itemKey => $item){
                    $info[$item->playItemCode] = $item->odds;
                    //输赢判断
                    if ($item->isMatchResult == 1){
                        $info['R'] = $item->playItemCode;           //  输赢
                    }

                    //推荐判断
                    if ($item->isRecommend){
                        $info['C'] = $item->playItemCode;
                    }
                }
                $alias = md5($match->homeTeam->teamName . $match->guestTeam->teamName . $match->matchTime . $match->jcNum . $play->playCode);   //主队名字 + 客队名字 + 比赛开始时间 + 比赛场次 + 玩法code
                $playsMap['_alias'] = $alias;
                $playsMap['_alias_grouping'] = md5($match->homeTeam->teamName . $match->guestTeam->teamName . $match->matchTime . $match->jcNum);

                $playsMap['match_time'] = $match->matchTime;
                $playsMap['jc_num'] = $match->jcNum;
                $playsMap['league'] = $match->leagueName;
                $playsMap['category_name'] = $match->categoryName;
                $playsMap['match_status'] = $match->matchStatus;
                $playsMap['code'] = $play->playCode;
                $playsMap['name'] = $play->playName;
                $playsMap['concede'] = $play->concede;
                $playsMap['info'] = json_encode($info);
                $playsMap['matchs'] = json_encode([
                                        'home' => $match->homeTeam->teamName,
                                        'home_icon' => $match->homeTeam->teamIcon,
                                        'away' => $match->guestTeam->teamName,
                                        'away_icon' => $match->guestTeam->teamIcon,
                                        'score' => $match->homeScore . ':' . $match->guestScore]);
                $playsMap['created_at'] = date('Y-m-d H:i:s');
                $playId .= Plays::firstOrCreate(['_alias'=>$alias], $playsMap)->id . ',';

            }
        }


        return substr($playId, 0, -1);
    }

    /**
     * 文章列表
     * @param $request
     * @return array
     */
    public static function list($request)
    {
        if (config('api.review_switch') == 'off'){
            $articleData = Article::select('plays','title','hit_rate_value','id','created_at','expert_alias')->where('expert_alias',$request->alias)->paginate($request->per_page);
        }else{
            $articleData = Article::select('plays','title','hit_rate_value','id','created_at','expert_alias')
                                  ->where('expert_alias',$request->alias)
                                  ->where('display', 1)
                                  ->where('check_status', 2)
                                  ->paginate($request->per_page);
        }

        if ($articleData->isEmpty()){
            return [];
        }

        $res = [];
        foreach ($articleData->items() as $item){
            $res['list'][] = ArticleForm::list($item);
        }
        $res['tp'] = $articleData->total();
        return $res;
    }

    /**
     * 文章详情
     * @param $request
     * @return array
     */
    public static function info($request)
    {
        $articleData = Article::find($request->article_id);
        $playList = Plays::whereIn('id', $articleData->plays)->get();

        $playMap = Base::ArrayGroupBy($playList->toArray(), '_alias_grouping');
        return ArticleForm::info($articleData, $playMap);
    }

    /**
     * 首页文章推荐
     * @return array
     */
    public static function plan($request)
    {
        //获取以开赛状态排序的文章
        if (config('api.review_switch') == 'off'){
            $ArticleData = Article::select('id', 'title', 'expert_alias', 'plays', 'rate_status')->orderBy('re_status', 'DESC')->orderBy('rate_status', 'ASC')->orderBy('created_at', 'DESC')->paginate($request->per_page);
        }else{
            $ArticleData = Article::select('id', 'title', 'expert_alias', 'plays', 'rate_status')
                                  ->where('display', 1)
                                  ->where('check_status', 2)
                                  ->orderBy('re_status', 'DESC')
                                  ->orderBy('rate_status', 'ASC')
                                  ->orderBy('created_at', 'DESC')
                                  ->paginate($request->per_page);
        }


        //取出文章对应的专家标识,和玩法的关联数据
        $aliasMap = [];
        $playMap = [];
        foreach ($ArticleData->items() as $expertAlias){
            $aliasMap[] = $expertAlias->expert_alias;
            //获取玩法的id,用作查询
            $pMap =  $expertAlias->plays;
            foreach ($pMap as $playId){
                $playMap[$playId] = $playId;
            }
        }

        //获取专家数据
        $expertsData = Experts::whereIn('_alias', $aliasMap)->get()->toArray();
        $expertsData = array_column($expertsData,null, '_alias');

        //获取玩法数据
        $playData = Plays::whereIn('id', $playMap)->get()->toArray();
        $playData = array_column($playData, null, 'id');

        $res = [];
        foreach ($ArticleData->items() as $item){
            $res['list'][] = ArticleForm::plan($item, $expertsData, $playData);
        }

        //先按胜率排, 再按开是否开始排
        $desRes = array_column($res['list'], 'hit_rate');
        array_multisort($desRes,SORT_DESC, $res['list']);
        $ascRes = array_column($res['list'], 'rate_status');
        array_multisort($ascRes,SORT_ASC, $res['list']);

        $res['tp'] = $ArticleData->total();
        return $res;
    }

    public static function record($request)
    {
        $articleData = Article::select('id', 'hit_rate_value', 'rate_status')->where('expert_alias', $request->alias)->take(10)->orderBy('created_at', 'DESC')->get();
        if ($articleData->isEmpty()){
            return [];
        }

        return ArticleForm::record($articleData);
    }
}

