<?php
/**
 * STA
 *
 * 实现smartTemplate的适配器
 *
 * @package    Plite.View
 * @author     ice_berg16(寻梦的稻草人)
 * @copyright  2004-2006 ice_berg16@163.com
 * @version    $Id: STA.php 134 2006-11-30 03:29:08Z icesyc $
 */

require_once("Plite/Lib/SmartTemplate/SmartTemplate.php");
require_once("Plite/View/TemplateInterface.php");

class STA implements TemplateInterface
{
	//是否启用缓存
	private $wantCache		= false;
	//数据
	private $data;

	/**
	 * 构造函数 
	 */
	public function __construct()
	{
		global $_CONFIG;
		$_CONFIG['template_dir']			= Config::get("viewPath") . DS;
		$_CONFIG['smarttemplate_compiled']	= Config::get("cachePath") . DS;
		$_CONFIG['smarttemplate_cache']		= Config::get("cachePath") . DS;
		$_CONFIG['cache_lifetime']			= Config::get("cacheLifeTime");
	}
	
	/**
	 * 启用缓存 
	 *
	 * @param $cacheLifeTime 缓存时间
	 */
	public function enableCache($cacheLifeTime=null)
	{		
		$this->wantCache = true;
		global $_CONFIG;
		if(is_null($cacheLifeTime))
			$cacheLifeTime = Config::get("cacheLifeTime");
		$_CONFIG['cache_lifetime'] = $cacheLifeTime;
	}

	/**
	 * 指定数据 
	 *
	 * @param array $data
	 */
	public function assign($data)
	{
		$this->data = $data;
	}

	/**
	 * 输出内容 
	 *
	 * @param string $tpl 模板文件 绝对路径
	 */
	public function display($tpl)
	{
		$file = basename($tpl);
		$st = new SmartTemplate($file);

		//启用缓存
		if($this->wantCache)
			$st->use_cache();
		$st->assign($this->data);
		$st->output();
	}
}
?>