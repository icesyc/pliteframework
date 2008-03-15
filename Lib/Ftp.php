<?php

/**
 * FTP类 
 *
 * 对FTP函数的封装
 *
 * @package    Plite.Lib
 * @author     ice_berg16(寻梦的稻草人)
 * @copyright  2004-2006 ice_berg16@163.com
 * @version    $Id$
 */

class Ftp
{

	/*
	 * FTP登录的连接标识
	 *
	 * @access private
	 * @var resource
	 */
	private $connId;

	/*
	 * 传输模式 FTP_BINARY 或 FTP_ASCII
	 *
	 * @access private
	 * @var int
	 */
	private $mode = FTP_BINARY;
	
	/*
	 * 主机名
	 *
	 * @access private
	 * @var string
	 */
	private $host;
	
	/*
	 * FTP端口
	 *
	 * @access private
	 * @var int
	 */
	private $port = 21;
	/*
	 * 用户名
	 *
	 * @access private
	 * @var string
	 */
	private $user;

	/*
	 * 密码
	 *
	 * @access private
	 * @var string
	 */
	private $pwd;
	
	/*
	 * 是否异步传输 
	 *
	 * @access private
	 * @var int;
	 */
	private $async = true;
	
	/*
	 * 超时设置
	 *
	 * @access private
	 * @var int
	 */
	private $timeout = 10;

	/*
	 * 列表时用的匹配模式
	 *
	 * @access private
	 * @var array
	 */
    private $listPattern = array(
		'pattern' => '/(?:(d)|.)([rwxt-]+)\s+(\w+)\s+([\w\d-]+)\s+([\w\d-]+)\s+(\w+)\s+(\S+\s+\S+\s+\S+)\s+(.+)/',
		'map'     => array(
			'is_dir'        => 1,
			'rights'        => 2,
			'files_inside'  => 3,
			'user'          => 4,
			'group'         => 5,
			'size'          => 6,
			'date'          => 7,
			'name'          => 8,
		)
    );

	/*
	 * 文件扩展名
	 *
	 * @access private
	 * @var array
	 */
	private $fileAscii = array('txt','asp','php','jsp','htm','html','aspx','js','css','xml','xsl');


	/*
	 * 监听函数
	 *
	 * @access private
	 * @var callback
	 */
	private $listener = null;

	/*
	 * 目录或文件过滤函数
	 * 在文件传输过程时，会调用该函数来过滤该目录或文件是否进行操作
	 *
	 * @access private
	 * @var callback
	 */
	private $filter = null;

	/*
	 * 输出过程中出错时重试的次数
	 *
	 * @access private
	 * @var int
	 */
	private $retryTime = 2;
	
	//重试的计数变量
	private $retryCount = 0;

	/*
	 * 构造函数 
	 *
	 * @param string $host	ftp主机名
	 * @param string $user	ftp用户名
	 * @param string $pwd	ftp密码
	 * @param int $port		ftp端口号
	 */
	public function __construct($host=null, $user=null, $pwd=null, $port=21)
	{
		$this->host = $host;
		$this->user = $user;
		$this->pwd  = $pwd;
		$this->port = $port;

		if(!is_null($host) && !is_null($user) && !is_null($pwd))
		{
			$this->login();
		}
	}

	/*
	 * 断开FTP连接 
	 */
	public function quit()
	{
		if(!is_resource($this->connId))
			return true;
		$this->notify(array('act' => 'quit'));
		$res = @ftp_close($this->connId);
	}
	
	/*
	 * FTP登录 
	 */
	public function login($host=null, $user=null, $pwd=null, $port=21)
	{	
		if(is_null($host)) $host = $this->host;
		if(is_null($user)) $user = $this->user;
		if(is_null($pwd)) $pwd = $this->pwd;
		$this->notify(array('act' => 'login', 'host' => $host));
		$res = @ftp_connect($host, $port, $this->timeout);
		if(!$res)
			throw new Exception("连接到FTP主机 ".$host." 失败");
		$this->connId = $res;
		$res = @ftp_login($this->connId, $user, $pwd);
		if(!$res)
			 throw new Exception("FTP登录时发生错误");
	}

	/*
	 * 更换目录 
	 *
	 * @param string $dir
	 */
	public function cd($dir)
	{
		$res = @ftp_chdir($this->connId, $dir);
		if(!$res)
			throw new Exception("未能切换到目录$dir");
	}

