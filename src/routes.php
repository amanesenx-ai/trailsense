<?php
declare(strict_types=1);

use TrailSense\Controllers\ActivityController;
use TrailSense\Controllers\AuthController;
use TrailSense\Controllers\GroupController;
use TrailSense\Controllers\HikeController;
use TrailSense\Controllers\HomeController;
use TrailSense\Controllers\PlanController;
use TrailSense\Controllers\ProfileController;
use TrailSense\Controllers\TrailController;

// route name => [HTTP method, controller, action]
return [
    'home'            => ['GET',  HomeController::class, 'index'],
    'sources'         => ['GET',  HomeController::class, 'sources'],
    'login'           => ['GET',  AuthController::class, 'showLogin'],
    'login.submit'    => ['POST', AuthController::class, 'login'],
    'register'        => ['GET',  AuthController::class, 'showRegister'],
    'register.submit' => ['POST', AuthController::class, 'register'],
    'logout'          => ['POST', AuthController::class, 'logout'],
    'account'         => ['GET',  AuthController::class, 'account'],
    'password.update' => ['POST', AuthController::class, 'changePassword'],

    'trails'          => ['GET',  TrailController::class, 'index'],
    'trail'           => ['GET',  TrailController::class, 'show'],
    'trail.report'    => ['POST', TrailController::class, 'report'],
    'trail.save'      => ['POST', TrailController::class, 'save'],
    'hike'            => ['GET',  HikeController::class, 'show'],

    'groups'          => ['GET',  GroupController::class, 'index'],
    'group.join'      => ['POST', GroupController::class, 'join'],
    'group.leave'     => ['POST', GroupController::class, 'leave'],

    'profile'         => ['GET',  ProfileController::class, 'show'],

    'plans'           => ['GET',  PlanController::class, 'index'],
    'plan.new'        => ['GET',  PlanController::class, 'create'],
    'plan.store'      => ['POST', PlanController::class, 'store'],
    'plan'            => ['GET',  PlanController::class, 'show'],
    'plan.check'      => ['POST', PlanController::class, 'check'],
    'plan.delete'     => ['POST', PlanController::class, 'delete'],
    'plan.edit'       => ['GET',  PlanController::class, 'edit'],
    'plan.update'     => ['POST', PlanController::class, 'update'],

    'feed'            => ['GET',  ActivityController::class, 'feed'],
    'activity'        => ['GET',  ActivityController::class, 'show'],
    'photo'           => ['GET',  ActivityController::class, 'photo'],
    'publish'         => ['GET',  ActivityController::class, 'create'],
    'publish.store'   => ['POST', ActivityController::class, 'store'],
    'activity.like'   => ['POST', ActivityController::class, 'like'],
    'comment.store'   => ['POST', ActivityController::class, 'comment'],
    'comment.delete'  => ['POST', ActivityController::class, 'deleteComment'],
];
