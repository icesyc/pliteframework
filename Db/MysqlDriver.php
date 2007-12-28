<?php
/**
 * Mysql数据库操作接口
 *
 * [dsn]
 * 		host, user, pwd, charset
 *
 * @package    Plite.Db
 * @author     ice_berg16(寻梦的稻草人)
 * @copyright  2004-2006 ice_berg16@163.com
 * @version    $Id: Mysql.php 155 2006-12-15 04:03:19Z icesyc $
 */

class MysqlDriver
{
	private $linkId;				 //用来保存连接Id

	private $queryId;				 //用来保存查询Id

	private $queryTimes = 0;		 //保存查询的次数

	public function __construct($dsn)
	{
		//连接数据库
		$this->connect($dsn);
	}

	/*
	 * 数据库连接 
	 */
	public function connect($dsn)
	{
		//设置DSN默认值
		if(!isset($dsn['host'])) $dsn['host'] = 'localhost';
		if(!isset($dsn['user'])) $dsn['user'] = '';
		if(!isset($dsn['pwd']))	 $dsn['pwd']  = '';
		if(!isset($dsn['database'])) $dsn['database'] = '';

		$this->linkId = @mysql_connect($dsn['host'], $dsn['user'], $dsn['pwd']);
		if (!$this->linkId)
			throw new MysqlException(mysql_error(), mysql_errno());

		if (!empty($dsn['database']) && !@mysql_select_db($dsn['database'], $this->linkId))
		{
			throw new MysqlException(mysql_error(), mysql_errno());
		}
		if(!empty($dsn['charset']))
		{
			$sql = "SET NAMES ". $dsn['charset'];
			$this->queryId = mysql_query($sql, $this->linkId);
			if(!$this->queryId)
				throw new MysqlException(mysql_error(), mysql_errno(), $sql);
		}
		return $this->linkId;
	}

