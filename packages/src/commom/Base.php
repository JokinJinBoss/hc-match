<?php


namespace packages\Pick\commom;


class Base
{
    public static function SleepRand()
    {
        return mt_rand(10,99);
    }

    /**
     * 获取发布间隔时间
     * @param $date
     * @return string
     */
    public static function PublishTime($date)
    {
        return  date('h',time() - strtotime($date)) . __('小时前发布');
    }

    /**
     * 根据key进行分组
     * @param $arr
     * @param string $key
     * @return array
     */
    public static function ArrayGroupBy($arr, $key = '')
    {
        $result = [];
        foreach($arr as $k => $v) {
            $result[$v[$key]][] = $v;
        }

        return array_values($result);
    }
}
