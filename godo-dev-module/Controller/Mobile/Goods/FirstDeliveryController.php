<?php
namespace Controller\Mobile\Goods;

class FirstDeliveryController extends \Controller\Mobile\Controller
{
	public function index()
	{
		$goodsNo = \Request::get()->get('goodsNo');

	}
}