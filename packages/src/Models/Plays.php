<?php


namespace packages\Pick\Models;


use App\Models\Base;

class Plays extends Base
{
    protected $table = 'plays';

    protected $primaryKey = "id";

    public $timestamps = false;

    protected $casts = [
        'info' => 'json',
        'matchs' => 'json',
    ];

    protected $fillable = [
        'code',
        'name',
        "concede",
        "info",
        "matchs",
        "_alias",
        "match_time",
        "jc_num",
        "league",
        "category_name",
        "created_at",
        "match_status",
        "_alias_grouping",
    ];
}
