<?php
/**
 * Html类 
 *
 * 提供一些生成HTML标签的函数
 *
 * @package    Plite.Lib
 * @author     ice_berg16(寻梦的稻草人)
 * @copyright  2004-2006 ice_berg16@163.com
 * @version    $Id$
 */

class Html
{

	public function __construct()
	{		
	}

	/**
	 * 构建下拉框
	 *
	 * @param string $selName select的名称
	 * @param array $source 数据源
	 * @param mixed $default 默认值
	 * @param array $attrArray 属性数组
	 * @param boolean emptyChoose 是否加入请选择
	 */
	public function select($selName, $source, $default=null, $attrArray=null, $emptyChoose=true)
	{
		$html = "<select name=\"$selName\"";
		$attrStr = " ";
		if(!empty($attrArray) && is_array($attrArray))
		{
			foreach($attrArray as $key => $value)
			{
				$attrStr .= "$key=\"$value\" ";
			}
		}
		$html .= $attrStr . ">\n";
		if($emptyChoose)
			$html .= "<option value=''>请选择</option>\n";

		foreach($this->$source as $k => $v)
		{
			if ($k == $default) 
			{				
				$html .= "<option value=\"$k\" selected=\"selected\">$v</option>\n";
			}
			else
			{
				$html .= "<option value=\"$k\">$v</option>\n";
			}
		}
		$html .= "</select>\n";
		return $html;
	}

	/**
	 * 构建单选框
	 *
	 * @param string $radioName radio的名称
	 * @param array $source 数据源
	 * @param mixed $default 默认值
	 * @param array $attrArray 属性数组
	 */
	public function radio( $radioName, $source, $default=null, $attrArray=null )
	{
		$html = "";
		$flag = false;
		$attrStr = " ";
		if(!empty($attrArray) && is_array($attrArray))
		{
			foreach($attrArray as $key => $value)
			{
				$attrStr .= "$key=\"$value\" ";
			}
		}
		foreach($source as $k => $v)
		{
			$id = $radioName . "_" . $k;
			if($k == $default || ($default == null && $flag == false))
			{				
				$html .= "<input type=\"radio\" name=\"$radioName\" value=\"$k\" checked=\"checked\" id=\"$id\""
					   . $attrStr . "/>\n"
					   . "<label for=\"$id\" id=\"label_$id\">$v</label>\n";
				$flag = true;
			}
			else
			{
				$html .= "<input type=\"radio\" name=\"$radioName\" value=\"$k\" id=\"$id\""
					   . $attrStr . "/>\n"
					   . "<label for=\"$id\" id=\"label_$id\">$v</label>\n";
			}			
		}
		return $html;
	}

	/*
	 * 构建复选框
	 *
	 * @param string $radioName radio的名称
	 * @param array $source 数据源
	 * @param mixed $default 默认值
	 * @param array $attrArray 属性数组
	 */
	public function checkbox($checkName, $source, $default=null, $attrArray=null)
	{
		$html = "";
		$attrStr = " ";
		if(!empty($attrArray) && is_array($attrArray))
		{
			foreach($attrArray as $key => $value)
			{
				$attrStr .= "$key=\"$value\" ";
			}
		}
		foreach($source as $k => $v)
		{
			$id = $checkName . "_" . $k;
			if(is_array($default) && in_array($k, $default))
			{
				$html .= "<input type=\"checkbox\" name=\"$checkName\" value=\"$k\" checked=\"checked\" id=\"$id\""
					   . $attrStr . "/>\n"
					   . "<label for=\"$id\" id=\"label_$id\">$v</label>\n";
			}
			else
			{
				$html .= "<input type=\"checkbox\" name=\"$checkName\" value=\"$k\" id=\"$id\""
					   . $attrStr . "/>\n"
					   . "<label for=\"$id\" id=\"label_$id\">$v</label>\n";
			}
		}
		return $html;
	}
	
	/*
	 * html的格式化
	 */
	public function h($str)
	{
		 return nl2br(str_replace(' ', '&nbsp;', htmlspecialchars($str)));
	}
}

?>