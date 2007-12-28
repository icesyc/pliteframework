<?php
/**
 * RBAC权限控制调度器
 *
 * @package    Plite.Dispatcher
 * @author     ice_berg16(寻梦的稻草人)
 * @copyright  ice_berg16@163.com
 * @version    $Id$
 */

class RBACDispatcher extends Dispatcher
{
	public function __construct()
	{
		parent::__construct();
	}
	
	/**
	 * RBAC的主调度函数
	 */
	public function dispatch()
	{
		//加载用户管理器
		$userPath = Config::get("RBAC.userModel");
		if(is_null($userPath))
			throw new Exception("未指定RBAC模块的userModel");
		$userClass = array_pop(explode(".", $userPath));
		Plite::load($userPath);
		Plite::load("Plite.Lib.RBAC.RBAC");
		
		//检查权限
		$param = $this->parseParam();
		
		$user = new $userClass();
		$roleId = $user->getRole();
		if(RBAC::validate($roleId, $param['controller'], $param['action']))
		{
			parent::dispatch();
		}
		else
		{
			//用户未登录
			if(!$user->isLoginned())
			{
				//执行登录事件
				$this->dispatchEvent("onLogin");
				return false;
			}

			//验证失败时触发事件
			$this->dispatchEvent("onValidateFailed");
		}		
	}
	
	/**
	 * RBAC事件触发函数
	 *
	 * 事件回调函数有两种方式，一种是含有二个元素的数组，该数组包含controller和action,调度器会执行指定的动作
	 * 另一种是字符串指定的函数名称，调度器会执行相应的函数
	 *
	 * @param string $event 在配置文件中指定的事件回调函数
	 */
	private function dispatchEvent($event)
	{
		//取得事件句柄
		$eventHandler = Config::get("RBAC." . $event);
		if(is_array($eventHandler))
		{
			list($controller, $action) = $eventHandler;
			$this->setController($controller);
			$this->setAction($action);
			parent::dispatch();
		}
		elseif(is_callable($eventHandler))
		{
			call_user_func($eventHandler, $event);
		}
		else
		{			
			throw new Exception("执行 <span class='red'>$event</span> 时发生错误, <span class='red'>$event</span> 不可执行");
		}		
		return true;
	}
}
?>