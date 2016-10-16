# ModxLoader

<h4>Плагин с классом Loader</h4>

<p>Плагин позволяет обращаться к сниппетам как через ajax так и напрямую</p>
<p>Для установки нужно создать плагин Loader с ниже указанным кодом и на события OnWebPageInit и OnPageNotFound</p>
<pre>
require MODX_BASE_PATH.'assets/plugins/loader/plugin.loader.php';
</pre>

<p>
пример вызова своего сниппета в php
</p>
<pre>
$modx->load->controller('account/controller/login', $config);
</pre>
<p>
<b>'account/controller/login'</b> - путь до нужного сниппета
</p>
<p>
<p><b>$config</b> - массив с передаваемыми параметрами
</p>

<br>
<p><b>AJAX</b></p>
```js
$.ajax({
    url: 'ajax?route=account/controller/login/ajax',
    dataType: 'json',
    type: 'post',
    data: params,
    success: function(json) {

	// ваш код

    },
    error: function(xhr, ajaxOptions, thrownError) {
	alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
    }
});
```

<br>
<p><b>PHP</b></p>
<p>Сниппеты должны придерживаться правил и находится в папке 'assets/snippets/'</p>
<p>ControllerAccountControllerLogin - Controller + 'account/controller/login'</p>

```php
<?php
class ControllerAccountControllerLogin extends Loader {

	public function index() {
	
		// ваш код
		
	}
	
}
?>
```
<br>
Рабочий код можно посмотреть в сниппете ModxAccount

PHP https://github.com/64j/ModxAccount/blob/master/account/controller/login.php

JS ajax - https://github.com/64j/ModxAccount/blob/master/account/view/login.tpl#L57

<br>
<br>
<br>
<p>Также можно установить плагин без инклюда, описанного выше. <br>
События плагина <b>OnWebPageInit</b> и <b>OnPageNotFound</b></p>
```php
<?php

// OnWebPageInit OnPageNotFound

if(!defined('MODX_BASE_PATH')) {
	die('Unauthorized access.');
}

$modx->load = new Loader($modx);

class Loader {
	private $registry;
	private $data = array();

	public function __construct($modx) {
		$this->modx = $modx;
	}

	public function __get($key) {
		return (isset($this->data[$key]) ? $this->data[$key] : null);
	}

	public function __set($key, $value) {
		$this->data[$key] = $value;
	}

	public function controller($route, $data = array()) {
		$parts = explode('/', str_replace('../', '', (string) $route));
		while($parts) {
			$file = MODX_BASE_PATH . 'assets/snippets/' . implode('/', $parts) . '.php';
			$class = 'Controller' . preg_replace('/[^a-zA-Z0-9]/', '', implode('/', $parts));
			if(is_file($file)) {
				include_once($file);
				break;
			} else {
				$method = array_pop($parts);
			}
		}
		if(!isset($method)) {
			$method = 'index';
		}
		if(substr($method, 0, 2) == '__') {
			return false;
		}
		if($method == 'index' && isset($_REQUEST['route'])) {
			die('Unauthorized access.');
		}
		$output = '';
		if(class_exists($class)) {
			$controller = new $class($this->modx);
			if(is_callable(array(
				$controller,
				$method
			))) {
				$output = call_user_func(array(
					$controller,
					$method
				), $data);
			}
		}
		return $output;
	}

	public function view($template, $data = array()) {
		$file = MODX_BASE_PATH . $template;
		if(file_exists($file)) {
			extract($data);
			ob_start();
			require($file);
			$output = ob_get_contents();
			ob_end_clean();
		} else {
			trigger_error('Error: Could not load template ' . $file . '!');
			exit();
		}
		return $output;
	}
}

if($modx->Event->name == 'OnPageNotFound') {
	if($_REQUEST['q'] == 'ajax' && isset($_REQUEST['route'])) {
		$modx->load->controller($_REQUEST['route'], $_REQUEST, true);
		exit;
	}
}
```
