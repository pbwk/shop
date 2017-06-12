<?php

namespace Back\Controller;


use Think\Auth;

use Think\Page;

/**
 * Class GoodsController
 * 后台商品管理控制器
 * @package Back\Controller
 */
class GoodsController extends CommonController
{

    /**
     *回收站列表
     */
     function  trashAction()
     {
         $this->select('trash');
     }
    /**
     * 查询列表的公共代码
     */
    function  select($type='list')
    {
        $this->assign('type',$type);

        $model = M('Goods');

        // * 搜索处理
        $filter = [];// 条件初始化

        $cond = ['is_deleted'=>'list'==$type?'0':'1'];

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
        $this->display('list');
    }

    /**
     * 复制操作
     */
    public function copyAction($id)
    {
        $old_id = $id; //原来的商品ID

        //复制，goods表
        $old_row = M('Goods')->find($old_id);
        //剔除主键
        unset($old_row['id']);
        //形成新的货号
        $old_row['upc'] = ''; //暂时为空 等待编辑
        D('Goods')->create($old_row);
        $new_id = D('Goods')->add();

        //复制 goods_attribute
        $rows_ga = M('GoodsAttribute')->field('attribute_id,sort,value')
            ->where(['goods_id'=>$old_id])->select();
        $rows_ga_new = array_map(function ($row) use($new_id){
            $row['goods_id'] = $new_id;
            $row['created_at'] = $row['updatd_at'] = \time();
            return $row;
        },$rows_ga);
        M('GoodsAttribute')->addAll($rows_ga_new);

        //重定向到新商品的编辑页面
        $this->redirect('list',['id'=>$new_id]);
    }
    /**
     * 列表
     */
    public function listAction()
    {
        $auth = new Auth();
        $result= $auth->check('Goods/list',session('admin.id'));
//        dump($result);
//        die();
        $this->select('list');
    }

    public function attrAction($id)
    {
        $this->assign('id',$id);
        $model = M('Goods');
        $goods = $model->find($id);
        if(IS_POST){
            //更新 goods.type_id
            $type_id = I('post.type_id',0);
            $model->type_id = $type_id; //AR模式 操作属性 就是操作字段
            $model->save();

            $model_ga = M('GoodsAttribute');

            // 删除非被type_id对应的商品属性关联
            $attribute_id_list = M('Attribute')
                ->where(['type_id'=>$type_id])
//                ->fetchSql(true)
                ->getField('id', true);
            $model_ga
                ->where([
                    'goods_id' => $id,
                    'attribute_id' => ['not in', $attribute_id_list]
                ])
                ->delete()
            ;
            $rows_ga = [];

            foreach (I('post.ga',[]) as $attr){
                //判断属性是否已存在
                 $row = $model_ga->where(['goods_id'=>$id,'attribute_id'=>$attr['attribute_id']])->find();
                if($row){
                    //存在 更新即可
                    $row['value'] = $attr['value'];
                    $model_ga->save($row);
                }else{
                    //不存在属性与商品的对应
                    $rows_ga[] = [
                        'goods_id'=> $id,
                        'attribute_id'=> $attr['attribute_id'],
                        'value'=> $attr['value'],
                    ];
                }
            }
            //一次性插入结果
            $model_ga->addAll($rows_ga);

            $this->redirect('attr',['id'=>$id]);

        }
        else{
            //GET
            //设置 展示
            //分配数据
            $this->assign('goods',$goods);
            $this->assign('type_list',M('Type')->order('sort')->select());

            $this->display();
        }


    }

    /**
     * 设置, 添加和修改
     * @param $id 编辑的主键, 存在为编辑, 不存在为添加
     */
    public function setAction($id=null)
    {
//
//        $auth = new Auth();
//        $result = $auth->check('Goods/set',session('admin.id'));
//        dump($result);
//        die();
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
     * ajax获取属性组内属性列表
     * 响应 ajax事件的方法
     */
    public function attrlistAction()
    {
        $goods_id = I('request.goods_id',0);
        $type_id = I('request.type_id',0);

        #属性列表
        $attr_list = M('Attribute')
            ->field('a.id,a.title,ga.value ga_value,ga.id as ga_id')
            ->alias('a')
            ->join("left join __GOODS_ATTRIBUTE__ ga ON ga.attribute_id = a.id AND ga.goods_id = $goods_id")
            ->where(['a.type_id'=>$type_id])
            ->order('a.sort')
            ->select();
        //ajax 的json 响应
        $this->ajaxReturn([
            'error' => 0,
            'data' => $attr_list
        ]);
    }
    /**
     * 批量操作: 删除
     */
    public function multiAction()
    {
        // 获取需要操作的id列表
        $selected = I('post.selected', []);

        if(empty($selected)){
            $this->redirect(I('post.operate')=='delete'?'list':'trash');
        }
        // 执行删除
        $cond['id'] = ['in', $selected];
        //确定操作
        switch (I('post.operate')){
            case 'delete':

                $auth = new Auth();
                $rule = 'goods-multi-delete';
                $result =  $auth->check($rule,session('admin_id'));
                if(!$result){
                    //授权失败
                    //提示
                    $this->redirect('Admin/login');
                }

                //执行逻辑删除
                M('Goods')->where($cond)->save(['is_deleted'=>1]);
                $this->redirect('list');
                break;

            case 'remove':


                $auth = new Auth();
                $rule = 'goods-multi-remove';
                $result =  $auth->check($rule,session('admin_id'));
                if(!$result){
                    //授权失败
                    //提示
                    $this->redirect('Admin/login');
                }
                //执行逻辑删除
                M('Goods')->where($cond)->delete();
                $this->redirect('trash');
                break;

            case 'undo':


                $auth = new Auth();
                $rule = 'goods-multi-undo';
                $result =  $auth->check($rule,session('admin_id'));
                if(!$result){
                    //授权失败
                    //提示
                    $this->redirect('Admin/login');
                }
                M('Goods')->where($cond)->save(['is_deleted'=>0]);
                $this->redirect('trash');
                break;
        }
//        M('Goods')->where($cond)->save(['is_deleted'=>1]);
//        $this->redirect('list');
    }
}