<?php


namespace packages\Pick\Models;


use App\Models\Base;

class MatchCate extends Base
{
    protected $table = 'match_class';
    protected $primaryKey = "id";
    private static  $_instance = null;

    protected $fillable = [
        'title',
        'code',
        'type',
        'status',
        'sort',
        'pc_icon',
        'h5_icon',
        'bg_img',
        'status',
        '_as'
    ];

    /**
     *
     * getInstance
     *
     * @return Singleton|null
     */
    public static function getInstance () {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    //定义一个修改器
    public function getTitleAttribute()
    {
        return self::$titleModify ? $this->attributes['title'] . '专家' : $this->attributes['title'];
    }
}
