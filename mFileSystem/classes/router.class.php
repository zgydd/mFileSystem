<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of router
 *
 * @author bartonjoe
 */
class router {

    private $method;
    private $url;
    private $maps;

    public function __construct() {
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->url = str_replace('//', '/', str_replace($_SERVER['SCRIPT_NAME'], '', $_SERVER['PHP_SELF']));
    }

    public function add($route, $callback) {
        $this->maps[$route] = $callback;
    }

    public function run() {
        $is_match = FALSE;
        foreach ($this->maps as $route => $callback) {
            if ('default' === $route) {
                continue;
            }

            if ($route == $this->url) {
                $is_match = TRUE;
                $callback();
                break;
            }
        }
        if (!$is_match) {
            $this->maps['default']();
        }
    }

}
