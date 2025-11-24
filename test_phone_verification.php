<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$request = Illuminate\Http\Request::create('/api/phone-verification/check-phone', 'POST', [
    'phone_number' => '0782284788'
]);
$request->headers->set('Content-Type', 'application/json');

$response = $kernel->handle($request);
echo $response->getContent();

$kernel->terminate($request, $response);
