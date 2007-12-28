<?php
/**
 * Timer 
 *
 * 提供一个计时器
 *
 * @package    Plite.Lib
 * @author     ice_berg16(寻梦的稻草人)
 * @copyright  2004-2006 ice_berg16@163.com
 * @version    $Id: Timer.php 134 2006-11-30 03:29:08Z icesyc $
 */

class Timer 
{
	//起始时间
	private $startTime;

	//结束时间
	private $endTime;	

	/**
	 * 构造函数 
	 */
	public function __construct() 
	{
		$this->start();
	}

	/**
	 * 开始计时
	 */
	public function start() 
	{
		$this->startTime = microtime(true);
	}
	
	/**
	 * 返回定时器的计时时间
	 *
	 * @param boolean $micro是否以毫秒形式返回
	 */
	public function getExecTime($micro=false) 
	{
		$this->endTime = microtime(true);
		$execTime = $this->endTime - $this->startTime;
		if(!$micro)
		{
			$execTime = $execTime * 1000;
			return round( $execTime, 4 );
		}
		return $execTime;
	}
}
?>