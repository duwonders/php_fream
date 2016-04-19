<?php
class route{
	private $method = null;			//请求方法
	private $request_config = null;	//请求参数数组
	private $module;    //默认为home模块
	private $controller; //默认为index控制器
	private $aciton; //默认为index方法
	//定义路径常量


	function __construct(){
		$this->get_method();	//获取请求方法，以及参数数组
		$this->get_path();		//获取模块以及控制器，方法名
		$this->get_conf();		//获取action参数数组
		$this->run();
	}
	
	private function get_method(){
		if($_SERVER['REQUEST_METHOD'] == 'GET'){
			$this->method = 'get';
			$this->request_config = $_GET;
		}else if($_SERVER['REQUEST_METHOD'] == "POST"){
			$this->method = 'post';
			$this->request_config = $_POST;
		}else{
			throw new Exception("还没有支持其他请求方法", 1);
		}
	}

	private function get_path(){
		$this->module = array_key_exists('m', $this->request_config) ? $this->request_config['m'] : 'home';
		$this->controller = array_key_exists('c', $this->request_config) ? $this->request_config['c'] : 'index';
		$this->action = array_key_exists('a', $this->request_config) ? $this->request_config['a'] : 'index';
	}

	private function get_conf(){
		// echo $this->module.$this->controller.$this->action;
		unset($this->request_config['m']);   //利用unset删除这个元素
		unset($this->request_config['a']);
		unset($this->request_config['c']);
		//这时的request_conf数组就只剩下action的参数了
	}

	private function run(){
		$path = './app/'."$this->module".'/controller/'.$this->controller.'Controller.class.php';
		if(file_exists($path)){
			require_once($path);
			$controller = $this->controller."Controller";	//拼接controller名字符串
			$action = $this->action;          //凭借方法名字符串
			$tempObj = new $controller();
			try{
				call_user_func_array(array($tempObj, $action), $this->request_config);//将参数数组给调用函数
			}catch(Exception $ex){
				throw new Exception("出现未知错误", 1);
			}
		}else{
			throw new Exception("没有对应控制器", 1);
			
		}
		
	}
	
}
