<?php
/**
 * View
 *
 * 视图程序
 *
 * @package    Plite
 * @author     ice_berg16(寻梦的稻草人)
 * @copyright  2004-2006 ice_berg16@163.com
 * @version    $Id$
 */

class View
{
	//视图数据
	public $viewData;
	//视图布局
	private $layout;
	//视图文件名称
	private $name;
	//是否缓存页面
	private $wantCache;
	//缓存时间
	private $cacheLifeTime;

	/**
	 * 构造函数
	 * @param object $controller 
	 */
	public function __construct()
	{}
	
	//构造数据
	public function construct($name, $viewData, $layout)
	{
		$this->name		= $name;
		$this->viewData = $viewData;
		$this->layout   = $layout;
	}

	/**
	 * 渲染并显示页面 
	 */
	public function render()
	{
		$this->renderLayout();

		if(Config::get("viewEngine") == 'php')
		{
			echo $this->fetch();
			return true;
		}
		else
		{
			$tpl = $this->loadEngine(Config::get("viewEngine"));
			if($this->wantCache)
				$tpl->enableCache($this->cacheLifeTime);
			$tpl->assign($this->viewData);
			$tpl->display($this->getViewFile());
			return true;
		}
	}
	
	/*
	 * 载入模板引擎 
	 *
	 * @param $engine 引擎的名称
	 */
	private function loadEngine($engine)
	{
		$path = PLITE_ROOT . DS . "View" . DS . $engine . ".php";
		if(!file_exists($path))
			throw Exception( "指定的模板引擎 <span class='red'>$engine</span> 不存在，加载失败。");
		require_once($path);
		return new $engine;
	}

	/*
	 * 取得布局文件的完整路径 
	 *
	 * @param string $layoutName 
	 */
	private function getLayoutFile($layoutName)
	{
		$path = Config::get("layoutPath") . DS . $layoutName . "." . Config::get("layoutExt");
		return $this->checkFile($path, 'layout');
	}

	/*
	 * 取得视图页面的完整路径 
	 *
	 * @param string $name
	 */
	private function getViewFile($name=null)
	{
		if(is_null($name))
			$name = $this->name;
		$path = Config::get("viewPath") . DS . $name . "." . Config::get("viewExt");
		return $this->checkFile($path);
	}

	/*
	 * 检测文件是否存在 
	 *
	 * @param string $f
	 * @param $type 'layout' 布局 'view' 视图
	 */
	private function checkFile($f, $type='view')
	{

		$text = $type == 'view' ? '视图' : '布局';
		if(!file_exists($f))
			throw new Exception("指定的{$text}文件 <span class='red'>$f</span> 不存在，加载失败");
		return $f;
	}

	//启用缓存
	public function enableCache($cacheLifeTime)
	{
		$this->wantCache	 = true;
		$this->cacheLifeTime = $cacheLifeTime;
	}

	/*
	 * @todo 取得页面输出的内容
	 */
	public function fetch()
	{		
		ob_start();	
		extract($this->viewData);	
		require($this->getViewFile());
		$content = ob_get_contents();		
		ob_end_clean();
		//保存为缓存文件
		if($this->wantCache)
		{
			$f = $this->getCacheFile();
			file_put_contents($f, $content);
		}
		return $content;
	}
	
	/**
	 * 渲染缓存页面 
	 *
	 * 有缓存页面时输出缓存，返回true，否则返回false
	 */
	public function renderCache()
	{
		$f = $this->getCacheFile();
		if($this->wantCache && $this->isCached($f))
		{
			echo file_get_contents($f);
			exit;
			//return true;
		}
		return false;
	}

	/**
	 * 生成缓存文件名
	 */
	public function getCacheFile()
	{
		return Config::get("cachePath") . DS . "cache_" . md5($_SERVER['REQUEST_URI'].$this->name.$this->cacheLifeTime);
	}

	/**
	 * 检查是否已缓存并且有效 
	 *
	 * @param string $file
	 */
	private function isCached($file)
	{

		if(!file_exists($file))
			return false;
		//已过期
		if(time() - filemtime($file) > $this->cacheLifeTime)
		{
			@unlink($file);
			return false;
		}
		return true;
	}

	/*
	 * @todo 渲染布局 
	 */
	public function renderLayout()
	{		
		if(!is_array($this->layout)) return false;
		foreach( $this->layout as $k => $v )
		{	
			if(!is_readable($this->getLayoutFile($v)))
				throw new Exception( "指定的布局 <span class='red'>$engine</span> 不存在，加载失败。");
			$this->viewData[$k] = file_get_contents($this->getLayoutFile($v));
		}
	}
}
?>