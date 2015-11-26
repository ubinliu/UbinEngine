<?php
/**
 *       Filename:  ControllerDemo.php
 *    Description:  demo controller
 *         Author:  liuyoubin@ubinliu.com
 *        Created:  2015-11-25 18:26:51
 */
class ControllerDemo {
	
	public function home(){
		UbinEngine::Redirect("http://www.ubinliu.com");
	}
	
	public function html(){
		$data = array(
			"username" => "ubin",
		);
		UbinEngine::RenderHtml(VIEW_PATH."hello", $data);
	}
	
	public function json(){
		$data = array(
			"request_id" => 123456789
		);
		UbinEngine::RenderJson(json_encode($data));
	}
	
	public function exception() {
		throw new Exception("coredump");
	}
	
	public function wait(){
		$data = array(
				"request_id" => 123456789
		);
		file_put_contents(LOG_PATH."request.log",
				date("Y-m-d H:i:s") . " wait before response\n", FILE_APPEND);
		UbinEngine::RenderJson(json_encode($data), true);
		
		sleep(10);
		
		file_put_contents(LOG_PATH."request.log",
				date("Y-m-d H:i:s") . " wait after response\n", FILE_APPEND);
	}
} 