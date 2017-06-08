<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 2017/6/7
 * Time: 上午8:47
 */
function Uorder($route,$param=[],$order=[],$field='')
{
    //判断是否按排序字段进行排序
    if($field == $order['order_field']){
        //已经依据当前字段进行排序
        $param['order_field'] = $field;
        $param['order_type'] = 'asc' == $order['order_type'] ? 'desc' : 'asc';
    }else{
        //使用其他字段进行排序
        $param['order_field'] = $field;
        $param['order_type'] = 'asc';
    }

    //U函数
    return U($route,$param);

}
function classOrder($order=[],$field='')
{
    //如果使用该字段排序 直接返回排序方式即可
    //否则返回 空字符
    return $field == $order['order_field'] ? $order['order_type'] : '';
}