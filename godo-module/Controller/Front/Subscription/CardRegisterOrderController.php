<?php

namespace Controller\Front\Subscription;

use App;
use Request;

class CardRegisterOrderController extends \Controller\Front\Controller
{

    public function index()
    {
        ini_set("session.cookie_samesite", "none");
        ini_set("session.cookie_secure", 1);


        $obj = App::load("\Component\Subscription\Subscription");
        $cfg = $obj->getCfg();

        $this->setData("isOrder", Request::get()->get("isOrder"));
        $this->setData("chars", $obj->getShuffleChars());
        $this->setData("subCfg", $cfg);
    }
}