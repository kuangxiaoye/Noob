<?php
declare (strict_types = 1);

namespace app\command;

use app\Service\Cm\SxService;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

class Qiandao extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('qiandao')
            ->setDescription('the qiandao command');
    }

    protected function execute(Input $input, Output $output)
    {
        (new \app\Service\Tl\TlService())->qianDao();
    }
}
