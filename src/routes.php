<?php
declare(strict_types=1);

use TrailSense\Controllers\ActivityController;
use TrailSense\Controllers\AuthController;
use TrailSense\Controllers\HomeController;
use TrailSense\Controllers\PlanController;
use TrailSense\Controllers\TrailController;

// route name => [HTTP method, controller, action]
return [
    'home'            => ['GET',  HomeController::class, 'index'],
    'login'           => ['GET',  AuthController::class, 'showLogin'],
    'login.submit'    => ['POST', AuthController::class, 'login'],
    'register'        => ['GET',  AuthController::class, 'showRegister'],
    'register.submit' => ['POST', AuthController::class, 'register'],
    'logout'          => ['POST', AuthController::class, 'logout'],
    'account'         => ['GET',  AuthController::class, 'account'],

    'trails'          => ['GET',  TrailController::class, 'index'],
    'trail'           => ['GET',  TrailController::class, 'show'],

    'plans'           => ['GET',  PlanController::class, 'index'],
    'plan.new'        => ['GET',  PlanController::class, 'create'],
    'plan.store'      => ['POST', PlanController::class, 'store'],
    'plan'            => ['GET',  PlanController::class, 'show'],
    'plan.check'      => ['POST', PlanController::class, 'check'],
    'plan.delete'     => ['POST', PlanController::class, 'delete'],

    'feed'            => ['GET',  ActivityController::class, 'feed'],
    'activity'        => ['GET',  ActivityController::class, 'show'],
    'photo'           => ['GET',  ActivityController::class, 'photo'],
    'publish'         => ['GET',  ActivityController::class, 'create'],
    'publish.store'   => ['POST', ActivityController::class, 'store'],
];
