<?php
/**
 * ORM类，提供对ADOdb_Active_Record的支持
 *
 * @author     ice_berg16(寻梦的稻草人)
 * @copyright  2004-2006 ice_berg16@163.com
 * @version    $Id: ModelTemplate.php 4 2008-03-15 13:15:42Z icesyc $
 */

require_once("Plite/Db/adodb5/adodb-active-record.inc.php");

class ORM extends ADOdb_Active_Record{
	
	public function __construct(){
		try
		{
			$db = Plite::get("DB");
		}
		catch(Exception $e)
		{
			//实现lazy load
			Plite::load("Plite.Db.DataSource");
			$db	= DataSource::getInstance(Config::get("dsn"))->adodb;
			Plite::set("DB", $db);
		}
		$table = $this->_pluralize(Config::get("tablePrefix").substr(get_class($this), strlen(Config::get("modelPrefix"))));
		parent::__construct($table, false, $db);
	}
}
?>