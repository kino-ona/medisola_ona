<?php

namespace Controller\Admin\Goods;

use Request;

class LayerGoodsIconRegisterController extends \Controller\Admin\Controller
{
    public function index()
    {
        $getValue = Request::get()->toArray();

        $this->getView()->setDefine('layout', 'layout_layer.php');
        $goods = \App::load('\\Component\\Goods\\GoodsAdmin');

        $goodsIcon = $goods->getAdminListGoodsIcon();
        $this->setData('goodsNo', $getValue['goodsNo']);
        $this->setData('goodsIcon', $goodsIcon['data']);
    }
}