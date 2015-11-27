# UbinEngine

## Introduction
> a simple but cute php framework for develop quickly

### code menu

* UbinEngine.php the core file
* index.php entrypoint of project
* controllers controller folder
* views view folder
* logs log folder

### function list
> require_once("UbinEngine.php")

#### route
> support simple route, but freedom

``` code
$routes = array(
	"^\/home" => array("ControllerDemo", "home"),
	"^\/json" => array("ControllerDemo", "json"),
	"^\/html" => array("ControllerDemo", "html"),
	"^\/ex" => array("ControllerDemo", "exception"),
	"^\/async" => array("ControllerDemo", "wait"),
);

UbinEngine::LoadRoute($routes);
```
Use uri regex as route patten, and we can define friendly uri. When request uri route success, Engine will new instance of callable class, then call it's method.

#### context
> per request has a global key value context store

```
UbinEngine::Set("logid", 123);
$logid = UbinEngine::Get("logid");
```

#### autoload
> support autoload class by classname, must use classname as php file name.

```
UbinEngine::AutoLoad(CONTROLLER_PATH);
UbinEngine::AutoLoad(LIB_PAHT);

```

#### hooks
> Engine default add a few hooks, such as  before request, after request, on error, on exception, on not found. And you can use register to add other hooks in your application

```
function after(){
	file_put_contents(LOG_PATH."request.log", date("Y-m-d H:i:s") . " NOTICE request end\n", FILE_APPEND);
}

function before(){
	file_put_contents(LOG_PATH."request.log", date("Y-m-d H:i:s") . " TRACE request start\n", FILE_APPEND);
}

UbinEngine::AfterRequest("after");
UbinEngine::BeforeRequest("before");

```

#### error handler
>  Engine can auto catch php exception and error. If not register on error handler and exception handler, Engine will write core file to record exception message, then render 500 Server Internal Error to client. Only you need to do is enable it.

```
UbinEngine::AutoHandleErrors(true);
```

#### render
> Engine support json, html, jsonp types to render to client. In order to not learn a template language, use php as template view directly.

* render html

```

    $data = array(
		"username" => "ubin",
	);
	UbinEngine::RenderHtml(VIEW_PATH."hello", $data);

```

* render json data

```
    $data = array(
		"request_id" => 123456789
	);
	UbinEngine::RenderJson(json_encode($data));
```

#### async exit

> Some times we need to do more works after request. For response client quickly, Engine use fastcgi_finish_request to flush buffer. The last param of RendXXX be set true to enable this function.

```
    file_put_contents(LOG_PATH."request.log",
				date("Y-m-d H:i:s") . " wait before response\n", FILE_APPEND);
	UbinEngine::RenderJson(json_encode($data), true);

	sleep(10);

	file_put_contents(LOG_PATH."request.log",
				date("Y-m-d H:i:s") . " wait after response\n", FILE_APPEND);
```
