<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>数据库操作发生错误</title>
<meta http-equiv="content-type" content="text/html;charset=utf-8"/>
<meta name="Generator" content="EditPlus"/>
<meta name="Author" content="冰山网络工作室"/>
<meta name="Keywords" content="plite,PHP Framework,PHP框架"/>
<style>
body{
	font-family: Verdana;
	font-size:14px;
}
h2{
	border-bottom:2px solid #DDD;
	padding:8px 0;
}
.title{
	margin:4px 0;
	color:#F60;
	font-weight:bold;
}
.message,#trace{
	padding:1em;
	border:solid 1px #000;
	margin:10px 0;
	background:#FFD;
	line-height:150%;
}
.message{
	background:#FFD;
}
#trace{
	background:#E7F7FF;
}
.red{
	color:red;
	font-weight:bold;
}
</style>
</head>
<body>
<div class="notice">
<h2>数据库操作发生错误</h2>
<p><strong>错误代码:</strong>　<span class="red"><?php echo $e->getCode()?></span></p>
<p class="title">[错误信息]</p>
<p class="message"><?php echo $e->getMessage()?></p>
<?php if($e->getSql()) {?>
<p class="title">[SQL语句]</p>
<p class="message"><?php echo $e->getSql()?></p>
<?php }?>
<p class="title">[TRACE]</p>
<p id="trace">
<?php
error_reporting(E_ALL ^ E_NOTICE);
foreach( $e->getTrace() as $k => $r )
{
	printf("#%d %s(%s) ", $k, $r['file'], $r['line']);
	if(isset($r['class']))
		echo $r['class'].$r['type'].$r['function']."(".join(",",$r['args']).")";
	else
		echo $r['function']."(".join(",",$r['args']).")";
	echo "<br/>";
}
?>
</p>
</div>
</body>
</html>
