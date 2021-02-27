<?php
namespace CloudLive\Hc\Commands;

use App\Models\Jobs;
use CloudLive\Hc\Service\JobsService;
use CloudLive\Common\Util\Base;
use CloudLive\Hc\Service\ArticleService;
use CloudLive\Match\Commands\Base\TelegramBot;
use Illuminate\Console\Command;

/*
 * 文章任务，跑完后跟新状态为已完成
 */
class CrawlerArticleCommand extends Command
{
    /**
     * 命令
     * @var string
     */
    protected $signature = 'crawler:hc-article';

    /**
     * 描述
     * @var string
     */
    protected $description = '红彩爬虫 --根据爬虫任务爬取文章详情数据和玩法数据';

    protected $chat_id = '-423518356';

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
                    dd($e->getMessage());
                    //update failure
                    $this->jobs->failure($job->_un_code,$e->getMessage());
                }
            }

        } catch (\Exception $e){
            $this->__alarm($e);
        }

    }
    /**
    * tg-报警
    *
    * @return mixed  default string,
    *  else Exception
    *
    */
    public function __alarm($e)
    {
        TelegramBot::sendMessage(
            $this->chat_id,
            TelegramBot::makeBody(['monitor'=>'crawler:hc-job','from'=>'crawler:hc-article','subject'=>'hc爬虫异常','date'=>date('Y-m-d H:i:s'),
                'content'=>'crawler:match,[Exception]:'.$e->getCode().$e->getMessage()])
        );
    }
}
