<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 2017/6/7
 * Time: 上午8:47
 */

//排序  兼容U函数

/**
 * @param $route  入口地址
 * @param array $param    参数
 * @param array $order  当前的排序规则['order_sort'=>'sort','order_type'=>'desc']  当前的排序组队 当前的排序顺序
 * @param string $field     需要生成的排序的字段名
// */
function Uorder($route,$param=[],$order=[],$field='')
{
    //如果需要排序的字段 正好等于当前排序的字段
    if($field == $order['order_field']) {
        //进行逆序排序
        $param['order_field'] = $field;
        $param['order_type'] ='asc' == $order['order_type'] ? 'desc' : 'asc';
    }else{
        //使用其他字段进行排序
        $param['order_field'] = $field;
        $param['order_type'] = 'asc';
    }

    return U($route,$param);
}
//function Uorder($route, $param=[], $order=[], $field='')
//{
//    // 判断当前是否以待排序字段进行排序
//    if($field == $order['order_field']) {
//        // 已经依据当前字段排序
//        $param['order_field'] = $field;
//        $param['order_type'] = 'asc' == $order['order_type'] ? 'desc' : 'asc';
//    } else {
//        // 当前使用其他字段进行排序
//        $param['order_field'] = $field;
//        $param['order_type'] = 'asc';
//    }
//    // U函数, 生成链接地址, 在参数处, 额外增加了关于排序的参数.
//    return U($route, $param);
//}

function authCheck($rule,$relation='or')
{

    # 超级管理员特例

    if('1' == session('admin.id')){
        return true;
    }

    $auth = new \Think\Auth();
    return $auth->check($rule,session('admin.id'),1,'url',$relation);
}

/*
 *如何确定用户是否拥有权限 将权限设置在用户组上
 * 如何去校验是否拥有权限 比对当前用户于当前动作(权限)之间的映射关系
 * 根据权限展示特定操作 可以选择 ：1 没有权限不展示 2 展示但不能操作
 */

function classOrder($order=[],$field='')
{
    //如果使用该字段排序 直接返回排序方式即可
    //否则返回 空字符
    return $field == $order['order_field'] ? $order['order_type'] : '';
}