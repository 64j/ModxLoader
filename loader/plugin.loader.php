<?php

if(!defined('MODX_BASE_PATH')) {
	die('Unauthorized access.');
}

// OnWebPageInit

if($modx->Event->name == 'OnWebPageInit') {

	class Loader {
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
			// спасибо Agel_Nash https://github.com/AgelxNash
			$parts = explode('/', preg_replace(array(
				'/\.*[\/|\\\]/i',
				'/[\/|\\\]+/i'
			), array(
				'/',
				'/'
			), (string) rtrim($route, '/')));
			while($parts) {
				$file = MODX_BASE_PATH . 'assets/snippets/' . implode('/', $parts) . '.php';
				$class = preg_replace('/[^a-zA-Z0-9]/', '', implode('/', $parts));
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

			if($method == 'index' && isset($_GET['route'])) {
				$this->modx->sendRedirect($this->modx->config['site_url']);
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

				} else {
					$this->modx->sendRedirect($this->modx->config['site_url']);
				}

			} else {
				$this->modx->sendRedirect($this->modx->config['site_url']);
			}

			return $output;
		}
	}

	$modx->load = new Loader($modx);
}
