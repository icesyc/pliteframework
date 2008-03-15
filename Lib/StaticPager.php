<?php
/**
 * StaticPager 
 *
 * 静态页面分页类，用于生成连续的静态页面导航条
 *
 * @package    Plite.Lib
 * @author     ice_berg16(寻梦的稻草人)
 * @copyright  2004-2006 ice_berg16@163.com
 * @version    $Id$
 */

/**
 * 包含父类
 */
require_once("Plite/Lib/Pager.php");

class StaticPager extends Pager
{
	public function __construct($pageParam=null)
	{
		if(!isset($pageParam['filePrefix']))
			$pageParam['filePrefix'] = "list_";
		if(!isset($pageParam['fileSuffix']))
			$pageParam['fileSuffix'] = ".htm";
		parent::__construct($pageParam);
	}

	/**
	 * 生成分页列表
	 *
	 * @return string 生成的HTML代码
	 */
	function makePage()
	{
		$currentPage= $this->getParam('currentPage');
		$recordCount= $this->getParam('recordCount');
		$pageCount	= max(1, ceil($recordCount / $this->getParam('pageSize')));
		$linkNumber = $this->getParam('linkNumber');
		$pre  = $this->getParam("filePrefix");
		$ext  = $this->getParam("fileSuffix");
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
				$page[] = sprintf('<a href="%s">%d</a>', $pre.$i.$ext, $i );
		}

		$linktpl = '<a href="%s">\\1</a>';
		$deflink = '<a href="#">\\1</a>';
		$prev = $next = $prevnav = $nextnav = $deflink;
		$first = sprintf($linktpl, $pre."1".$ext );
		$last  = sprintf($linktpl, $pre.$pageCount.$ext );
		//非第一页，生成上一页按钮
		if( $currentPage > 1 )
			$prev = sprintf($linktpl, $pre.($currentPage-1).$ext );
		//非最后一页，生成下一页按钮
		if( $currentPage < $pageCount )
			$next = sprintf('<a href="%s">\\1</a>', $pre.($currentPage+1).$ext );
		//非第一页导航
		if( $start > $linkNumber )
			$prevnav = sprintf('<a href="%s">\\1</a>', $pre.($start-$linkNumber).$ext );
		//非最后一页导航
		if( $start + $linkNumber < $pageCount )
			$nextnav = sprintf('<a href="%s">\\1</a>', $pre.($start+$linkNumber).$ext );
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
		return  preg_replace( $reg, $rpt, $preFormat);
	}
}

?>