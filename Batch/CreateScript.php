<?php
/**
 * CreateScript
 *
 * 自动创建model和controller的脚本
 *
 * @package    Plite.Batch
 * @author     ice_berg16(寻梦的稻草人)
 * @copyright  2004-2006 ice_berg16@163.com
 * @version    $Id: CreateScript.php 199 2007-09-08 01:17:05Z icesyc $
 */

if(!isset($argc) || $argc < 2)
	die('Usage: cs path/to/your/application');
$path = $argv[1]; 
if(substr($path,strlen($path)-1) != DIRECTORY_SEPARATOR)
	$path .= DIRECTORY_SEPARATOR;

if(!file_exists($path."config.php"))
	exit("can not find 'config.php' in the path '$path'.");

//加载配置文件
require_once("../Init.php");

Config::import($path."config.php");

if(!is_dir(Config::get("modelPath"))){
	mkdir(Config::get("modelPath"));
}
if(!is_dir(Config::get("controllerPath")))
	mkdir(Config::get("controllerPath"));

try
{
	Plite::load("Plite.Db.DataSource");
	$DB	= DataSource::getInstance(Config::get("dsn"));
	$tables = $DB->tableList();
}
catch(Exception $e)
{
	exit("error: ".$e->getMessage());
}
//循环取得每个table的字段名
foreach($tables as $t)
{
	//创建model文件
	$table = substr($t,strlen(Config::get("tablePrefix")));
	$class = preg_replace("/_(.)/e", "strtoupper('\\1')", $table);
	$file = sprintf(Config::get("modelPath")."/M_%s.php", $class);
	if(!file_exists($file))
	{
		$content = file_get_contents(PLITE_ROOT."/Model/ModelTemplate.php");
		$content = sprintf($content, $class, $table);
		if(file_put_contents($file, $content))
			echo "$file created.\n";
		else
			exit("$file create failed.");
	}
	//创建controller文件
	$file = sprintf(Config::get("controllerPath")."/C_%s.php", $class);
	if(!file_exists($file))
	{
		$content = file_get_contents(PLITE_ROOT."/Controller/ControllerTemplate.php");
		$content = sprintf($content, $class);
		if(file_put_contents($file, $content))
			echo "$file created.\n";
		else
			exit("$file create failed.");
	}
}
?>