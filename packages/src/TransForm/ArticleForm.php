<?php
namespace packages\Pick\TransForm;


use packages\Pick\commom\Base;
use packages\Pick\Models\Plays;

class ArticleForm
{
    public static function list($item)
    {
        $playMap = Plays::whereIn('id', $item->plays)->get();

        $res = [];
        $res['title'] = $item->title;
        $res['alias'] = $item->expert_alias;
        $res['hit_rate_value'] = $item->hit_rate_value;
        $res['article_id'] = $item->id;
        $res['publish_time'] = Base::PublishTime($item->created_at);
        $res['experts_id'] = $item->experts->id;

        $matchList = [];
        foreach ($playMap as $k => $match){

            $matchs = json_decode($match->matchs, true);
            $score = explode(':', $matchs['score']);

            $matchList[$k]['category_name'] = $match->category_name;
            $matchList[$k]['league_name'] = $match->league;
            $matchList[$k]['match_time']  = $match->match_time;
            $matchList[$k]['guest_name']  = $matchs['away'];
            $matchList[$k]['guest_score'] = $score[1];
            $matchList[$k]['home_name']   = $matchs['home'];
            $matchList[$k]['home_score']  = $score[0];
        }
        $res['match_list'] = $matchList;

        return $res;
    }

    /**
     * 文章详情数据组装
     * @param $article
     * @param $plays
     * @return array
     *
     * title：文章标题
     * content: 文章内容
     * hit_rate_value：文章状态描述
     * publish_time：发布距离时间
     * match_list[
     *     category_name:竞猜类型
     *     guest_name：客队名字
     *     guest_name：客队分数
     *     home_name: 主队名字
     *     home_score：主队分数
     *     match_time：开赛时间
     *     league_name：联赛
     *     play_vo_list[
     *          play_name: 玩法
     *          play_code:玩法标识
     *          concede:  分数
     *          item_vo_list[ //胜平负玩法赔率
     *              odds:赔率
     *              play_item_code： 玩法标识
     *              play_item_name： 玩法名字
     *          ]
     *     ]
     * ]
     */
    public static function info($article, $plays)
    {
        $res = [];
        $res['title']   = $article->title;
        $res['content'] = $article->content;
        $res['iswin']   = $article->iswin;
        $res['hit_rate_value'] = $article->hit_rate_value;
        $res['publish_time']   = Base::PublishTime($article->created_at);
        $res['created_at']     = strtotime($article->created_at);

        $matchArr = [];
        foreach ($plays as $k => $matchList){
            $playVoList = [];
            foreach ($matchList as $playKey => $playMap){
                if (!isset($matchArr[$k])){
                    $matchs = json_decode($playMap['matchs'], true);
                    $score = explode(':', $matchs['score']);

                    $matchArr[$k]['category_name'] = $playMap['category_name'];
                    $matchArr[$k]['guest_name']    = $matchs['away'];
                    $matchArr[$k]['guest_score']   = $score[1];
                    $matchArr[$k]['guest_icon']    = $matchs['away_icon'];
                    $matchArr[$k]['home_name']     = $matchs['home'];
                    $matchArr[$k]['home_score']    = $score[0];
                    $matchArr[$k]['home_icon']     = $matchs['home_icon'];

                    $matchArr[$k]['match_time']    = $playMap['match_time'];
                    $matchArr[$k]['match_status']  = $playMap['match_status'];
                    $matchArr[$k]['league_name']   = $playMap['league'];
                    $matchArr[$k]['jc_num']        = $playMap['jc_num'];
                }

                //赛事数据
                $playVoList['play_name'] = $playMap['name'];
                $playVoList['play_code'] = $playMap['code'];
                $playVoList['concede']   = $playMap['concede'];

                //组装赔率数据
                $i = 0;
                $itemVoList = [];
                $info = json_decode($playMap['info'],true);

                foreach ($info as $key => $odds){
                    if (isset(Plays::$apiPlayInfo[$key])){
                        $itemVoList[$i]['odds'] = $odds;
                        $itemVoList[$i]['play_item_code'] = $key;
                        $itemVoList[$i]['play_item_name'] = Plays::$playInfo[$key];
                        $i++;
                    }else{
                        $playVoList[$key] = $odds;
                    }
                }

                //组装玩法
                $playVoList['item_vo_list'] = $itemVoList;
                //组装玩法赔率
                $matchArr[$k]['play_vo_list'][] = $playVoList;
            }
        }
        $res['match_list'] = $matchArr;

        return $res;
    }

    public static function plan($article, $expertsData, $playData)
    {
        $res = [];
        //命中率排序数据
        $res['title']      = $article->title;
        $res['article_id'] = $article->id;
        $res['publish_time'] = Base::PublishTime($article->created_at);
        $res['hit_rate']   = $expertsData[$article->expert_alias]['hit_rate'];
        $res['rate_status']  = $article->rate_status;
        $res['alias']    = $expertsData[$article->expert_alias]['_alias'];
        $res['user_id']  = $expertsData[$article->expert_alias]['id'];

        //专家数据
        $expert = [
            'nickname' => $expertsData[$article->expert_alias]['nickname'],
            'avatar'   => $expertsData[$article->expert_alias]['avatar'],
            'slogan'   => $expertsData[$article->expert_alias]['slogan'],
            'best_win' => $expertsData[$article->expert_alias]['best_win'],
            'max_win'  => $expertsData[$article->expert_alias]['max_win'],
            'hit_rate' => $expertsData[$article->expert_alias]['hit_rate'],
        ];
        $res['expert'] = $expert;

        //赛事数据
        $playMap = $article->plays;
        $matchList = [];
        foreach ($playMap as $k => $playId){
            $matchs = json_decode($playData[$playId]['matchs'],true);
            $score = explode(':', $matchs['score']);
            $matchList[$k]['category_name'] = $playData[$playId]['category_name'];
            $matchList[$k]['guest_name']    = $matchs['away'];
            $matchList[$k]['guest_score']   = $score[1];
            $matchList[$k]['home_name']     = $matchs['home'];
            $matchList[$k]['home_score']    = $score[0];
            $matchList[$k]['match_time']    = $playData[$playId]['match_time'];
            $matchList[$k]['league_name']   = $playData[$playId]['league'];
        }

        $res['match_list'] = $matchList;
        return $res;
    }

    public static function record($data)
    {
        $res = [];
        $planMap = [];
        foreach ($data as $k => $item){
            $planMap['type']  = $item->rate_status;
            $planMap['value'] = $item->hit_rate_value;
            $planMap['article_id'] = $item->id;
            $res[] = $planMap;
        }

        return $res;
    }
}
