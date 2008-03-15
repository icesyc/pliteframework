<?php
/**
 * Init.php 
 *
 * 框架的初始化程序
 *
 * @package    Plite
 * @author     ice_berg16(寻梦的稻草人)
 * @copyright  2004-2006 ice_berg16@163.com
 * @version    $Id$
 */

/**
 *  Plite的版本
 */
define("PLITE_VERSION", "1.8.4");

/**
 * 定义框架的根目录
 */
define("PLITE_ROOT", dirname(__FILE__));

/**
 * 目录分隔符的短写
 */
define("DS", "/");

/**
 * 前端控制器保存在共享对象中的键名
 */
define("DISPATCHER_KEY", "__Dispatcher__");

//加载基本文件
require_once(PLITE_ROOT . "/Plite.php");
require_once(PLITE_ROOT . "/Config.php");
require_once(PLITE_ROOT . "/Exception/ErrorProcessor.php");

//加载默认的配置文件
Config::import(PLITE_ROOT . "/Config/DefaultConfig.php");

//设置默认的异常处理程序
set_exception_handler(Config::get("exceptionHandler"));

//设置包含路径
set_include_path(get_include_path().PATH_SEPARATOR.dirname(PLITE_ROOT));
?>