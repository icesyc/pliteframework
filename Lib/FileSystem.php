<?php
/**
 * FileSystem 
 *
 * 提供对文件系统的访问
 *
 * @package    Plite.Lib
 * @author     ice_berg16(寻梦的稻草人)
 * @copyright  2004-2006 ice_berg16@163.com
 * @version    $Id$
 */

class FileSystem
{
	public function __construct()
	{
	}

	/*
	 * 递归目录 
	 *
	 * @param string $dir 目录
	 * @param callback $callback 递归的回调函数
	 * @param boolean $removeDir 递归时是否删除空目录
	 * @param boolean $recurive 是否递归
	 */
	public function recursiveDir($dir, $callback, $removeDir=false, $recursive=true)
	{
		if(!is_dir($dir))
			throw new Exception("$dir 不是目录");
		if(!$dh = opendir($dir))
			throw new Exception("不能打开目录 $dir");
		$res = array();
		while(($file = readdir($dh)) !== false)
		{
			if($file == "." || $file == "..") continue;
			$filePath = $dir . "/" . $file;

			if(is_dir($filePath))	//为目录,递归删除
			{
				if($recursive)
					$res[$file] = self::recursiveDir($filePath, $callback, $removeDir, true);
				else
					$res[] = call_user_func($callback, $filePath);
			} 
			else		//为文件,直接删除
			{
				$res[] = call_user_func($callback, $filePath);
			}
		}
		//文件删除完成,删除该目录
		closedir($dh);
		if($removeDir)
			@rmdir($dir);
		return $res;
	}

	/*
	 * 删除目录下所有文件及子目录
	 *
	 * @param $dir 目录
	 */
	public function removeDir($dir) 
	{
		$callback = create_function('$file','return @unlink($file);');
		return self::recursiveDir($dir, $callback, true);
	}

	/*
	 * 列出目录下所有文件及子目录名称
	 *
	 * @param string $dir 目录名称
	 * @param boolean $recursive是否递归
	 */
	function listDir($dir, $recursive=false) 
	{
		$callback = create_function('$file','return basename($file);');
		return self::recursiveDir($dir, $callback, false, $recursive);
	}

	/*
	 * 建立目录 
	 *
	 * @param string $dir 目录的路径
	 * @param boolean $recursive是否递归
	 */
	function mkdir($dir, $recursive=true)
	{
		if(!is_dir($dir))
		{
			if($recursive)
				self::mkdir(dirname($dir));
			if(!@mkdir($dir))
				throw new Exception(__CLASS__."->".__METHOD__.":创建目录 $dir 失败，检查是否有权限.");
		}
	}

}
?>