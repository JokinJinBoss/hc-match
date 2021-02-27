<?php


namespace CloudLive\Hc\TransForm;


use App\Models\Experts;

class ExpertsForm
{
    /**
     * 专家列表数据组装
     * @param $data
     * @return array
     *
     * nickname: 专家名称
     * avatar: 头像
     * slogan: 头衔
     * hit_rate: 命中率
     * max_win:  最大连中
     * best_win: 最近连中
     * thread_count: 方案数
     * t : 总条数
     */
    public static function list($data)
    {

        $res = [];
        foreach ($data->items() as $key => $item){
            $res['list'][$key]['nickname'] = $item->nickname;
            $res['list'][$key]['avatar']   = $item->avatar;
            $res['list'][$key]['slogan']   = $item->slogan;
            $res['list'][$key]['hit_rate'] = $item->hit_rate;
            $res['list'][$key]['max_win']  = $item->max_win;
            $res['list'][$key]['best_win'] = $item->best_win;
            $res['list'][$key]['user_id']   = $item->id;
            $res['list'][$key]['thread_count'] = $item->thread_count;
            $res['list'][$key]['alias'] = $item->_alias;
            $res['list'][$key]['good_at'] = $item->good_at;
        }

        $res['tp'] = $data->total();
        return $res;
    }

    public static function home($data, $req, $page)
    {
        $res = [];
        if (empty($req)){
            foreach (Experts::$classCode as $code){
                $i = 0;
                foreach ($data as $item){
                    if ($i > ($page - 1)){
                        break;
                    }
                    if ($code == $item['class_code']){
                        $res[$code][$i]['nickname'] = $item['nickname'];
                        $res[$code][$i]['avatar']   = $item['avatar'];
                        $res[$code][$i]['slogan']   = $item['slogan'];
                        $res[$code][$i]['desc']     = $item['desc'];
                        $res[$code][$i]['user_id']  = $item['id'];
                        $res[$code][$i]['hit_rate'] = $item['hit_rate'];
                        $res[$code][$i]['alias'] = $item['_alias'];
                        $i++;
                    }
                }

                if (empty($res[$code])){
                    $res[$code] = [];
                }
            }
        }else{
            $i = 0;
            foreach ($data as $item){
                if ($i > ($page - 1)){
                    break;
                }
                $res[$i]['nickname'] = $item['nickname'];
                $res[$i]['avatar']   = $item['avatar'];
                $res[$i]['slogan']   = $item['slogan'];
                $res[$i]['desc']     = $item['desc'];
                $res[$i]['user_id']  = $item['id'];
                $res[$i]['hit_rate'] = $item['hit_rate'];
                $res[$i]['alias'] = $item['_alias'];
                $i++;
            }
        }

        return $res;
    }
}
