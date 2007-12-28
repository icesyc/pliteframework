<?php
/**
 * 使用HTTP上传文件
 *
 * 使用例子如下<br/>
 * $pf = new IBPostFile( "http://host.com/test.php");<br/>
 * $pf->setFile( "uploadFile", "d:/images/ice.gif" );<br/>
 * $pf->send();<br/>
 * echo $pf->getResponse();
 *
 * @package    Plite.Lib
 * @author     ice_berg16(寻梦的稻草人)
 * @copyright  2004-2006 ice_berg16@163.com
 * @version    $Id: PostFile.php 134 2006-11-30 03:29:08Z icesyc $
 */

class PostFile
{
	private $url;		//要发送文件的URL
	private $formData;	//发送的表单数据
	private $fileData;	//文件数据
	private $boundary;	//数据分隔标识
	private $response;	//保存服务器返回的信息
	private $username;	//需要身份验证时的用户名
	private $pwd;		//需要身份验证时的密码
	private $port;		//端口号

	public  $debug = false;	//是否调试

	/* 函数: IBPostFile
	** 功能: Constructor
	** 参数: $url String 要发送文件的URL
	*/
	public function __construct($url="", $port="80")
	{
		$this->url		= $url;
		$this->port		= $port;
		$this->boundary = "---------------------------" . substr(md5(time()), -12);
	}
	
	/**	
	 * 发送数据并保存结果
	 * 
	 * @return 返回服务器返回的HTTP原始数据
	 */
	public function send()
	{
		$urlArray = parse_url($this->url);
		$fp = fsockopen($urlArray['host'], $this->port);

		$requestData = $this->buildRequest();

		//*
		fwrite($fp, $requestData);
		
		$content = "";
		while(!feof($fp))
		{
			$content .= fread($fp, 4096);
		}
		fclose($fp);
		//*/
		$this->response = $content;

		if($this->debug)
		{
			echo "---------HTTP-REQUEST-------";
			echo "<pre>$requestData</pre><br/>";
			echo "---------HTTP-RESPONSE------";
			echo "<pre>$content</pre>";
		}
		//*/
		return $content;
	}

	/**
	 * 取得服务器端返回的原始HTTP数据 
	 */
	public function getResponse()
	{
		return $this->response;
	}

	/** 
	 * 返回服务器端的HTTP头信息
	 */
	public function getResponseHeader()
	{
		$arr = explode("\r\n\r\n", $this->response);
		$header = array_shift($arr);
		return $header;
	}
	
	/**
	 * 返回服务器端的内容信息
	 */
	public function getResponseContent()
	{
		$arr = explode("\r\n\r\n", $this->response);
		array_shift($arr);

		return join("\r\n\r\n", $arr);
	}
	
	/**
	 * 设置表单的字段值
	 *
	 * @param array $formData 字段名和值的数组
	 */
	public function setForm($formData)
	{
		$this->formData = $this->buildFormData($formData);
	}

	/**
	 * 设置要发送的文件
	 *
	 * @param string $name 文件名，即file域的name
	 * @param string $filePath 要发送的文件路径
	 */
	public function setFile($name, $filePath)
	{

		$this->fileData = $this->buildFileData($name, $filePath);
	}
	
	/**
	 * 设置身份验证时需要的用户名和密码
	 *
	 * @param string $user 用户名
	 * @param string $pwd 密码
	 */
	public function setAuth($user, $pwd)
	{
		$this->username = $user;
		$this->pwd		= $pwd;
	}

	/*
	 * 建立请求
	 */
	private function buildRequest()
	{
		$urlArray = parse_url($this->url);
		$request = array();

		$request[] = "POST {$urlArray['path']} HTTP/1.0";
		$request[] = "Host: {$urlArray['host']}";
		$request[] = "User-Agent: Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0)";
		$request[] = "Accept: */*";
		$request[] = "Accept-Language: zh-cn";
		$request[] = "Connection: Keep-Alive";
		$request[] = "Cache-Control: no-cache";
		
		//需要身份验证
		if (!empty($this->username) && !empty($this->pwd)) 
		{
    	    $request[] = 'Authorization: BASIC ' . base64_encode($this->username.':'.$this->pwd);
    	}

		$request[] = "Content-Type: multipart/form-data; boundary={$this->boundary}";
		$request[] = "Content-Length: " . $this->getDataLength() . "\r\n";

		$requestString = join("\r\n", $request) . "\r\n" . $this->formData . "\r\n" . $this->fileData;

				
		if($this->debug)
		{
			echo "----------- REQUEST_INFOMATION -----------";
			echo "<pre>" . $requestString . "</pre>";
		}
		return $requestString;
	}

	/**
	 * 返回要发送数据的长度
	 */
	private function getDataLength()
	{
		return strlen($this->formData) + strlen("\r\n") + strlen($this->fileData);
	}
	
	/**
	 * 创建发送的数据格式
	 */
	private function buildFormData($formData)
	{
		$postData = array();
		foreach($formData as $k => $v)
		{
			$row   = array();
			$row[] = "--{$this->boundary}";
			$row[] = "Content-Disposition: form-data; name=\"$k\"\r\n";
			$row[] = "$v";

			$postData[] = join("\r\n", $row);
		}

		return join("\r\n", $postData);
	}
	
	/**
	 * 创建发送的文件格式
	 *
	 * @param string $name file域的name
	 * @param string $filePath 文件的绝对路径
	 */
	function buildFileData($name, $filePath)
	{
		//读取文件信息
		$fname  = basename($filePath);
		$fp		= fopen ($filePath, "r");
		$data	= fread ($fp, filesize($filePath));
		fclose ($fp); 
		
		$postData = array();
		$postData[] = "--{$this->boundary}";
		$postData[] = "Content-Disposition: form-data; name=\"$name\"; filename=\"$fname\"\r\n";
		//$postData[] = "Content-Type: text/plain\r\n";
		$postData[] = $data;
		$postData[] = "--{$this->boundary}--";

		return join("\r\n", $postData);
	}
}
?>