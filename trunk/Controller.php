<?php
/**
 * Controller 
 *
 * 控制器超类
 *
 * @package    Plite
 * @author     ice_berg16(寻梦的稻草人)
 * @copyright  2004-2006 ice_berg16@163.com
 * @version    $Id$
 */

class Controller
{	
	//视图对象
	protected $view;

	//是否自动进行渲染，dispatcher检查此变量来确定是否执行render();
	public  $autoRender = true;		

	public function __construct()
	{	
		$this->view = new View();

		//指定视图文件名称，默认为当前的action
		$viewFile = Router::$controller . "_" . Router::$action;
		$this->view->setFile($viewFile);
	}

	/*
	 * 检查是否为post提交 
	 */
	public function isPost()
	{
		return strtolower($_SERVER['REQUEST_METHOD']) == 'post';
	}

	/*
	 * 设置一个视图变量 
	 *
	 * @param array|string 当为array时为一个变量数组，否则为变量的键值
	 * @param mixed 变量值
	 */
	public function set($key, $value=null)
	{
		$this->view->set($key, $value);
	}

	/*
	 * 指定要显示的页面 
	 *
	 * @param string $name
	 */
	public function setView($name)
	{
		$this->view->setFile($name);
	}
		
	/*
	 * 渲染一个视图
	 *
	 * @param string $file 视图文件名
	 */
	public function renderView($file=null)
	{
		return $this->view->render($file);
	}

	/*
	 * 转向另外一个动作 
	 *
	 * @param string $controller 
	 * @param string $action
	 * @param array $params 调用的参数
	 */
	public function forward($controller=null, $action=null, $params=null)
	{
		Router::$controller = $controller;
		Router::$action     = $action;
		Router::$arguments  = $params;
		Plite::get("dispatcher")->dispatch();
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