<?php
/**
 * Controller 
 *
 * 控制器超类
 *
 * @package    Plite
 * @author     ice_berg16(寻梦的稻草人)
 * @copyright  2004-2006 ice_berg16@163.com
 * @version    $Id: Controller.php 161 2006-12-30 08:44:37Z icesyc $
 */

class Controller
{
	public  $viewData = array();	//视图数据
	public  $layout   = array();	//布局数组
	public  $viewFile;				//视图文件名称
	public  $cacheAction = array();	//要缓存的动作
	private $view;					//视图对象

	//是否自动进行渲染，dispatcher检查此变量来确定是否执行render();
	public  $autoRender = true;		

	public function __construct()
	{		
		$this->view = new view();

		//指定视图文件名称，默认为当前的action
		$this->setView();
	}

	/*
	 * 检查是否为post提交 
	 */
	protected function isPost()
	{
		return count($_POST) > 0;
	}

	/*
	 * 设置一个视图变量 
	 *
	 * @param array|string 当为array时为一个变量数组，否则为变量的键值
	 * @param mixed 变量值
	 */
	public function set($key,$value=null)
	{
		if(is_array($key))
			$this->viewData = array_merge($this->viewData, $key);
		else
			$this->viewData[$key] = $value;
	}

	/*
	 * 指定要显示的页面 
	 *		 如果未指定则默认为action的名称
	 *		 当启用缓存时，不使用默认名称的情况下一定要在useCache前进行调用，否则视图文件会出错。
	 * @param string $name
	 */
	public function setView($name=null)
	{
		if(is_null($name))
		{
			$ctl = Plite::get(DISPATCHER_KEY)->getController();
			$act = Plite::get(DISPATCHER_KEY)->getAction();
			$this->viewFile = $ctl . "_" . $act;
		}
		else
			$this->viewFile = $name;
	}
	
	/*
	 * 启用缓存功能 
	 *
	 * 如果有缓存文件则输出缓存内容返回true，否则返回false
	 */
	public function useCache($cacheLifeTime=null)
	{
		if(!$cacheLifeTime) $cacheLifeTime = Config::get("cacheLifeTime");
		$this->view->construct($this->viewFile, $this->viewData, $this->layout);
		$this->view->enableCache($cacheLifeTime);
		return $this->view->renderCache();		
	}

	/*
	 * 指定要缓存的动作，只允许在子类的构造函数中调用
	 *
	 * @param string $action 为数组时键名为action,值为缓存时间
	 * @param int $cacheLifeTime 缓存时间，$action为数组时为null
	 */
	public function cacheAction($action, $cacheLifeTime=null)
	{
		if(is_array($action))
			$this->cacheAction = array_merge($this->cacheAction, $action);
		else
			$this->cacheAction[$action] = $cacheLifeTime;
	}

	/*
	 * 处理缓存 只被dispacher调用
	 *
	 * 如果有缓存文件则输出缓存内容返回true，否则返回false
	 */
	public function processCache()
	{
		if(count($this->cacheAction) == 0 )
			return false;
		$act = Plite::get(DISPATCHER_KEY)->getAction();
		//检查当前动作是否被要求缓存
		if(array_key_exists($act, $this->cacheAction))
		{
			return $this->useCache($this->cacheAction[$act]);
		}
	}

	/*
	 * 设置布局 
	 *
	 * @param string $key
	 * @param string $name 布局名称
	 */
	protected function setLayout($key, $name)
	{
		$this->layout[$key] = $name;
	}

	/*
	 * 渲染一个视图
	 *
	 * @param string $file 视图文件名
	 */
	public function renderView($file=null)
	{
		if($file)
		{
			$this->viewFile = $file;
		}

		$this->view->construct($this->viewFile, $this->viewData, $this->layout);
		return $this->view->render();
	}

	/*
	 * 转向另外一个动作 
	 *
	 * @param string $controller 
	 * @param string $action
	 * @param array $params 调用的参数
	 * @param boolean $redirect 是否重定向
	 */
	public function forward($controller=null, $action=null, $params=null, $redirect=false)
	{
		$ctl = Config::get("CTL");
		$act = Config::get("ACT");
		$_REQUEST[$ctl]= is_null($controller) ? Config::get("defaultController") : $controller;
		$_REQUEST[$act] = is_null($action) ? Config::get("defaultAction") : $action;
		if($params)
			$_REQUEST = array_merge($_REQUEST, $params);
		if( $redirect )
		{
			header(sprintf("Location: %s?%s=%s&%s=%s", $_SERVER['SCRIPT_NAME'], $ctl, $controller, $act, $action));
			exit;
		}
		else
			return Plite::get(DISPATCHER_KEY)->dispatch();
	}

	/*
	 * 重定向 
	 *
	 * @param string $url 
	 */
	public function redirect($url)
	{
		header("Location: ". $url);
		exit;
	}
}
?>