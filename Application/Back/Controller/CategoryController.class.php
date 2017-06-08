<?php

namespace Back\Controller;


use Back\Model\CategoryModel;
use Think\Controller;
use Think\Page;

/**
 * Class CategoryController
 * 后台分类管理控制器
 * @package Back\Controller
 */
class CategoryController extends Controller
{

    /**
     * 列表
     */
    public function listAction()
    {
        $model = D('Category');

        // * 搜索处理
        $cond = $filter = [];// 条件初始化
        // 根据特殊的业务逻辑完成搜索条件的设置
        // 将搜索条件, 再次分配到模板中
        $this->assign('filter', $filter);

        // * 排序字段
        $order = [];
        $order_str = '';
        $this->assign('order', $order);

        // * 分页处理
        $this->assign('page_html', '');

        // * 获取数据
        $rows = $model->getTree();
        $this->assign('rows', $rows);
        // 展示
        $this->display();
    }

    /**
     * 设置, 添加和修改
     * @param $id 编辑的主键, 存在为编辑, 不存在为添加
     */
    public function setAction($id=null)
    {
        // 分配id到模板
        $this->assign('id', $id);

        $model = D('Category');
        if (IS_POST) {
            // 处理添加的数据
            // 获取品牌(自定义)模型
            // 创建数据(数据校验, 自动填充, 从POST中绑定数据)
            if ($model->create()) {
                // 创建成功, 执行插入,
                if (is_null($id)) {
                    // 添加
                    $result = $new_id = $model->add();
                } else {
                    // 更新
                    $result = $model->save();
                }
                // 判断插入的结果
                if($result) {
                    // 设置成功

                    // 删除缓存
                    $this->clearCache();

                    // 跳转到列表页
                    $this->redirect('list');
                }
            }
            // create()或者add()失败
            // 将参错误信息和错误数据(原始的POST数据)
            session('message', $model->getError());
            session('data', $_POST);
            // 重定向
            $param = is_null($id) ? [] : ['id'=>$id];
            $this->redirect('set', $param);
        }

        else {
            // 可能存在的错误数据和错误消息, 分配(assign)到模板中
            $this->assign('message', session('message') ? session('message') : []);
            // 一次性的会话数据, 立即删除(阅后即焚)
            session('message', null);

            // 用户填写的错误数据
            $data = session('data') ? session('data') : [];
            // 如果没有错误数据, 判断当前是否为编辑, 如果是编辑, 获取正在编辑的数据
            if (empty($data) && !is_null($id)) {
                // 编辑操作
                // 当前正在编辑的数据
                $data = $model->find($id);
            }
            $this->assign('data', $data);
            session('data', null);

            # 需要的数据
            //全部分类
            $this->assign('category_list', $model->getTree());

            // 表单展示
            $this->display();
        }

    }

    /**
     * 批量操作: 删除
     */
    public function multiAction()
    {
        // 获取需要操作的id列表
        $selected = I('post.selected', []);
        // 执行删除
        $cond['id'] = ['in', $selected];
        M('Category')->where($cond)->delete();

        // 删除缓存
        $this->clearCache();

        $this->redirect('list');
    }

    private function clearCache()
    {
        // 初始化缓存配置
        \S([
            'type' => 'redis', // redis类型缓存
            'host' => \C('REDIS_HOST'),
            'port' => \C('REDIS_PORT'),
        ]);
        // 删除
        \S(CategoryModel::CACHE_TREE, null);
    }
}