<?php
/**
 * DefaultConfig
 *
 * 应用程序的配置文件
 *
 * @package    Plite.Config
 * @author     ice_berg16(寻梦的稻草人)
 * @copyright  2004-2006 ice_berg16@163.com
 * @version    $Id$
 */


return array(

	//控制器的名称
	"CTL"				=> "C",
	//动作的名称
	"ACT"				=> "A",
	//默认的控制器
	"defaultController"	=> "index",
	//默认的动作
	"defaultAction"		=> "index",	
	//定义控制器前缀
	"controllerPrefix"	=> "C_",
	//定义model前缀
	"modelPrefix"		=> "M_",
	//数据表前缀
	"tablePrefix"		=> "plite_",
	//视图文件的扩展名
	"viewExt"			=> "htm",
	//布局文件的扩展名
	"layoutExt"			=> "htm",
	//使用的模板引擎
	"viewEngine"		=> "php",
	//默认的编译模板缓存时间(单位:秒)
	"cacheLifeTime"		=> 30*60*60*24,
	//使用的控制器
	"dispatcher"		=> "Plite.Dispatcher",
	//url模式
	"urlMode"			=> "pathInfo",
	//是否启用session
	"sessionStart"		=> false,
	//发送编码
	"headerCharset"		=> false,
	//默认时区"
	"timezone"			=> "Asia/Shanghai",
	//默认的异常处理程序
	"exceptionHandler"	=> array("ErrorProcessor", "process"),
	//数据库连接DSN设置
	'dsn'				=> array(
		"driver"	=> "Mysql",	//抽象层的驱动类
		"host"		=> "localhost",
		"user"		=> "root",
		"pwd"		=> "",
		"database"	=> "plite",
		"charset"	=> ""
	),

	/**
	 * RBAC的事件设置，每个事件可以指定一个包含controller,action的数组或一个函数
	 * 如'onLogin' => array('user', 'login')，'onValidateFailed' => 'callback_function'
	 * aclPath 可以指定一个ACL文件来做来权限配置文件,该配置文件返回一个以角色为键名的,权限为值的的数组,格式为
	 * 'controller' => array('action', 'action2');
	 * 或'controller' => 'all', 当指定all时则该控制器的所有动作都为允许
	 *
     */
	'RBAC'				=> array(
		//指定用户管理器,用户管理器必须实现getRole,isLoginned函数
		'userModel'		   => 'Plite.Lib.RBAC.User',
		'onLogin'		   => array('index', 'login'),
		'loginSuccessFlag' => 'loginned',
		'onValidateFailed' => array('index', 'validateFailed'),

		//可以设置保存在session中的用户表的字段,用户登录成功时,这些字段将会自动被存储在session中
		//默认情况下,只存储user_id, user_name, role_id三个字段
		'sessionFields'	   => array(),

		//ACL的存放位置,如果为文件,则需要指定文件路径
		'aclPath'		   => 'database',
		//不具有任何角色的名称, 该值用来指定ACL文件中对应的键名
		'noneRole'		   => 'none',
		//具有任何角色的名称, 该值用来指定ACL文件中对应的键名
		'defaultRole'	   => 'default',
	)
);
?>