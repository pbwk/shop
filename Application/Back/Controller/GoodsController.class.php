<?php

namespace Back\Controller;


use Think\Controller;
use Think\Page;

/**
 * Class GoodsController
 * 后台商品管理控制器
 * @package Back\Controller
 */
class GoodsController extends Controller
{

    /**
     * 列表
     */
    public function listAction()
    {
        $model = M('Goods');

        // * 搜索处理
        $cond = $filter = [];// 条件初始化
        // 根据特殊的业务逻辑完成搜索条件的设置
        // 将搜索条件, 再次分配到模板中
        $this->assign('filter', $filter);

        // * 排序字段
        $order = [];
        $order_str = '';
        // 获取用户传递的排序条件
        $order_field = I('get.order_field', null, 'trim');
        $order_type = I('get.order_type', 'asc', 'trim');
        if (!is_null($order_field)) {
            // 排序处理
            $order_str = $order_field . ' ' . $order_type;
            // 记录下当前的排序条件
            $order['order_field'] = $order_field;
            $order['order_type'] = $order_type;
        }
        $this->assign('order', $order);

        // * 分页处理
        // 查询总符合条件的记录数
        $count = $model->where($cond)->count();
        $limit = 10;
        $page = new Page($count, $limit); // use Think\Page;
        // 定制分页样式
        // 整体结构
        $page->setConfig('theme', '<div class="col-sm-6 text-left"><ul class="pagination">%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END%</ul> </div><div class="col-sm-6 text-right">%HEADER%</div>');
        // 提示信息header部分
        $page->setConfig('header', '显示开始 %PAGE_FIRST% 到 %PAGE_LAST% 之 %TOTAL_ROW% （总 %TOTAL_PAGE% 页）');
        // 翻页部分
        $page->setConfig('prev', '&lt;');
        $page->setConfig('next', '&gt;');
        $page->setConfig('first', '|&lt;');
        $page->setConfig('last', '&gt;|');
        $this->assign('page_html', $page->show());

        // * 获取数据
        $rows = $model
            ->where($cond)
            ->order($order_str)
            // 利用分页类获取offset和limit, 避免计算 当前页码 和 offset的操作
            ->limit($page->firstRow . ', ' . $page->listRows)// 分页数据
            ->select();
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

        $model = D('Goods');
        if (IS_POST) {
            // 处理添加的数据
            // 获取品牌(自定义)模型
            // 创建数据(数据校验, 自动填充, 从POST中绑定数据)
            if ($model->create()) {
                // 创建成功, 执行插入,
                if (is_null($id)) {
                    // 添加
                    $result = $new_id = $id = $model->add();
                } else {
                    // 更新
                    $result = $model->save();
                }
                // 判断插入的结果
                if($result) {
                    // 设置成功
                    # 维护 分类与商品关系
                    // 当前商品原有的
                    $categories_id_old = [];
                    // select category_id from category_goods where goods_id=$id
                    if ($fields = M('CategoryGoods')->where(['goods_id'=>$id])->getField('category_id', true)) {
                        $categories_id_old = $fields;
                    }
                    // 本次提交的
                    $categories_id_new = I('post.categories_id', []);

                    // 找到 需要插入的, 和 需要删除的
                    // array_diff(); 差集, 从一个数组中, 找出不存在于另一个数组中的元素.
                    // 找需要插入的
                    $add_list = array_diff($categories_id_new, $categories_id_old);
                    // 构建插入的记录数组, 形成含有: category_id和goods_id的数组
                    $add_rows = array_map(function($category_id) use($id) {
                        return [
                            'category_id' => $category_id,
                            // $id是外层作用域的变量, 需要使用use将其导入.
                            'goods_id' => $id,
                        ];
                    }, $add_list);
                    // 一次性插入多条
                    M('CategoryGoods')->addAll($add_rows);
                    // 需要删除的
                    $delete_list = array_diff($categories_id_old, $categories_id_new);
                    if (!empty($delete_list)) {
                        // 有需要删除的才删除
                        M('CategoryGoods')->where([
                            'goods_id'=>$id,
                            'category_id' => ['in', $delete_list]
                        ])->delete();
                    }

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
                // 所选扩展分类
                $data['categories_id'] = [];
                // select category_id from category_goods where goods_id=$id
                if ($fields = M('CategoryGoods')->where(['goods_id'=>$id])->getField('category_id', true)) {
                    $data['categories_id'] = $fields;
                }
            }
            session('data', null);
            $this->assign('data', $data);

            // 分配相关数据
            $this->assign('stock_status_list', M('StockStatus')->order('sort')->select());
            $this->assign('sku_list', M('Sku')->order('sort')->select());
            $this->assign('length_unit_list', M('LengthUnit')->order('sort')->select());
            $this->assign('weight_unit_list', M('WeightUnit')->order('sort')->select());
            $this->assign('tax_list', M('Tax')->order('sort')->select());
            $this->assign('brand_list', M('Brand')->order('sort')->select());
            $this->assign('category_list', D('Category')->getTree());


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
        M('Goods')->where($cond)->delete();

        $this->redirect('list');
    }
}