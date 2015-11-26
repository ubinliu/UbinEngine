<?php
/**
 *       Filename:  index.php
 *    Description:  demo entrypoint
 *         Author:  liuyoubin@ubinliu.com
 *        Created:  2015-11-25 18:26:51
 */

define("ROOT_PATH", dirname(__FILE__));
define("CONTROLLER_PATH", ROOT_PATH . "/controllers/");
define("LIB_PAHT", ROOT_PATH . "/libs/");
define("VIEW_PATH", ROOT_PATH . "/views/");
define("LOG_PATH", ROOT_PATH . "/logs/");

require_once("UbinEngine.php");

UbinEngine::AutoLoad(CONTROLLER_PATH);
UbinEngine::AutoLoad(VIEW_PATH);
UbinEngine::AutoLoad(LIB_PAHT);
UbinEngine::AutoHandleErrors(true);

$routes = array(
	"^\/home" => array("ControllerDemo", "home"),
	"^\/json" => array("ControllerDemo", "json"),
	"^\/html" => array("ControllerDemo", "html"),
	"^\/ex" => array("ControllerDemo", "exception"),
	"^\/async" => array("ControllerDemo", "wait"),
);

UbinEngine::LoadRoute($routes);

function after(){
	file_put_contents(LOG_PATH."request.log", date("Y-m-d H:i:s") . " NOTICE request end\n", FILE_APPEND);
}

function before(){
	file_put_contents(LOG_PATH."request.log", date("Y-m-d H:i:s") . " TRACE request start\n", FILE_APPEND);
}

UbinEngine::AfterRequest("after");
UbinEngine::BeforeRequest("before");

UbinEngine::Run();
