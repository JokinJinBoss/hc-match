<?php
namespace packages\Pick\Commands;

use Illuminate\Console\Command;
use packages\Pick\commom\Base;
use packages\Pick\Models\Jobs;
use packages\Pick\Service\ArticleService;
use packages\Pick\Service\JobsService;

/*
 * 文章任务，跑完后跟新状态为已完成
 */
class ArticleCommand extends Command
{
    /**
     * 命令
     * @var string
     */
    protected $signature = 'pick-article';

    /**
     * 描述
     * @var string
     */
    protected $description = '根据任务抓取文章和玩法信息';


    /**
     * 初始化
     */
    public function __construct()
    {
        $this->jobs = new JobsService();
        parent::__construct();
    }

    public function handle()
    {
        try {

            $jobData = Jobs::select('id', 'url', 'class_code','_un_code')->where('job_type', 1)->where('status', 0)->get();
            $serObj = new ArticleService();

            foreach ($jobData as $k => $job) {
                try {

                    $serObj->store($job);
                    usleep(Base::SleepRand());

                    //update sucess
                    $this->jobs->sucess($job->_un_code);
                } catch (\Exception $e) {

                    //update failure
                    $this->jobs->failure($job->_un_code,$e->getMessage());
                }
            }

        } catch (\Exception $e){
            //采集报错可以通过TG或者邮件的形式发送
            dd($e->getMessage());
        }

    }
}
