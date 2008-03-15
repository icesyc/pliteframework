<?php
/**
 * Tree
 *
 * 实现数据库存储树型数据，使用中值排序法来实现排序，动态改变排序
 * 基数，理论上可以达到无限级别。
 *
 * @package    Plite.Lib
 * @author     ice_berg16(寻梦的稻草人)
 * @copyright  2004-2006 ice_berg16@163.com
 * @version    $Id$
 */

class Tree
{

	/*---------- 数据库描述 -------------------
		id			编号
		root_id		根部门编号
		deep		深度
		ordernum	排序字段
	------------------------------------------
	CREATE TABLE `lq_sort` (
	  `id` int(11) unsigned NOT NULL auto_increment,
	  `root_id` int(11) unsigned NOT NULL default '0',
	  `deep` tinyint(4) unsigned NOT NULL default '0',
	  `ordernum` int(11) unsigned NOT NULL default '0',
	  PRIMARY KEY  (`id`),
	  KEY `ordernum` (`ordernum`)
	) TYPE=MyISAM 

	*/
	
	private		$increment = 16; //排序基数每次增加的数量
	protected	$DB;			 //数据库抽象层
	protected	$table;			 //表名字

	/*
	 * 构造函数 
	 *
	 * @param object $DB 数据库抽像层
	 */
	public function __construct($DB, $table)
	{
		$this->DB		 = $DB;
		$this->table	 = $table;
	}
	
	/*
	 * 添加节点 
	 *
	 * @param parentId 父节点
	 * @param data 数据
	 * @return int
	 */
	public function add($parentId, $data, $position="last")
	{
		if(empty($parentId))
		{
			return $this->addTopNode($data, $position);
		}
		else
		{			
			return $this->addChild($data, $parentId, $position);
		}
	}

