<?php

namespace Controller\Front\Subscription\Inicis;

use App;
use Request;

class AjaxController extends \Controller\Front\Controller 
{
    public function index()
    {
        $get = Request::get()->toArray();
        $post = Request::post()->toArray();
        $in = array_merge($get, $post);
        
        $obj = App::load("\Component\Subscription\Subscription");
        $cfg = $obj->getCfg();
        $pg = $obj->getPgInstance();
        switch ($in['mode']) {
            case "getPGParams" : 
                if ($in['isMobile'])
                    $pg->isMobile = true;
                
                $sign = $pg->getPgSign($in['uid'], $in['price'], $cfg['timestamp']);
                $data = [
                    'uid' => $in['uid'],
                    'timestamp' => $cfg['timestamp'],
                    'sign' => $sign,
                ];
                
                header("Content-type: application/json;charset=utf-8");
                echo json_encode($data);
                break;
        }
        exit;
    }
}