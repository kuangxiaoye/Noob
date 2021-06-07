<?php
declare (strict_types = 1);

namespace app\command;

use app\Service\Cm\SxService;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

class Sxdsgoods extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('sxdsgoods')
            ->setDescription('the sxdsgoods command');
    }

    protected function execute(Input $input, Output $output)
    {
        // 指令输出
        (new SxService())->sxdsgoods();
    }
}
