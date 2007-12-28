<?php
/**
 * summary
 *
 * @author     ice_berg16(寻梦的稻草人)
 * @copyright  2004-2006 ice_berg16@163.com
 * @version    $Id: ControllerTemplate.php 192 2007-05-22 05:43:00Z icesyc $
 */

class C_%1$s extends Controller
{
	public function __construct()
	{
		parent::__construct();
	}
	
	//列表
	public function index()
	{
		$%1$s = Plite::modelFactory("%1$s");
		$%1$sList = $%1$s->findAll();
		$this->set("%1$sList", $%1$sList);
	}

	//保存
	public function save()
	{
		$%1$s = Plite::modelFactory("%1$s");
		if($this->isPost())
		{
			$%1$s->save($_POST);
		}
		else
		{
			$id =  isset($_GET['id']) ? $_GET['id'] : null;
			$this->set($%1$s->get($id));
		}
	}

	//删除
	public function delete()
	{
		$this->autoRender = false;
		$%1$s = Plite::modelFactory("%1$s");
		if(empty($_GET['id'])) throw new Exception("未指定id");
		$%1$s->delete($_GET['id']);
		$this->redirect($_SERVER['HTTP_REFERER']);
	}
}
?>