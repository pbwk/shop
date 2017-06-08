<?php


namespace Back\Model;


use Think\Model;

/**
 * 分类 模型
 */
class CategoryModel extends Model
{
    // 开启批量验证
    protected $patchValidate = true;
    // 验证规则
    protected $_validate = [
        // 补充验证规则
    ];
    // 自动填充
    protected $_auto = [
        ['created_at', 'time', self::MODEL_INSERT, 'function'],
        ['updated_at', 'time', self::MODEL_BOTH, 'function'],
        // 补充填充规则
    ];

    public function getTree()
    {
        //初始化缓存配置
        \S([
            'type' => 'redis', //缓存类型为redis
            'host' => \C('REDIS_HOST'),
            'port' => \C('REDIS_PORT'),
        ]);

        //选获取全部的分类
        $rows = $this->select();
        //在完成递归的计算子分类及其缩进层级数
        $tree = $this->tree($rows,0,0);

        return $tree;
    }

    /**
     * 递归计算子分类和分类层级
     *
     */
    private function tree($rows,$id=0,$level=0)
    {
        //静态数组 存储所有找到的后代分类
        static $tree = [];
        //遍历全部分类
        foreach ($rows as $row){
            //判断是否为当前$id 分类的子分类
            if($id == $row['parent_id']){
                //是子分类
                //记录当前分类的层级数
                $row['level'] = $level;
                //存储到$tree中
                $tree[] = $row;
             //递归检索其后代分类
                $this->tree($rows,$row['id'],$level+1);

            }
        }
        //返回$tree
        return $tree;
    }
}