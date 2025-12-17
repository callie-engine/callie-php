# 🚀 Callie PHP

**A zero-dependency, upload-and-run PHP micro-framework for building REST APIs.**

Perfect for shared hosting, cPanel, XAMPP, or any server with PHP 7.4+.

---

## ✨ Features

- **Zero Dependencies** - No Composer required, just download and run
- **Express-style Routing** - `$app->get()`, `$app->post()`, etc.
- **Built-in Query Builder** - Secure MySQL operations with PDO
- **JWT Authentication** - Built-in token generation and verification
- **CORS Ready** - Pre-configured for frontend integration

---

## 📦 Installation

### Option 1: Download ZIP (Recommended)
1. [Download the latest release](https://github.com/lracdim/callie-php/releases)
2. Extract to your server folder
3. Configure `.env` with your database credentials
4. Done! Visit `http://localhost/your-folder`

### Option 2: Via Composer
```bash
composer create-project lracdim/callie-php my-app --stability=dev
```

---

## 🚀 Quick Start

### 1. Configure Database
Copy `.env.example` to `.env` and update your credentials:
```env
DB_HOST=127.0.0.1
DB_NAME=your_database
DB_USER=root
DB_PASSWORD=your_password
```

### 2. Create Your First Route
Edit `routes.php`:
```php
$app->get('/api/hello', function($ctx) {
    $ctx->success(['message' => 'Hello, World!']);
});

$app->get('/api/users', function($ctx) {
    $users = Database::getInstance()->table('users')->get();
    $ctx->success($users);
});

$app->post('/api/users', function($ctx) {
    $id = Database::getInstance()->table('users')->insert($ctx->body);
    $ctx->success(['id' => $id], 'User created', 201);
});
```

### 3. Test It
```bash
curl http://localhost/your-folder/api/hello
```

---

## 📁 Project Structure

```
├── core/           # Framework internals (don't edit)
│   ├── Callie.php
│   ├── Router.php
│   ├── Database.php
│   └── Security.php
├── .env            # Your credentials (never commit!)
├── .env.example    # Template for .env
├── .htaccess       # Apache routing rules
├── index.php       # Entry point
├── routes.php      # YOUR API LOGIC GOES HERE
└── schema.sql      # Database schema template
```

---

## 🔐 Authentication

```php
// Login - Generate Token
$app->post('/auth/login', function($ctx) {
    $user = Database::getInstance()
        ->table('users')
        ->where('email', $ctx->body['email'])
        ->first();
    
    if (!$user || !Security::verifyPassword($ctx->body['password'], $user['password'])) {
        return $ctx->error('Invalid credentials', 401);
    }
    
    $token = Security::generateToken(['id' => $user['id'], 'role' => $user['role']]);
    $ctx->success(['token' => $token]);
});

// Protected Route
$app->get('/api/profile', function($ctx) {
    $user = Security::auth(); // Returns 401 if invalid
    $ctx->success($user);
});
```

---

## 📋 Requirements

- PHP 7.4 or higher
- PDO extension (enabled by default)
- MySQL 5.7+ or MariaDB 10.2+

---

## 📄 License

MIT License - Use it however you want!

---

Made with ❤️ for developers who just want to ship.
