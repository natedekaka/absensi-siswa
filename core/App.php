<?php

class App {
    protected $controller = 'Home';
    protected $method = 'index';
    protected $params = [];

    public function __construct() {
        $url = $this->parseUrl();
        
        if (file_exists("../controllers/" . ucfirst($url[0]) . "Controller.php")) {
            $this->controller = ucfirst($url[0]) . "Controller";
            unset($url[0]);
        }

        require_once "../controllers/" . $this->controller . ".php";
        $this->controller = new $this->controller;

        if (isset($url[1])) {
            if (method_exists($this->controller, $url[1])) {
                $this->method = $url[1];
                unset($url[1]);
            }
        }

        $this->params = $url ? array_values($url) : [];
        call_user_func_array([$this->controller, $this->method], $this->params);
    }

    public function parseUrl() {
        if (isset($_GET['url'])) {
            return explode('/', filter_var(rtrim($_GET['url'], '/'), FILTER_SANITIZE_URL));
        }
        return ['home'];
    }
}

function view($view, $data = []) {
    extract($data);
    require "../views/$view.php";
}

function redirect($url) {
    header("Location: " . BASE_URL . $url);
    exit;
}

function auth() {
    if (!isset($_SESSION['user'])) {
        redirect('login');
    }
}

function authRole($roles = []) {
    auth();
    if (!in_array($_SESSION['user']['role'], $roles)) {
        $_SESSION['error'] = "Akses ditolak";
        redirect('dashboard');
    }
}
