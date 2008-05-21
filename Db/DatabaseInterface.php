<?php
/**
 * summary	���ݿ�����Ľӿ�
 *
 * @package    
 * @author     ice_berg16(Ѱ�εĵ�����)
 * @copyright  ice_berg16@163.com
 * @version    $Id$
 */

interface DatabaseInterface{	
	public function connect($dsn);
	public function meta($table);
	public function column($type);
	public function tableList();
	public function exec($sql);
	public function query($sql);
	public function limit($sql, $count, $offset);
	public function beginTransaction();
	public function commit();
	public function rollBack();
	public function lastInsertId();
	public function quote($str);
	public function prepare($sql, $input=null);
}

interface statementInterface{
	public function execute($input=null);
	public function fetch($mode=null);
	public function fetchAll($mode=null);
	public function rowCount();
	public function fetchColumn($key=0);
	public function setFetchMode($mode);
}
?>