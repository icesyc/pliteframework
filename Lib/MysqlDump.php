<?php
/**
 * IBMysqlDump 
 *
 * Mysql 数据库备份程序
 *
 * @package    Plite.Lib
 * @author     ice_berg16(寻梦的稻草人)
 * @copyright  2004-2006 ice_berg16@163.com
 * @version    $Id: MysqlDump.php 155 2006-12-15 04:03:19Z icesyc $
 */

/**
 * 定义换行符 
 */
if(!defined("CRLF"))
	define("CRLF", "\r\n");

class MysqlDump
{

	private $DB;		//数据库抽象层

	private $dumpOption;//备份选项 可选值如下
						//dbName	数据库名称 string
						//dumpType	备份类型 structure 结构 full 结构和数据
						//savePath	保存为文件时的路径 string
						//dumpType	完全备份还是只备份结构 structure | full
						//saveType	保存方式，保存在服务器上还是发送到客户端 server | client
						//skipTable	不备份的表名,多个用逗号隔开
						//timeout	设置防止超时的时间 默认为30秒
	private $fh;		//文件指针，当数据保存在服务器上时使用

	private $timeStart;	//备份的起始时间

	/** 
	 * 构造函数
	 */
	public function __construct($DB)
	{
		$this->DB = $DB;
		$this->init();
	}
	
	//初始化函数
	function init()
	{
		$this->timeStart = time();
		$this->setDumpOption("timeout", 30);
	}

	/**
	 * 取得表结构
	 */
	function getTableStructure($table)
	{
		$sql = "SHOW CREATE TABLE " . $this->backQuote($table);
		$st  = $this->DB->prepare($sql);
		$st->execute();
		$ts = $st->fetchColumn(1);
		$this->exportHandler(str_replace("\n", CRLF, $ts) . ";");
	}
	
	/**
	 * 取得表数据
	 */
	function getTableData($table)
	{
		//取得字段信息
		$fields = $this->fieldsType($table);
		$fieldsInt = count($fields);
		
		$schemaInsert = 'INSERT INTO ' . $this->backQuote($table)
                      . ' VALUES (';
		//取得所有数据
		$search       = array("\x00", "\x0a", "\x0d", "\x1a");
        $replace      = array('\0', '\n', '\r', '\Z');
		$values		  = array();
		$sql = "SELECT * FROM " . $this->backQuote($table);
		$st  = $this->DB->prepare($sql);
		$st->execute();
		//保存输出
		$output = "";
		while($rec = $st->fetch())
		{
			for($i=0; $i<$fieldsInt; $i++)
			{
				$cntField = $rec[$fields[$i]['field']];
				$cntType  = $fields[$i]['type'];
				//echo $cntField;
				if(!isset($cntField))
				{
					$values[] = 'NULL';
				}
				elseif($cntField == '0' || $cntField != '')
				{
					//a number
					if($this->isIntType($cntType))
					{
						$values[] = $cntField;
					}
					//a blob 
					elseif($this->isBlobType($cntType))
					{
						$values[] = '0x' . bin2hex($cntField) ;
					}
					// a string
					else
					{
						$values[] = "'" . str_replace($search, $replace, $this->quote($cntField)) . "'";
					}
				}
				else
				{
					$values[] = "''";
				}				
			}
			$insertLine = $schemaInsert . implode(', ', $values) . ');' . CRLF;
			unset($values);
			$this->exportHandler($insertLine);
		}
		return true;
	}
	
	/*
	 * 备份表结构
	 */
	function dumpTableStructure($table)
	{
		$header = CRLF . CRLF
				. '# ' . CRLF
				. '# 表的结构 ' . $table . CRLF
				. '# ' . CRLF . CRLF;
				
		$this->exportHandler($header);
		$this->getTableStructure($table);
	}

	/**
	 * 备份表数据
	 */
	function dumpTableData($table)
	{
		$header = CRLF . CRLF
				. '# ' . CRLF
				. '# 导出表的数据 ' . $table . CRLF
				. '# ' . CRLF . CRLF;

		$this->exportHandler($header);
		$this->getTableData($table);
	}
	
	/**
	 * 功能 备份数据库中的所有表
	 */
	function dumpDB()
	{
		$this->exportInit();

		$sql = "SHOW TABLES";	
		$st  = $this->DB->prepare($sql);
		$st->execute();
		$st->setFetchMode(MYSQL_NUM);
		$res = array();

		if(array_key_exists("skipTables", $this->dumpOption))
		{
			$skipTables = explode(",", $this->dumpOption['skipTables']);
		}
		while($rec = $st->fetch())
		{
			//是否为不备份的表
			if(isset($skipTables) && in_array($rec[0], $skipTables))
			{
				continue;
			}
			array_push($res, $rec[0]);
		}
		//检查不备份的表名
		$st->setFetchMode(MYSQL_ASSOC);
		
		$isData = false;
		foreach($res as $table)
		{
			$this->dumpTableStructure($table);
			if(array_key_exists("dumpType",$this->dumpOption) && $this->dumpOption['dumpType'] == 'full')
			{
				$this->dumpTableData($table);
			}
		}
		$this->exportOver();
		return true;
	}

