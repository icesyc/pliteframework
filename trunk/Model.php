<?php
/**
 * Model
 *
 * 基本数据操作模型,实现添加,删除,修改,查询等	
 *
 * @package    Plite
 * @author     ice_berg16(寻梦的稻草人)
 * @copyright  2004-2006 ice_berg16@163.com
 * @version    $Id$
 */

class Model
{
	//数据访问层
	protected $DB;			
	
	//表的名称
	protected $table;

	//加了表前缀的全名
	protected $fullTableName;

	//字段名数组
	protected $fields = array();	
	
	//主键
	protected $PK;			

	//是否调试
	protected $debug = false;	
	
	//数据过滤器数组
	protected $dataFilters = array();

	//基本SQL
	const INSERT_SQL = "INSERT INTO %s (%s) VALUES(%s)";
	const UPDATE_SQL = "UPDATE %s SET %s WHERE %s = '%s'";
	const DELETE_SQL = "DELETE FROM %s WHERE %s %s";

	//过滤器类型
	const FILTER_FIND = 1;
	const FILTER_SAVE = 2;

	//数据库调试的异常代码
	const SQL_DEBUG_CODE = 1001;

	//构造函数
	public function __construct() 
	{
		try
		{
			$this->DB = Plite::get("DB");
		}
		catch(Exception $e)
		{
			//实现lazy load
			Plite::load("Plite.Db.DataSource");
			$DB	= DataSource::getInstance(Config::get("dsn"));
			Plite::set("DB", $DB);
			$this->DB = $DB;
		}

		if(empty($this->table))
		{
			throw new Exception("未指定表名称");
		}
		
		//取得表全名
		$this->fullTableName = Config::get("tablePrefix") . $this->table;

		//初始化字段信息
		$this->getMetaInfo();
	}

	/** 
	 * 取得字段信息
	 */
	public function getMetaInfo()
	{
		$meta = $this->DB->meta($this->fullTableName);
		$this->fields = $meta['fields'];
		$this->PK	  = $meta['primaryKey'];
	}

	/**
	 * 动态函数，findBy,deleteBy的实现 
	 *
	 * findBy函数有二个参数，第一个是字段的值可以是数组，也可以是单值<br/>
	 * 其它的参数与findAll的参数相同。
	 *
	 * @param $m 访问动态函数的名称
	 * @param $a 动态函数的参数
	 */
	public function __call($m, $a)
	{
		if(strpos($m, "By") === false)
			throw new Exception("Model Error:调用的函数 $m 目前还未实现。");

		list($act, $field) = explode("By", $m);
		//camelize => underscore
		$field = strtolower(preg_replace('/(?<=\w)[A-Z]/', '_\\0', $field));

		switch($act)
		{
			//findByField
			case 'find':
				$a[0] = $field . " " . $this->fieldCondition($a[0]);
				return call_user_func_array(array($this, 'self::find'), $a);
				break;
			//findAllByField
			case 'findAll':
				$a[0] = $field . " " . $this->fieldCondition($a[0]);
				return call_user_func_array(array($this, 'self::findAll'), $a);
				break;
			//deleteByField
			case 'delete':
				return self::deleteByField($field, $a[0]);
				break;
			default:
				throw new Exception("Model Error:调用的函数 $m 目前还未实现。");
				break;
		}
	}

	/**
	* 插入数据
	*
	* @param array $data
	*
	* @return 刚插入的自增ID
	*/
	public function create($data)
	{	
		$this->applyFilter($data, self::FILTER_SAVE);
		$value = array();
		$field = array();
		foreach($this->fields as $f) 
		{
			//查看主键是否为空,为空则跳过
			if($f['name'] == $this->PK && empty($data[$f['name']]))
			{
				continue;
			}
			//自动生成时间戳
			if($f['name'] == 'created')
			{
				array_push($field, $f['name']);
				array_push($value, time());
				continue;
			}
			if(array_key_exists($f['name'], $data))
			{
				if($f['type'] != 'numeric')
				{
					$data[$f['name']] = $this->DB->quote($data[$f['name']]);
				}
				array_push($field, $f['name']);
				array_push($value, $data[$f['name']]);
			}
		}
		$sql = sprintf(self::INSERT_SQL, $this->fullTableName, join(",", $field), join(",", $value));
		$this->debugSql($sql);
		$this->DB->exec($sql);
		return $this->DB->lastInsertId();
	}

	/**
	 * 更新数据 
	 *
	 * @param array $data 
	 */
	public function update($data) 
	{
		$this->applyFilter($data, self::FILTER_SAVE);
		$value = array(); 
		foreach($this->fields as $f) 
		{
			if($f['name'] == $this->PK)	//为主键，跳过
			{
				continue;
			}
			//自动更新时间戳
			if($f['name'] == 'updated' || $f['name'] == 'modified')
			{
				array_push($value, $f['name'] . "=" . time());
				continue;
			}

			if (array_key_exists($f['name'], $data))
			{
				if($f['type'] != 'numeric')
				{
					$data[$f['name']] = $this->DB->quote($data[$f['name']]);
				}
				array_push($value, $f['name'] . "=" . $data[$f['name']]);
			}
		}
		//如果没有需要更新的字段,则返回
		if(count($value) == 0) return true;
		$sql = sprintf(self::UPDATE_SQL, $this->fullTableName, join(",", $value), $this->PK, $data[$this->PK]);
		$this->debugSql($sql);
		return $this->DB->exec($sql) != -1;
	}

