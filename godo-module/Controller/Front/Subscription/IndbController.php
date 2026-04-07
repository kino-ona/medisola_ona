<?php

namespace Controller\Front\Subscription;

use App;
use Request;

class IndbController extends \Controller\Front\Controller 
{
    private $db;
    private $obj;
    private $cartObj;
    private $in;
    
    public function __construct()
    {
        parent::__construct();
        
        $this->db = App::load('DB');
        $this->obj = App::load(\Component\Subscription\Subscription::class);
        $this->cartObj = App::load(\Component\Subscription\Cart::class);
        $get = Request::get()->toArray();
        $post = Request::post()->toArray();
       
        $this->in = array_merge($get, $post);
    }
    public function index()
    {
        $in = $this->in;
        switch ($in['mode']) {
            case "delete_card" : 
                if (empty($in['idx_card']))
                    return $this->js("alert('결제카드를 선택하세요.');");
                
                $res = $this->obj->setCard($in['idx_card'])->delCard();
                if ($res)
                    return $this->js("parent.location.reload();");
                
                return $this->js("alert('삭제에 실패하였습니다.");
                break;
            case "delete_cart" : 
                if (empty($in['cartSno']))
                    return $this->js("alert('삭제할 상품을 선택하세요.');");
                
                foreach ($in['cartSno'] as $idx) {
                    $this->cartObj->set($idx)->del();
                }
                
                return $this->js("parent.location.reload();");
                break;
            case "change_cart" : 
                if ($in['cart']['cartSno']) {
                    $idx = $in['cart']['cartSno'];
                    $goodsCnt = $in['cart']['goodsCnt']?$in['cart']['goodsCnt']:1;
                    
                    $this->cartObj->set($idx, $goodsCnt)->changeEa();
                }
                
                return $this->js("parent.location.reload();");
                break;  
             case "coupon_delete" : 
                if ($in['cart']['cartSno']) {
                    $idx = $in['cart']['cartSno'];
                    $this->cartObj->set($idx)->setMode('coupon_delete')->del();
                }
                
                return $this->js("parent.location.reload();");
                break;
             case "change_address" : 
                if (empty($in['uid']))
                    return $this->js("alert('잘못된 접근입니다.');");
                
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
                    $in[$k] = $this->db->escape($v);
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
                if ($this->db->query($sql))
                    return $this->js("alert('변경되었습니다.');parent.parent.location.reload();");
                
                return $this->js("alert('변경에 실패하였습니다.');");
                break;
             case "cancel" : 
                if (empty($in['idx']))
                    return $this->js("alert('잘못된 접근입니다.');");
                
                if (!$info = $this->obj->getSubscription($in['idx']))
                    return $this->js("alert('신청 정보가 존재하지 않습니다.');");
                
                $memNo = \Session::get("member.memNo");
                if ($memNo != $info['memNo'])
                    return $this->js("alert('본인이 신청하신건만 취소하실 있습니다.');");
                
                $cfg = $this->obj->getCfg();
                $cancelEa = $cfg['cancelEa']?$cfg['cancelEa']:0;
                $orderCnt = 0;
                if ($schedule_list = $this->obj->getScheduleListByUid($info['uid'])) {
                   foreach ($schedule_list as $v) {
                      if ($v['order']) {                                
                          $s = substr($v['order']['orderStatus'], 0, 1);
                           if (in_array($s, ['p','g','d','s']))
                               $orderCnt++;
                       }
                    }
                }

                $cancelPossible = true;
                if ($cancelEa > 0 && $orderCnt < $cancelEa)
                     $cancelPossible = false;

                 if (!$cancelPossible)
                     return $this->js("alert('취소가 불가합니다.');");
                 
                 $sql = "UPDATE wm_subscription_schedule_list SET isStop='1' WHERE idx='{$in['idx']}'";
                 if ($this->db->query($sql))
                     return $this->js("alert('취소되었습니다.');parent.location.reload();");
                 
                 return $this->js("alert('취소에 실패하였습니다.');");
                break;
             case "recover" : 
                if (empty($in['idx']))
                    return $this->js("alert('잘못된 접근입니다.');");
                
                if (!$info = $this->obj->getSubscription($in['idx']))
                    return $this->js("alert('신청 정보가 존재하지 않습니다.');");
                
                $memNo = \Session::get("member.memNo");
                if ($memNo != $info['memNo'])
                    return $this->js("alert('본인이 신청하신건만 복구하실 있습니다.');");
                
                 $sql = "UPDATE wm_subscription_schedule_list SET isStop='0' WHERE idx='{$in['idx']}'";
                 if ($this->db->query($sql))
                     return $this->js("alert('복구되었습니다.');parent.location.reload();");
                 
                 return $this->js("alert('복구에 실패하였습니다.');");
                break;
             case "change_period" : 
                if (empty($in['idx']))
                    return $this->js("alert('잘못된 접근입니다.');");
                
                if (empty($in['period']))
                    return $this->js("alert('변경할 배송주기를 선택하세요.');");
                
                $in['period'] = $this->db->escape($in['period']);
                $in['idx'] = $this->db->escape($in['idx']);
                
                $sql = "UPDATE wm_subscription_cart SET period='{$in['period']}' WHERE idx='{$in['idx']}'";
                if ($this->db->query($sql)) {
                    return $this->js('parent.parent.location.reload();');
                }
                
                return $this->js("alert('배송주기 변경에 실패하였습니다.');");
                break;
            case 'stopSubscription' :
                if ($in['uid']) {
                    $this->obj->stopSubscription($in['uid']);
                    $this->json(['ok' => true, 'msg' => '해지처리되었습니다.']);
                }
                break;
        }
        exit;
    }
}