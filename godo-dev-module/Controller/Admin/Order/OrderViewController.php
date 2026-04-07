<?php

/**
 * This is commercial software, only users who have purchased a valid license
 * and accept to the terms of the License Agreement can install and use this
 * program.
 *
 * Do not edit or add to this file if you wish to upgrade Godomall5 to newer
 * versions in the future.
 *
 * @copyright ⓒ 2016, NHN godo: Corp.
 * @link      http://www.godo.co.kr
 */
namespace Controller\Admin\Order;

use App;

/**
 * 주문 상세 페이지
 * [관리자 모드] 주문 상세 페이지
 *
 * @package Bundle\Controller\Admin\Order
 * @author  Conan Kim <kmakugo@gmail.com>
 */
class OrderViewController extends \Bundle\Controller\Admin\Order\OrderViewController
{
	public function index()
	{
		$this->splitScheduledDeliveryIfRequested();
		parent::index();
	}

	/**
	 * 다회차 배송 주문에서 회차 배송이 어떤 이유에서 정상적으로 생성되지 않았을 때 수동으로 생성하는 숨은 기능
	 * http://gdadmin.medisola2.godomall.com/order/order_view.php?orderNo=[order-no]&action=split-scheduled-delivery 와 같이
	 * 요청하면 회차 배송을 생성한다.
	 * 이미 회차 배송이 생성되어 있는 경우 생성하지 않으므로 문제가 있는 회차 배송을 수동으로 삭제 후 적용한다.
	 * @return void
	 */
	protected function splitScheduledDeliveryIfRequested()
	{
		$request = \App::getInstance('request');
		$orderNo = $request->get()->get('orderNo');
		$action = $request->get()->get('action');

		if ($action == 'split-scheduled-delivery' && !empty($orderNo)) {
			$orderAdmin = \App::load('\\Component\\Order\\OrderAdmin');
			// Use 'g1' as changeStatus to avoid overriding paymentDt
			// The actual status will be updated in the trySplitScheduledDeliveries function
			$orderAdmin->trySplitScheduledDeliveries([$orderNo], 'g1');
		}
	}
}