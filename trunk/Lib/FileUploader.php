<?php

/**
 * 提供对文件上传的的封装处理 
 *
 * 
 *
 * @package    Plite.Lib
 * @author     ice_berg16(寻梦的稻草人)
 * @copyright  2004-2006 ice_berg16@163.com
 * @version    $Id: FileUploader.php 200 2007-10-16 05:23:30Z icesyc $
 */

class FileUploader
{
	//保存所有的上传文件列表
	private $files = array();

	/**
	 * 构造函数 
	 *
	 * 将上传文件的数据保存在数组中，对于同名的数组，则分别产生新的序列
	 * 如<input type='file' name='file[]'/>
	 * <input type='file' name='file[]'/>
	 * 产生file1, file2的对应数组
	 *
	 * @param string $type 允许的上传文件类型
	 */
	public function __construct($type=null)
	{
		foreach($_FILES as $key => $info)
		{
			if(is_array($info['name']))
			{
				foreach($info['name'] as $k => $v)
				{
					$inf = array(
						"name"		=> $info['name'][$k],
						"type"		=> $info['type'][$k],
						"tmp_name"	=> $info['tmp_name'][$k],
						"error"		=> $info['error'][$k],
						"size"		=> $info['size'][$k]
					);
					$this->files[$key.$k] = new UploadFile($inf, $type);
				}
			}
			else{
				$this->files[$key] = new UploadFile($info, $type);
			}
		}
	}

	/**
	 * 设置允许上传的文件类型
	 *
	 * @param string $type 允许上传的文件类型
	 */
	public function setAllow($type){
		if(is_null($type)) return true;
		foreach($this->files as &$f)
		{
			$f->setAllow($type);
		}
	}

	/**
	 * 返回指定名称的上传文件对象 
	 *
	 * @param @string $name
	 * @return UploadFile对象
	 */
	public function getFile($name)
	{
		if(isset($this->files[$name]))
			return $this->files[$name];
		return null;
	}

	/**
	 * 返回所有的上传文件对象数组 
	 *
	 * @return Array
	 */
	public function getFiles()
	{
		return $this->files;
	}

	/**
	 * 将所有的上传文件移动到指定文件夹 
	 *
	 * @param $dir 要移动的目录
	 * @param int flag 1代表使用原来的文件名, 2代表使用随机的文件名
	 * @param string $prefix 文件前缀
	 * @param boolean $forceGBK 是否强制转换为GBK编码的文件名,只针对utf-8编码的中文有效
	 *
	 * @return 返回一个路径列表
	 */
	public function move($dir, $flag=UploadFile::FILE_OLD_NAME, $prefix='', $forceGBK=false)
	{
		foreach($this->files as $f)
		{
			$path[] = $f->move($dir, $flag, $prefix, $forceGBK);
		}
		return $path;
	}
}

/**
 * 封装一个上传文件对象
 *
 * UploadFile是配合FileUploader一起使用的，单独使用时要注意
 * 初始化时要将用$_FILES['filename']来构造，并且filename不能是数组
 * $uf = new UploadFile($_FILES['filename']);
 *
 *
 * @package    Plite.Lib
 * @author     ice_berg16(寻梦的稻草人)
 * @copyright  2004-2006 ice_berg16@163.com
 * @version    $Id: FileUploader.php 200 2007-10-16 05:23:30Z icesyc $
 */

class UploadFile
{
	//保存上传文件的信息数组
	private $file;
  
	//允许上传的文件类型
	private $type;

	//移动时使用原始文件名
	const FILE_OLD_NAME = 1;

	//移动时使用随机文件名
	const FILE_RANDOM_NAME = 2;

	/**
	 * 构造函数
	 *
	 * @param array $file 上传文件信息数组
	 * @param string $type 允许上传的文件类型
	 *
	 */
	public function __construct($file, $type=null)
	{
		$this->file = $file;
		if(!is_null($type)){
			$this->setAllow($type);
		}
	}

	/**
	 * 移动上传文件 
	 *
	 * 可以使用UploadFile::FILE_RANDOM_NAME,UploadFile::FILE_OLD_NAME作为文件名
	 * 分别为随机名，原始文件名，使用新的文件名时要包括扩展名
	 *
	 * @param string $dir 目标目录
	 * @param int|string name 
	 * @param string $prefix 文件名的前缀
	 * @param boolean $forceGBK 是否强制转换为GBK编码的文件名,只针对utf-8编码的中文有效
	 * @return 如果成功返回上传后的新路径，否则抛出异常
	 */
	public function move($path, $name=self::FILE_OLD_NAME, $prefix='', $forceGBK=false)
	{	
		//先进行文件类型检查
		$this->checkAllow();
		if(substr($path,-1) != "/" && substr($path,-1) != "\\")
			$path .= "/";
		if($name == self::FILE_RANDOM_NAME)
			$name = uniqid($prefix) . "." . $this->getExt();
		elseif($name == self::FILE_OLD_NAME){
			$name = $prefix . $this->getName();
		}
		$path .= $name;
		if($forceGBK){
			$path = iconv("UTF-8", "GBK//IGNORE", $path);
		}
		if(file_exists($path))
			throw new Exception("要上传的文件已经存在于目录中 =>" . $path);
		if(move_uploaded_file($this->file['tmp_name'], $path))
			return $path;
		throw new Exception("移动上传文件 $path 时出错");
	}
	
	/**
	 * 检查上传文件的类型
	 *
	 * @param string $type 允许的文件类型,以,分隔
	 */
	private function checkAllow(){
		if(is_null($this->type)) return true;
		if(!in_array($this->getExt(), $this->type)){
			throw new Exception($this->getExt()."类型的文件不允许上传");
		}
		return true;
	}

	/**
	 * 设置允许的文件类型
	 *
	 * @param string $type 允许的文件类型
	 */
	public function setAllow($type){
		$this->type = explode(",", $type);
	}

	//文件是否上传成功
	public function uploadOK()
	{
		return $this->file['error'] == UPLOAD_ERR_OK;
	}

	//取得扩展名
	public function getExt()
	{
		return pathinfo($this->file['name'], PATHINFO_EXTENSION);
	}
	
	//使用魔法函数来取得文件的一些信息
	public function __call($method, $arg)
	{
		if(substr($method,0,3) == "get")
		{
			$attr = strtolower(substr($method,3));
			if(isset($this->file[$attr]))
				return $this->file[$attr];
			else
				return null;
		}
		return false;
	}
}
?>