	/**
	 * 保存数据，根据主键自动调用插入或更新 
	 *
	 * @param array $data 要保存的数据
	 * @return integer|bool
	 */
	public function save($data)
	{
		if(array_key_exists($this->PK, $data) && !empty($data[$this->PK]))
			return $this->update($data);
		else
			return $this->create($data);
	}

	/**
	 * 根据主键删除记录
	 * 
	 * @param integer|array 主键值或数组
	 */
	public function delete($id)
	{
		return $this->deleteByField($this->PK, $id);
	}

	/**
	 * 根据条件删除 
	 *
	 * @param string $f 字段名
	 *
	 * @param mixed	字段值或数组
	 */
	final public function deleteByField($f, $v)
	{
		$v = $this->fieldCondition($v);
		$sql = sprintf(self::DELETE_SQL, $this->fullTableName, $f, $v);
		$this->debugSql($sql);
		return $this->DB->exec($sql);
	}

	/*
	 * 创建空的记录集
	 */
	public function createEmptySet()
	{
		foreach($this->fields as $f)
		{
			$set[$f['name']] = $f['default'];
		}
		return $set;
	}

	/**
	 * 根据主键取得一条记录
	 *
	 * @param integer $id
	 * @return array 一条记录集
	 */
	public function get($id=null)
	{
		if(is_null($id)) return $this->createEmptySet();
		if(!is_numeric($id)) $id = $this->DB->quote($id);

		$sql = "SELECT * FROM %s WHERE %s = %d";
		$sql = sprintf($sql, $this->fullTableName, $this->PK, $id);
		$this->debugSql($sql);
		$st = $this->DB->prepare($sql);
		$st->execute();
		return $this->applyFilter($st->fetch(), self::FILTER_FIND);
	}
	
	/**
	 * 返回表指定字段的段
	 *
	 * @param string $f 字段名
	 * @param array $condition 条件数组
	 * @return 
	 */
	public function field($name, $condition=null, $order=null)
	{
		if($row = self::find($condition, $name, $order))
		{
			return $row[$name]; 
		}
		return false;
	}

	/**
	 * 返回查询的记录总数
	 *
	 * @return integer
	 */
	public function findCount($condition)
	{
		$row = self::find($condition, "count(*) AS count");
		return $row['count'];
	}

	/**
	 * 取得记录集的函数,没有分组和联接功能，
	 *
	 * @param array|string $condition 查询条件
	 * @param string $fields 字段列表
	 * @param string $order 排序字段
	 * @param integer $limit 取的记录数
	 * @param integer $page 页数
	 * @param boolean $count 是否计算分页参数
	 * @return array 记录集
	 */
	public function findAll($condition=null, $fields="*", $order=null, $limit=null, $page=1, $count=false)
	{		
		$where = $condition;
		$cond = compact('where' ,'fields', 'order', 'limit', 'page', 'count');
		return $this->_query($cond);
	}
	
	/**
	 * 取得一条记录
	 *
	 * @param array|string $condition 条件数组
	 * @param string $fields 字段列表
	 * @return array 返回一条记录
	 */
	public function find($condition=null, $fields="*")
	{
		$rows = self::findAll($condition, $fields, null, 1);
		return isset($rows[0]) ? $rows[0] : false;		
	}