	/*
	 * 取得指定目录的文件列表 
	 * 该函数返回一个文件列表，每个单元为一个文件或目录的关联数组，该数组有如下结构
     *           ["name"]        =>  string 文件或目录名<BR>
     *           ["rights"]      =>  string 权限<BR>
     *           ["user"]        =>  string 该文件的主用户<BR>
     *           ["group"]       =>  string 用户组<BR>
     *           ["files_inside"]=>  string 目录下的文件或目录数目总和<BR>
     *           ["date"]        =>  int 文件创建日期<BR>
     *           ["is_dir"]      =>  bool true, 是否为目录<BR>
	 * 注意，该函数返回的数组不包含.和..
	 *
	 * @param string $path	路径
	 * @return array 文件列表
	 * @return 文件列表的数组，每个单元为一个文件或目录的数组
	 */
	public function ls($path=null)
	{
		if(is_null($path)) $path = ftp_pwd($this->connId);		
		$this->notify(array('act' => 'list', 'remoteDir' => $path));

		$res = ftp_rawlist($this->connId, $path);
		if(!$res)
			throw new Exception("取得目录 $path 的文件列表时出错");
		$files = array();
		foreach( $res as $line )
		{
			if(!preg_match($this->listPattern['pattern'], $line, $m)) continue;
			foreach( $this->listPattern['map'] as $k => $v )
			{
				$file[$k] = $m[$v];
			}
			if($file['name'] == "." || $file['name'] == "..") continue;
			array_push($files, $file);
		}
		return $files;
	}

	/*
	 * 递归删除一个目录
	 *
	 * @param string $path	目录路径
	 * @return bool
	 */
	public function rmDir($path)
	{
		$files = $this->ls($path);
		foreach( $files as $f )
		{
			//是目录
			$fname = $path."/".$f['name'];
			if($f['is_dir'])
			{
				$this->rmDir($fname);
			}
			else
			{				
				$this->notify(array('act' => 'delete', 'remoteDir' => $path, 'file' => $f['name']));
				if(!@ftp_delete($this->connId, $fname))
					throw new Exception("删除文件 ".$fname." 时出错.");				
			}
		}
		//删除该目录
		$this->notify(array('act' => 'delete', 'remoteDir' => $path));
		if(!@ftp_rmdir($this->connId, $path))
			throw new Exception("删除目录 ".$path." 时出错.");
		return true;
	}

	/*
	 * 递归上传一个目录
	 * 远程目录需要指定新的目录名如/root/path/to/newDir
	 *
	 * @param string $lp 本地路径
	 * @param string $rp 远程路径
	 * @return bool
	 */
	public function putDir($lp, $rp)
	{
		if($this->fileFilter($lp, true))
		{
			$this->notify(array(
					'act'		=> 'skip', 
					'localDir'	=> $lp,
					'remoteDir' => $rp		
				));
			return true;
		}

		if(!is_dir($lp))
			throw new Exception("指定的本地目录 $lp 不存在");
		if(!@ftp_chdir($this->connId, $rp))
		{
			//在远程目录建立相应的目录层次
			$this->notify(array(
					'act'		=> 'mkdir',
					'remoteDir' => dirname($rp)
				));
			if(!@ftp_mkdir($this->connId, $rp))
			throw new Exception("建立远程目录 $rp 时失败");
		}
		
		$dir = @dir($lp);
		while(($f = $dir->read()) !== false)
		{
			if($f == "." || $f == "..") continue;
			//本地路径
			$p1 = $lp . "/" . $f;
			//远程路径
			$p2 = $rp . "/". $f;
			if(is_dir($p1))	//为目录,递归
			{
				$this->putDir($p1, $p2);
			} 
			else		//为文件,直接操作
			{
				$this->put($p1, $p2);
			}
		}
		$this->notify(array(
				'act'		=> 'putDirFinish',
				'localDir'	=> $lp,
				'remoteDir' => $rp
			));
	}
	
