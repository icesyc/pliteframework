<?php
/**
 * MVC框架的运行程序 
 *
 * Plite框架默认并不加载MVC框架所必须的,Init.php程序只是负责加载最基本的文件和初始化工作
 * 在初始化工作完成后，如果需要启用MVC模式来运行框架，使用MVC::run()就可以了。在这之前可以使用
 * Config类来对框架进行配置
 *
 * @package	   Plite 
 * @author     ice_berg16(寻梦的稻草人)
 * @copyright  2004-2006 ice_berg16@163.com
 * @version    $Id$
 */

class MVC
{
	/**
	 * 构造函数私有，不能被实例化
	 */
	private function __construct(){}

	/**
	 * 启动MVC程序，调用Dispatcher，执行用户动作，需要在调用该方法之前指定一个调度器
	 * 可以使用Config::set("dispatcher", "Plite.Dispatcher")来设定
	 * Dispatcher必须是一个相对路径，路径用"."来分隔
	 */
	public static function run()
	{
		//加载系统钩子
		if($hooks = Config::get("hooks")){
			foreach($hooks as $hook){
				file_exists($hook) and require_once($hook);
			}
		}
		
		//自动启用session
		if(Config::get("sessionStart"))
		{
			session_start();
		}
		
		//发送编码
		if(Config::get("headerCharset"))
		{
			header("Content-Type:text/html;charset=" . Config::get("headerCharset"));
		}

		//设置默认时区
		if(Config::get("timezone"))
		{
			date_default_timezone_set(Config::get("timezone"));
		}
		
		//设置默认的异常处理程序
		if(Config::get("exceptionHandler"))
		{
			set_exception_handler(Config::get("exceptionHandler"));
		}
		
		//加载MVC所必须的文件
		Plite::load("Plite.Router");
		Plite::load("Plite.Dispatcher");
		Plite::load("Plite.Controller");
		Plite::load("Plite.Model");
		Plite::load("Plite.View");
		
		View::$directory = Config::get("viewPath");
		View::$engine	 = Config::get("viewEngine");
		View::$cachePath = Config::get("cachePath");

		Event::run("system.routing");

		//建立路由
		Router::setup();

		//加载用户配置的调度器
		$dsp = Config::get("dispatcher");
		Plite::load($dsp);

		//取得类名
		$class = array_pop(explode(".", $dsp));
		$dispatcher = new $class();
		Plite::set("dispatcher", $dispatcher);

		//执行调度
		Event::run("system.dispatching");
		$dispatcher->dispatch();
	}
}
?>