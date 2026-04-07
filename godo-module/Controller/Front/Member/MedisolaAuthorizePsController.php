<?php

namespace Controller\Front\Member;

use Component\Medisola\MedisolaAuthorizeApi;

class MedisolaAuthorizePsController extends \Bundle\Controller\Front\Controller
{
    public function index()
    {
        $session = \App::getInstance('session');

        $session->del(MedisolaAuthorizeApi::SESSION_APP_AUTHORIZE);
        $this->redirect(URI_HOME);
    }
}