	/*
	 * 上传一个文件到远程目录
	 *
	 * @param string $lp 本地路径
	 * @param string $rp 远程路径
	 * @param int $mode 传输模式 FTP_ASCII | FTP_BINARY
	 * @return bool
	 */
	public function put($lp, $rp, $mode=null)
	{
		if($this->fileFilter($lp, false))
		{
			$this->notify(array(
					'act'		=> 'skip',
					'localDir'	=> dirname($lp),
					'remoteDir' => dirname($rp), 
					'file'		=> basename($lp)
				));
			return true;
		}

		if(!file_exists($lp))
			throw new Exception("指定的本地文件 $lp 不存在");
		
		if(is_null($mode)) $mode = $this->checkMode($lp);
		if($this->async)
		{
			$fh = fopen($lp, "r");
			$res = @ftp_nb_fput($this->connId, $rp, $fh, $mode, ftp_size($rp));
            while($res == FTP_MOREDATA)
			{
                $this->notify(array(
							'act'		=> 'put',
							'localDir'	=> dirname($lp),
							'remoteDir' => dirname($rp), 
							'file'		=> basename($lp),
							'filePos'	=> ftell($fh)
							));
                $res = @ftp_nb_continue($this->connId);
            }
			fclose($fh);
		}
		else
		{
            $res = @ftp_put($this->connId, $rp, $lp, $mode);
		}
		if(!$res)
		{
			if($this->retryCount++ < $this->retryTime )
				$this->put($lp, $rp, $mode);
			else
				throw new Exception("上传文件 $lp 时发生错误");
		}
		$this->notify(array(
					'act'		=> 'putFinish',
					'localDir'	=> dirname($lp),
					'remoteDir' => dirname($rp), 
					'file'		=> basename($lp)
				));
		//重置
		$this->retryCount = 0;
		return true;
	}

	/*
	 * 递归下载一个目录
	 *
	 * @param string $rp 远程路径
	 * @param string $lp 本地路径
	 * @return bool
	 */
	public function getDir($rp, $lp)
	{
		if($this->fileFilter($rp, true))
		{
			$this->notify(array(
					'act'		=> 'skip',
					'localDir'	=> $lp,
					'remoteDir' => $rp
				));
			return true;
		}

		if(!@ftp_chdir($this->connId, $rp))
			throw new Exception("指定的远程目录 $rp 不存在");
		//目录不存在，建立目录
		if(!is_dir($lp))
		{
			$this->notify(array(
					'act'		=> 'mkdir',
					'localDir'	=> dirname($lp)
				));
			if(!@mkdir($lp))
				throw new Exception("建立本地目录 $lp 失败");
		}
		$files = $this->ls($rp);
		foreach( $files as $f )
		{
			//是目录
			$p1 = $rp."/".$f['name'];
			$p2 = $lp."/".$f['name'];
			if($f['is_dir'])
			{
				$this->getDir($p1, $p2);
			}
			else
			{				
				$this->get($p1, $p2);		
			}
		}
		$this->notify(array(
				'act'		=> 'getDirFinish',
				'localDir'	=> $lp,
				'remoteDir' => $rp
			));
	}
	
	/*
	 * 下载一个文件 
	 *
	 * @param string $rp 远程路径
	 * @param string $lp 本地路径
	 * @param int $mode 传输模式 FTP_ASCII | FTP_BINARY
	 */
	public function get($rp, $lp, $mode=null)
	{
		if($this->fileFilter($rp, false))
		{
			$this->notify(array(
					'act'		=> 'skip', 
					'localDir'	=> dirname($lp),
					'remoteDir' => dirname($rp), 
					'file'		=> basename($lp)
				));
			return true;
		}

		if(!@opendir(dirname($lp)))
			throw new Exception("指定的目录 ".dirname($rp)." 不存在或无权访问.");
		if(is_null($mode)) $mode = $this->checkMode($rp);
		if($this->async)
		{
			$fh = fopen($lp, "a");
			$res = @ftp_nb_fget($this->connId, $fh, $rp, $mode, filesize($lp));
            while($res == FTP_MOREDATA)
			{
                $this->notify(array(
							'act'		=> 'put',
							'localDir'	=> dirname($lp),
							'remoteDir' => dirname($rp),
							'file'		=> basename($rp),
							'filePos'	=> ftell($fh)
							));
                $res = @ftp_nb_continue($this->connId);
            }
		}
		else
		{
            $res = @ftp_get($this->connId, $lp, $rp, $mode);
		}
		if(!$res)
		{
			if($this->retryCount++ < $this->retryTime )
				$this->get($rp, $lp, $mode);
			else
				throw new Exception("下载文件 $rp 时发生错误");
		}
		$this->notify(array(
				'act'		=> 'getFinish', 
				'localDir'	=> dirname($lp),
				'remoteDir' => dirname($rp), 
				'file'		=> basename($lp)
			));
		//重置
		$this->retryCount = 0;
		return true;
	}
	
