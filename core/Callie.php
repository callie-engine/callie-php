<?php

namespace Callie;

require_once __DIR__ . '/Env.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Router.php';
require_once __DIR__ . '/Context.php';
require_once __DIR__ . '/Security.php';

class Callie {
    protected $router;
    
    public function __construct() {
        // 1. Load Env
        Env::load(__DIR__ . '/../.env');

        // 2. Setup Security
        Security::https();      // Force HTTPS in production
        Security::cors();       // CORS headers
        Security::rateLimit();  // Rate limiting (100 req/min)
        
        // 3. Init Core
        $this->router = new Router();
        
        // 4. Global Error Handler
        set_exception_handler(function($e) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        });
    }

    // Proxy methods to router
    public function get($path, $handler) { $this->router->get($path, $handler); }
    public function post($path, $handler) { $this->router->post($path, $handler); }
    public function put($path, $handler) { $this->router->put($path, $handler); }
    public function delete($path, $handler) { $this->router->delete($path, $handler); }
    public function group($prefix, $cb) { $this->router->group($prefix, $cb); }

    public function run() {
        // Init DB connection
        Database::getInstance()->connect();
        
        $ctx = new Context();
        $this->router->dispatch($ctx);
    }
}

// Global aliases for backward compatibility (non-Composer users)
// These allow users to write `Database::` instead of `\Callie\Database::`
if (!class_exists('Database', false)) {
    class_alias('Callie\Database', 'Database');
}
if (!class_exists('Security', false)) {
    class_alias('Callie\Security', 'Security');
}
if (!class_exists('Env', false)) {
    class_alias('Callie\Env', 'Env');
}
