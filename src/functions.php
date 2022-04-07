<?php

//调试打印php变量
if(!function_exists('vd')){
	function vd() {
		static $i = 1;
		if(func_num_args()>0){
			foreach(func_get_args() as $arg){
				$out[] = var_export($arg, true);
			}
			$tpl = str_repeat("%s",func_num_args());
			if(PHP_SAPI == 'cli'){
				echo vsprintf("%s# $tpl\n", array_merge([$i++], $out));
			}else{
				echo vsprintf("<pre><b>%s#</b>$tpl</pre>", array_merge([$i++], $out));
			}
		}else{
			die('call function dump without any params');
		}
	}
}

