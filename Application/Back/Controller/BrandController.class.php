<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 2017/6/6
 * Time: 下午1:12
 */
namespace Back\Controller;

class BrandController extends CommonController
{
    public function setAction($id=null)
    {

        //分配id 到模板
        $this->assign('id',$id);

        $model = D('Brand');


        if(IS_POST){
            //处理添加的数据

            if($model->create()){

                //判断插入
                if(is_null($id)){
                    //添加
                    $result= $new_id= $model->add();

                }else{
                    $result = $model->save();
                }
                if($result){
                    //设置成功 跳转
                    $this->redirect('list');
                }

            }
            //将错误信息保存到session 重定向
            session('message',$model->getError());
            session('data',$_POST);
            //重定向
            $param = is_null($id) ? [] :['id'=>$id];

            $this->redirect('set',$param);
        }
        else{
            //错误是重定向的再次请求
          $this->assign('message',session('message') ? session('message') : []);

            session('message',null);
            //用户填写时的错误数据提示
           $data=session('data') ? session('data') : [];

           //如果没有错误数据 判断是否为编辑 如果是编辑 获取正在编辑的数据
            if(empty($data) && !is_null($id)){
                //判断为编辑状态
                $data = $model->find($id);
            }

            $this->assign('data',$data);
           session('data',null);

            $this->display();

        }


    }

    public function listAction()
    {
        $model = M('Brand');

        //搜索处理

        $cond= $filter= []; //条件初始化
        //判断url地址来是否有filter_title
        $filter_title = I('get.filter_title',null,'trim');
        if(!is_null($filter_title)){
            $cond['title'] = ['like',$filter_title.'%'];
            $filter['filter_title'] = $filter_title;
        }
        //将搜索条件在分配到模板中
        $this->assign('filter',$filter);

        // 排序字段

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

        //分页处理
        $count = $model->where($cond)->count();

        $limit = 2;
        $page = new \Think\Page($count,$limit);

        $page->setConfig('theme', '<div class="col-sm-6 text-left"><ul class="pagination">%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END%</ul> </div><div class="col-sm-6 text-right">%HEADER%</div>');
        // 提示信息header部分
        $page->setConfig('header', '显示开始 %PAGE_FIRST% 到 %PAGE_LAST% 之 %TOTAL_ROW% （总 %TOTAL_PAGE% 页）');

        // 翻页部分
        $page->setConfig('prev', '&lt;');
        $page->setConfig('next', '&gt;');
        $page->setConfig('first', '|&lt;');
        $page->setConfig('last', '&gt;|');


        $this->assign('page_html',$page->show());

            //获取数据
        $rows=$model->where($cond)
            ->order($order_str)
            //利用分页类 来获取offset 和  limit 避免计算当前页面和offset
            ->limit($page->firstRow.','.$page->listRows)
            ->select(); //分页数据
        $this->assign('rows',$rows);
        $this->display();
    }

    public function titleUniqueCheckAction($id=null)
    {
        $title = I('request.title');
        $model = M('Brand');
        $cond['title'] = $title;
        if(!is_null($id)){
            //当前ID不等于ID
            $cond['id'] = ['NEQ',$id];
        }
        $row = $model->where($cond)->find();
        if($row){
            //检索到 存在 未通过
            echo 'false';  //响应false字符串
        }else{
            //未检索到 ,不存在
            echo 'true';

        }
    }

    public function multiAction()
    {
        // 获取需要操作的id列表
        $selected = I('post.selected', []);
        // 执行删除
        $cond['id'] = ['in', $selected];
        M('Brand')->where($cond)->delete();

        //取消商品关联
        M('Goods')
            ->where(['brand_id'=>['in', $selected]])
            ->save(['brand_id'=>0])
        ;

        $this->redirect('list');
    }

}