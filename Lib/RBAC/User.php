<?php
/**
 * 用户管理器
 *
 * 用户管理器提供对RBAC的用户进行管理，表结构如下
 * CREATE TABLE `user` (
 *  `user_id` int(11) unsigned NOT NULL auto_increment,
 *  `user_name` varchar(32) NOT NULL,
 *  `password` varchar(32) NOT NULL,
 *	`role_id` varchar(32) NOT NULL,
 *  PRIMARY KEY  (`user_id`)
 * )
 *
 * @package    Plite.Lib.RBAC
 * @author     ice_berg16(寻梦的稻草人)
 * @copyright  ice_berg16@163.com
 * @version    $Id$
 */

class User extends Model
{
	//用户名存在的异常代码
	const USER_EXISTS = -1;

	//用户名不存在的异常代码
	const USER_NONEXISTS = -2;

	//密码错误的异常代码
	const PASSWORD_INVALID = -3;

	protected $table = "user";
	
	/**
	 * 创建一个账号
	 *
	 * @param array $data 用户信息数组
	 */
	public function create($data)
	{
		$rows = $this->findByUserName($data['user_name']);
		if(!empty($rows))
			throw new Exception("用户名已经存在", self::USER_EXISTS);
		$data['password'] = md5($data['password']);
		return parent::create($data);
	}

	/**
	 * 取得用户的角色
	 * 
	 * @return string
	 */
	public function getRole()
	{
		if(isset($_SESSION['RBAC']) && isset($_SESSION['RBAC']['role_id']))
		{
			return $_SESSION['RBAC']['role_id'];
		}
		return false;
	}

	/**
	 * 返回存在在session中的RBAC信息
	 *
	 * @return array 
	 */
	public function RBACInfo()
	{
		if(isset($_SESSION['RBAC']))
		{
			return $_SESSION['RBAC'];
		}
		return false;
	}

	/**
	 * 帐户登录检查程序
	 *
	 * @param string $name 帐户名
	 * @param string $password  帐户密码
	 * @reutrn boolean 如果用户名或密码错误则会抛出异常
	 */
	public function login($name, $password)
	{
		$user = $this->findByUserName($name);
		if (empty($user))
			throw new Exception("用户名不存在", self::USER_NONEXISTS);

		if($user['password'] == md5($password))
		{
			//登录成功
			$_SESSION['RBAC'][Config::get("RBAC.loginSuccessFlag")]	= true;
			$_SESSION['RBAC']['user_id']	= $user['user_id'];
			$_SESSION['RBAC']['user_name']	= $name;
			$_SESSION['RBAC']['role_id']	= $user['role_id'];

			//保存到session中的user字段
			if($f = Config::get("RBAC.sessionFields")){
				foreach($f as $v){
					$_SESSION['RBAC'][$v] = $user[$v];
				}
			}
			return true;
		}
		else
			throw new Exception("密码错误", self::PASSWORD_INVALID);		
	}
	
	/**
	 * 判断用户是否登录
	 * 
	 * @return true 已登录 false 未登录
	 */
	public function isLoginned() {
		return !empty($_SESSION['RBAC'][Config::get("RBAC.loginSuccessFlag")]);
	}

	/**
	 * 用户注销登录
	 */ 
	public function logout()
	{
		unset($_SESSION['RBAC']);
	}

	/**
	 * 帐户修改密码
	 *
	 * @param $id 帐户编号
	 * @param $newPwd 新密码
	 * @return boolean
	 */
	public function setPwd($id, $newPwd)
	{
		return $this->update(array("user_id"=>$id, "password"=>md5($newPwd)));
	}
}
?>