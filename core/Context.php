<?php

namespace Callie;

class Context {
    public $params = [];
    public $query = [];
    public $body = [];
    public $headers = [];
    public $method;
    public $path;

    public function __construct() {
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Parse Query
        $this->query = $_GET;

        // Parse Body
        $input = file_get_contents('php://input');
        $this->body = json_decode($input, true) ?? $_POST;

        // Parse Headers
        $this->headers = getallheaders();
    }

    public function status($code) {
        http_response_code($code);
        return $this;
    }

    public function json($data) {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    public function success($data = [], $message = 'Success', $code = 200) {
        $this->status($code)->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
    }

    public function error($message = 'Error', $code = 500, $errors = null) {
        $this->status($code)->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ]);
    }

    public function getHeader($key) {
        $key = strtolower($key);
        $headers = array_change_key_case($this->headers, CASE_LOWER);
        return $headers[$key] ?? null;
    }
}
