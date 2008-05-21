<?php
/**
 * summary	adodb�Ľӿڣ���Ҫ����adodb5.04�Ž�ѹ��Plite/DbĿ¼��
 * 
 * 
 * @package    
 * @author     ice_berg16(Ѱ�εĵ�����)
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