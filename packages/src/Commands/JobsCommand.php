<?php
namespace packages\Pick\Commands;

use App\Exceptions\BusinessException;

use Illuminate\Console\Command;
use packages\Pick\commom\Base;
use packages\Pick\commom\Crawler;
use packages\Pick\Models\Jobs;
use packages\Pick\Service\JobsService;

/**
 * 抓取红彩 专家数据
 * Class ExpertsCommand
 * @package CloudLive\Hc\Commands
 */
class JobsCommand extends Command
{
    /**
     * 命令
     * @var string
     */
    protected $signature = 'pick-jobs';

    /**
     * 描述
     * @var string
     */
    protected $description = '根据网易红彩地址抓取信息，插入爬虫任务';

    private $url = [
        'football' => 'https://hongcai.163.com/api/web/expert/list/1/0/1000',         //足球
        'basketball' => 'https://hongcai.163.com/api/web/expert/list/2/0/1000',        //篮球
    ];

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
            foreach ($this->url as $hKey => $item){
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
        }
    }
}
