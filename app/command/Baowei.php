<?php
declare (strict_types = 1);

namespace app\command;

use app\Service\Cm\SxService;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

class Baowei extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('baowei')
            ->setDescription('the baowei command');
    }

    protected function execute(Input $input, Output $output)
    {
        // 指令输出
        (new \app\Service\Takeout\BaoWeiService())->autoGetOrder();
    }
}
