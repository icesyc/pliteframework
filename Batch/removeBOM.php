<?php
/**
 * 用于移除UTF-8格式文件的BOM标记，指定文件夹后即可批量修改
 *
 * @package    Plite.Batch
 * @author     ice_berg16(寻梦的稻草人)
 * @copyright  2004-2006 ice_berg16@163.com
 * @version    $Id$
 */

require_once("../Lib/FileSystem.php");
$fs = new FileSystem();

$dir = "d:/www/Plite";
$ftype = array("php", "htm", "css", "js");
$fs->recursiveDir($dir, 'removeBOM');

function removeBOM($f)
{
	$ext = pathinfo($f, PATHINFO_EXTENSION);
	if(in_array($ext, $GLOBALS['ftype'])){
		$content = file_get_contents($f);
		$c1 = ord($content{0});
		$c2 = ord($content{1});
		$c3 = ord($content{2});
		if($c1 == 0xEF && $c2 == 0xBB && $c3 == 0xBF){
			$content = substr($content, 3);
			file_put_contents($f, $content);
			echo $f . " -> remove BOM OK\n";
		}else{
			echo $f . " -> no BOM\n";
		}
	}
}
?>