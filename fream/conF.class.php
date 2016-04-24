<?php
class conF{
	public function display(){
		$className = get_class($this);//获取当前控制器名
		$relName = preg_replace('/Controller/', '', $className);
		$action = debug_backtrace()[1]['function'];
		$viewFilePath = './app/home/view/'.$relName.'/'.$action.'.html';  // 获取html模板的位置
		$test = new parser(array($viewFilePath), $action.'.php', true); //模板引擎生成缓存文件
		require_once('./app/home/cache/'.$action.'.php');
	}

	public function D($host = 'localhost', $user = 'root', $password = '', $db_name='test'){
		try{
			return Mysql::get_instance($host, $user, $password, $db_name);
		}catch(Exception $ex){
			throw new Exception("数据链接字段有错", 1);
		}
	}
}
