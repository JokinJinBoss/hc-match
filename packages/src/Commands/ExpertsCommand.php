<?php
namespace packages\Pick\Commands;


use Illuminate\Console\Command;
use packages\Pick\commom\Base;
use packages\Pick\Models\Jobs;
use packages\Pick\Service\ExpertsService;
use packages\Pick\Service\JobsService;

/*
 * 专家任务每次采集都要所有专家数据一起跟新
 */
class ExpertsCommand extends Command
{
    /**
     * 命令
     * @var string
     */
    protected $signature = 'pick-experts';

    /**
     * 描述
     * @var string
     */
    protected $description = '根据任务抓取专家信息';

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

            $jobData = Jobs::select('_un_code', 'url', 'class_code')->where('job_type', 0)->where('status',0)->get();
            $serObj = new ExpertsService();
            foreach ($jobData as $job){
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
