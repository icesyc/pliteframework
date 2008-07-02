<?php
/**
 * Dispatcher
 *
 * 前端控制器
 *
 * @package    Plite
 * @author     ice_berg16(寻梦的稻草人)
 * @copyright  2004-2006 ice_berg16@163.com
 * @version    $Id$
 */

class Dispatcher
{	
	/*
	 * 分派请求 
	 *
	 */
	public function dispatch()
	{

		Event::run("system.dispatching");
		//加上控制器前缘
		$controller = Config::get("controllerPrefix") . Router::$controller;
		$file = Router::$directory . DS . $controller . ".php";
		
		if(!file_exists($file))
			throw new Exception("请求的控制器 <span class='red'>$file::$controller</span> 不存在。");

		require_once($file);

		Event::run("system.preController");

		$ctl = new $controller();
		if(!method_exists($ctl, Router::$action))
			throw new Exception("请求的控制器 <span class='red'>$file::$controller</span> 不存在动作 <span class='red'>$action</span>。");
		//执行action
		$ctl->$action();
		
		$ctl->autoRender and $ctl->renderView();

		Event::run("system.postController");
	}
}
?>