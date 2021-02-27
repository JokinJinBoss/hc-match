<?php
namespace CloudLive\Hc\Commands;

use App\Exceptions\BusinessException;
use App\Models\Jobs;
use CloudLive\Common\Base\Crawler;
use CloudLive\Common\Util\Base;
use CloudLive\Hc\Service\JobsService;
use CloudLive\Match\Commands\Base\TelegramBot;
use CloudLive\Common\Config\UrlMap;
use Illuminate\Console\Command;

/**
 * 抓取红彩 专家数据
 * Class ExpertsCommand
 * @package CloudLive\Hc\Commands
 */
class CrawlerJobsCommand extends Command
{
    /**
     * 命令
     * @var string
     */
    protected $signature = 'crawler:hc-jobs';

    /**
     * 描述
     * @var string
     */
    protected $description = '红彩爬虫 --根据固定地址爬取庄家信息，插入爬虫任务';

    protected $chat_id = '-423518356';

    /**
     * 初始化
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        try {

            $uidMap = [];
            $unCode = [];

            $jobClass = new JobsService;
            //获取专家已经插入的任务
            $jobsData = Jobs::select('_un_code')->where('job_type', 0)->get()->toArray();
            if (!empty($jobsData)){
                $jobsData = array_column($jobsData, null, '_un_code');
            }

            //插入专家任务
            foreach (UrlMap::HC_EXPERTS_URL as $hKey => $item){
                $crl = (new Crawler())->setUrl($item)->warpRequest()->async();

                foreach ($crl->results as $item){
                    $data = (array) json_decode($item->getBody()->getContents());
                    if (empty($data)){
                        throw new BusinessException($hKey . "expert data is empty",1070);
                    }
                    $uidMap[$hKey] = $jobClass->exStore($hKey, $data['data'], $unCode, $jobsData);
                }
            }

            //获取已插入的文章任务
            $jobsArData = Jobs::select('_un_code')->where('job_type', 1)->get()->toArray();
            if (!empty($jobsArData)){
                $jobsArData = array_column($jobsArData, null, '_un_code');
            }

            //插入文章任务
            foreach ($uidMap as $classCode => $uMap){
                if (empty($uMap)){
                    continue;
                }

                foreach ($uMap as $uid){
                    $jobClass->arStore($classCode, $uid, $jobsArData);
                    usleep(Base::SleepRand());
                }
            }

        } catch (\Exception $e) {
            dd($e->getMessage());
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
            TelegramBot::makeBody(['monitor'=>'crawler:hc-job','from'=>'hk-idc','subject'=>'hc爬虫异常','date'=>date('Y-m-d H:i:s'),
                'content'=>'crawler:match,[Exception]:'.$e->getCode().$e->getMessage()])
        );
    }
}
