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