	/**
	 * 取得表中的字段信息
	 * 数组中包含如下信息
	 * [fields] => array<br/>
	 *		[name] 字段名称<br/>
	 *		[type] 字段类型 numeric, string, datetime<br/>
	 *		[null] 是否可以为null<br/>
	 *		[default] 默认值<br/>
	 * [primaryKey] 主键字段名称(没有则返回null)
	 *
	 * @param string $table 表名称
	 * @return array 返回一个字段信息数组
	 */
	public function meta($table)
	{
		$primaryKey = null;
		$fields = array();
		$st = $this->query('DESC '. $table);
		while($row = $st->fetch())
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
		$type = explode("(", $type);
		$col = array_shift($type);
		
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
		while($row = $st->fetch(MYSQL_NUM)){
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
	public function exec($sql)
	{
		$this->queryTimes++;
		$this->queryId = mysql_query($sql, $this->linkId);
		if(!$this->queryId)
			throw new MysqlException(mysql_error(), mysql_errno(), $sql);
		return mysql_affected_rows($this->linkId);
	}

	/**
	 * 执行一条SQL语句,返回一个MysqlStatement对象
	 *
	 * @param string $sql sql语句
	 * @return MysqlStatement对象
	 */
	public function query($sql)
	{	
		$this->queryTimes++;
		$st = new MysqlStatement($sql, $this->linkId);
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
	public function limit($sql, $count, $offset=0)
	{
		if($offset > 0)
			return $sql.sprintf(" LIMIT %d, %d", $offset, $count);
		else
			return $sql." LIMIT $count";
	}

	/**
	 * 开始一个事务
	 */
	public function beginTransaction()
	{
		$sql = "START TRANSACTION";
		if(!mysql_query($sql, $this->linkId))
			throw new MysqlException(mysql_error(), mysql_errno(), $sql);
		return true;
	}
	
	/**
	 * 提交一个事务
	 */
	public function commit()
	{
		$sql = "COMMIT";
		if(!mysql_query($sql, $this->linkId))
			throw new MysqlException(mysql_error(), mysql_errno(), $sql);
		return true;
	}

	/**
	 * 回滚一个事务
	 */
	public function rollBack()
	{
		$sql = "ROLLBACK";
		if(!mysql_query($sql, $this->linkId))
			throw new MysqlException(mysql_error(), mysql_errno(), $sql);
		return true;
	}

	/**
	 *  返回查询的次数
	 */
	public function getQueryTimes()
	{
		return $this->queryTimes;
	}
	
	/**
	 * 返回最后一次插入的自增Id
	 */
	public function lastInsertId() 
	{
		return mysql_insert_id($this->linkId);
	}
	
	/*
	 * 字符串转义 
	 *
	 * @param string $str
	 */
	public function quote($str)
	{
		return "'" . mysql_real_escape_string($str) . "'";
	}

	/**
	 * 返回一个已填充好数据的MysqlStatement对象
	 *
	 * @param string $sql sql语句
	 * @param array $input 要填充的数组
	 * @return MysqlStatement对象
	 */
	 public function prepare($sql, $input=null)
	 {
		$st = new MysqlStatement($sql, $this->linkId);
		$st->__prepare($input);
		return $st;
	 }
}


//返回的记录集
class MysqlStatement
{
	private $linkId;	//连接标志
	private $queryId;	//查询的结果
	private $fetchMode = MYSQL_ASSOC;	//取得记录的模式
	private $sql;		//sql语句

	/**
	 * 构造函数
	 *
	 * @param string $sql sql语句
	 * @param resource $linkId mysql的连接句柄
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
		$this->queryId = mysql_query($sql, $this->linkId);
		if(!$this->queryId)
			throw new MysqlException(mysql_error(), mysql_errno(), $sql);
		return true;
	}

	/**
	 * 从记录集中返回一条记录
	 *
	 * @param int $mode 取得记录的模式 MYSQL_NUM|MYSQL_ASSOC|MYSQL_BOTH
	 * @return array 一条记录
	 */
	public function fetch($mode=null)
	{
		if(is_null($mode))
			$mode = $this->fetchMode;
		return  mysql_fetch_array($this->queryId, $mode);
	}
	
	/**
	 * 返回所有记录
	 *
	 * @param int $mode 取得记录的模式 MYSQL_NUM|MYSQL_ASSOC|MYSQL_BOTH
	 * @return array 记录集
	 */
	public function fetchAll($mode=null)
	{
		if(is_null($mode))
			$mode = $this->fetchMode;
		$rows = array();
		while($row = mysql_fetch_array($this->queryId, $mode))
			array_push($rows, $row);
		return $rows;
	}

	/**
	 * 返回影响的记录数
	 */
	public function rowCount()
	{
		return mysql_affected_rows($this->queryId);
	}

	/**
	 * 取得下一行记录的某个字段值
	 *
	 * @param int 键值
	 */
	public function fetchColumn($key=0)
	{
		$row = mysql_fetch_array($this->queryId, MYSQL_NUM);
		return $row[$key];
	}

	/**
	 * 设置取得记录的模式
	 * 
	 * @param int $mode 取得记录的模式 MYSQL_NUM|MYSQL_ASSOC|MYSQL_BOTH
	 */
	public function setFetchMode($mode)
	{
		if ( $mode == MYSQL_ASSOC || $mode == MYSQL_NUM || $mode == MYSQL_BOTH ) 
		{
			$this->fetchMode = $mode;
		}
		else
		{
			throw new MysqlException("未知的Fetch Mode");
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
			throw new MysqlException("输入数组与?不匹配",0,$this->sql);
		$sql = '';
		for($i=0; $i<$ci; $i++)
		{
			$sql .= $sqlSeq[$i];
			switch( gettype($input[$i]) )
			{
				case 'string':
					$sql .= "'".mysql_real_escape_string($input[$i])."'";
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

/**
 * Mysql异常类
 *
 * @package    Plite.Db
 * @author     ice_berg16(寻梦的稻草人)
 * @copyright  2004-2007 ice_berg16@163.com
 * @version    $Id: MysqlException.php 134 2006-11-30 03:29:08Z icesyc $
 */

class MysqlException extends Exception
{
	private $sql;

	/**
	 * 构造函数
	 *
	 * @param string $msg 错误信息
	 * @param int $code 错误代码
	 * @param string $sql 发生错误的SQL语句
	 */
	public function __construct($msg=null, $code=0, $sql=null)
	{
		parent::__construct($msg, $code);
		$this->sql = $sql;
	}
	
	/**
	 * 返回当前SQL语句
	 *
	 * @return string sql语句
	 */
	public function getSql()
	{
		return $this->sql;
	}

}
?>