<?php


namespace Back\Model;


use Think\Model;

class BrandModel extends Model
{
    // 开启批量验证
    protected $patchValidate = true;
    // 验证规则
    protected $_validate = [
        ['title', 'require', '品牌名称必须'],
        ['title', '', '品牌已经存在', self::EXISTS_VALIDATE, 'unique'],
        ['site', 'url', '官网地址必须为有效的URL', self::VALUE_VALIDATE], // 值不为空时验证
        ['sort', 'require', '排序字段必须'],
        ['sort', 'number', '排序需要使用数值'],
    ];
    // 自动填充
    protected $_auto = [
        ['created_at', 'time', 1, 'function'],
        ['updated_at', 'time', 2, 'function'],
    ];
}