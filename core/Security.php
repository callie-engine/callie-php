<?php

namespace Callie;

class Security {
    
    private static $jwtSecret = null;
    
    /**
     * Initialize security with JWT secret from env
     */
    public static function init() {
        self::$jwtSecret = Env::get('JWT_SECRET', 'change-this-secret-in-production');
    }
    
    /**
     * CORS Headers
     */
    public static function cors() {
        $origin = Env::get('CORS_ORIGIN', '*');
        
        header("Access-Control-Allow-Origin: $origin");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
        
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }

    /**
     * Force HTTPS in production
     */
    public static function https() {
        $env = Env::get('APP_ENV', 'development');
        
        if ($env === 'production') {
            if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
                $redirect = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
                header("Location: $redirect", true, 301);
                exit;
            }
        }
    }

    /**
     * Rate Limiting (file-based for shared hosting)
     */
    public static function rateLimit($limit = 100, $duration = 60) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $cacheDir = __DIR__ . '/../cache';
        
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        
        $file = $cacheDir . '/rate_' . md5($ip) . '.json';
        
        $data = file_exists($file) ? json_decode(file_get_contents($file), true) : null;
        
        if (!$data || (time() - $data['start']) > $duration) {
            $data = ['count' => 1, 'start' => time()];
        } else {
            $data['count']++;
        }
        
        file_put_contents($file, json_encode($data));
        
        if ($data['count'] > $limit) {
            header('Content-Type: application/json');
            header('Retry-After: ' . ($duration - (time() - $data['start'])));
            http_response_code(429);
            echo json_encode(['success' => false, 'message' => 'Too Many Requests']);
            exit;
        }
    }

    /**
     * Generate JWT Token
     */
    public static function generateToken($payload, $expiresIn = 86400) {
        if (!self::$jwtSecret) self::init();
        
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload['iat'] = time();
        $payload['exp'] = time() + $expiresIn;
        $payload = json_encode($payload);
        
        $base64Header = self::base64UrlEncode($header);
        $base64Payload = self::base64UrlEncode($payload);
        
        $signature = hash_hmac('sha256', "$base64Header.$base64Payload", self::$jwtSecret, true);
        $base64Signature = self::base64UrlEncode($signature);
        
        return "$base64Header.$base64Payload.$base64Signature";
    }

    /**
     * Verify JWT Token
     */
    public static function verifyToken($token) {
        if (!self::$jwtSecret) self::init();
        
        $parts = explode('.', $token);
        if (count($parts) !== 3) return null;
        
        list($base64Header, $base64Payload, $base64Signature) = $parts;
        
        $signature = hash_hmac('sha256', "$base64Header.$base64Payload", self::$jwtSecret, true);
        $expectedSignature = self::base64UrlEncode($signature);
        
        if (!hash_equals($expectedSignature, $base64Signature)) {
            return null;
        }
        
        $payload = json_decode(self::base64UrlDecode($base64Payload), true);
        
        if ($payload['exp'] < time()) {
            return null;
        }
        
        return $payload;
    }

    /**
     * Auth Middleware - Call this in protected routes
     */
    public static function auth() {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        
        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        
        $token = $matches[1];
        $payload = self::verifyToken($token);
        
        if (!$payload) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Invalid or expired token']);
            exit;
        }
        
        return $payload;
    }

    /**
     * Hash password securely
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    /**
     * Verify password
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }

    /**
     * Sanitize input
     */
    public static function sanitize($data) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = self::sanitize($value);
            }
            return $data;
        }
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }

    // Helper: Base64 URL encode
    private static function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    // Helper: Base64 URL decode
    private static function base64UrlDecode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}

