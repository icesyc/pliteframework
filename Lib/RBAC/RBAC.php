<?php
/**
 * RBAC类，用于检测用户的权限
 *
 * @package    Plite.Lib.RBAC
 * @author     ice_berg16(寻梦的稻草人)
 * @copyright  ice_berg16@163.com
 * @version    $Id$
 */

class RBAC
{
	public function __construct()
	{}
	
	/**
	 * 验证角色是否对指定资源有权限访问
	 *
	 * @param integer|array $roleId 角色id
	 * @param string $controller 控制器名称
	 * @param string $action	动作名称
	 * @return boolean
	 */
	public static function validate($roleId, $controller, $action)
	{
		$path = Config::get("RBAC.aclPath");
		if($path == "database"){
			Plite::load("Plite.Lib.RBAC.Role");
			$role = new Role();
			if($roleId === false){
				$roleId = Config::get("RBAC.noneRole");
				return $role->checkAct($roleId, $controller, $action);
			}
			//查找当前角色和默认角色是否有该权限
			$roleId = array($roleId, Config::get("RBAC.defaultRole"));
			return $role->checkAct($roleId, $controller, $action);
		}
		else{
			$acl = self::getAcl($roleId);
			return self::check($acl, $controller, $action);
		}
	}

	/**
	 * 取得当前角色的的访问控制列表
	 *
	 * @param $roldId int 角色id
	 */
	public static function getAcl($roleId){
		$path = Config::get("RBAC.aclPath");
		if(!file_exists($path)){
			throw new Exception("ACL文件不存在");
		}
		$act = require($path);
		//没有任何角色的用户
		if($roleId === false) {
			$role = Config::get("RBAC.noneRole");
			return isset($act[$role]) ? $act[$role] : array();
		}
		if(isset($act[$roleId])){
			return $act[$roleId];
		}
		else{
			if(isset($act[Config::get("RBAC.defaultRole")])){
				return $act[Config::get("RBAC.defaultRole")];
			}
			else{
				return array();
			}
		}
	}

	/**
	 * 对当前的控制器和动作进行检查,如果不需要验证则直接dispatch
	 *
	 * @param $acl string|array 定义的访问控制列表数组
	 * @param $controller string 当前控制器
	 * @param $action string 当前动作
	 */
	public static function check($acl, $controller, $action)
	{
		//对所有动作不进行检查
		if(is_string($acl) && $acl == 'all')
		{
			return true;
		}

		if(!is_array($acl)) throw new Exception('acl应该是一个数组');

		$allow = false;

		//当前访问的控制器在允许列表中
		if(array_key_exists($controller, $acl))
		{
			//取得允许列表中的动作
			$actionList = $acl[$controller];

			//如果动作为'all',则说明该控制器里的所有动作都允许访问
			if(is_string($actionList) && $actionList == 'all')
			{
				$allow = true;
			}
			//在允许访问的动作列表中检查当前动作
			elseif(is_array($actionList) && in_array($action, $actionList))
			{
				$allow = true;
			}
		}
		//允许访问
		if($allow)
		{
			return true;
		}
		//不允许访问时返回false
		return false;
	}
}
?>