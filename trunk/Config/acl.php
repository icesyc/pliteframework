<?php
/**
 * 用户访问权限控制列表样例
 * 用户自行检查时可使用
 * $acl = RBAC::getAcl($roleId);
 * RBAC::check($acl, $controller, $action);
 * 来检查是否可以访问某些特殊权限
 *
 * @package    Config
 * @author     ice_berg16(寻梦的稻草人)
 * @copyright  ice_berg16@163.com
 * @version    $Id$
 */
return array(
	//没有任何用户角色的用户权限
	'none' => array(),

	//默认用户角色的权限
	'default' => array(
		'index'	=> array('index', 'save', 'delete'),
		'user'	=> array('index', 'save', 'delete'),
		'article'=> array('index', 'save', 'delete', 'show')
		'product'=> 'all',
	),

	//role_id为1的用户的权限
	1 => 'all'

);

?>