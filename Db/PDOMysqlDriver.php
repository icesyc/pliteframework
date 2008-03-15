<?php
/**
 * PDOMysql接口
 *
 * [dsn]
 * 		host, user, pwd, charset, option
 * 
 * @package    Plite.Db
 * @author     ice_berg16(寻梦的稻草人)
 * @copyright  ice_berg16@163.com
 * @version    $Id$
 */

class PDOMysqlDriver extends PDO
{
	//构造函数
	public function __construct($dsn)
	{
		$host   = isset($dsn['host']) ? $dsn['host'] : 'localhost';
		$user   = isset($dsn['user']) ? $dsn['user'] : null;
		$pwd    = isset($dsn['pwd']) ? $dsn['pwd'] : null;
		$option = isset($dsn['option']) ? $dsn['option'] : null;
		$d = "mysql:host=$host";
		if(isset($dsn['database'])) $d .= ";dbname=" . $dsn['database'];
		parent::__construct($d, $user, $pwd, $option);
		parent::setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		if(!empty($dsn['charset'])){
			$sql = $this->exec("SET NAMES ". $dsn['charset']);
		}
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
		if($offset > 0 ){
			return $sql.sprintf(" LIMIT %d, %d", $offset, $count);
		}else{
			return $sql." LIMIT $count";
		}
	}

	/**
	 * 取得表中的字段信息
	 * 数组中包含如下信息
	 * [fields] => array<br/>
	 *		[name] 字段名称<br/>
	 *		[type] 字段类型 numeric, string, datetime<br/>
	 *		[null] 是否可以为null<br/>
	 *		[default] 默认值<br/>
	 * [primaryKey] 主键字段名称(没有则返回null
	 *
	 * @param string $table 表名称
	 * @return array 返回一个字段信息数组
	 */
	public function meta($table)
	{
		$primaryKey = null;
		$fields = array();
		$st = $this->query('DESC '. $table);
		while($row = $st->fetch(PDO::FETCH_ASSOC))
		{
			$fields[] = array(
				'name'	  => $row['Field'],
				'type'	  => $this->column($row['Type']),
				'null'	  => $row['Null'] == 'YES' ? true : false,
				'default' => $row['Default']
			);
			//判断是否为主键
			if($row['Key'] == 'PRI')
			{
				$primaryKey = $row['Field'];
			}
		}
		return array(
		'fields'	 => $fields,
		'primaryKey' => $primaryKey
		);
	}

	/**
	 * 根据字段元类型返回指定字段类型, 改写cakePHP的对应代码
	 *
	 * @param string 字段类型
	 */
	protected function column($type)
	{
		$col = array_shift(explode("(", $type));

		if(in_array($col, array('date', 'time', 'datetime', 'timestamp', 'year')))
		{
			return 'datetime';
		}

		if(strpos($col, 'int') !== false || in_array($col, array('float', 'double', 'decimal', 'dec')))
		{
			return 'numeric';
		}

		if(strpos($col, 'char') !== false
		|| strpos($col, 'text') !== false
		|| strpos($col, 'blob') !== false
		|| strpos($col, 'binary') !== false)
		{
			return 'string';
		}

		if($col == 'set' || $col == 'emum')
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
		$sql = "SHOW TABLES";
		$st = $this->query($sql);
		$tables = array();
		while($row = $st->fetch(PDO::FETCH_NUM)){
			$tables[] = $row[0];
		}
		return $tables;
	}
}
?>
