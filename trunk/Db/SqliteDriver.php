<?php
/**
 * SQLite 数据库抽象层
 *
 * [dsn] database 数据库文件的路径
 * 
 * @package    Plite.Db
 * @author     ice_berg16(寻梦的稻草人)
 * @copyright  ice_berg16@163.com
 * @version    $Id: SqliteDriver.php 192 2007-05-22 05:43:00Z icesyc $
 */

class SqliteDriver
{
	private $linkId;				 //用来保存连接Id

	private $queryId;				 //用来保存查询Id

	private $queryTimes = 0;		 //保存查询的次数
	
	/**
	 * 构造函数
	 *
	 * @param array $dsn 数据库连接DSN
	 */
	public function __construct($dsn){
		if(!isset($dsn['database'])) throw new Exception("未指定数据库名称");
		$this->connect($dsn['database']);
	}
	
	/**
	 * 连接数据库
	 *
	 * @param string $db 数据库名称
	 */
	public function connect($db){
		$this->linkId = @sqlite_open($db, 0666, $error);
		if(!$this->linkId)
			throw new Exception($error);
	}
	
	/**
	 * 取得表中的字段信息
	 * 数组中包含如下信息
	 * [fields] => array<br/>
	 *		[name] 字段名称<br/>
	 *		[type] 字段类型 numeric, string<br/>
	 *		[null] 是否可以为null
	 *		[default] 默认值
	 * [primaryKey] 主键字段名称(没有则返回null)
	 *
	 * @param string $table 表名称
	 * @return array 返回一个字段信息数组
	 */
	public function meta($table){
		$primaryKey = null;
		$fields = array();
		$st = $this->query("PRAGMA table_info('$table')");
		while($row = $st->fetch())
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
		while($row = $st->fetch(SQLITE_NUM)){
			$tables[] = $row[0];
		}
		return $tables;
	}
	
	/**
	 * 执行一条SQL语句,返回影响的记录数
	 *
	 * @param string $sql sql语句
	 * @return int 影响的记录数
	 */
	public function exec($sql){
		$this->queryTimes++;
		$this->queryId = sqlite_exec($this->linkId, $sql, $error);
		if(!$this->queryId)
			throw new Exception($error);
		return sqlite_changes($this->linkId);
	}
	
	/**
	 * 执行一条SQL语句,返回一个SqliteStatement对象
	 *
	 * @param string $sql sql语句
	 * @return SqliteStatement对象
	 */
	public function query($sql){
		$this->queryTimes++;
		$st = new SqliteStatement($sql, $this->linkId);
		$st->execute();
		return $st;
	}
	
	/**
	 * 返回带offset查询的sql片段
	 *
	 * @param string $sql
	 * @param int $count 查询结果的返回的记录数
	 * @param int $offset 记录指针的偏移量
	 * @return string sql语句
	 */
	public function limit($sql, $count, $offset=0){
		$sql .= " LIMIT $count";
		if($offset > 0)
		$sql .= " OFFSET $offset";
		return $sql;
	}
	
	/**
	 * 开始一个事务
	 */
	public function beginTransaction(){
		return $this->exec("BEGIN");
	}
	
	/**
	 * 提交一个事务
	 */
	public function commit(){
		return $this->exec("COMMIT");
	}
	
	/**
	 * 回滚一个事务
	 */
	public function rollBack(){
		return $this->exec("ROLLBACK");
	}
	
	/**
	 * 返回当前查询的总次数
	 */
	public function getQueryTimes()
	{
		return $this->queryTimes;
	}
	
	/**
	 * 返回最新插入的自增ID
	 */
	public function lastInsertId(){
		return sqlite_last_insert_rowid($this->linkId);
	}
	
	/**
	 * 字符串转义
	 *
	 * @param string $str 要转义的字符串
	 */
	public function quote($str){
		return sqlite_escape_string($str);
	}
	
	/**
	 * 返回一个已填充好数据的SqliteStatement对象
	 *
	 * @param string $sql sql语句
	 * @param array $input 要填充的数组
	 * @return SqliteStatement对象
	 */
	public function prepare($sql, $input=null){
		$st = new SqliteStatement($sql, $this->linkId);
		$st->__prepare($input);
		return $st;
	}
}

class SqliteStatement
{	
	private $linkId;		//连接标志
	private $queryId;	//查询的结果
	private $fetchMode = SQLITE_ASSOC;	//取得记录的模式
	private $sql;		//sql语句

	/**
	 * 构造函数
	 *
	 * @param string $sql sql语句
	 * @param resource $linkId sqlite的连接句柄
	 */
	public function __construct($sql, $linkId)
	{
		$this->sql = $sql;
		$this->linkId = $linkId;
	}

	/**
	 * 执行一个SQL查询
	 *
	 * @param array $input 用于查询的填充数组
	 * @return boolean 查询成功返回true,否则抛出异常
	 */
	public function execute($input=null)
	{
		$sql = $this->__prepare($input);
		$this->queryId = sqlite_query($this->linkId, $sql, $this->fetchMode, $error);
		if(!$this->queryId)
			throw new Exception($error);
		return true;
	}

	/**
	 * 从记录集中返回一条记录
	 *
	 * @param int $mode 取得记录的模式 SQLITE_NUM|SQLITE_ASSOC|SQLITE_BOTH
	 * @return array 一条记录
	 */
	public function fetch($mode=null)
	{
		if(is_null($mode))
			$mode = $this->fetchMode;
		return  sqlite_fetch_array($this->queryId, $mode);
	}
	
	/**
	 * 返回所有记录
	 *
	 * @param int $mode 取得记录的模式 SQLITE_NUM|SQLITE_ASSOC|SQLITE_BOTH
	 * @return array 记录集
	 */
	public function fetchAll($mode=null)
	{
		if(is_null($mode))
			$mode = $this->fetchMode;
		$rows = array();
		while($row = sqlite_fetch_array($this->queryId, $mode))
			array_push($rows, $row);
		return $rows;
	}

	/**
	 * 返回影响的记录数
	 */
	public function rowCount()
	{
		return sqlite_changes($this->linkId);
	}

	/**
	 * 取得下一行记录的某个字段值
	 *
	 * @param int 键值
	 */
	public function fetchColumn($key=0)
	{
		return sqlite_column($this->queryId, $key);
	}

	/**
	 * 设置取得记录的模式
	 * 
	 * @param int $mode 取得记录的模式 SQLITE_NUM|SQLITE_ASSOC|SQLITE_BOTH
	 */
	public function setFetchMode($mode)
	{
		if ( $mode == SQLITE_ASSOC || $mode == SQLITE_NUM || $mode == SQLITE_BOTH ) 
		{
			$this->fetchMode = $mode;
		}
		else
		{
			throw new Exception("未知的Fetch Mode");
		}		
	}

	/**
	 * 内置的prepare函数,用于构造SQL
	 *
	 * @param array $input 要填充的数组
	 * @return string sql语句
	 */
	public function __prepare($input=null)
	{
		if(is_null($input))
			return $this->sql;
		$sqlSeq = explode("?", $this->sql);
		$ci     = count($input);
		if(count($sqlSeq)-1 != $ci)
			throw new Exception("输入数组与?不匹配",0,$this->sql);
		$sql = '';
		for($i=0; $i<$ci; $i++)
		{
			$sql .= $sqlSeq[$i];
			switch( gettype($input[$i]) )
			{
				case 'string':
					$sql .= "'".sqlite_escape_string($input[$i])."'";
					break;
				case 'NULL':
					$sql .= "''";
					break;
				default:
					$sql .= $input[$i];
					break;
			}
		}
		return $sql;
	}
}
?>