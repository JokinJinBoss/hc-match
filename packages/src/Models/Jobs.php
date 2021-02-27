<?php


namespace packages\Pick\Models;


use App\Models\Base;

class Jobs extends Base
{
    protected $table = 'jobs';

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
        'job_type',
        'status',
        "_un_code",
        "url",
        "data_json",
        "recv",
    ];
}
