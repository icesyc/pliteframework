<?php
/**
 * ErrorProcessor
 *
 * 框架的错误和异常处理类
 *
 * @package    Exception
 * @author     ice_berg16(寻梦的稻草人)
 * @copyright  2004-2006 ice_berg16@163.com
 * @version    $Id: ErrorProcessor.php 200 2007-10-16 05:23:30Z icesyc $
 */

class ErrorProcessor
{
	private function __construct()
	{
	}

	/*
	 * 处理捕捉到的异常
	 *
	 * @param Exception $e
	 */
	public static function process($e)
	{

		switch(get_class($e))
		{
			case 'MysqlException':
				require("Plite/Exception/DATABASE_ERROR.php");
				break;
			case 'Exception':
				if( $e->getCode() == 0 )
					require("Plite/Exception/DEFAULT_ERROR.php");
				else
					require("Plite/Exception/".$e->getCode().".php");
				break;
			default:
				require("Plite/Exception/DEFAULT_ERROR.php");
				break;
		}
		exit;
	}
}
?>