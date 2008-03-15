<?php
/**
 * StaticPager 
 *
 * ��̬ҳ���ҳ�࣬�������������ľ�̬ҳ�浼����
 *
 * @package    Plite.Lib
 * @author     ice_berg16(Ѱ�εĵ�����)
 * @copyright  2004-2006 ice_berg16@163.com
 * @version    $Id$
 */

/**
 * ��������
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
	 * ���ɷ�ҳ�б�
	 *
	 * @return string ���ɵ�HTML����
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
		//���ɵ�����
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
		//�ǵ�һҳ��������һҳ��ť
		if( $currentPage > 1 )
			$prev = sprintf($linktpl, $pre.($currentPage-1).$ext );
		//�����һҳ��������һҳ��ť
		if( $currentPage < $pageCount )
			$next = sprintf('<a href="%s">\\1</a>', $pre.($currentPage+1).$ext );
		//�ǵ�һҳ����
		if( $start > $linkNumber )
			$prevnav = sprintf('<a href="%s">\\1</a>', $pre.($start-$linkNumber).$ext );
		//�����һҳ����
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