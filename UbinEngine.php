<?php
/**
 *       Filename:  UbinEngine.php
 *    Description:  Engine for Route,Config,Autoload,Exception,Render...
 *         Author:  liuyoubin@ubinliu.com
 *        Created:  2015-11-25 18:26:51
 */
class UbinEngine{
	
	private static $routes = array();
	private static $classes = array();
	private static $context = array();
	private static $hooks = array();
	private static $libpath = array();
	private static $heads = array();
	private static $body = "";
	//route
	/**
	 * register routes array
	 * @param array $routes ex.array("^\/home"=>array("HomeController","default"))
	 * @throws Exception
	 */
	public static function LoadRoute(array $routes){
		foreach ($routes as $uri => $callback){
			if (!is_callable($callback)) {
				throw new Exception("Invalid callback function for $uri");
			}
		}
		self::$routes = $routes;
	}
	//hooks
	/**
	 * regitster hooks
	 * @param unknown $name
	 * @param unknown $callback
	 * @throws Exception
	 */
	public static function RegisterHooks($name, $callback){
		if (!is_callable($callback)){
			throw new Exception("Invalid callback function $name hook");
		}
		self::$hooks[$name] = $callback;
	}
	/**
	 * register callback before request execute
	 * @param unknown $callback
	 */
	public static function BeforeRequest($callback){
		self::RegisterHooks("before", $callback);
	}
	/**
	 * register callback after request execute
	 * @param unknown $callback
	 */
	public static function AfterRequest($callback){
		self::RegisterHooks("after", $callback);
	}
	/**
	 * register error handler ex.function HandleError($errno, $errstr, $errfile, $errline){}
	 * @param unknown $callback
	 */
	public static function OnError($callback){
		self::RegisterHooks("error", $callback);
	}
	
	/**
	 * register exception handler ex. function HandleException($ex){}
	 * @param unknown $callback
	 */
	public static function OnException($callback){
		self::RegisterHooks("exception", $callback);
	}
	
	/**
	 * register handler when not found uri
	 * @param unknown $callback
	 */
	public static function OnNotfound($callback){
		self::RegisterHooks("notfound", $callback);
	}
	/**
	 * exec callback function
	 * @param unknown $callback
	 * @param array $params
	 * @throws Exception
	 */
	private static function execute($callback, array &$params = array()) {
		if (is_callable($callback)) {
			return 	call_user_func_array($callback, $params);
		}
		else {
			throw new Exception('Invalid callback function.'.json_encode($callback));
		}
	}
	//context
	/**
	 * get global param from context
	 * @param unknown $name
	 * @param string $default
	 */
	public static function Get($name, $default = null){
		return isset(self::$context[$name]) ? self::$context[$name] : $default;
	}
	/**
	 * set global param to context
	 * @param unknown $name
	 * @param unknown $value
	 */
	public static function Set($name, $value){
		self::$context[$name] = $value;
	}
	
	//exception	
	/**
	 * default error handler
	 * @param unknown $errno
	 * @param unknown $errstr
	 * @param unknown $errfile
	 * @param unknown $errline
	 */
	public static function HandleError($errno, $errstr, $errfile, $errline) {
    	$errorInfo = array("err_file"=>$errfile.":".$errline,
    			"err_no"=>$errno,
    			"err_str"=>$errstr
    	);
    	
    	self::ExitOnError("ERROR:".json_encode($errorInfo));
    }
    /**
     * default exception handler
     * @param Exception $e
     */
    public static function HandleException(Exception $e) {
    	$errorInfo = array(
    			"err_file"=>$e->getFile().":".$e->getLine(),
    			"err_no"=>$e->getCode(),
    			"err_str"=>$e->getMessage()
    	);
    	
    	self::ExitOnError("EXCEPTION:".json_encode($errorInfo));
    }
    
    public static function ExitOnError($message){
    	file_put_contents("./core.".date("YmdH"),
    			date("Y-m-d H:i:s")." ".$message."\n",
    			FILE_APPEND);
    	self::$heads["Content-Type"] = "text/html";
    	self::$body = "<html><head><title>500</title></head><body><h1>Server Internal Error</h1></body></html>";
    	self::Render();
    }
	
    /**
     * engine auto process error and exception
     * @param unknown $enabled
     */
	public static function AutoHandleErrors($enabled)
	{
		if ($enabled) {
			if (isset(self::$hooks["error"])) {
				set_error_handler(self::$hooks["error"]);
			}else{
				set_error_handler(array(__CLASS__, "HandleError"));
			}
			
			if (isset(self::$hooks["exception"])) {
				set_exception_handler(self::$hooks["exception"]);
			}else{
				set_exception_handler(array(__CLASS__, 'HandleException'));
			}
		}else{
			restore_error_handler();
			restore_exception_handler();
		}
	}
	
