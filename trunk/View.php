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
	public $data;
	//视图文件名称
	private $name;
	//是否缓存页面
	private $wantCache;
	//缓存时间
	private $cacheLifeTime;
	//模板引擎
	public static $engine = 'php';
	//模板目录
	public static $directory = 'view';
	//缓存目录
	public static $cachePath = 'cache';

	/**
	 * 构造函数
	 *
	 * @param string $file 视图文件
	 */
	public function __construct($file=null){
		is_null($file) or $this->name = $file;
	}

	/**
	 * 设置模板引擎 
	 */
	public function setEngine($engine){
		self::$engine = $engine;
	}

	/**
	 * 设置模板目录 
	 */
	public function setDirectory($dir){
		self::$directory = $dir;
	}
	
	/**
	 * 设置模板目录 
	 */
	public function setCachePath($dir){
		self::$cachePath = $dir;
	}

	/**
	 * 渲染并显示页面 
	 */
	public function render($file=null)
	{
		is_null($file) or $this->name = $file;

		if(self::$engine == 'php')
		{
			echo $this->fetch();
			return true;
		}
		else
		{
			$tpl = $this->loadEngine(self::$engine);
			$this->wantCache and $tpl->enableCache($this->cacheLifeTime);
			$tpl->assign($this->data);
			$tpl->display($this->getViewFile());
			return true;
		}
	}
	
	/*
	 * 设置一个视图变量 
	 *
	 * @param array|string 当为array时为一个变量数组，否则为变量的键值
	 * @param mixed 变量值
	 */
	public function __set($key, $value=null){
		if(is_array($key))
		{
			$this->data = array_merge($this->data, $key);
		}
		else
		{
			$this->data[$key] = $value;
		}
	}

	/*
	 * 用于转换成字符串
	 *
	 */
	public function __toString(){
		return $this->render();
	}

	/*
	 * 设置一个视图文件
	 *
	 * @param string $f 文件名称
	 */
	public function setFile($f)
	{
		$this->name = $f;
	}

	/*
	 * 载入模板引擎 
	 *
	 * @param $engine 引擎的名称
	 */
	private function loadEngine($engine)
	{
		$path = dirname(__FILE__) . "/View/" . $engine . ".php";
		if(!file_exists($path))
			throw Exception( "指定的模板引擎 <span class='red'>$engine</span> 不存在，加载失败。");
		require_once($path);
		return new $engine;
	}

	/*
	 * 取得视图页面的完整路径 
	 *
	 * @param string $name
	 */
	private function getViewFile($name=null)
	{
		is_null($name) and $name = $this->name;
		$path = self::$directory . "/" . $name . ".htm";
		return $this->checkFile($path);
	}

	/*
	 * 检测文件是否存在 
	 *
	 * @param string $f
	 * @param $type 'layout' 布局 'view' 视图
	 */
	private function checkFile($f)
	{
		if(!file_exists($f))
			throw new Exception("指定的视图文件 <span class='red'>$f</span> 不存在，加载失败");
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
		extract($this->data);	
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
	 * 生成缓存文件名
	 */
	public function getCacheFile()
	{
		return self::$cachePath . "/cache_" . md5($_SERVER['REQUEST_URI'].$this->name.$this->cacheLifeTime);
	}

	/**
	 * 检查是否已缓存并且有效 
	 *
	 * @param string $file
	 */
	private function isCached($file=null)
	{
		is_null($file) and $file = $this->getCacheFile();

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
}
?>