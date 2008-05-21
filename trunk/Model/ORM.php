<?php

require_once("Plite/Db/adodb5/adodb-active-record.inc.php");
class ORM extends ADOdb_Active_Record{
	
	public function __construct(){
		try
		{
			$db = Plite::get("DB");
		}
		catch(Exception $e)
		{
			//й╣ожlazy load
			Plite::load("Plite.Db.DataSource");
			$db	= DataSource::getInstance(Config::get("dsn"))->adodb;
			Plite::set("DB", $db);
		}
		$table = $this->_pluralize(Config::get("tablePrefix").substr(get_class($this), strlen(Config::get("modelPrefix"))));
		parent::__construct($table, false, $db);
	}
}
?>