<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 2017/6/11
 * Time: 上午9:24
 */
namespace Back\Controller;
use Think\Controller;

use Think\Auth;

class CommonController extends Controller
{
    protected function _initialize()
    {
        # 判断当前是否不需要校验
        $no_check = [
            'Admin' => ['login'],
            // 控制器 => 此控制器中的动作列表
//            'Goods' => ['x', 'y'],
        ];
        // 存在该控制器, 同时 动作在控制器中的动作列表中
        if (isset($no_check[CONTROLLER_NAME]) && in_array(ACTION_NAME, $no_check[CONTROLLER_NAME])) {
            // 该动作不需要校验
            return ;
        }

        # 超级管理员特例
        if('1' == session('admin.id')) {
            return ;
        }

        # 授权校验
        $auth = new Auth();
        $rule = CONTROLLER_NAME . '/' . ACTION_NAME;
        $result = $auth->check($rule, session('admin.id'));
        if (! $result) {
            // 授权失败
            // 给出提示, 重新登录
            $this->redirect('Admin/login');
        }
    }
}