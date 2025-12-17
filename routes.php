<?php

/*
|--------------------------------------------------------------------------
| WELCOME TO CALLIE PHP! 🚀
|--------------------------------------------------------------------------
|
| This file (routes.php) is the "Brain" of your application.
| 
| HOW IT WORKS:
| 1. You register a URL path (e.g., '/users')
| 2. You function that runs when someone hits that URL.
| 3. You return data using $ctx->success().
|
| THE $ctx VARIABLE:
| The $ctx (Context) object is passed to every function. It contains:
| - $ctx->body    : The data sent by the user (POST/PUT data)
| - $ctx->query   : The URL parameters (e.g., ?page=1)
| - $ctx->params  : The Dynamic IDs (e.g., /users/:id)
|
*/

// -----------------------------------------------------------------------------
// 1. BASIC ROUTE
// Try visiting: http://localhost/
// -----------------------------------------------------------------------------
$app->get('/', function($ctx) {
    // This sends a JSON response with HTTP 200 OK
    $ctx->success([
        'message' => 'Welcome to Callie!',
        'status' => 'It works!'
    ]);
});

// -----------------------------------------------------------------------------
// 2. DATABASE EXAMPLE (READ)
// Try visiting: http://localhost/users
// Note: Requires you to set up your database in .env first!
// -----------------------------------------------------------------------------
$app->get('/users', function($ctx) {
    // Database::getInstance() automatically connects using your .env credentials
    $users = Database::getInstance()
        ->table('users')
        ->limit(10)
        ->get();
        
    $ctx->success($users);
});

// -----------------------------------------------------------------------------
// 3. POST EXAMPLE (CREATE)
// Send a POST request to: http://localhost/users
// With JSON body: { "email": "test@example.com", "password": "secret" }
// -----------------------------------------------------------------------------
$app->post('/users', function($ctx) {
    // $ctx->body contains the JSON data sent by the frontend
    $body = $ctx->body;
    
    // FAIL FAST: Check for errors first
    if (!isset($body['email'])) {
        return $ctx->error('Email is required', 400);
    }
    
    // Insert into database
    $id = Database::getInstance()
        ->table('users')
        ->insert([
            'email' => $body['email'],
            // Always hash passwords!
            'password' => password_hash($body['password'], PASSWORD_DEFAULT)
        ]);
        
    $ctx->success(['id' => $id], 'User created successfully', 201);
});

// -----------------------------------------------------------------------------
// 4. DYNAMIC PARAMETER EXAMPLE
// Try visiting: http://localhost/users/1
// -----------------------------------------------------------------------------
$app->get('/users/:id', function($ctx) {
    // Access the ':id' part of the URL from $ctx->params
    $id = $ctx->params['id'];
    
    $user = Database::getInstance()
        ->table('users')
        ->where('id', $id)
        ->first();
        
    if (!$user) {
        return $ctx->error('User not found', 404);
    }
    
    $ctx->success($user);
});
