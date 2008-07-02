<?php
/**
 * Event
 *
 * 提供事件机制的静态类
 *
 * @package    Plite
 * @author     ice_berg16(寻梦的稻草人)
 * @copyright  2004-2006 ice_berg16@163.com
 * @version    $Id: Dispatcher.php 4 2008-03-15 13:15:42Z icesyc $
 */

class Event
{
	//事件集合
	private static $events = array();

	/**
	 * 添加一个事件
	 * 
	 * @param string $name 事件名称
	 * @param callback $callback 可运行的回调函数
	 */
	public static function add($name, $callback)
	{
		if(!array_key_exists($name, self::$events))
		{
			self::$events[$name] = array();
		}
		if(!in_array($callback, self::$events[$name], true)){
			self::$events[$name][] = $callback;
		}
		return true;
	}

	/**
	 * 运行某个事件
	 * 
	 * @param string $name 事件名称
	 * @param mix $data 执行事件时要传递的数据
	 */
	public static function run($name, $data=null){
		if(!array_key_exists($name, self::$events)){
			return false;
		}
		foreach(self::$events[$name] as $func){
			if(is_null($data)){
				call_user_func($func);
			}else{
				call_user_func($func, $data);
			}
		}
		return true;
	}

	/**
	 * 删除某个事件
	 * 
	 * @param string $name 事件名称
	 */
	public static function clear($name){
		unset(self::$events[$name]);
		return true;
	}
}
?>