<?php
require_once __DIR__ . '/../core/Router.php';
require_once __DIR__ . '/../core/Autoloader.php';

use Core\Router;
use Core\Autoloader;

// Register autoloader
Autoloader::register();

// Enable CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

$router = new Router();

// Loan routes
$router->get('/loans', 'LoanController@index');
$router->get('/loans/{id}', 'LoanController@show');
$router->post('/loans', 'LoanController@store');
$router->put('/loans/{id}', 'LoanController@update');
$router->delete('/loans/{id}', 'LoanController@destroy');
$router->get('/loans/search', 'LoanController@search');
$router->get('/statistics', 'LoanController@statistics');

// Payment routes
$router->get('/loans/{id}/payments', 'PaymentController@getLoanPayments');
$router->get('/loans/{id}/payments/summary', 'PaymentController@getPaymentSummary');
$router->get('/loans/{id}/payments/export', 'PaymentController@exportPayments');
$router->post('/loans/{id}/payments', 'PaymentController@createPayment');
$router->get('/payments', 'PaymentController@getAllPayments');
$router->get('/payments/{id}', 'PaymentController@getPayment');
$router->put('/payments/{id}', 'PaymentController@updatePayment');
$router->delete('/payments/{id}', 'PaymentController@deletePayment');
$router->get('/payments/statistics', 'PaymentController@getPaymentStatistics');
$router->get('/loans/{id}/schedule', 'LoanController@paymentSchedule');

// Auth routes
$router->post('/auth/login', 'AuthController@login');
$router->post('/auth/register', 'AuthController@register');
$router->post('/auth/change-password', 'AuthController@changePassword');
$router->get('/auth/user', 'AuthController@getUser');

// Admin routes
$router->get('/admin/users', 'AdminController@getUsers');
$router->put('/admin/users/role', 'AdminController@updateRole');
$router->put('/admin/users/status', 'AdminController@updateStatus');
$router->delete('/admin/users', 'AdminController@deleteUser');

// 404 handler
$router->setNotFound(function() {
    http_response_code(404);
    echo json_encode([
        'success' => false, 
        'message' => 'API endpoint not found',
        'error_code' => 404
    ]);
});

// Dispatch routes
$router->dispatch();
?>