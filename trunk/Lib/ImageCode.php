<?php
/**
 * ImageCode 
 *
 * 图片验证码生成程序
 *
 * @package    Plite.Lib
 * @author     ice_berg16(寻梦的稻草人)
 * @copyright  2004-2006 ice_berg16@163.com
 * @version    $Id$
 */

class ImageCode
{
	
	/**
	 * 构造函数
	 */
	public function __construct()
	{}

	/**
	 * code from AntiSpamImage class 
	 *
	 * @author nio
	 */
	public function makeCode($grade=5, $dir='h')
	{
		@session_start();
		$_SESSION['IMAGE_CODE'] = str_replace(array('0', 'o'), array('1', 'p'), strtolower(substr(md5(rand()), 20, 4)));
		$char = $_SESSION['IMAGE_CODE'];
		$im = @imagecreate(90, 20)
			or die ("Cannot initialize new GD image stream!");
		$background_color = imagecolorallocate($im, 225, 225, 225);
		//random points
		for ($i = 0; $i <= 128; $i++) {
			$point_color = imagecolorallocate($im, rand(0,255), rand(0,255), rand(0,255));
			imagesetpixel($im, rand(2,128), rand(2,38), $point_color);
		}
		//output characters
		for ($i = 0; $i < strlen($char); $i++) {
			$text_color = imagecolorallocate($im, rand(0,255), rand(0,128), rand(0,255));
			$x = 10 + $i * 20;
			$y = rand(1, 4);
			imagechar($im, 5, $x, $y,  $char{$i}, $text_color);
		}
		
		if($grade > 0){
			$w = imagesx($im);
			$h = imagesy($im);
			if($dir=="h"){
				for($i=0;$i<$w;$i+=2){
					imagecopyresampled($im,$im, $i-2, sin($i/10)*$grade,$i,0,2,$h,2,$h);
				}
			}else{
				for($i=0;$i<$h;$i+=2){
					imagecopyresampled($im,$im, sin($i/10)*$grade,$i-2,0,$i,$w,2,$w,2);
				}
			}
		}
		//ouput PNG
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		// HTTP/1.1
		header("Cache-Control: no-store, no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0", false);
		// HTTP/1.0
		header("Pragma: no-cache");
		header("Content-type: image/png");
		imagepng($im);
		imagedestroy($im);    
		exit;
	}
	
	/**
	 * 检查验证码是否正确
	 */
	function isValidCode()
	{
		@session_start();
		//进行严密检查
		if(empty($_SESSION['IMAGE_CODE']) || empty($_POST['securitycode']))
		{
			return false;
		}
		return $_POST['securitycode'] == $_SESSION['IMAGE_CODE'];
	}
}
?>