<?php

#TODO : jeter une exception si pattern mal formé ?

class Router {

	public $routes = [];
	public $data = [];

	public $vars = [];
	public $stars = [];
	public $route = [];

	function route() {
		$this->route = [];
		$this->vars = [];
		$this->stars = [];
		foreach ($this->routes as $route) {
			$go = true;
			$patterns = [];
			$values = [];
			foreach ($route as $key => $pattern) {
	# TODO : si pattern est un tableau, il faut que l'un des éléments match (OU logique)
	# Pour le GET...
				if (isset($this->data[$key])) {
					$pattern = self::get_pattern($pattern);
					$value = $this->data[$key];
					if (self::match($pattern, $value)) {
						$patterns[$key] = $pattern;
						$values[$key] = $value;
					}
					else {
						$patterns = [];
						$values = [];
						$go = false;
						break;
					}
				}
			}
			if ($go) {
				$this->route = $route;
				foreach (array_keys($route) as $key) {
					if (isset($this->data[$key])) {
						$this->vars[$key] = self::get_vars($patterns[$key], $values[$key]);
						$this->stars[$key] = self::get_stars($patterns[$key], $values[$key]);
					}
					else {
						$this->vars[$key] = [];
						$this->stars[$key] = [];
					}
				}
				break;
			}
		}
		
		return $this->route;
	}

	function associate_vars($src, $dest) {
		if (isset($this->vars[$src])) {
			$this->vars[$dest] = isset($this->vars[$dest]) ? array_merge($this->vars[$dest], $this->vars[$src]) : $this->vars[$src];
		}
	}

	function apply($vars = []) {
		$route = [];
		foreach (array_keys($this->route) as $key) {
			$used_vars = $vars;
			if (isset($this->vars[$key])) {
				$used_vars = array_replace_recursive($this->vars[$key], $used_vars);
			}
			$route[$key] = $this->route[$key];
			if (strpos($route[$key], "{")) {
				foreach ($used_vars as $var => $var_value) {
					$route[$key] = preg_replace("!\{".$var."(=[^\}]+)?\}!", $var_value, $route[$key]);
				}
			}
			if (isset($this->stars[$key])) {
				$route[$key] =  vsprintf(str_replace("*", "%s", $route[$key]), $this->stars[$key]);
			}
		}
		
		return $route;
	}
	
	static function match($pattern, $value) {
		$pattern = self::clean_pattern($pattern);

		return preg_match("!^$pattern$!", $value);
	}

	static function clean_pattern($pattern) {
		$pattern = preg_replace("/:VAR[^:]+:/", "", $pattern);
		$pattern = str_replace(":STAR:", "", $pattern);

		return $pattern;
	}

	static function get_pattern($pattern) {
		$regex = str_replace(".", "\.", $pattern);
		$regex = str_replace("*", "(:STAR:.*)", $regex);
		$regex = preg_replace("!\[([^\]]+)\]!", "($1)?", $regex);
		$regex = preg_replace("!\{([^\}=]+)=([^\}=]+)\}!", "(:VAR$1:$2)", $regex);
		$regex = preg_replace("!\{([^\}]+)\}!", "(:VAR$1:[^/]+)", $regex);

		return $regex;
	}

	static function get_positions($needle, $pattern) {
		$last_pos = 0;
		$positions = [];
		$length = strlen($needle);
		$i = 1;
		while (($last_pos = strpos($pattern, "(", $last_pos)) !== false) {
			if (substr($pattern, $last_pos + 1, $length) == $needle) {
				$positions[] = $i;
				$last_pos = $last_pos + $length;
			}
			else {
				$last_pos++;
			}
			$i++;
		}

		return $positions;
	}

	static function get_vars($pattern, $value) {
		$vars_positions = self::get_positions(":VAR", $pattern);
		$vars_names = [];
		preg_match_all("!:VAR([^:]+):!", $pattern, $matches);
		if (isset($matches[1])) {
			foreach ($matches[1] as $var_name) {
				$vars_names[] = $var_name;
			}
		}
		$pattern = self::clean_pattern($pattern);
		$vars = [];
		if (preg_match("!^$pattern$!", $value, $matches)) {
			$i = 0;
			foreach ($vars_positions as $pos) {
				$var_name = $vars_names[$i];
				$i++;
				$vars[$var_name] = isset($matches[$pos]) ? $matches[$pos] : null;
			}
		}

		return $vars;
	}

	static function get_stars($pattern, $value) {
		$stars_positions = self::get_positions(":STAR", $pattern);
		$pattern = self::clean_pattern($pattern);
		$stars = [];
		if (preg_match("!^$pattern$!", $value, $matches)) {
			foreach ($stars_positions as $pos) {
				$stars[] = $matches[$pos];
			}
		}

		return $stars;
	}
}
