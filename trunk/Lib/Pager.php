<?php
/**
 * Pager��
 *
 * �ֲ�������������
 *
 * @package    Plite.Lib
 * @author     ice_berg16(Ѱ�εĵ�����)
 * @copyright  2004-2006 ice_berg16@163.com
 * @version    $Id: Pager.php 211 2007-12-07 07:28:59Z icesyc $
 */

class Pager
{
	//��ҳ����
	private $pageParam = array();
	//��ҳ��ʽ
	private $formatStr = "Pages: [current]/[total] �ܼ�¼�� [recordCount] [prev]��һҳ[/prev] [prevnav]ǰ10ҳ[/prevnav] [nav]  [nextnav]��10ҳ[/nextnav] [next]��һҳ[/next]";

	//���캯��
	public function __construct($pageParam=null)
	{
		if($pageParam)
			$this->setParam($pageParam);
		if(!isset($pageParam['linkNumber']))
			$this->pageParam['linkNumber'] = 10;
	}
	
	/*
	 * ���÷�ҳ��ʽ 
	 *
	 * @param string | array Ϊstringʱ$valueΪֵ��Ϊarrayʱ$valueΪnull
	 * @param mixed $value $keyΪstringʱ��Ч
	 *				currentPage ��ǰҳ
	 *				pageSize	ÿҳ��¼��
	 *				recordCount �ܼ�¼��
	 *				linkNumber	��ʾ1 2 3 4..������ʽʱ����ʾ�ĸ���
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

	//ȡ��һ����ҳ����
	public function getParam($key)
	{
		return $this->pageParam[$key];
	}

	/**
	 * ���ø�ʽ
	 * 
	 * example "Pages: [current]/[total] �ܼ�¼�� [recordCount] [prev]��һҳ[/prev] [prevnav]ǰ10ҳ[/prevnav] [nav]  [nextnav]��10ҳ[/nextnav] [next]��һҳ[/next]";
	 * @param $formatStr string 
	 * 
	 */
	public function setFormat($formatStr)
	{
		$this->formatStr = $formatStr;
	}
	
	//ȡ�÷�ҳ��ʽ
	public function getFormat()
	{
		return $this->formatStr;
	}

	/**
	 * ���ɷ�ҳ������
	 */
	public function makePage()
	{
		$currentPage= $this->getParam('currentPage');
		$recordCount= $this->getParam('recordCount');
		$pageCount	= max(1, ceil($recordCount / $this->getParam('pageSize')));
		$linkNumber = $this->getParam('linkNumber');

		//���ݵ�ǰURL�����µ�URL����
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
		//���ɵ�����
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
		//�ǵ�һҳ��������һҳ��ť
		if( $currentPage > 1 )
			$prev = sprintf($linktpl, $url.($currentPage-1) );
		//�����һҳ��������һҳ��ť
		if( $currentPage < $pageCount )
			$next = sprintf('<a href="%s">\\1</a>', $url.($currentPage+1) );
		//�ǵ�һҳ����
		if( $start > $linkNumber )
			$prevnav = sprintf('<a href="%s">\\1</a>', $url.($start-$linkNumber) );
		//�����һҳ����
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