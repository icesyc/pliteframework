<?php
/**
 * Plite 
 *
 * 提供共享对象的存储与访问
 *
 * @package    Plite
 * @author     ice_berg16(寻梦的稻草人)
 * @copyright  2004-2006 ice_berg16@163.com
 * @version    $Id$
 */
class Plite
{
	//共享对象集合
	static private $collection  = array();
	static private $libs		= array();
	static private $models		= array();

	//禁止访问构造函数
	private function __construct() {}

	//注册一个共享对象
	public static function set($key, $obj)
	{
		if(self::exists($key))
			throw new Exception("Plite: 名称为 <span class='red'>$key</span> 的对象已经被注册.");
		self::$collection[$key] = $obj;
	}

	/**
	 * 取出共享对象
	 * 为一个参数时返回该共享对象
	 * 多个参数时返回一个共享对象数组
	*/
	public static function get()
	{
		if(func_num_args() == 1)
		{
			$key = func_get_arg(0);
			if(!self::exists($key))
				throw new Exception("Plite: 名称为 <span class='red'>$key</span> 的对象不存在.");
			return self::$collection[$key];
		}
		$arr = func_get_args();
		$col = array();
		foreach($arr as $key)
		{
			if(!self::exists($key))
			{
				$e = "Plite: 名称为 <span class='red'>$key</span> 的对象不存在.";
				break;
			}
			array_push($col, self::$collection[$key]);
		}
		if(!empty($e))
			throw new Exception($e);
		return $col;
	}

	//名称是否被注册
	public static function exists($key)
	{
		return array_key_exists($key, self::$collection);
	}

	//删除一个共享对象
	public static function remove($key)
	{
		if(!self::exists($key))
			throw new Exception("Plite: unset对象时出错，名称为 <span class='red'>$key</span> 的对象不存在.");
		unset(self::$collection[$key]);
	}

	//载入类
	public static function load($class, $dir=null)
	{
		$path = str_replace(".", "/", $class) . ".php";
		
		if($dir)
		{
			if(!is_dir($dir))
				throw new Exception("Plite: 指定的目录 <span class='red'>$dir</span> 不存在");
			$path = $dir . "/" . $path;
		}
		
		if(self::isReadable($path))
		{			
			include_once($path);
		}
		else
		{
			throw new Exception("Plite: 要载入的文件 <span class='red'>$path</span> 不存在");
		}
	}

	/*
	 * 载入类库并返回一个实例化好的对像
	 *
	 * @param string $name 类库名称
	 * @param array $param 参数
	 * @return object 类库对象
	 */
	public static function libFactory($name, $param=array())
	{
	
		if(!array_key_exists($name, self::$libs))
		{
			self::load("Plite/Lib/".$name);
			//通过反射实现变参数的class实例化
			$rc = new ReflectionClass($name);
			self::$libs[$name] = call_user_func_array(array($rc, 'newInstance'), $param);
		}
		return self::$libs[$name];
	}

	/*
	 * 载入model类并返回一个实例化好的对像
	 *
	 * @param string, string ...
	 * @return 为一个参数时返回该对象，多个参数时返回对象数组
	 */
	public static function modelFactory()
	{
		$col = array();
		foreach( func_get_args() as $model )
		{
			if(!array_key_exists($model, self::$models))
			{
				$model = Config::get("modelPrefix") . $model;
				$path = Config::get("modelPath") . "/" . $model . ".php";
				if(!file_exists($path))
					throw new Exception(sprintf("系统加载 <span class='red'>%s</span> 时失败，未找到Model文件.", $path));
				require($path);
				if(!class_exists($model))
					throw new Exception(sprintf("在 <span class='red'>%s</span> 中找不到 <span class='red'>%s</span> 类.", $path, $model));
				self::$models[$model] = new $model();
			}
			array_push($col, self::$models[$model]);
		}
		if(count($col) == 1)
			return $col[0];
		else
			return $col;
	}

    /**
     * 检查文件是否可读，该函数使用include_path，参考Zend.php
	 * 
     *
     * @param string $file
     * @return boolean
     */
    static public function isReadable($file)
    {
		$fh = @fopen($file, 'r', true);
		if($fh)
		{
			fclose($fh);
			return true;
		}
		return false;
    }
}
?>