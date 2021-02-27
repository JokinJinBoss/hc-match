<?php
namespace packages\Pick\Models;

use App\Models\Base;

class Experts extends Base
{
    protected $table = 'experts';

    protected $primaryKey = "id";

    public $timestamps = false;

    private static  $_instance = null;
    public static function getInstance () {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    protected $fillable = [
        'nickname',
        '_alias',
        'hc_userid',
        'avatar',
        'slogan',
        'desc',
        'max_win',
        'hit_rate',
        'best_win',
        'thread_count',
        'class_code',
        'good_at',
        'isrobot',
        'check_status',
    ];
}
