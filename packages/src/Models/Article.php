<?php

namespace packages\Pick\Models;

use App\Models\Base;

class Article extends Base
{
    protected $table = 'article';

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
        'title',
        'content',
        'iswin',
        'expert_alias',
        'plays',
        'rate_status',
        'created_at',
        'hit_rate_value',
        're_status',
        'logo',
    ];

    public function setPlaysAttribute($value)
    {
        $this->attributes['plays'] = implode(',', $value);
    }
}
