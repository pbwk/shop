<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 2017/6/8
 * Time: 下午9:14
 */

function htmlScriptFilter($content='')
{
    //s, 万能点 单行模式
    //i 大小写不敏感
    $pattern = '/<script.*?>.*?<\/script>/si';
    //使用回调函数的结果进行替换
    return \preg_replace_callback($pattern,function ($match){
        return \htmlspecialchars($match[0]);
    },$content);
}