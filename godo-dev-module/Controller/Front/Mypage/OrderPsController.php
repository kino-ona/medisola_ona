<?php

/**
 * This is commercial software, only users who have purchased a valid license
 * and accept to the terms of the License Agreement can install and use this
 * program.
 *
 * Do not edit or add to this file if you wish to upgrade Godomall5 to newer
 * versions in the future.
 *
 * @copyright ⓒ 2025, Medisola.
 * @link https://weare.medisola.co.kr
 */

namespace Controller\Front\Mypage;

use App;
use Exception;
use Request;

/**
 * Class OrderPsController
 *
 * @package Bundle\Controller\Front\Mypage
 * @author  Conan Kim <kmakugo@gmail.com>
 */
class OrderPsController extends \Bundle\Controller\Front\Mypage\OrderPsController
{
  /**
   * {@inheritdoc}
   */
  public function index()
  {
    try {

      $postValue = Request::post()->xss()->toArray();
      $order = App::load('\\Component\\Order\\Order');

      switch ($postValue['mode']) {
        case 'changeEstimatedDeliveryDate':
            $scheduledDeliverySno = $postValue['scheduledDeliverySno'];
            $estimatedDeliveryDate = $postValue['estimatedDeliveryDate'];
            $updateFollowings = $postValue['updateFollowings'];
            $isFreshDelivery = $postValue['isFreshDelivery'];

            // 변경 전 기존 날짜 조회
            $oldDeliveries = $order->fetchScheduledDeliveriesBySno($scheduledDeliverySno, false);
            $oldDate = $oldDeliveries[0]['estimatedDeliveryDt'] ?? '';
            $orderNo = $oldDeliveries[0]['orderNo'] ?? '';

            $order->changeEstimatedDeliveryDates($scheduledDeliverySno, $estimatedDeliveryDate, $updateFollowings, $isFreshDelivery);

            // 관리자 메일 발송 (실패해도 사용자 응답에 영향 없음)
            try {
                $this->sendDeliveryDateChangeNotification($orderNo, $oldDate, $estimatedDeliveryDate, $updateFollowings);
            } catch (\Exception $e) {
                // 메일 실패 시 무시
            }

            $this->json(
              [
                'code'    => 200,
                'message' => __('배송예정일자가 성공적으로 변경되었습니다.'),
              ]
            );
          break;
        default:
          parent::index();
          break;
      }
    } catch (Exception $e) {
      if (Request::isAjax()) {
        $this->json(
          [
            'code'    => 0,
            'message' => $e->getMessage(),
          ]
        );
      } else {
        throw $e;
      }
    }
  }

  /**
   * 배송예정일 변경 시 관리자에게 알림 메일 발송
   */
  private function sendDeliveryDateChangeNotification($orderNo, $oldDate, $newDate, $updateFollowings)
  {
      // 수신자 목록 (필요 시 여기에 추가)
      $recipients = [
          'gh.kim@medisola.co.kr',
      ];

      $basicInfo = gd_policy('basic.info');
      $senderEmail = $basicInfo['email'] ?: $recipients[0];
      $mallNm = $basicInfo['mallNm'] ?? '메디쏠라';

      $memNm = \Session::get('member.memNm') ?: '비회원';
      $memId = \Session::get('member.memId') ?: '-';

      $subject = "[메디쏠라] 배송예정일 변경 알림 - 주문번호 {$orderNo}";

      $body = "<div style='font-family:sans-serif; max-width:600px; margin:0 auto;'>"
          . "<h2 style='color:#333;'>배송예정일 변경 알림</h2>"
          . "<table style='width:100%; border-collapse:collapse; margin:16px 0;'>"
          . "<tr><td style='padding:8px; border:1px solid #ddd; background:#f9f9f9; width:120px;'>주문번호</td>"
          .     "<td style='padding:8px; border:1px solid #ddd;'><a href='http://gdadmin.medisola2.godomall.com/order/order_view.php?orderNo={$orderNo}' style='color:#2563EB; text-decoration:underline;'>{$orderNo}</a></td></tr>"
          . "<tr><td style='padding:8px; border:1px solid #ddd; background:#f9f9f9;'>회원명 (ID)</td>"
          .     "<td style='padding:8px; border:1px solid #ddd;'>{$memNm} ({$memId})</td></tr>"
          . "<tr><td style='padding:8px; border:1px solid #ddd; background:#f9f9f9;'>변경 전 날짜</td>"
          .     "<td style='padding:8px; border:1px solid #ddd;'>{$oldDate}</td></tr>"
          . "<tr><td style='padding:8px; border:1px solid #ddd; background:#f9f9f9;'>변경 후 날짜</td>"
          .     "<td style='padding:8px; border:1px solid #ddd; font-weight:bold; color:#E53E3E;'>{$newDate}</td></tr>"
          . "<tr><td style='padding:8px; border:1px solid #ddd; background:#f9f9f9;'>이후 회차 변경</td>"
          .     "<td style='padding:8px; border:1px solid #ddd;'>" . ($updateFollowings ? '예' : '아니오') . "</td></tr>"
          . "</table>"
          . "<p style='color:#999; font-size:12px;'>이 메일은 자동 발송된 알림입니다.</p>"
          . "</div>";

      foreach ($recipients as $to) {
          $mailMime = App::load('\\Component\\Mail\\MailMime');
          $mailMime
              ->setFrom($senderEmail, $mallNm)
              ->setTo($to, '관리자')
              ->setSubject($subject)
              ->setHtmlBody($body)
              ->send();
      }
  }
}