	/*
	 * 添加顶级节点
	 *
	 * @param $data Array 保存节点数据的数组
	 * @param $type String 可选值为('first','last')添加节点的类型
	 * @return int|flase 成功时返回节点ID，失败返回0
	 */
	public function addTopNode($data, $type="last")
	{
		if($type == "first")
		{
			//后推所有节点
			$this->push($this->increment, 0);
			$ordernum = 0;			
		}
		else
		{
			$sql = "SELECT MAX(ordernum) AS maxNum FROM {$this->table}";
			$st  = $this->DB->query($sql);
			$rec = $st->fetch();
			if($rec["maxNum"] == '')
				$ordernum = 0;
			else
				$ordernum = $rec["maxNum"] + $this->increment;
		}

		$deep	  = 0;
		$root_id	  = 0;

		$sql = "INSERT INTO {$this->table} 
				VALUES(null, $root_id, $deep, $ordernum";

		if(!empty($data))
		{
			foreach($data as &$v){
				$v = $this->DB->quote($v);
			}
			$sql .= ", ". join(",", $data);
		}
		$sql .= ")";
		$this->DB->exec($sql);
		return $this->DB->lastInsertId();

	}

	/** 
	 * 添加子节点
	 *
	 * @param Array $data 保存节点数据的数组
	 * @param int $id 父结点的ID
	 * @param string $type 可选值为('first','last')添加节点的类型
	 * @return 成功时返回节点ID，失败返回0
	 */
	public function addChild($data, $id, $type="last")
	{
		
		//取得父结点信息
		$parentNode = $this->getNode($id);		
		
		$neigh		= $this->getNeighbour($parentNode, $type);
		$prevNode	= $neigh['prev'];
		$nextNode	= $neigh['next'];

		if(!$nextNode)	//下一个结点不存在
		{
			$ordernum = $prevNode['ordernum'] + $this->increment;
		}
		else
		{
			//取得差值
			$ordernum = $this->epenthesis($nextNode["ordernum"], $prevNode["ordernum"]);
		}
		
		$root_id = $parentNode["root_id"] == 0 ? $parentNode["id"] : $parentNode["root_id"];
		$deep   = $parentNode["deep"] + 1;

		$sql = "INSERT INTO {$this->table} 
				VALUES(null, $root_id, $deep, $ordernum";

		if(!empty($data))
		{
			foreach($data as &$v){
				$v = $this->DB->quote($v);
			}
			$sql .= ", ". join(",", $data);
		}
		$sql .= ")";

		$this->DB->exec($sql);
		return $this->DB->lastInsertId();

	}

	/**
	 * 计算$a,$b的差值，如果小于2则动态调节后面节点的排序基数
	 *
	 * @param $a, $b 两节点的排序基数
	 * @return 两个排序基数的差值
	 */	
	private function epenthesis($a, $b)
	{
		if($a < $b)
		{
			list($a, $b) = array($b, $a);
		}	

		if($a - $b < 2)
		{
			$this->push($this->increment, $a);
			$a += $this->increment;
		}

		return floor(($a + $b) / 2);
	}
	
	/**
	 * 向后推进节点，增加步长
	 *
	 * @param int $step 推进的步长
	 * @param int $ordernum 排序字段值
	 */
	private function push($step, $ordernum)
	{
		$sql = "UPDATE {$this->table}
					SET ordernum=ordernum+$step
					WHERE ordernum >= $ordernum";
		return $this->DB->exec($sql);
	}

	/**
	 * 根据节点的深度格式化文本的显示层次
	 *
	 * @param string $text 文本
	 * @param int $deep 节点的深度
	 * @param string $img 是否使用小图标
	 * @return 格式化后的文本
	 */
	public function setDeep($text, $deep, $img=null)
	{
		if($img != null)
		{
			$text = $img . $text;
		}
		if($deep == 0)
			return $text;
		$prefix = str_repeat("&nbsp;", $deep*4);

		return $prefix . $text;
	}

	/**
	 * 删除节点及其子节点
	 *
	 * @param int $id 节点id
	 * @return 成功时返回删除节点的个数，失败返回0
	 */
	public function removeNode($id)
	{
		$node	= $this->getNode($id);

		$root_id = $node["root_id"] == 0 ? $node["id"] : $node["root_id"];
		$ordernum = $node["ordernum"];
		$sql = "SELECT id, deep FROM {$this->table}
				WHERE root_id=$root_id AND ordernum > $ordernum
				ORDER BY ordernum ";

		$st = $this->DB->query($sql);

		$res = array();		//总记录集
		while($rec = $st->fetch())
		{
			if($rec['deep'] <= $node['deep'])
			{
				break;
			}
			$res[] = $rec['id'];
		}
		//将父节点加入
		array_push($res, $id);
		$sql = "DELETE FROM {$this->table} WHERE id IN(" . join(",", $res) . ")";			
		return $this->DB->exec($sql);
	}

	/**
	 * 取得指定结点的所有子节点
	 *
	 * @param int $id 节点id 为0时列出整个树
	 * @param int $level 列出的层数，默认为所有
	 * @return 成功时返回数据数组，失败返回0
	 */
	public function listTree($id=0, $level=0)
	{
		if($level > 0)
		{
			return $this->listLevelTree($id, $level);
		}

		if($id == 0)	//列出整个树
		{
			$sql = "SELECT * FROM {$this->table}
					ORDER BY ordernum";
			$st = $this->DB->query($sql);
			return $st->fetchAll();
		}
		//列出部分树
		$node = $this->getNode($id);
		if($node['root_id'] == 0)	//为顶层结点
		{
			$sql = "SELECT * FROM {$this->table}
					WHERE root_id=$id 
					ORDER BY ordernum";
			$st = $this->DB->query($sql);
			return $st->fetchAll();
		}
		else
		{
			$ordernum = $node["ordernum"];
			$root_id   = $node['root_id'];
			$sql = "SELECT * FROM {$this->table}
					WHERE root_id=$root_id AND ordernum > $ordernum
					ORDER BY ordernum ";

			$st = $this->DB->query($sql);

			$res = array();		//总记录集
			while($rec = $st->fetch())
			{
				if($rec['deep'] <= $node['deep'])	break;
				$res[] = $rec;
			}
			return $res;
		}		
	}

	/**
	 * 取得指定结点的所有子节点
	 *
	 * @param int $id 节点id 为0时列出整个树
	 * @param int $level 列出子数的层数，默认为所有
	 * @return 成功时返回数据数组，失败返回0
	 */
	private function listLevelTree($id=0, $level)
	{

		$res = array();
		if($id == 0)	//列出整个树
		{
			$sql = "SELECT * FROM {$this->table}
					ORDER BY ordernum";
			
			$st  = $this->DB->query($sql);
			while($rec = $st->fetch())
			{
				if($rec['deep'] < $level)
				{					
					$res[] = $rec;
				}
			}
			return $res;
		}		
		else
		{
			//列出部分树
			$node = $this->getNode($id);
			$rootDeep = $node['deep'];
			
			if($node['root_id'] == 0)	//为顶层结点
			{
				$sql = "SELECT * FROM {$this->table}
						WHERE root_id=$id 
						ORDER BY ordernum";
				$st  = $this->DB->query($sql);
				while($rec = $st->fetch())
				{
					if($rec['deep'] <= $rootDeep + $level)
					{					
						$res[] = $rec;
					}
				}
				return $res;
			}
			else
			{
				$ordernum = $node["ordernum"];
				$root_id   = $node['root_id'];

				$res = array();

				$sql = "SELECT * FROM {$this->table}
						WHERE root_id=$root_id AND ordernum > $ordernum
						ORDER BY ordernum ";
				$st = $this->DB->query($sql);

				while($rec = $st->fetch())
				{
					if($rec['deep'] <= $rootDeep)
					{
						return $res;
					}
					if($rec['deep'] <= $rootDeep + $level)
					{					
						$res[] = $rec;
					}
				}
				//如果记录集中没有结点，返回记录集
				return $res;
			}
		}
	}

	/**
	 * 显示树
	 */		
	public function viewTree()
	{
		$tree = $this->listTree();
		echo "<table border=0>";
		foreach($tree as $node)
		{
			echo "<tr><td>". $this->setDeep($node['id'], $node['deep']) ."</td></tr>";
		}
		echo "</table>";
	}

	/**
	 * 修改指定节点的数据
	 *
	 * @param int $id 节点id
	 * @param Array $data 节点数据数组
	 * @return 成功时返回1，失败返回0
	 */
	public function setNode($id, $data)
	{
		$expr = "";
		foreach($data as $k => $v)
		{
			$expr .= "$k='$v',";
		}
		$expr = substr($expr, 0, strlen($expr) - 1);

		$sql = "UPDATE {$this->table} 
				SET $expr
				WHERE id=$id";

		return $this->DB->exec($sql) >= 0;
	}

	/**
	 * 取得指定节点的数据
	 *
	 * @param int $id 节点id
	 * @return 成功时返回节点数据数组，失败返回0
	 */
	public function getNode($id)
	{
		$sql = "SELECT * FROM {$this->table}
				WHERE id = $id";

		$st  = $this->DB->query($sql);
		return $st->fetch();
	}

	/**
	 * 取得要插入子节点的前后邻居
	 *
	 * @param int|array $id结点的ID 或结点数组
	 * @param string $type first为从该节点开始取两个节点, last为从该节点最后一个子节点起取二个节点
	 * @return 成功时返回节点数组，失败返回0
	 */
	private function getNeighbour($id, $type='last')
	{
		$parent = is_array($id) ? $id : $this->getNode($id);
		
		$sql = "SELECT * FROM {$this->table}
				WHERE ordernum > {$parent['ordernum']}
				ORDER BY ordernum";

		$st  = $this->DB->query($sql);
		if($type == 'first')
		{
			$neigh = array("prev" => $parent, "next" => $st->fetch());
		}
		if($type == 'last')
		{
			$neigh = array();
			while($rec = $st->fetch())
			{
				//找到下一组节点起始位置
				if($rec['deep'] <= $parent['deep'] && !array_key_exists('next', $neigh))
				{
					$neigh['next'] = $rec;
					break;
				}
				$neigh['prev'] = $rec;
			}
		}

		//如前节点不存在则父节点为前节点
		if(!array_key_exists('prev', $neigh))
		{
			$neigh['prev'] = $parent;
		}
		//如后节点不存在则设为空
		if(!array_key_exists('next', $neigh))
		{
			$neigh['next'] = "";
		}
		return $neigh;

	}

	/**
	 * 取得指定节点的所有祖先节点
	 *
	 * @param int $id 节点ID
	 * @param boolean $withme 是否包含当前结点
	 * @return 成功时返回节点数组，失败返回0
	 */
	public function getAncestor($id, $withme=true)
	{
		$node = $this->getNode($id);
		$sql = "SELECT * FROM {$this->table}
				WHERE (root_id={$node['root_id']} OR id={$node['root_id']}) AND deep<{$node['deep']} AND ordernum<{$node['ordernum']}
				ORDER BY ordernum DESC";

		$st  = $this->DB->query($sql);
		$res = $withme ? array($node) : array();			//记录总集
		$prev= null;			//前一条记录
		$deep= $node['deep']-1;	//比较用的深度计数
		
		while($rec = $st->fetch())
		{
			if($rec['deep'] == $deep)
			{
				$res[] = $rec;
				$deep--;
			}
		}
		//print_r($res);
		return array_reverse($res);
	}

	/*
	 * 取得父结点信息 
	 *
	 * @param int $id 节点id
	 * @return array 父节点信息
	 */
	public function getParent($id)
	{
		$node = $this->getNode($id);
		$sql = "SELECT * FROM {$this->table}
				WHERE (root_id={$node['root_id']} OR id={$node['root_id']}) AND deep<{$node['deep']} AND ordernum<{$node['ordernum']}
				ORDER BY ordernum DESC
				LIMIT 1";
		$st  = $this->DB->query($sql);
		return $st->fetch();
	}

	public function moveUp($id)
	{
		
	}

	public function moveDown()
	{
		
	}
}
?>