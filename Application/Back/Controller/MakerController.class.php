<?php

namespace Back\Controller;

use Think\Controller;

/**
 * Class MakerController
 * 生成功能控制器
 * @package Back\Controller
 */
class MakerController extends Controller
{

    public function tableAction()
    {
        $this->display();
    }

    public function generateAction()
    {
        // post请求执行
        if (! IS_POST) {
            $this->redirect('table');
        }

        # 获取表单提交的数据
        $table = I('post.table', null, 'trim');
        $comment =  I('post.comment', '', 'trim');

        # 处理需要替换的数据
        // 下划线分割, 首字母大写, 连接到一起
        // 获取控制器
        $controller = implode(array_map('ucfirst', explode('_', $table)));
        // 获取模型
        $model = implode(array_map('ucfirst', explode('_', $table)));
        // 获取标题
        $title = ''===$comment ? $table : $comment;

        # 替换生成控制器
        $template = \file_get_contents(APP_PATH . 'Back/CodeTemplate/controller.template');
        $search = ['%CONTROLLER%', '%MODEL%', '%TITLE%'];// 占位符集合
        $replace = [$controller, $model, $title]; // 替换的内容
        // 执行替换
        $content = str_replace($search, $replace, $template);
        // 写入到指定文件
        $file = $controller . 'Controller.class.php';
        \file_put_contents(APP_PATH . 'Back/Controller/' . $file, $content);
        echo '控制器文件: ', $file, ' 生成成功', '<br>';

        # 生成模型
        $template = \file_get_contents(APP_PATH . 'Back/CodeTemplate/model.template');
        $search = ['%MODEL%', '%TITLE%'];// 占位符集合
        $replace = [$model, $title]; // 替换的内容
        // 执行替换
        $content = str_replace($search, $replace, $template);
        // 写入到指定文件
        $file = $model . 'Model.class.php';
        file_put_contents(APP_PATH . 'Back/Model/' . $file, $content);
        echo '模型文件: ', $file, ' 生成成功', '<br>';

        # 创建视图子目录
        $view_path = APP_PATH . 'Back/View/' . $controller . '/';
        if (! is_dir($view_path)) {
            mkdir ($view_path, 0755, true);// 0755 0八进制数(整型的表示方式)
        }

        # 生成列表模板
        ## 与字段相关的模板
        $table_head_list = $table_data_list = '';
        // 遍历全部的字段
        foreach(I('post.field', []) AS $field) {
            // 找到需要列表展示的字段
            if (isset($field['is_list'])) {
                // 拼凑表头部分
                if (isset($field['is_order'])) {
                    $template = \file_get_contents(APP_PATH . 'Back/CodeTemplate/table_head_order.template');
                } else {
                    $template = \file_get_contents(APP_PATH . 'Back/CodeTemplate/table_head.template');
                }
                // 替换生成
                $search = ['%FIELD-NAME%', '%FIELD-COMMENT%'];// 占位符集合
                $replace = [$field['name'], $field['comment']]; // 替换的内容
                // 执行替换
                $table_head_list .= str_replace($search, $replace, $template);

                // 拼凑数据部分
                $template = \file_get_contents(APP_PATH . 'Back/CodeTemplate/table_data.template');
                // 替换生成
                $search = ['%FIELD-NAME%'];// 占位符集合
                $replace = [$field['name']]; // 替换的内容
                // 执行替换
                $table_data_list .= str_replace($search, $replace, $template);
            }
        }
        ## list 整体模板
        $template = \file_get_contents(APP_PATH . 'Back/CodeTemplate/list.template');
        // 替换生成
        $search = ['%TITLE%', '%TABLE-HEAD-LIST%', '%TABLE-DATA-LIST%'];// 占位符集合
        $replace = [$title, $table_head_list, $table_data_list]; // 替换的内容
        // 执行替换
        $content = str_replace($search, $replace, $template);
        // 写入到指定文件
        $file = 'list.html';
        file_put_contents($view_path . $file, $content);
        echo '列表模板文件: ', $file, ' 生成成功', '<br>';


        # 生成set模板
        ## 遍历字段, 生成需要set的字段模板
        $form_field_list = '';
        foreach(I('post.field', []) AS $field) {
            // 是否为需要设置
            if (isset($field['is_set'])) {
                $template = \file_get_contents(APP_PATH . 'Back/CodeTemplate/form_field.template');
                // 替换生成
                $search = ['%FIELD-NAME%', '%FIELD-COMMENT%', '%IS-REQUIRED%'];// 占位符集合
                $replace = [$field['name'], $field['comment'], isset($field['is_required'])?'required':'']; // 替换的内容
                // 执行替换
                $form_field_list .= str_replace($search, $replace, $template);
            }
        }
        ## 整体设置模板
        $template = \file_get_contents(APP_PATH . 'Back/CodeTemplate/set.template');
        // 替换生成
        $search = ['%TITLE%', '%FORM-FIELD-LIST%'];// 占位符集合
        $replace = [$title, $form_field_list]; // 替换的内容
        // 执行替换
        $content = str_replace($search, $replace, $template);
        // 写入到指定文件
        $file = 'set.html';
        file_put_contents($view_path . $file, $content);
        echo '设置模板文件: ', $file, ' 生成成功', '<br>';

    }

    public function tableInfoAction()
    {
        # 表名
        $table = I('request.table', null, 'trim');

        # 获取表的comment
        $table_schema = \C('DB_NAME');
        $table_name = \C('DB_PREFIX') . $table;
        $sql = "SELECT * FROM `information_schema`.`TABLES` WHERE TABLE_SCHEMA='$table_schema' AND TABLE_NAME='$table_name'";
        $rows = M()->query($sql); //(new Model())->query(), 返回2维
        $table_comment = $rows[0]['table_comment'];

        # 获取字段信息
        $sql = "SELECT COLUMN_NAME, COLUMN_COMMENT FROM `information_schema`.`COLUMNS` WHERE TABLE_SCHEMA='$table_schema' AND TABLE_NAME='$table_name'";
        $field_list = M()->query($sql);

        // 响应数据
        $this->ajaxReturn([
            'table_comment'=>$table_comment,
            'field_list' => $field_list,
        ]);// 返回json数据
    }

}