<?php
/**
 * Validator 
 *
 * 提供数据验证的函数
 *
 * @package    Plite.Lib
 * @author     ice_berg16(寻梦的稻草人)
 * @copyright  2004-2006 ice_berg16@163.com
 * @version    $Id: Validator.php 186 2007-04-25 08:00:26Z icesyc $
 */

class Validator
{

	//规则数组
	private $rules = array();

	public function __construct()
	{
	}

	/*
	 * 检验数据不能为空
	 */
	public function required($data) 
	{
		return is_array($data) ? count($data) > 0 : trim($data) != "";
	}

	/*
	 * 检验数据不能超过指定长度
	 *
	 * @param string $data
	 * @param int $len 长度
	 */
	public function maxLength($data, $len)
	{
		return strlen($data) <= $len;
	}

	/*
	 * 检验数据不能小于指定长度
	 *
	 * @param string $data
	 * @param int $len 长度
	 */
	public function minLength($data, $len)
	{
		return strlen($data) >= $len;
	}

	/** 
	 * 检验数据长度是否在指定范围内
	 *
	 * @param sring $data
	 * @param int $s 最小长度
	 * @param int $e 最大小度
	 */
	public function range($data, $s, $e)
	{
		return strlen($data) >= $s && strlen($data) <= $e;
	}

	/*
	 * 检验数据是不是只是数字和字母
	 */
	public function alphaNumber($data)
	{
		$re = "|^[a-zA-Z0-9]+$|";
		return $this->match($data, $re);
	}

	/*
	 * 检查数据是否为大于零的整数
	 */
	public function isInt( $data )
	{
		$re = "|^\d+$|";
		return preg_match($re, $data);
	}

	/*
	 * 检查数据是否为日期格式
	 */
	public function isDate( $data )
	{
		$re = "#^\d{4}([/-])([0][0-9]|[1][0-2])\\1([0-2][0-9]|[3][0-1])$#";
		return $this->match( $data, $re );
	}

	/*
	 * 检查数据是否为正确的email格式
	 */
	public function isEmail($data)
	{
		$re = "#^[\w\.-]+@\w+\.\w+(\.\w+)?$#";
		return $this->match($data, $re);
	}

	/*
	 * 检查数据是否为正确的电话格式
	 */
	public function isPhone($data)
	{
		$re = "#^\d+(\-\d+){0,2}$#";
		return $this->match($data, $re);
	}

	/*
	 * 检查数据是否匹配给定的模式
	 *
	 * @param string $data
	 * @param string $re 正则表达式
	 */
	public function match($data, $re)
	{
		return preg_match($re, $data);
	}

	/*
	 * 检查给定两个数据是否相等
	 *
	 * @param string $data1,$data2
	 */
	public function equal($data1, $data2)
	{
		return $data1 === $data2;
	}
}
?>