<?php

namespace Controller\Mobile\Member;

use Component\Medisola\MedisolaAuthorizeApi;

class MedisolaAuthorizePsController extends \Bundle\Controller\Mobile\Controller
{
    public function index()
    {
        $session = \App::getInstance('session');

        $session->del(MedisolaAuthorizeApi::SESSION_APP_AUTHORIZE);
        $this->redirect(URI_MOBILE);
    }
}