	/**
	 * 根据数组信息进行查询并返回记录集
	 *
	 * 参数是一个关键数组，有以下选项<br>
	 * field 字段列表<br>
	 * from  主表,未指定则使用当前表<br>
	 * join  连接的表条件 type[inner,left] table[name] on[condition] 默认为left<br>
	 * where 查询条件数组<br>
	 * group 编组<br>
	 * order 排序字段<br>
	 * page int 页数<br>
	 * limit int 取的记录数<br>
	 * count 是否计算分页参数
	 *
	 * @param array $condition 查询数组
	 * @return array 记录集
	 */
	final protected function _query($condition)
	{
		if(!isset($condition['fields']))  
			$condition['fields'] = "*";
		$sql = "SELECT {$condition['fields']} ";
		$from= "FROM ".(!empty($condition['from']) ? $condition['from'] : $this->fullTableName) . " ";
		$where=$join=$group=$order="";
		foreach( $condition as $k => $v )
		{
			if(empty($v)) continue;
			switch($k)
			{
				case 'join':
					if(isset($v[0]) && is_array($v[0]))	//多表join
					{
						foreach($v as $j)
						{
							if(!isset($j['type']))
								$j['type'] = 'LEFT';
							else 
								$j['type'] = strtoupper( $j['type'] );
							$join .= "{$j['type']} JOIN {$j['table']} ON {$j['on']} ";
						}
					}
					else	//单表join
					{
						//得到 $type,$table, $on, 				
						extract($condition['join']);
						if(!isset($type)) 
							$type = 'LEFT';
						else 
							$type = strtoupper( $type );
						$join = "$type JOIN $table ON $on ";
					}
					break;
				case 'where':
					if(is_array($v))
						$where = "WHERE ". join(" AND ", $v);
					else
						$where = "WHERE ". $v;
					break;
				case 'group':
					$group = " GROUP BY " . $v;
					break;
				case 'order':
					$order = " ORDER BY " . $v;
					break;
				case 'limit':
					$offset = 0;
					if(!empty($condition['page']))
						$offset = ($condition['page'] - 1) * $v;
					$count = $v;
					break;
			}
		}

		$sql = $sql.$from.$join.$where.$group.$order;
		//加入limit
		if(!empty($condition['limit']))
		{
			$sql = $this->DB->limit($sql, $count, $offset);
		}

		if(!empty($condition['count']) && $condition['count'] == true)
		{
			if(empty($condition['page']) || empty($condition['limit']))
				throw new Exception("未指定分页参数");
			if(!empty($condition['group']))
				$countSql = sprintf("SELECT count(DISTINCT %s) AS count ", $condition['group']);
			else
				$countSql = "SELECT count(*) AS count ";
			$st = $this->DB->prepare($countSql.$from.$join.$where);
			$st->execute();
			$recordCount = $st->fetchColumn();
			$this->setPageParam($recordCount, $condition['page'], $condition['limit']);
		}

		return $this->findAllBySql($sql);
	}

	/**
	 * 对记录集中的记录应用过滤器
	 *
	 * @param array $row 一条记录
	 * @param int $type 要应用的过滤器类型 FILTER_FIND, FILTER_SAVE
	 * @return array 过滤后的记录
	 */
	protected function applyFilter($row, $type)
	{
		foreach($this->dataFilters as $filter)
		{
			if($filter['type'] == $type)
			{
				$row = call_user_func($filter['callback'], $row);
			}
		}
		return $row;
	}

	//增加一个记录过滤器
	public function setFilter($filter, $type)
	{
		$this->dataFilters[] = array(
			'callback'	=> $filter,
			'type'		=> $type
		);
	}

	/**
	 * 清除过滤器
	 *
	 * @param integer|null 过滤器的类型
	 */
	public function clearFilter($type=null)
	{
		if(is_null($type))
		{
			$this->dataFilters = array();
		}
		else
		{
			foreach($this->dataFilters as $k => $f)
			{
				if($f['type'] == $type)
				{
					unset($this->dataFilters[$k]);
				}
			}
		}
	}

	/**
	 * 对于一个字段值，生成可以用于SQL的条件
	 *
	 * @param mix 单值或数组
	 */
	public function fieldCondition($f)
	{
		if(is_array($f))
		{
			if(!is_numeric($f[0]))
			{
				$f = array_map(array($this->DB, 'quote'), $f);
			}
			$where = "IN (" . join(",", $f) . ")";
		}
		else
		{
			if(!is_numeric($f))
			{
				$f = $this->DB->quote($f);
			}
			$where = "=" . $f;
		}
		return $where;
	}

	/**
	 * 调试用的函数 
	 *
	 * @param string $sql
	 */
	protected function debugSql($sql)
	{
		if($this->debug)
			throw new Exception($sql, self::SQL_DEBUG_CODE);
	}

	/**
	 * 设置分页参数 
	 *
	 * @param int recordCount 记录总数
	 * @param int $page 当前页
	 * @param 每页记录数
	 */
	public function setPageParam($recordCount, $page, $rows)
	{
		$this->pageParam['recordCount'] = $recordCount;
		$this->pageParam['pageSize']    = $rows;
		$this->pageParam['currentPage'] = $page;
	}

	/**
	 * 返回分页参数
	 */
	public function getPageParam()
	{

		if(isset($this->pageParam) && is_array($this->pageParam)) 
			return $this->pageParam;
		else
			throw new Exception("分页参数未提供.");
	}

	/**
	 * 执行一条sql查询并返回结果的第一条记录
	 *
	 * @param string $sql
	 */
	final public function findBySql($sql)
	{
		$this->debugSql($sql);
		$st = $this->DB->prepare($sql);
		$st->execute();

		$row = $st->fetch();
		return $this->applyFilter($row, self::FILTER_FIND);
	}

	/**
	 * 执行一条sql查询并返回结果集
	 *
	 * @param string $sql
	 */
	final public function findAllBySql($sql)
	{
		$this->debugSql($sql);
		$st = $this->DB->prepare($sql);
		$st->execute();
		
		$res = array();
		while($row = $st->fetch())
		{
			$res[] = $this->applyFilter($row, self::FILTER_FIND);
		}
		return $res;
	}
	
	/**
	 * 执行一条sql语句
	 *
	 * @param string $sql
	 */
	final public function exec($sql){
		return $this->DB->exec($sql);
	}

	//是否启用sql调试
	public function setDebug($bool=true)
	{
		$this->debug = $bool;
	}
}

?>