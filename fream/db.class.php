<?php
class Mysql{
	private static $_instance = null;
	private $db_name;
	private $password;
	private $usrname;
	private $host;
	private $connection = null;
	/* 以下是数据库的链接 */



//链接数据库的参数以及保存实例的变量

	private function __construct($host, $usrname, $password ,$db_name){
		$this->db_name = $db_name;
		$this->usrname = $usrname;
		$this->password = $password; 
		$this->host = $host;
		$this->conn();
	}
//构造函数初始化变量

	private function conn(){
		$dsn = "mysql:host=$this->host;dbname=$this->db_name;charset=utf8";
		if(!$this->connection = new PDO($dsn, $this->usrname, $this->password)){
			trigger_error("数据库链接有误");
		}
	}
//数据库链接的方法在构造函数中调用

	public function __clone(){
		trigger_error("clone is not allow");
	}
//防止克隆的方法

	public static function get_instance($host, $usrname, $password ,$db_name){
		if(!(self::$_instance instanceof self)){
			self::$_instance = new self($host, $usrname, $password ,$db_name);
		}else{
			trigger_error("数据库已链接");
		}
		return self::$_instance;
	}

	/* 以下是增删改查的方法 */
	public function _exec($sql){
		$res = $this->connection->prepare($sql);
		return $res->execute();
	}

	public function select_by_sql($sql){
		$res = $this->connection->prepare($sql);
		$lalala = $res->execute();
		$result = $this->fich_all($res);
		return $result;
	}
//查询方法

	public function add_by_sql($sql){
		if($this->connection->exec($sql)){
			return 1;
		}else{
			return 0;
		}
	}
//添加方法

	public function delete_by_sql($sql){
		if($this->connection->exec($sql)){
			return 1;
		}else{
			return 0;
		}
	}
//删除方法

	public function update_by_sql($sql){
		$res = $this->connection->prepare($sql);
		return $res->execute();
	}
//更改方法

	private function fich_all($res){
		return $res->fetchAll(PDO::FETCH_ASSOC);
	}
//分解查询后的数组方法

	public function startsw(){
		$this->connection->beginTransaction();
	}
	public function stopsw(){
		$this->connection->commit();
	}

}
$conn = Mysql::get_instance('localhost', 'root', '', 'test');
