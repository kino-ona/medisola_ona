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
 * @link http://www.godo.co.kr
 */

namespace Controller\Admin\Order;

use App;
use Request;
use DB;
use Component\Order\OrderAdmin;

/**
 * 주문 상세 처리 페이지 커스텀 (for 회차배송)
 *
 * @package \Controller\Admin\Order
 * @author  Conan Kim<kmakugo@gmail.com>
 */
class OrderPsController extends \Bundle\Controller\Admin\Order\OrderPsController
{
  /**
   * @inheritdoc
   *
   * @throws LayerException
   */
  public function index()
  {
    // --- 모듈 호출
    $order = new OrderAdmin();
    // $delivery = App::load('\\Component\\Delivery\\Delivery');
    $postArray = Request::post()->toArray();
    
    switch ($postArray['mode']) {
      case 'combine_delivery_status_change': // 회차배송 상태 변경
        try {
          // $postValueEtc = $postValue = [];
          $tmpPostValue = $postArray;
          $postValue = $tmpPostValue;

          unset($postValue['statusCheck'], $postValue['deliveryStatus'], $postValue['escrowCheck'], $postValue['invoiceCompanySno'], $postValue['deliveryMethodFl']);

          // 외부채널 주문건과 나머지 채널 주문건의 처리를 다르게 하기위해 post 값 재구성
          if (count($tmpPostValue) > 0) {
            foreach ($tmpPostValue['statusCheck'] as $statusCode => $valueArray) {
              $checkBoxCdArray = array_keys($tmpPostValue['orderChannelFl'][$statusCode]);
              if (count($valueArray) > 0) {
                foreach ($valueArray as $index => $checkBoxCd) {
                  $reverseCheckBoxCdArray = array_flip($checkBoxCdArray);
                  $realIndex = $reverseCheckBoxCdArray[$checkBoxCd];
                  // 현재 외부채널 주문건은 사용하지 않음 (shop, naverPay) (2025-02-06) - Conan Kim
                  // if ($tmpPostValue['orderChannelFl'][$statusCode][$checkBoxCd] === 'etc') {
                  //   $postValueEtc['statusCheck'][$statusCode][] = $checkBoxCd;
                  //   $postValueEtc['deliveryStatus'][$statusCode][] = $tmpPostValue['deliveryStatus'][$statusCode][$realIndex];
                  //   $postValueEtc['escrowCheck'][$statusCode][] = $tmpPostValue['escrowCheck'][$statusCode][$realIndex];
                  // } else {
                    $postValue['statusCheck'][$statusCode][] = $checkBoxCd;
                    $postValue['deliveryStatus'][$statusCode][] = $tmpPostValue['deliveryStatus'][$statusCode][$realIndex];
                    $postValue['escrowCheck'][$statusCode][] = $tmpPostValue['escrowCheck'][$statusCode][$realIndex];
                    $postValue['invoiceCompanySno'][$statusCode][] = $tmpPostValue['invoiceCompanySno'][$statusCode][$realIndex];
                    $postValue['deliveryMethodFl'][$statusCode][] = $tmpPostValue['deliveryMethodFl'][$statusCode][$realIndex];
                  // }
                }
              }
            }
          }

          // --- 외부채널 주문건 상태변경
          // 현재 외부채널 주문건은 사용하지 않음 (shop, naverPay) (2025-02-06) - Conan Kim
          // if (count($postValueEtc) > 0) {
          //   $postValueEtc['mode'] = $tmpPostValue['mode'];
          //   $postValueEtc['changeStatus'] = $tmpPostValue['changeStatus'];
          //   $postValueEtc['deliveryStatusBottom'] = $tmpPostValue['deliveryStatusBottom'];
          //   $postValueEtc['deliveryStatusUpdateFl'] = 'y';

          //   $externalOrder = \App::load('\\Component\\Order\\ExternalOrder');
          //   DB::transaction(
          //     function () use ($externalOrder, $postValueEtc) {
          //       // originally the method name was updateOrderStatusDelivery
          //       // and there is no method updateDeliveryStatusDelivery
          //       $externalOrder->updateDeliveryStatusDelivery($postValueEtc); 
          //     }
          //   );
          // }

          // --- 일반채널 주문건 상태변경
          if (count($postValue) > 0) {
            //배송상태 변경이고 변경 주문건이 모두 방문수령일 경우 SMS발송 제한

            // FIXME: 테스트를 위해 아래 sms 발송 로직 주석처리
            // 회차배송 상태 변경시 SMS 발송이 필요한 경우 아래 주석을 해제하고 활용할 것
            /* 
            $changeStatusCriteria = substr($postValue['changeStatus'], 0, 1);

            $deliveryStatusUpdateFl = 'y';
            
            if ($changeStatusCriteria == 'd') { // Delivery 배송(중/완료)
              $tmpDelivery = $delivery->getDeliveryCompany(null, true);
              $visitSno = '';
              $deliverySno = [];
              if (empty($tmpDelivery) === false) {
                foreach ($tmpDelivery as $key => $val) {
                  if ($val['companyKey'] == 'visit') {
                    $visitSno = $val['sno'];
                    break;
                  }
                }
                unset($tmpDelivery);
              }
              foreach ($postValue['invoiceCompanySno'] as $goodsSno) {
                foreach ($goodsSno as $companySno) {
                  if (empty($companySno) === false) {
                    $deliverySno[] = $companySno;
                  } else {
                    if ($postValue['deliveryMethodFl'][$goodsSno] == 'visit') {
                      $deliverySno[] = $visitSno;
                    }
                  }
                }
              }
              if (empty($deliverySno) === false && !array_diff($deliverySno, [$visitSno])) {
                $postValue['useVisit'] = $deliveryStatusUpdateFl = 'n';
                Request::post()->set('useVisit', 'y');
              }
            }
            */

            // FIXME: 테스트를 위해 아래 sms 발송 로직 주석처리
            // 회차배송 상태 변경시 SMS 발송이 필요한 경우 아래 주석을 해제하고 활용할 것
            /*
            if ($deliveryStatusUpdateFl === 'y') {
              $smsAuto = \App::load('Component\\Sms\\SmsAuto');
              $smsAuto->setUseObserver(true);
            }
            */

            // order_list_goods 와 orderGoodsSimple 는 원래 페이지가 배송준비중 페이지 기반으로 만들어진 것이라 다른 동작 유지를 위해
            // 회차배송 로직임에도 불구하고 그대로 사용하고 있음, 회차 배송은 order_list_goods 와 orderGoodsSimple 에서만 사용됨.
            if ($postValue['fromPageMode'] === 'order_list_goods' && $postValue['searchView'] === 'orderGoodsSimple') {
              DB::transaction(
                function () use ($order, $postValue) {
                  $order->changeScheduledDeliveryStatus($postValue);
                }
              );
            } else {
              // 회차배송에서는 사용되지 않는 로직.
              DB::transaction(
                function () use ($order, $postValue) {
                  $order->requestStatusChangeList(Request::post()->toArray());
                }
              );
            }

            // TODO: 테스트를 위해 아래 sms 발송 로직 주석처리
            // 회차배송 상태 변경시 SMS 발송이 필요한 경우 아래 주석을 해제하고 활용할 것
            /*
            if ($deliveryStatusUpdateFl === 'y') {
              $smsAuto->notify();
            }
            */
          }

          $this->layer(__('회차배송상태 변경 처리가 완료되었습니다.'), null, 2000);
        } catch (Exception $e) {
          if (Request::isAjax()) {
            $this->json([
              'code' => 0,
              'message' => $e->getMessage(),
            ]);
          } else {
            throw new LayerException($e->getMessage(), null, null, null, 7000);
          }
        }
        break;
      case 'combine_delivery_invoice_change': // 회차배송 송장번호 변경
        try {
          $postValue = $postArray;

          // order_list_goods 와 orderGoodsSimple 는 원래 페이지가 배송준비중 페이지 기반으로 만들어진 것이라 다른 동작 유지를 위해
          // 회차배송 로직임에도 불구하고 그대로 사용하고 있음, 회차 배송은 order_list_goods 와 orderGoodsSimple 에서만 사용됨.
          if ($postValue['fromPageMode'] === 'order_list_goods' && $postValue['searchView'] === 'orderGoodsSimple') {
            //상품준비중 리스트 주문번호별 처리
            $order->saveScheduledDeliveryOrderInvoice($postValue);
          } else {
            throw new LayerException(__('회차배송은 상품별 송장번호 저장이 불가합니다.'));
          }
          $this->layer(__('송장번호 저장이 완료되었습니다.'), null, 2000);
        } catch (Exception $e) {
          if (Request::isAjax()) {
            $this->json([
              'code' => 0,
              'message' => $e->getMessage(),
            ]);
          } else {
            throw $e;
          }
        }
        break;
      default:
        parent::index();
    }
  }
}
