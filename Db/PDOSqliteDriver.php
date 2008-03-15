<?php
/**
 * PDOSqlite接口
 *
 * [dsn] 
 * 	   database  数据库名称
 * 	   version  指定sqlite的版本,不指定版本不能打开sqlite2创建的数据库
 * 
 * @package    Plite.Db
 * @author     ice_berg16(寻梦的稻草人)
 * @copyright  ice_berg16@163.com
 * @version    $Id$
 */

class PDOSqliteDriver extends PDO
{
	//构造函数
	public function __construct($dsn)
	{
		if(!isset($dsn['database'])) throw new Exception("未指定数据库名称");
		$prefix = (isset($dsn['version']) && $dsn['version'] == 2) ? 2 : '';
		$dsn = "sqlite$prefix:" . $dsn['database'];
		parent::__construct($dsn);
	}

	/**
	 * 返回指定条数的记录数用的sql语句
	 *
	 * @param string $sql sql语句
	 * @param int $count 记录数
	 * @param int $offset 偏移量
	 * @return string sql语句
	 */
	public function limit($sql, $count, $offset=0)
	{
		$sql .= " LIMIT $count";
		if($offset > 0)
			$sql .= " OFFSET $offset";
		return $sql;
	}

	/**
	 * 取得表中的字段信息
	 * 数组中包含如下信息
	 * [fields] => array<br/>
	 *		[name] 字段名称<br/>
	 *		[type] 字段类型 numeric, string<br/>
	 *		[null] 是否可以为null<br/>
	 *		[default] 默认值<br/>
	 * [primaryKey] 主键字段名称(没有则返回null)
	 *
	 * @param string $table 表名称
	 * @return array 返回一个字段信息数组
	 */
	public function meta($table){
		$primaryKey = null;
		$fields = array();
		$st = $this->query("PRAGMA table_info('$table')");
		while($row = $st->fetch(PDO::FETCH_ASSOC))
		{
			$fields[] = array(
				'name'	  => $row['name'],
				'type'	  => $this->column($row['type']),
				'null'	  => $row['notnull'] == 0 ? true : false,
				'default' => $row['dflt_value']				
			);
			//判断是否为主键
			if($row['pk'] == 1)
			{
				$primaryKey = $row['name'];
			}
		}
		return array(
			'fields'	 => $fields,
			'primaryKey' => $primaryKey
		);
	}

	/**
	 * 根据字段元类型返回指定字段类型
	 *
	 * @param unknown_type $type
	 */
	protected function column($type){
		$type = explode("(", $type);
		$col = array_shift($type);
		
		if(strpos($col, 'int') !== false
		|| strpos($col, 'real') !== false
		|| strpos($col, 'floa') !== false
		|| strpos($col, 'doub') !== false)
		{
			return 'numeric';
		}

		if(strpos($col, 'char') !== false 
		|| strpos($col, 'text') !== false
		|| strpos($col, 'clob') !== false)
		{
			return 'string';
		}		
		return 'string';
	}

	/**
	 * 返回数据库的所有表名称
	 */
	public function tableList()
	{
		$sql = "SELECT name FROM sqlite_master where type='table'";
		$st = $this->query($sql);
		$tables = array();
		while($row = $st->fetch(PDO::FETCH_NUM)){
			$tables[] = $row[0];
		}
		return $tables;
	}
}
?>
