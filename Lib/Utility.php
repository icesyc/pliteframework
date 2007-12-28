<?php
/**
 * Utility 
 *
 * 提供一些常用函数的封装
 *
 * @package    Plite.Lib
 * @author     ice_berg16(寻梦的稻草人)
 * @copyright  2004-2006 ice_berg16@163.com
 * @version    $Id: Utility.php 182 2007-04-21 09:27:39Z icesyc $
 */

class Utility
{
	/*
	 * 中文截取，支持gb2312,gbk,utf-8,big5 
	 *
	 * @param string $str 要截取的字串
	 * @param int $start 截取起始位置
	 * @param int $length 截取长度
	 * @param string $charset utf-8|gb2312|gbk|big5 编码
	 * @param $suffix 是否加尾缀
	 */
	public function csubstr($str, $start=0, $length, $charset="utf-8", $suffix=true)
	{
		if(function_exists("mb_substr"))
		{
			if(mb_strlen($str, $charset) <= $length) return $str;
			$slice = mb_substr($str, $start, $length, $charset);
		}
		else
		{
			$re['utf-8']   = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
			$re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
			$re['gbk']	  = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
			$re['big5']	  = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
			preg_match_all($re[$charset], $str, $match);
			if(count($match[0]) <= $length) return $str;
			$slice = join("",array_slice($match[0], $start, $length));
		}
		if($suffix) return $slice."…";
		return $slice;
	}

	/**
	 * 根据字节数转换成相应的单位
	 *
	 * @param int $byte 字节数字
	 * @return 转换后单位的字符串(如1.34K,2.30M)
	 */
	public function sizeCount($byte)
	{
		if($byte >= 1073741824)
		{
			$byte = round($byte / 1073741824, 2) . " G";
		}
		elseif($byte >= 1048576)
		{
			$byte = round($byte / 1048576, 2) . " M";
		}
		elseif($byte >= 1024)
		{
			$byte = round($byte / 1024, 2) . " K";
		}
		else
		{
			$byte = $byte . " bytes";
		}
		return $byte;
	}

	/**
	 * 发送下载文件 
	 *
	 * @param string $fileName 文件的绝对路径
	 */
	public function sendFile($fileName)
	{
		$defaultMineTypes=array(
			'css'	=> 'text/css',
			'gif'	=> 'image/gif',
			'jpg'	=> 'image/jpeg',
			'jpeg'	=> 'image/jpeg',
			'htm'	=> 'text/html',
			'html'	=> 'text/html',
			'js'	=> 'javascript/js'
		);
		//echo $fileName;
		if(!is_file($fileName))
			throw new Exception("文件不存在，程序终止.");
		header("Pragma: public");
		header("Expires: 0"); // set expiration time
		header("Cache-Component: must-revalidate, post-check=0, pre-check=0");

		$mineType='text/plain';

		if (function_exists("mime_content_type"))
			$mineType = mime_content_type($fileName);
		else
		{
			$ext=strtolower(substr(strrchr($fileName, '.'), 1));
			if(isset($defaultMineTypes[$ext]))
				$mineType = $defaultMineTypes[$ext];
		}

		$fn = basename($fileName);

		header("Content-type: $mineType");
		header("Content-Length: " . filesize($fileName));
		header("Content-Disposition: attachment; filename=\"$fn\"");
		header('Content-Transfer-Encoding: binary');
		readfile($fileName);
		exit();
	}

	/**
	 * 功能 将数据转换为SmartTemplate表格格式
	 *
	 * @param array $records 数组记录
	 * @param int $cols 表格中每行显示的记录数
	 * @param string $colsKey 数组中每行的键名
	 */
	public function tableFormat($records, $cols=5, $colsKey="TD")
	{
		$ROWS = array();
		while($col = array_splice($records, 0, $cols))
		{
			$ROWS[][$colsKey] = $col;
		}
		return $ROWS;
	}

}

?>