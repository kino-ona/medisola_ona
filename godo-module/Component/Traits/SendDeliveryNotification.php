<?php

namespace Component\Traits;

use App;

/**
 * 배송예정일 변경 알림 메일 발송
 *
 * Front/Mobile OrderPsController에서 공통 사용
 */
trait SendDeliveryNotification
{
    /**
     * 배송예정일 변경 시 관리자에게 알림 메일 발송
     *
     * @param string $orderNo 주문번호
     * @param string $oldDate 변경 전 날짜
     * @param string $newDate 변경 후 날짜
     * @param bool $updateFollowings 이후 회차 변경 여부
     * @return array 수신자별 발송 결과
     */
    protected function sendDeliveryDateChangeNotification($orderNo, $oldDate, $newDate, $updateFollowings)
    {
        $recipients = [
            'help@medisola.co.kr',
            'gh.kim@medisola.co.kr',
            'kyengeun.ko@medisola.co.kr',
            'jungmi.kim@medisola.co.kr'
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

        $results = [];
        foreach ($recipients as $to) {
            $mailMime = App::load('\\Component\\Mail\\MailMime');
            $sendResult = $mailMime
                ->setFrom($senderEmail, $mallNm)
                ->setTo($to, '관리자')
                ->setSubject($subject)
                ->setHtmlBody($body)
                ->send();
            $results[$to] = $sendResult;
        }
        return $results;
    }
}
