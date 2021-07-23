<?php
// 应用公共文件

/**
 * 获取当前时间 Y-m-d H:i:s
 */
function dateNow()
{
    return date("Y-m-d H:i:s", time());
}

function dateNowDay()
{
    return date("Y-m-d", time());
}