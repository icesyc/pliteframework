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
	//访问参数数组
	private $param = array();

	public function __construct()
	{
	}
	
	/*
	 * 分派请求 
	 *
	 */
	public function dispatch()
	{
		extract($this->parseParam());

		//加上控制器前缘
		$controller = Config::get("controllerPrefix") . $controller;
		$file = Config::get("controllerPath") . DS . $controller . ".php";

		if(!file_exists($file))
			throw new Exception("请求的控制器 <span class='red'>$file::$controller</span> 不存在。");

		require_once($file);
		$ctl = new $controller();

		if(!method_exists($ctl, $action))
			throw new Exception("请求的控制器 <span class='red'>$file::$controller</span> 不存在动作 <span class='red'>$action</span>。");

		//处理缓存
		$ctl->processCache();

		//执行action
		$r = $ctl->$action();
		return $ctl->autoRender ? $ctl->renderView() : $r;
	}

	/*
	 * 解析参数 
	 */
	public function parseParam()
	{
		//如果已经存在参数则返回
		if(!empty($this->param))
		{
			return $this->param;
		}

		//分析url模式
		$this->parseMode();
		$param = array();
		$ctl   = Config::get("CTL");
		$act   = Config::get("ACT");
		$param['controller'] = !empty($_REQUEST[$ctl]) ? $_REQUEST[$ctl] : Config::get("defaultController");
		$param['action']	 = !empty($_REQUEST[$act]) ? $_REQUEST[$act] : Config::get("defaultAction");
		$this->param = $param;
		return $param;
	}

	/*
	 * 分析URL模式
	 */
	public function parseMode()
	{
		switch(Config::get("urlMode"))
		{
			//pathInfo模式
			case 'pathInfo':
				if(empty($_SERVER['PATH_INFO'])) return;
				$arr = explode("/", substr($_SERVER['PATH_INFO'], 1));
				$_REQUEST[Config::get("CTL")] = isset($arr[0]) ? $arr[0] : null;
				$_REQUEST[Config::get("ACT")] = isset($arr[1]) ? $arr[1] : null;
				$arr = array_slice($arr, 2);
				while($arr){
					$key = array_shift($arr);
					$value = array_shift($arr);
					$_REQUEST[$key] = $_GET[$key] = $value;
				}
				break;
			default:
				return;
		}
	}

	/*
	 * 指派控制器
	 */
	public function setController($controller)
	{
		$this->param['controller'] = $controller;
	}

	/*
	 * 指派动作
	 */
	public function setAction($action)
	{
		$this->param['action'] = $action;
	}

	/*
	 * 取得控制器的名称 
	 */
	public function getController()
	{
		if(isset($this->param['controller']))
			return $this->param['controller'];
		else
			return false;

	}

	/*
	 * 取得动作的名称 
	 */
	public function getAction()
	{
		if(isset($this->param['action']))
			return $this->param['action'];
		else
			return false;
	}
}
?>