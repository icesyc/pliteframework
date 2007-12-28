<?php
/**
 * 将Plite框架转换为GBK编码
 *
 * @package    Plite.Batch
 * @author     ice_berg16(寻梦的稻草人)
 * @copyright  2004-2006 ice_berg16@163.com
 * @version    $Id: UTF2GB.php 193 2007-05-22 06:49:10Z icesyc $
 */

//先将文件进行编码转换
$root = dirname(dirname(__FILE__));
require("../Lib/FileSystem.php");
$fs = new FileSystem();
$fs->recursiveDir($root, 'utf2gb');

//修改几个error.php的meta 编码
$files = array(
	$root."/Exception/1001.php",
	$root."/Exception/DATABASE_ERROR.php",
	$root."/Exception/DEFAULT_ERROR.php"
);
foreach($files as $f)
{
	$str = file_get_contents($f);
	$str = str_replace("charset=utf-8", "charset=gbk", $str);
	file_put_contents($f, $str);
}

function utf2gb($f)
{
	if(pathinfo($f,PATHINFO_EXTENSION) == "php")
	{
		$content = file_get_contents($f);
		$content = iconv("UTF-8", "GBK//IGNORE", $content);
		file_put_contents($f, $content);
		echo $f." -> OK\n";
	}
}
?>