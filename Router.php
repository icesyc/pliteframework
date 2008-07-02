<?php
/**
 * Router
 *
 * 提供URL的路由功能
 *
 * @package    Plite
 * @author     ice_berg16(寻梦的稻草人)
 * @copyright  2004-2006 ice_berg16@163.com
 * @version    $Id: Dispatcher.php 4 2008-03-15 13:15:42Z icesyc $
 */

class Router
{
	//当前uri
	public static $currentUri;
	//控制器名称
	public static $controller;
	//动作名称
	public static $action;
	//控制器所在目录
	public static $directory;
	//url后缀
	public static $urlSuffix;

	//保存所有的参数列表
	public static $arguments;

	//初始化
	public static function setup(){
		//设置控制器目录
		self::$directory = Config::get('controllerPath');
		self::$urlSuffix = Config::get('router.urlSuffix');

		//取得路由配置
		$routes = Config::get('router');
		//是path info模式还是正常的模式？
		if(isset($_SERVER['PATH_INFO']))
		{
			self::$currentUri = $_SERVER['PATH_INFO'];
			//除去url后缀
			if(self::$urlSuffix && substr(self::$currentUri, -strlen(self::$urlSuffix)) == self::$urlSuffix)
			{
				self::$currentUri = substr(self::$currentUri, 0, -strlen(self::$urlSuffix));
			}

			$segments = explode("/", self::findRoute($routes));
			
			self::$controller = $segments[0];
			self::$action     = isset($segments[1]) ? $segments[1] : 'index';
			if(count($segments) > 2)
			{
				self::$arguments = $arr = array_slice($segments, 2);
				while($arr)
				{
					$key = array_shift($arr);
					$value = array_shift($arr);
					$_REQUEST[$key] = $_GET[$key] = $value;
				}
			}
		}
		else
		{
			self::$currentUri = $_SERVER['PHP_SELF'];
			self::$controller = self::findAccessor('controller');
			self::$action	  = self::findAccessor('action');
			self::$controller == '' and self::$controller = $routes['default'];
			self::$action == '' and self::$action = 'index';
		}
	}

	/*
	 * 在$_GET, $_POST中查找控制器或动作名称，没有就返回系统默认的名称
	 */
	private static function findAccessor($type)
	{
		$ass = $type == 'controller' ? Config::get('CTL') : Config::get('ACT');
		return !empty($_GET[$ass]) ? $_GET[$ass] : (!empty($_POST[$ass]) ? $_POST[$ass] : null);
	}

	/*
	 * 在配置文件中查找路由
	 */
	private static function findRoute($routes){
		//除去前后的分隔符
		$segments = trim(self::$currentUri, "/");
		if(count($routes) < 2)
		{
			return $segments;
		}
		//判断直接路由
		if(isset($routes[self::$currentUri]))
		{
			$segments = $routes[self::$currentUri];
		}
		else
		{
			foreach($routes as $k => $v)
			{
				if($v == 'default') continue;
				//匹配到路由
				if(preg_match('#^'.$k.'$#', self::$currentUri))
				{
					$segments = preg_replace('#^'.$k.'$#', $v, $segments);
				}
			}
		}
		return $segments;
	}

}
?>