	/*
	 *  返回当前目录
	 *
	 * @return string 当前工作目录
	 */
	public function pwd()
	{
		return ftp_pwd($this->connId);
	}
	
	/*
	 * 自动调用一些FTP命令的魔法函数
	 *
	 * @param
	 * @return
	 */
	public function __call($func, $arg)
	{
	
		if(!function_exists('ftp_'.$func))
			throw new Exception("调用的函数 ftp_".$func." 不存在");
		array_unshift($arg, $this->connId);
		return call_user_func_array('ftp_' . $func, $arg);
	}

	/*
	 * 执行一条FTP命令 
	 *
	 * @param string $cmd
	 * @return
	 */
	public function cmd($cmd)
	{
		$res = @ftp_raw($this->connId, $cmd);
        if (!$res)
			throw new Exception("执行命令 $cmd 失败");
		else
			return $res;
	}
	
	/*
	 * 根据文件扩展名自动检测使用ASCII或BINARY方式传输 
	 *
	 * @param
	 * @return
	 */
	public function checkMode($file)
	{
		//没有扩展名就返回二进制模式
		if(!preg_match("#\.(.+)$#U", $file, $m))
			return FTP_BINARY;
		$ext = $m[1];
		if(in_array($ext, $this->fileAscii))
			return FTP_ASCII;
		return FTP_BINARY;
	}
	
	/*
	 * 检测该目录或文件是否可以被操作 
	 *
	 * @param string $file 源目录或文件的完整路径
	 * @param bool $isDir 是否为目录
	 * @return 如果
	 */
	public function fileFilter($file, $isDir)
	{
		//默认不过滤
		if(is_null($this->filter)) return false;
		//由回调函数来检查是否过滤
		return call_user_func($this->filter, $file, $isDir);
	}

	/*
	 * 设置一个过滤函数
	 * 使用一个回调函数来检查当前传输的目录或文件是否被过滤，该函数被传递二个参数<br/>
	 * 第一个是源文件源目录或文件的完整路径，第二个标识为是否为目录
	 *
	 * @param callback $filter
	 */
	public function setFilter($filter)
	{
		$this->filter = $filter;
	}

	/*
	 * 设置FTP的被动模式
	 *
	 * @param bool $bool
	 * @return bool 
	 */
	public function setPasv($bool=true)
	{
		return ftp_pasv($this->connId, $bool);
	}

	/*
	 * 设置是否为异步传输 
	 *
	 * @param bool $bool
	 */
	public function setAsync($bool=true)
	{
		$this->async = $bool;	
	}

	/*
	 * 设置使用的传输模式 
	 *
	 * @param $mode 取值为FTP_ASCII 或 FTP_BINARY
	 */
	public function setMode($mode)
	{
		$this->mode = $mode;	
	}

	/*
	 * 设置监听者 
	 *
	 * @param callback $listener
	 */
	public function setListener($listener)
	{
		$this->listener = $listener;
	}

	/*
	 * 设置文件上传出错时重试的次数 
	 *
	 * @param int $time 重试的次数
	 */
	public function setRetryTime($time)
	{
		$this->retryTime = $time;
	}

	/*
	 * 设置超时秒数 
	 *
	 * @param int $sec 秒数
	 */
	public function setTimeout($sec)
	{
		$this->timeout = $sec;
	}

	/*
	 * 通知该对象的监听者当前FTP的操作状态，信息 
	 * $status是保存当前FTP的一个关联数组，可能有如下键值<br/>
	 * ["act"]		=> 当前的动作<br/>
	 * ["dir"]		=> 当前操作的目录<br/>
	 * ["file"]		=> 当前操作的文件<br/>
	 * ["filePos"]	=> 当前操作文件的传输位置<br/>
	 *
	 * @param array $status
	 */
	public function notify($status)
	{
		if(!is_null($this->listener))
			call_user_func($this->listener, $status);
	}

	//析构函数
	public function __destruct()
	{
		$this->quit();
	}
}
?>