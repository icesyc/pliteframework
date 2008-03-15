<?php
/**
 * TemplateInterface
 *
 * plite框架的模板接口
 *
 * @package    Plite.View
 * @author     ice_berg16(寻梦的稻草人)
 * @copyright  2004-2006 ice_berg16@163.com
 * @version    $Id$
 */

interface TemplateInterface 
{
	/**
	 * 启用模板缓存功能 
	 *
	 * @param int $cacheLifeTime 缓存生命期
	 */
	public function enableCache($cacheLifeTime=null);

	/**
	 * 应用模板数据 
	 *
	 * @param array $data 模板数据
	 */
	public function assign($data);

	/**
	 * 显示页面 
	 *
	 * @param string $tpl 模板文件(绝对路径)
	 */
	public function display($tpl);

}
?>