	/**
	 * 恢复数据库
	 *
	 * @param string $dbFile 数据库文件的路径
	 * @return false 文件不存在；
	 */
	function restore($dbFile)
	{
		if(!file_exists($dbFile))
		{
			return false;
		}
		
		$sqlfile = file_get_contents($dbFile);		
		$sqlArray = preg_split("/;[\r\n]+/", $sqlfile);
		unset($sqlfile);

		foreach($sqlArray as $sql)
		{
			$sql = trim(preg_replace("/^\s*#.+$/m", "", $sql));
			if(!empty($sql))
			{
				if(preg_match('/CREATE TABLE `(.*)`/iU',$sql,$tblarr))
				{
					//数据表存在则先删除原来的
					$tblName = $tblarr[1];
					$this->DB->exec("DROP TABLE IF EXISTS $tblName ");
					$this->DB->exec($sql);

				}
				else	//添加记录的查询语句
				{
					$this->DB->exec($sql);
				}

				$timeNow = time();
				if ($timeNow >= $this->timeStart + $this->dumpOption['timeout']) {
					$this->timeStart = $timeNow;
					header('X-pmaPing: Pong');
					echo "<!-- restoring... -->";
				} // end if
			}
		}
		return true;
	}

	/**
	 * 输出控制函数
	 *
	 * @param string $line 要输出的内容
	 */
	function exportHandler($line)
	{
		//保存在服务器上
		if($this->dumpOption['saveType'] == 'server')
		{
			@fwrite($this->fh, $line);
			$timeNow = time();
			if ($timeNow >= $this->timeStart + $this->dumpOption['timeout']) {
				$this->timeStart = $timeNow;
				header('X-pmaPing: Pong');
				echo "<!-- dumping... -->";
			} // end if
		}
		else
		{
			echo $line;
		}
	}

	/** 
	 * 输出的初始化	
	 * 
	 * 如果为保存在服务器上则创建文件指针，为客户端时输出HTTP头
	 */
	function exportInit()
	{
		$fname = $this->getDumpFileName();
		if($this->dumpOption['saveType'] == 'server')
		{
			if(file_exists($fname))
			{
				@unlink($fname);
			}
			$this->fh = fopen($fname, "a");
		}	
		else
		{
			header('Content-Type: application/octet-stream');
			header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
			header('Content-Disposition: attachment; filename="' . $fname . '"');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: public');
		}
	}

	/**
	 * 关闭文件指针
	 */
	function exportOver()
	{
		if($this->fh)
			@fclose($this->fh);
	}

	/**
	 * 生成要备份的文件名
	 */
	function getDumpFileName()
	{
		if(array_key_exists("dbName",$this->dumpOption))
		{
			$fname	= $this->dumpOption['dbName'] . "_" . date("Y-m-d")
					. "_" . $this->dumpOption['dumpType'] . ".sql";
		}
		else
			$fname	= "IBMysqlDump_" . date("Y-m-d")
					. "_" . $this->dumpOption['dumpType'] . ".sql";
		
		//保存在服务器上
		if($this->dumpOption['saveType'] == 'server')
		{
			
			$ds	= substr($this->dumpOption['savePath'], -1);	//取得最后一个字符，判断是否为目录分隔符
			if( $ds != "\\" || $ds != "\/")
			{
				$f = $this->dumpOption['savePath'] . "/" . $fname;
			}
			else
				$f = $this->dumpOption['savePath'] . $fname;

			return $f;
		}
		else
			return $fname;
	}

	/**
	 * 设置备份选项 
	 *
	 * @param string $key 选项名称
	 * @param string $value 选项值
	 */
	function setDumpOption($key, $value)
	{
		$this->dumpOption[$key] = $value;
	}

	/**
	 * 判断类型是否为数字类型 
	 */
	function isIntType($type)
	{
		if($type == 'tinyint' || $type == 'smallint' || $type == 'mediumint' || $type == 'int' || $type == 'bigint')
			return true;
		else
			return false;
	}

	/**
	 * 判断类型是否为BLOB类型 
	 */
	function isBlobType($type)
	{
		if($type == 'blob' || $type == 'mediumblob' || $type == 'longblob' || $type == 'tinyblob')
			return true;
		else
			return false;
	}

	/**
	 * 取得表所有字段的类型
	 */
	function fieldsType($table)
	{
		$sql = "SHOW FIELDS FROM " . $this->backQuote($table);
		$st  = $this->DB->prepare($sql);
		$st->execute();
		$res = array();
		while($row = $st->fetch())
		{
			$rec['field']= $row["Field"];
			$rec['type'] = preg_replace("#\(.*#", "", $row["Type"]);
			$res[] = $rec;
		}
		return $res;
	}


	/**
	 * 给字符串加mysql类型的引用
	 */
	function backQuote($str)
	{
		if(is_array($str))
		{
			foreach($str as $k => $v)
			{
				$res[$k] = "`" . $v . "`";
			}
			return $res;

		}
		else
			return "`" . $str . "`";
	}
	
	/**
	 * 转义
	 */
	function quote($str)
	{
		$str = str_replace("\\", "\\\\", $str);
		return str_replace("'", "\'", $str);	
	}
}
?>