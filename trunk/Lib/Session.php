<?php
/**
 * Session 
 *
 * 封装session的操作，支持用数据库保存
 *
 * @package    Plite.Lib
 * @author     ice_berg16(寻梦的稻草人)
 * @copyright  2004-2006 ice_berg16@163.com
 * @version    $Id: Session.php 155 2006-12-15 04:03:19Z icesyc $
 */

/**
 *  Session数据库使用的表名
 */
define("SESSION_TABLE", "plite_session");

/**
 *  Session的过期时间
 */
define("SESSION_EXPIRE_TIME", ini_get("session.gc_maxlifetime"));

class Session
{	
	/* 用数据库保存session时的表结构
	----- <plite_session> -----------
			sessID		session_id
			sessData	数据
			expireTime	过期时间
	-----------------------------*/

	/** 
	 * 构造函数
	 *
	 * @param string $savePath 保存的路径 为 'DB' 时为使用数据库
	 * @param DB 数据库抽象层
	 */
	public function __construct($savePath = null, $DB = null)
	{
		if($savePath == 'DB')
		{
			if(is_null($DB)) exit( "Session: DB has not been set." );
			$this->DB = $DB;
			//设置session名称
			session_name("PLITESESS");
			session_set_save_handler( 
				array($this, "SOpen"), 
				array($this, "SClose"),
				array($this, "SRead"),
				array($this, "SWrite"),
				array($this, "SDestroy"),
				array($this, "Sgc")
			);
		}
		else
		{
			if(is_dir($savePath))
			{
				session_save_path($savePath);
			}
		}
		//开启session
		session_start();
	}

	/**
	 *  设置session值
	 *
	 * @param string $key
	 * @param mix $value
	 */
	public function set($key, $value)
	{
		$_SESSION[$key] = $value;
	}
	
	/* 函数 get( $key )
	** 功能 取得session值
	** 参数 $key 要取值的关联索引
	*/
	public function get($key)
	{
		return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
	}

	public function SOpen($sessPath, $sessName)
	{}

	public function SClose()
	{
		return true;
	}

	public function SRead($sessID)
	{
		$sql = "SELECT sessData FROM " . SESSION_TABLE . " 
				WHERE sessID = '$sessID' AND expireTime > " . time();

		$st = $this->DB->prepare($sql);
		$st->execute();
		return $st->fetchColumn("sessData");
	}

	public function SWrite($sessID, $sessData)
	{
		$expireTime = time() + SESSION_EXPIRE_TIME;
		$sql = "REPLACE INTO " . SESSION_TABLE . "
				VALUES('$sessID', '$expireTime', '$sessData')";
		return $this->DB->exec($sql);
	}

	public function SDestory($sessID)
	{
		$sql = "DELETE FROM " . SESSION_TABLE . " 
				WHERE sessID = '$sessID'";
		return $this->DB->exec($sql);
	}

	public function Sgc()
	{
		$sql = "DELETE FROM " . SESSION_TABLE . " 
				WHERE expireTime < " . time();
		return $this->DB->exec($sql);
	}

}

?>