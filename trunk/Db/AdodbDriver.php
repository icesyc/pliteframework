<?php
/**
 * summary	adodb的接口，需要下载adodb5.04放解压到Plite/Db目录下
 * 
 * 
 * @package    
 * @author     ice_berg16(寻梦的稻草人)
 * @copyright  ice_berg16@163.com
 * @version    $Id$
 */

require_once('Plite/Db/adodb5/adodb-exceptions.inc.php');
require_once('Plite/Db/adodb5/adodb.inc.php');

class AdodbDriver{

	public $adodb;

	public function __construct($dsn){
		$this->adodb = ADONewConnection($dsn['dbType']);
		$this->adodb->Connect($dsn['host'], $dsn['user'], $dsn['pwd'], $dsn['database']);
	}

	public function __call($m, $a){
		return call_user_func_array(array($this->adodb,$m), $a);
	}
}

?>