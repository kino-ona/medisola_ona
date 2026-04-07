<?php

namespace Controller\Admin\Order;

use App;
use Request;

class IndbSubscriptionController extends \Controller\Admin\Controller
{
    public function index()
    {
        $get = Request::get()->toArray();
        $post = Request::post()->toArray();
        $in = array_merge($get, $post);
        $db = App::load('DB');
        $obj = App::load(\Component\Subscription\Subscription::class);
        switch ($in['mode']) {
            case "update_card_info" :
                if (empty($in['idx']))
                    return $this->layerNotReload("잘못된 접근입니다.");

                $in['memo'] = $db->escape($in['memo']);
                $setData = "memo='{$in['memo']}'";
                if ($in['password']) {
                    if (!is_numeric($in['password']))
                        return $this->layerNotReload("비밀번호는 숫자로 입력하세요.");

                    if (strlen($in['password']) != 6)
                        return $this->layerNotReload("비밀번호는 6자 숫자로만 입력하세요.");

                    $hash = $obj->getPasswordHash($in['password']);
                    $setData .= ",password='{$hash}'";
                }

                $sql = "UPDATE wm_subscription_cards SET " . $setData . " WHERE idx='{$in['idx']}'";
                if ($db->query($sql))
                    return $this->layer("수정되었습니다.");

                return $this->layer("수정에 실패하였습니다.");
                break;
            case "update_card_list" :
                if (empty($in['idx']))
                    return $this->layerNotReload("수정할 결제카드를 선택하세요.");

                foreach ($in['idx'] as $idx) {
                    $memo = $db->escape($in['memo'][$idx]);
                    $password = $in['password'][$idx];
                    $setData = "memo='{$memo}'";

                    if ($password) {
                        $hash = $obj->getPasswordHash($password);
                        $setData .= ",password='{$hash}'";
                    }

                    $sql = "UPDATE wm_subscription_cards SET {$setData} WHERE idx='{$idx}'";
                    $db->query($sql);
                }

                $this->layer("수정되었습니다.");
                break;
            case "delete_card_list" :
                if (empty($in['idx']))
                    return $this->layerNotReload("삭제할 결제카드를 선택하세요.");

                foreach ($in['idx'] as $idx)
                    $db->query("DELETE FROM wm_subscription_cards WHERE idx='{$idx}'");

                $this->layer("삭제되었습니다.");
                break;
            case "cancelOrder" :
                if (empty($in['orderNo']))
                    return $this->layerNotReload("잘못된 접근입니다.");

                if ($obj->cancel($in['orderNo']))
                    return $this->layer("처리되었습니다.");

                return $this->layer("처리에 실패하였습니다.");
                break;
            case "change_card" :
                if (empty($in['uid']))
                    return $this->layerNotReload("잘못된 접근입니다.");

                if (empty($in['idx_card']))
                    return $this->layerNotReload("결제카드를 선택하세요.");

                $sql = "UPDATE wm_subscription_apply SET idx_card='{$in['idx_card']}' WHERE uid='{$in['uid']}'";
                if ($db->query($sql))
                    return $this->layer("변경되었습니다.");

                return $this->layer("변경에 실패하였습니다.");
                break;
            case "manual_pay" :
                if (empty($in['idx']))
                    return $this->layerNotReload("잘못된 접근입니다.");

                if ($obj->pay($in['idx'], false, true))
                    return $this->layer("처리되었습니다.");

                return $this->layer("처리에 실패하였습니다.");
                break;
            case "change_schedule_list" :
                if (empty($in['idx']))
                    return $this->layerNotReload("변경할 회차를 선택하세요.");

				if ($in['procMode'] == 'date') {
					foreach ($in['idx'] as $idx) {
						$in['schedule_date_org'][$idx] = $in['schedule_date'][$idx] ?? '';
					}
					$pre = '0000-00-00';
					foreach ($in['schedule_date_org'] as $key => $val) {
						if ($pre >= $val) return $this->layerNotReload("결제예정일을 회차순으로 설정해주세요");
						$pre = $val;
					}
					// 검증만 하고 exit 제거 → 아래 foreach에서 실제 UPDATE 수행
				}

                foreach ($in['idx'] as $idx) {
                    switch ($in['procMode']) {
                        case "unpaid":
                            $sql = "UPDATE wm_subscription_schedule_list SET isPayed='0' WHERE idx='{$idx}'";
                            break;
                        case "paid":
                            $sql = "UPDATE wm_subscription_schedule_list SET isPayed='1' WHERE idx='{$idx}'";
                            break;
                        case "date" :
                            if (!$schedule_date = $in['schedule_date'][$idx])
                                continue;

                            $stamp = strtotime($schedule_date);
                            $sql = "UPDATE wm_subscription_schedule_list SET schedule_stamp='{$stamp}' WHERE idx='{$idx}'";
                            break;
                        case "stop" :
                            $sql = "UPDATE wm_subscription_schedule_list SET isStop='1' WHERE idx='{$idx}'";
                            break;
                        case "open" :
                            $sql = "UPDATE wm_subscription_schedule_list SET isStop='0' WHERE idx='{$idx}'";
                            break;
                    }
                    if ($sql)
                        $db->query($sql);


                }
                $this->layer("변경되었습니다.");
                break;
            case "register_schedule" :
                if (empty($in['uid']))
                    return $this->layerNotReload("잘못된 접근입니다.");

                if (empty($in['delivery_ea']))
                    return $this->layerNotReload("배송횟수를 선택하세요.");

                if (empty($in['period']))
                    return $this->layerNotReload("배송주기를 선택하세요.");


                $obj->extendSchedule($in['uid'], $in['delivery_ea'], $in['period']);

                $this->layer("처리되었습니다.");

                break;
            case "update_schedule" :
                if (empty($in['uid']))
                    return $this->layerNotReload("수정할 정기결제신청건을 선택하세요.");

                foreach ($in['uid'] as $uid) {
                    $autoExtend = $in['autoExtend'][$uid] ? 1 : 0;
                    $sql = "UPDATE wm_subscription_apply SET autoExtend='{$autoExtend}' WHERE uid='{$uid}'";
                    $db->query($sql);
                }

                $this->layer('수정되었습니다.');
                break;
            case "delete_schedule" :
                if (empty($in['uid']))
                    return $this->layerNotReload("삭제할 정기결제신청건을 선택하세요.");

                foreach ($in['uid'] as $uid) {
                    $sql = "DELETE FROM wm_subscription_apply WHERE uid='{$uid}'";
                    $db->query($sql);
                    $sql = "DELETE FROM wm_subscription_apply_items WHERE uid='{$uid}'";
                    $db->query($sql);
                    $sql = "DELETE FROM wm_subscription_schedule_list WHERE uid='{$uid}'";
                    $db->query($sql);
                }

                $this->layer('삭제되었습니다.');
                break;
            case "delete_schedule_each" :
                if (empty($in['idx']))
                    return $this->layerNotReload("잘못된 접근입니다.");

                $sql = "DELETE FROM wm_subscription_schedule_list WHERE idx='{$in['idx']}'";
                if ($db->query($sql))
                    return $this->layer("삭제되었습니다.");

                return $this->layer("삭제에 실패하였습니다.");
                break;
            case "change_period" :
                if (empty($in['uid']))
                    return $this->layerNotReload("잘못된 접근입니다.");

                if (empty($in['period']))
                    return $this->layerNotReload("배송주기를 선택하세요.");

                $sql = "UPDATE wm_subscription_apply SET period='{$in['period']}' WHERE uid='{$in['uid']}'";
                if ($db->query($sql))
                    return $this->layer("변경되었습니다.");

                return $this->layer("변경에 실패하였습니다.");
                break;
            case "update_delivery_info" :
                if (empty($in['uid']))
                    return $this->layerNotReload("잘못된 접근입니다.");

                $orderPhone = $orderCellPhone = $receiverPhone = $receiverCellPhone;
                if ($in['orderPhone'][0] && $in['orderPhone'][1] && $in['orderPhone'][2])
                    $orderPhone = implode("-", $in['orderPhone']);

                if ($in['orderCellPhone'][0] && $in['orderCellPhone'][1] && $in['orderCellPhone'][2])
                    $orderCellPhone = implode("-", $in['orderCellPhone']);

                if ($in['receiverPhone'][0] && $in['receiverPhone'][1] && $in['receiverPhone'][2])
                    $receiverPhone = implode("-", $in['receiverPhone']);

                if ($in['receiverCellPhone'][0] && $in['receiverCellPhone'][1] && $in['receiverCellPhone'][2])
                    $receiverCellPhone = implode("-", $in['receiverCellPhone']);

                foreach ($in as $k => $v) {
                    $in[$k] = $db->escape($v);
                }

                $sql = "UPDATE wm_subscription_apply 
                                SET 
                                    orderName='{$in['orderName']}',
                                    orderPhone='{$orderPhone}',
                                    orderCellPhone='{$orderCellPhone}',
                                    orderZonecode='{$in['orderZonecode']}',
                                    orderZipcode='{$in['orderZipcode']}',
                                    orderAddress='{$in['orderAddress']}',
                                    orderAddressSub='{$in['orderAddressSub']}',
                                    receiverName='{$in['receiverName']}',
                                    receiverPhone='{$receiverPhone}',
                                    receiverCellPhone='{$receiverCellPhone}',
                                    receiverZonecode='{$in['receiverZonecode']}',
                                    receiverAddress='{$in['receiverAddress']}',
                                    receiverAddressSub='{$in['receiverAddressSub']}',
                                    orderMemo='{$in['orderMemo']}'
                              WHERE uid='{$in['uid']}'";
                if ($db->query($sql))
                    return $this->layer("수정되었습니다.");

                return $this->layer("수정에 실패하였습니다.");
                break;
            case "change_goods_option" :
                if (empty($in['idx']))
                    return $this->layerNotReload("변경할 상품을 선택하세요.");

                foreach ($in['idx'] as $idx) {
                    $optionSno = "";
                    if ($in['optionSno'][$idx])
                        $optionSno = implode(INT_DIVISION, $in['optionSno'][$idx]);

                    $sql = "UPDATE wm_subscription_apply_items SET optionSno='{$optionSno}' WHERE idx='{$idx}'";
                    $db->query($sql);
                }

                $this->layer("수정되었습니다.");
                break;
            case "batch" :
                if (empty($in['idx']))
                    return $this->layerNotReload("처리할 신청건을 선택하세요.");

                $obj->setMode($in['proc_mode']);
                foreach ($in['idx'] as $idx) {
                    $obj->setIdx($idx)->setBoolean(false)->process();
                }

                $this->layer("처리되었습니다.");
                break;
                
            case 'stopSubscription' :
                $obj->stopSubscription($in['uid']);
                $this->json(['ok' => true, 'msg' => '해지처리되었습니다.']);
                break;
        }
        exit;
    }
}