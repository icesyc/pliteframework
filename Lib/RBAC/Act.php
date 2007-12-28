<?php
/**
 * RBAC中的资源管理器
 *
 * 资源是以controller和action的形式保存在数据表中，如果两条资源记录的controller和action都相同，那么它们是同一资源
 * 默认情况下资源在创建时会检查这种唯一性。<br/>
 * 表的结构如下
 * CREATE TABLE `act` (
 *	`id` int(11) unsigned NOT NULL auto_increment,
 *	`act_name` varchar(32) NOT NULL,
 *	`controller` varchar(128) NOT NULL,
 *	`action` varchar(128) default NULL,
 *	PRIMARY KEY  (`id`),
 *  KEY `controller` (`controller`,`action`)
 * );
 *
 * @package    Plite.Lib.RBAC
 * @author     ice_berg16(寻梦的稻草人)
 * @copyright  ice_berg16@163.com
 * @version    $Id$
 */

class Act extends Model
{
	protected $table = 'act';
	
	//资源存在时抛出的异常代码
	const ACT_EXISTS = -1;

	/**
	 * 创建一个资源
	 *
	 */
	public function create($act, $unique=true)
	{
		if($unique && $this->exists($act))
		{
			throw new Exception("要创建的资源已经存在", self::ACT_EXISTS);
		}
		return parent::create($act);
	}

	/**
	 * 检查指定的资源是否存在
	 */
	public function exists($act)
	{
		$sql = "SELECT act_id FROM %s
				WHERE controller='%s' AND action='%s'";
		$sql = sprintf($sql, $this->fullTableName, $act['controller'], $act['action']);
		return $this->findBySql($sql);
	}
}
?>