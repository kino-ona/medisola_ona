<?php

namespace Controller\Api\Load;

use App;

class SubscriptionCronController extends \Controller\Api\Controller {
    public function index() {

            gd_Debug("iii");
            exit;

        $front = App::load("\\Controller\\Front\Subscription\\CronController");
        $front->index();

        exit();
    }
}