<?php
/**
 * AppPath.php
 *
 * 应用程序的路径配置样板文件，使用Plite框架必须要对应用程序的路径进行设置，然后通过
 * Config::import($file)来导入，以便框架能正常运行
 *
 * @package    Plite.Config
 * @author     ice_berg16(寻梦的稻草人)
 * @copyright  2004-2006 ice_berg16@163.com
 * @version    $Id$
 */

define("APP_ROOT", dirname(__FILE__));

return array(

	//一些基本的路径设置
	'appRoot'			=> APP_ROOT,
	'controllerPath'	=> APP_ROOT . DS . "controller",
	"modelPath"			=> APP_ROOT . DS . "model",
	"viewPath"			=> APP_ROOT . DS . "view",
	"layoutPath"		=> APP_ROOT . DS . "view" . DS . "layout",
	"cachePath"			=> APP_ROOT . DS . "cache",	
);
?>