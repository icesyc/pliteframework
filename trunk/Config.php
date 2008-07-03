<?php
/**
 * Config
 *
 * 应用程序配置类
 *
 * @package    Plite
 * @author     ice_berg16(寻梦的稻草人)
 * @copyright  2004-2006 ice_berg16@163.com
 * @version    $Id$
 */

class Config
{
	//配置数组
	static private $ini  = array();

	//禁止访问构造函数
	private function __construct() {}

	/**
	 * 更改配置信息 
	 *
	 * 字符串可以使用set('dsn.driver','Mysql')的形式来设置子选项
	 *
	 * @param string|array $key 键名 可以是数组或字符串
	 * @return
	 */
	public static function set($key, $value=null)
	{
		if(is_array($key) && is_null($value))
			self::$ini = array_merge(self::$ini, $key);
		else
		{
			$arr = explode(".", $key);
			//用引用方式建立关联数组
			$pt  = &self::$ini;
			while($arr)
			{				
				if(!is_array($pt)) $pt = array();
				$key = array_shift($arr);
				$pt = &$pt[$key];
			}
			$pt = $value;
		}
	}

	/**
	 * 取得配置信息 
	 *
	 * 字符串可以使用get('dsn.driver')的形式来取得子选项，对于不存在的键，将返回null
	 * 
	 * @param string $key 配置变量的键值
	 * @return mix 返回配置信息的值
	 */
	public static function get($key=null)
	{
		if(is_null($key)) return self::$ini;
		$arr = explode(".", $key);
		//用引用方式查找关联数组
		$pt  = &self::$ini;
		while($arr)
		{
			if(!is_array($pt)) return null;
			$key = array_shift($arr);
			$pt = &$pt[$key];
		}
		return $pt;
	}

	//载入配置文件
	public static function import($configFile)
	{
		if(!file_exists($configFile))
			throw new Exception(sprintf("配置文件不存在 -> <span class='red'>%s</span>",$configFile));
		$ini = require($configFile);
		if(is_array($ini))
		{
			self::$ini = array_merge(self::$ini, $ini);
		}
	}

}
?>