<?php


namespace Back\Model;


use Think\Model;

/**
 * 管理员 模型
 */
class AdminModel extends Model
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

  /**
   * 根据用户名和密码校验
   */
  public function checkAdmin($username,$password)
  {
      //获取用户
      $row = $this->getByUsername($username);
      if($row){
          //找到某个用户
          //判断密码
          if($row['password'] == md5($password. $row['salt'])){
              //密码正确
              unset($row['password']);
              return $row;
          }
      }
      return false;
  }
}