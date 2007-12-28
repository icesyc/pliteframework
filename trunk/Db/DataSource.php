<?php
/**
 * DataSource
 *
 * 数据库通用接口，用于返回数据库操作对象
 *
 * @package    Plite.Db
 * @author     ice_berg16(寻梦的稻草人)
 * @copyright  2004-2006 ice_berg16@163.com
 * @version    $Id: DataSource.php 159 2006-12-30 07:30:11Z icesyc $
 */

class DataSource
{
	//singleton的实例
	static private $instance = null;

	//数据库操作对象
	private $db;	

	//数据库连接信息
	private $dsn;

	private function __construct($dsn) {
		$this->dsn = $dsn;
	}

	static public function getInstance($dsn)
	{
		if(is_null(self::$instance))
		{
			self::$instance = new dataSource($dsn);
		}

		return self::$instance->getDbo($dsn);
	}

	//返回数据库对象
	private function getDbo($dsn=null)
	{
		$driver = $dsn['driver'] . "Driver";
		if(!file_exists(PLITE_ROOT."/Db/$driver.php"))
		{
			throw new Exception("指定的Driver: $driver 不存在");
		}

		require_once("Plite/Db/".$driver.".php");
		$this->db = new $driver($dsn);
		return $this->db;
	}
}
?>