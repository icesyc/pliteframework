<?php
/**
 * 角色管理器
 *
 * 角色管理器只提供简单的角色管理，表结构如下
 * CREATE TABLE `role` (
 *  `role_id` int(11) unsigned NOT NULL auto_increment,
 *  `role_name` varchar(32) default NULL,
 *  PRIMARY KEY  (`role_id`),
 *  KEY `role_id` (`role_id`)
 * )
 *
 * @package    Plite.Lib.RBAC
 * @author     ice_berg16(寻梦的稻草人)
 * @copyright  ice_berg16@163.com
 * @version    $Id$
 */

class Role extends Model
{

	//表名称
	protected $table = 'role';

	/**
	 * 构造函数
	 */
	public function __construct()
	{
		parent::__construct();
		$this->assocTbl = Config::get("tablePrefix") . "role_act";
		$this->actTbl	= Config::get("tablePrefix") . "act";
	}

	/**
	 * 添加一个角色，同时将角色关联的操作资源保存到角色资源关联表
	 * 
	 * @param array $role 角色数据数组
	 * @param array $act 资源数组
	 * @return integer $id 生成的角色id
	 */
	public function create($role, $acts)
	{
		$id = parent::create($role);
		$this->updateAct($id, $acts);
		return $id;
	}
	
	/**
	 * 更新一个角色信息
	 *
	 * @param array $role 角色数据数组
	 * @param array $act 资源数组
	 */
	public function update($role, $acts)
	{
		parent::update($role);
		$this->updateAct($role['role_id'], $acts);
	}
	
	/**
	 * 删除一个角色
	 *
	 * @param integer|array $id 角色id或数组
	 * @return boolean
	 */
	public function delete($id)
	{
		$id  = is_array($id) ? "=" . $id : "IN (" .join(",",$id) . ")";
		$sql = "DELETE FROM %s WHERE role_id ". $id;
		$this->DB->exec(sprintf($sql, $this->fullTableName));
		$this->DB->exec(sprintf($sql, $this->assocTbl));
		return true;
	}
	
	/**
	 * 取得某个角色所能访问的资源
	 *
	 * @param integer|array $id 角色id
	 * @return array 记录集
	 */
	public function getAct($id)
	{
		$id  = !is_array($id) ? "=" . $id : "IN (" .join(",",$id) . ")";
		$sql = "SELECT ra.role_id, a.act_id, a.act_name, a.controller, a.action
				FROM %s AS ra
				INNER JOIN %s AS a ON ra.act_id=a.act_id
				WHERE ra.role_id " . $id;
		$sql = sprintf($sql, $this->assocTbl, $this->actTbl);
		return $this->findAllBySql($sql);
	}
	
	/**
	 * 取得某个角色所能访问的资源,只返回资源的id列表
	 * 
	 * @param integer|array $id 角色id
	 * @return array 资源的id列表
	 */
	public function getActList($id)
	{
		$id  = !is_array($id) ? "=" . $id : "IN (" .join(",",$id) . ")";
		$sql = sprintf("SELECT act_id FROM %s WHERE role_id %s", $this->assocTbl, $id);
		$res = $this->findAllBySql($sql);
		$r   = array();
		foreach($res as $row)
		{
			array_push($r, $row['act_id']);
		}
		return $r;
	}

	/**
	 * 检查某个角色对指定资源是否有访问权限
	 *
	 * @param integer|array $id 角色id
	 * @param string $controller 控制器名称
	 * @param string $action	动作名称
	 * @return boolean 
	 */
	public function checkAct($id, $controller, $action)
	{
		$id  = !is_array($id) ? "=" . $id : "IN (" .join(",",$id) . ")";
		$sql = "SELECT ra.role_id, a.controller, a.action, a.act_name 
				FROM %s AS ra
				INNER JOIN %s AS a ON ra.act_id=a.act_id
				WHERE a.controller='$controller' AND a.action='$action' AND ra.role_id " . $id;
		$sql = sprintf($sql, $this->assocTbl, $this->actTbl);
		return $this->findBySql($sql) ? true : false;
	}
	
	/**
	 * 更新角色资源关联信息
	 *
	 * @param integer $id 角色id
	 * @param array $acts 资源数组
	 * @return boolean
	 */
	private function updateAct($id, $acts)
	{
		//先删除原来的
		$sql = sprintf("DELETE FROM %s WHERE role_id=%d", $this->assocTbl, $id);
		$this->DB->exec($sql);
		//添加新的关联信息
		foreach($acts as $act)
		{
			$sql = sprintf("INSERT INTO %s (role_id, act_id) VALUES(%d, %d)", 
				$this->assocTbl, $id, $act);
			$this->DB->exec($sql);
		}
		return true;
	}
}
?>