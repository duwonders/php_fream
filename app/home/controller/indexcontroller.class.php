<?php
class indexController extends conF{
	public function index(){
		$this->display();
	}
	public function lalala(){
		$db = $this->D();
		var_dump($db->select_by_sql('select * from user'));
	}
}