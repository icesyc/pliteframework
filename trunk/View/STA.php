<?php
/**
 * STA
 *
 * 实现smartTemplate的适配器
 *
 * @package    Plite.View
 * @author     ice_berg16(寻梦的稻草人)
 * @copyright  2004-2006 ice_berg16@163.com
 * @version    $Id$
 */

require_once("Plite/View/SmartTemplate/class.smarttemplate.php");
require_once("Plite/View/TemplateInterface.php");

class STA implements TemplateInterface
{
	//smartTemplate模板对象
	private $tpl;

	/**
	 * 构造函数 
	 */
	public function __construct()
	{
		$this->tpl = new SmartTemplate;
		$this->tpl->template_dir = Config::get("viewPath") . DS;
		$this->tpl->temp_dir     = Config::get("cachePath") . DS;
		$this->tpl->cache_dir    = Config::get("cachePath") . DS;
	}
	
	/**
	 * 启用缓存 
	 *
	 * @param $cacheLifeTime 缓存时间
	 */
	public function enableCache($cacheLifeTime=null)
	{	
		if(is_null($cacheLifeTime))
			$cacheLifeTime = Config::get("cacheLifeTime");
		$this->tpl->cache_lifetime = $cacheLifeTime;
		$this->tpl->use_cache();
	}

	/**
	 * 指定数据 
	 *
	 * @param array $data
	 */
	public function assign($data)
	{
		$this->tpl->assign($data);
	}

	/**
	 * 输出内容 
	 *
	 * @param string $tpl 模板文件 绝对路径
	 */
	public function display($tpl)
	{
		$this->tpl->set_templatefile(basename($tpl));
		$this->tpl->output();
	}
}
?>