	//autoload
	/**
	 * auto require_once file by classname
	 * @param unknown $class
	 */
	public static function loadClass($class) {
		if (isset(self::$classes[$class])) {
			require_once self::$classes[$class];
			return;
		}
		
		$class_file = str_replace(array('\\', '_'), '/', $class).'.php';
	
		foreach (self::$libpath as $dir) {
			$file = $dir.'/'.$class_file;
			if (file_exists($file)) {
				self::$classes[$class] = $file;
				require_once  $file;
				return;
			}
		}
	}
	
	private static $autoload_registed = false;
	/**
	 * add auto load path for autoload
	 * @param unknown $dir
	 */
	public static function AutoLoad($dir){
		if (self::$autoload_registed == false) {
			self::$autoload_registed = true;
			spl_autoload_register(array(__CLASS__, 'loadClass'));
		}
		
		if (is_array($dir)) {
			foreach ($dir as $value) {
				self::AutoLoad($value);
			}
		}else if (is_string($dir)) {
			if (!in_array($dir, self::$libpath)) self::$libpath[] = rtrim($dir,"/");
		}
	}
	
	//entry
	/**
	 * entry point
	 */
	public static function Run(){		
		
		$request_uri = empty($_SERVER["REQUEST_URI"]) ?
		"/" : $_SERVER["REQUEST_URI"];
		$route_succ = false;
		
		foreach (self::$routes as $uri_patten => $callback){
			
			if (preg_match("/$uri_patten/i", $request_uri, $matches)) {
				
				$route_succ = true;
				
				if (isset(self::$hooks["before"])) {
					self::execute(self::$hooks["before"]);					
				}
				
				if (is_array($callback)) {
					list($classname, $method) = $callback;
					$ins = new $classname();
					$ins->$method();
				}else{
					self::execute(array($ins, $method));
				}
				
				return;
			}
		}
		
		if (!$route_succ) {
			self::NotFound();
		}
		
	}
	/**
	 * not found callback
	 */
	public static function NotFound(){
		if (isset(self::$hooks["notfound"])) {
			self::execute(self::$hooks["notfound"]);
			return;
		}
		self::$heads["Content-Type"] = "text/html";
		self::$body = "<html><head><title>404</title></head><body><h1>Not Found</h1></body></html>";
		self::Render();
	}
	/**
	 * redirect url
	 * @param unknown $url
	 */
	public static function Redirect($url){
		header("Location: $url");
		exit;
	}
	
	/**
	 * render jsonp
	 * @param unknown $js
	 * @param string $async
	 */
	public static function RenderJsonP($js, $async = false){
		self::$heads["Content-Type"] = "application/javascript";
		self::$body = $js;
		
		self::Render($async);
	}
	/**
	 * render json
	 * @param unknown $json_string
	 * @param string $async
	 */
	public static function RenderJson($json_string, $async = false){
		self::$heads["Content-Type"] = "application/json";
		self::$body = $json_string;
		
		self::Render($async);
	}
	/**
	 * render html
	 * @param unknown $tpl
	 * @param array $data
	 * @param string $async
	 * @throws Exception
	 */
	public static function RenderHtml($tpl, array $data, $async = false){
		self::$heads["Content-Type"] = "text/html";
		if ((substr($tpl, -4) != '.php')) {
			$tpl .= '.php';
		}
		
		if (!file_exists($tpl)) {
			throw new Exception("Invalid tpl $tpl");
		}
		
		if (ob_get_length() > 0) {
			ob_end_clean();
		}
		
		ob_start();
		
		extract($data);
		include $tpl;		
		
		self::$body = ob_get_clean();
		
		self::Render($async);
	}
	/**
	 * response to client
	 * @param string $async
	 */
	private static function Render($async = false){
		if (ob_get_length() > 0) {
			ob_end_clean();
		}
			
		ob_start();
		
		foreach (self::$heads as $name => $value){
			header("$name: $value");
		}
		
		if (($length = strlen(self::$body)) > 0) {
			header('Content-Length: '.$length);
		}	
		
		echo(self::$body);
		
		if (isset(self::$hooks["after"])) {
			self::execute(self::$hooks["after"]);
		}
		
		if ($async) {
			fastcgi_finish_request();
			return;
		}
		
		exit;
	}	
	
}