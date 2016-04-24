<?php
	date_default_timezone_set('PRC');
	class parser {
		private $file_arr;    //需要编译的模板文件列表
		private $target_file;	//目标文件
		private $file_content; 
		private $flag = false;
		private $exp_js = '/<script[\s]+?src=[\'\"]([\s\S]+?)[\'\"]>[\s\S]*?<\/script>/';
		function __construct ($file_arr, $target_file, $b_js = false) {//是否将外链js绑定进页面
			$this->target_file = $target_file;
			if (!Cache_file::check_cache($file_arr,$target_file)) {  //检查缓存
				$this->file_arr = $file_arr;
				$this->join();	//凭借模板文件
				if($b_js){	
					$this->join_js();  //拼接js文件
				}
				$this->replace();
				$this->flag = true;
			}
		}
		private function join () {	//该方法用于拼接文件模板文件
			foreach ($this->file_arr as $value) {
				$value = file_get_contents($value);
				$this->file_content .= $value;
			}
		}
		private function join_js () {
			preg_match_all($this->exp_js, $this->file_content,$js_arr);	//找到所有的jscript标签
			$GLOBALS['n_arr'] = array();
			for ($i = 0,$len = count($js_arr[1]); $i < $len; $i++) {
				$temp = $js_arr[1][$i];
				$GLOBALS['n_arr'][$js_arr[1][$i]] = '<script>'.file_get_contents($temp).'</script>';//获取具体js文件内容
			}
			$this->file_content = preg_replace_callback(	//将标签具体替换为文件内容
				$this->exp_js,
				function ($match) {
					return $GLOBALS['n_arr'][$match[1]];
				},
				$this->file_content
			);
			unset($GLOBALS['n_arr']);
		}
		private function replace () {
			$temp = new Mould($this->file_content);  	//进行模版中自定义标签的编译替换
			if(!file_exists('cache')){
				mkdir('app/home/cache', 0777);
			}
			file_put_contents('app/home/cache/'.$this->target_file,$temp->replace());
		}
		public function req_file () {//需要修改
			// if ($this->flag) {
			// 	echo '编译';
			// 	ob_start();
			// 	require 'cache/'.$this->target_file;
			// 	$temp = ob_get_contents();
			// 	ob_clean();
			// 	file_put_contents('cache/'.$this->target_file,$temp);
			// }
			return 'app/home/cache/'.$this->target_file;
		}
		function __destruct () {
			// unlink("$this->target_file");
		}
	}
	class Mould {
		private $variable = '/(?<!\\\\)\{(\$[^\s]+?)\}/';
		private $foreach_sec = '/<each\s+([^\s]+)\s+in\s+([^\s]+)>/';
		private $for_sec = '/[^\s=,;<>=0-9][^\,s=;<>=]*/';
		private $temp_for_sec = '/<each[\s\S]+?>/';
		private $for_sec_back = '/<\/each>/';
		private $file_content;
		function __construct ($file_content) {
			$this->file_content = $file_content;
		}
		public function replace () {
			$this->match_var();
			$this->match_foreach();
			$this->match_for();
			return $this->file_content;
		} 
		function match_var () {
			$this->file_content = preg_replace_callback(
				$this->variable,
				function ($match) {
					/*
					return '<?php echo ("\'".'.$match[1].'."\'"); ?>';
					*/
					return '<?php echo ('.$match[1].'); ?>';
				},
				$this->file_content
			);
		}
		function match_foreach () {
			$this->file_content = preg_replace_callback(
				$this->foreach_sec,
				function ($match) {
					return '<?php foreach('.'$'.$match[2].' as '.'$'.$match[1].' => $value)  {  ?>';
				},
				$this->file_content
			);
			$this->file_content = preg_replace_callback(
				$this->for_sec_back,
				function ($match) {
					return '<?php }  ?>';
				},
				$this->file_content
			);
		}
		function match_for () {
			preg_match_all(
				$this->temp_for_sec
				,$this->file_content
				,$match_arr
			);
			for($i = 0,$len = count($match_arr[0]);$i < $len;$i++){
				$strk[] = preg_replace_callback(
					$this->for_sec,
					function ($march) {
						return '$'.$march[0];
					},
					substr($match_arr[0][$i],5,strlen($match_arr[0][$i]) - 6)
				);
				$strk[$i] = '<?php for('.$strk[$i].') {?>';
			}
			if($i == 0){
				return;
			}
			$str_arr = preg_split(
				$this->temp_for_sec,
				$this->file_content
			);
			$str = '';
			for($i = 0,$len = count($strk);$i < $len;$i++){
				$str .= $str_arr[$i];
				$str .= $strk[$i];
			}
			$str .= $str_arr[$i];
			$this->file_content = $str;
		}
	} 
	class Cache_file {
		static private $file_arr;
		static private $target_file;
		static private $cache_arr;
		static public function check_cache ($file_arr,$target_file) {
			$flag01 = false;
			$flag02 = true;
			self::$file_arr = $file_arr;
			self::$target_file = $target_file;
			if(!file_exists('app/home/cache')){  //如果不存在就创建文件夹
				mkdir('app/home/cache', 0777);
			}
			if(!file_exists('app/home/cache/cache.json')){ //创建缓存说明文件
				$arr = array(
					'target_files'=>array($target_file),
					'model_files'=>array()
				);
				foreach ($file_arr as $value) {       //为每一个缓存文件添加说明
					$arr['model_files'][] = array(
						'name'=>$value,
						'last_change'=>self::get_change_time($value)
					);
				}
				$fopen = fopen('app/home/cache/cache.json','wb ');
				fwrite($fopen,json_encode($arr));			//将说明写入josn文件
				fclose($fopen);
			}
			self::$cache_arr = json_decode(file_get_contents('app/home/cache/cache.json'),true);//读取缓存记录文件，获取缓存文件数组的相关信息
			if(!(array_search(self::$target_file, self::$cache_arr['target_files']) > -1)){
				//如果缓存记录中没有该文件就handle
				self::handle_cache(array(self::$target_file),'target_files');
				$flag02 = false;
			}
			if (!file_exists('app/home/cache/'.$target_file)) {
				$flag02 = false;
			}
			$temp_arr = array();
			for ($i = count(self::$file_arr) - 1;$i > -1;$i--) {
				for ($j = count(self::$cache_arr['model_files']) - 1;$j > -1;$j--) {
					if ($file_arr[$i] == self::$cache_arr['model_files'][$j]['name']) {
						if (self::get_change_time(self::$file_arr[$i]) == self::$cache_arr['model_files'][$j]['last_change']) {
							unset(self::$file_arr[$i]);
						}
					}
				}
			}
			if (count(self::$file_arr) == 0) {
				$flag01 = true;
			}
			self::handle_cache(self::$file_arr,'model_files');
			file_put_contents('app/home/cache/cache.json', json_encode(self::$cache_arr));
			return $flag01&&$flag02;
		}
		static private function handle_cache ($file_arr,$target) {
			foreach ($file_arr as $i => $value) {
				$flag = false;
				if($target == 'target_files'){
					self::$cache_arr['target_files'][] = $file_arr[$i];
				}else{
					for ($j = count(self::$cache_arr['model_files']) - 1;$j > -1;$j--) {
						if ($file_arr[$i] == self::$cache_arr['model_files'][$j]['name']) {
							self::$cache_arr['model_files'][$j]['last_change'] = self::get_change_time($file_arr[$i]);
							$flag = true;
							break;
						}
					}
					if (!$flag) {
						self::$cache_arr['model_files'][] = array('name'=>$file_arr[$i],'last_change'=>self::get_change_time($file_arr[$i]));
					}
				}
			}
			
		}
		static private function get_change_time ($file) {
			$timer = filemtime($file);
			return date("Y-m-d H:i:s",$timer);
		}
	}
	