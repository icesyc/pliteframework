<?php
/**
 * Pager类
 *
 * 分布导航条生成类
 *
 * @package    Plite.Lib
 * @author     ice_berg16(寻梦的稻草人)
 * @copyright  2004-2006 ice_berg16@163.com
 * @version    $Id: Pager.php 211 2007-12-07 07:28:59Z icesyc $
 */

class Pager
{
	//分页参数
	private $pageParam = array();
	//分页格式
	private $formatStr = "Pages: [current]/[total] 总记录数 [recordCount] [prev]上一页[/prev] [prevnav]前10页[/prevnav] [nav]  [nextnav]后10页[/nextnav] [next]下一页[/next]";

	//构造函数
	public function __construct($pageParam=null)
	{
		if($pageParam)
			$this->setParam($pageParam);
		if(!isset($pageParam['linkNumber']))
			$this->pageParam['linkNumber'] = 10;
	}
	
	/*
	 * 设置分页形式 
	 *
	 * @param string | array 为string时$value为值，为array时$value为null
	 * @param mixed $value $key为string时有效
	 *				currentPage 当前页
	 *				pageSize	每页记录数
	 *				recordCount 总记录数
	 *				linkNumber	显示1 2 3 4..这种形式时，显示的个数
	 */
	public function setParam($key, $value=null)
	{
		if(is_array($key))
		{
			$this->pageParam = array_merge($this->pageParam, $key);
		}
		else
		{
			$this->pageParam[$key] = $value;
		}
	}

	//取得一个分页参数
	public function getParam($key)
	{
		return $this->pageParam[$key];
	}

	/**
	 * 设置格式
	 * 
	 * example "Pages: [current]/[total] 总记录数 [recordCount] [prev]上一页[/prev] [prevnav]前10页[/prevnav] [nav]  [nextnav]后10页[/nextnav] [next]下一页[/next]";
	 * @param $formatStr string 
	 * 
	 */
	public function setFormat($formatStr)
	{
		$this->formatStr = $formatStr;
	}
	
	//取得分页格式
	public function getFormat()
	{
		return $this->formatStr;
	}

	/**
	 * 生成分页导航条
	 */
	public function makePage()
	{
		$currentPage= $this->getParam('currentPage');
		$recordCount= $this->getParam('recordCount');
		$pageCount	= max(1, ceil($recordCount / $this->getParam('pageSize')));
		$linkNumber = $this->getParam('linkNumber');

		//根据当前URL生成新的URL链接
		if(empty($_SERVER['QUERY_STRING']))
		{
			$url = $_SERVER['REQUEST_URI'] . "?page=";
		}
		else
		{
			if(isset($_GET['page']))
			{
				$url = preg_replace("|page.+|", "page=", $_SERVER['REQUEST_URI']);
			}
			else
			{
				$url = $_SERVER['REQUEST_URI'] . "&page="; 
			}
		}
		$page = array();
		//生成导航条
		$start = max(1,$currentPage -  $linkNumber / 2);
		$to    = $start + $linkNumber;
		for( $i=$start; $i<$to; $i++ )
		{
			if( $i > $pageCount ) break;
			if( $i == $currentPage )
				$page[] = "<span class='current'>".$currentPage."</span>";
			else
				$page[] = sprintf('<a href="%s">%d</a>', $url.$i, $i );
		}

		$linktpl = '<a href="%s">\\1</a>';
		$deflink = '<a href="#">\\1</a>';
		$prev = $next = $prevnav = $nextnav = $deflink;
		$first = sprintf($linktpl, $url."1" );
		$last  = sprintf($linktpl, $url.$pageCount );
		//非第一页，生成上一页按钮
		if( $currentPage > 1 )
			$prev = sprintf($linktpl, $url.($currentPage-1) );
		//非最后一页，生成下一页按钮
		if( $currentPage < $pageCount )
			$next = sprintf('<a href="%s">\\1</a>', $url.($currentPage+1) );
		//非第一页导航
		if( $start > $linkNumber )
			$prevnav = sprintf('<a href="%s">\\1</a>', $url.($start-$linkNumber) );
		//非最后一页导航
		if( $start + $linkNumber < $pageCount )
			$nextnav = sprintf('<a href="%s">\\1</a>', $url.($start+$linkNumber) );
		$reg = array( "[current]", "[total]", "[recordCount]", "[nav]" );
		$rpt = array( $currentPage, $pageCount, $recordCount, join( " ", $page ) );
		
		$preFormat = str_replace($reg, $rpt, $this->getFormat());

		$reg = array( "#\[prev\](.+)\[\/prev\]#isU", 
					  "#\[next\](.+)\[\/next\]#isU", 
					  "#\[first\](.+)\[\/first\]#isU",
					  "#\[last\](.+)\[\/last\]#isU", 
					  "#\[prevnav\](.+)\[\/prevnav\]#isU", "#\[nextnav\](.+)\[\/nextnav\]#isU", 
					);
		
		$rpt = array( $prev, $next, $first, $last, $prevnav, $nextnav );
		return preg_replace( $reg, $rpt, $preFormat );
	}
}
?>