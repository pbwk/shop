<?php


namespace Back\Model;


use Think\Model;

/**
 * 长度单位 模型
 */
class LengthUnitModel extends Model
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
}