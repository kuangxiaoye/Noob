<?php
declare (strict_types = 1);

namespace app\command;

use app\Service\Cm\SxService;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * 爬取神仙代售账号
 * Class Sxds
 * @package app\command
 */
class Sxdsme extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('sxdsme')
            ->setDescription('the sxdsme command');
    }

    protected function execute(Input $input, Output $output)
    {
        (new SxService())->sxdsme();//12311
    }
}
