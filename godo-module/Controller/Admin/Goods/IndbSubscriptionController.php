<?php
namespace Controller\Admin\Goods;

use Request;
use App;

class IndbSubscriptionController extends \Controller\Admin\Controller 
{
    public function index()
    {
        $post = Request::post()->toArray();
        $get = Request::get()->toArray();
        $in = array_merge($post, $get);
        $db = App::load('DB');

        switch ($in['mode']) {
            case "update_config" : 
                foreach ($in as $k => $v) {
                    if (is_array($v)) {
                        foreach ($v as $_k => $_v)
                            $in[$k][$_k] = $db->escape($_v);
                    } else {
                        $in[$k] = $db->escape($v);
                    }
                }
                $period = "";
                if ($in['period'])
                    $period = implode(",", $in['period']);

                $sql = "UPDATE wm_subscription_config 
                                SET 
                                    pg= '{$in['pg']}',
                                    useMode= '{$in['useMode']}',
                                    mid= '{$in['mid']}',
                                    signKey= '{$in['signKey']}',
                                    lightKey= '{$in['lightKey']}',
                                    discount= '{$in['discount']}', 
                                    smsDays= '{$in['smsDays']}',
                                    deliveryDays= '{$in['deliveryDays']}', 
                                    cancelEa='{$in['cancelEa']}',
                                    period='{$period}',
                                    deliveryEa='{$in['deliveryEa']}',
                                    discount='{$in['discount']}',
                                    smsPass='{$in['smsPass']}',
                                    smsTemplate= '{$in['smsTemplate']}',
                                    terms= '{$in['terms']}',
                                    cardTerms='{$in['cardTerms']}',
                                    orderGuide='{$in['orderGuide']}'";

                if ($db->query($sql))
                    return $this->layer("저장되었습니다.");

                return $this->layer("저장에 실패하였습니다.");
                break;
              case "update_goods_config_list" :
                if (empty($in['goodsNo']))
                    return $this->layerNotReload("수정할 상품을 선택하세요.");

                foreach ($in['goodsNo'] as $goodsNo) {
                    $goodsNo = (int)$goodsNo;
                    $isSubscription = $in['isSubscription'][$goodsNo] ? 1 : 0;
                    $linkedSubscriptionGoodsNo = isset($in['linkedSubscriptionGoodsNo'][$goodsNo]) ? (int)$in['linkedSubscriptionGoodsNo'][$goodsNo] : 0;

                    $arrBind = [];
                    $sql = "UPDATE " . DB_GOODS . "
                            SET isSubscription=?,
                                linkedSubscriptionGoodsNo=?
                            WHERE goodsNo=?";
                    $db->bind_param_push($arrBind, 'i', $isSubscription);
                    $db->bind_param_push($arrBind, 'i', $linkedSubscriptionGoodsNo);
                    $db->bind_param_push($arrBind, 'i', $goodsNo);
                    $db->bind_query($sql, $arrBind);
                }

                $this->layer("수정되었습니다.");
                break;
              case "update_goods_config" : 
                if (empty($in['goodsNo']))
                    return $this->layerNotReload("잘못된 접근입니다.");
                
                $period = "";
                if ($in['period'])
                    $period = implode(",", $in['period']);
                
                $db->query("DELETE FROM wm_subscription_goods_config WHERE goodsNo='{$in['goodsNo']}'");
                /*
                $sql = "INSERT INTO wm_subscription_goods_config 
                                SET 
                                    goodsNo='{$in['goodsNo']}',
                                    period='{$period}',
                                    deliveryEa='{$in['deliveryEa']}',
                                    discount='{$in['discount']}'";
                */
                $sql = "INSERT INTO wm_subscription_goods_config 
                                SET 
                                    goodsNo='{$in['goodsNo']}',
                                    discount='{$in['discount']}'";                    
                if ($db->query($sql))
                    return $this->layer("저장되었습니다.");
                
                return $this->layer("저장에 실패하였습니다.");
                break;
              case "register_holiday" : 
                if (empty($in['date']))
                    return $this->layerNotReload("공휴일을 선택하세요.");
                
                $stamp = strtotime($in['date']);
                $sql = "SELECT COUNT(*) as cnt FROM wm_subscription_holiday WHERE stamp='{$stamp}'";
                $row = $db->fetch($sql);
                if ($row['cnt'] > 0)
                    return $this->layerNotReload($in['date']."는 이미 등록된 공휴일 입니다.");
                
                 $in['memo'] = $db->escape($in['memo']);
                 $sql = "INSERT INTO wm_subscription_holiday 
                                SET 
                                    stamp='{$stamp}',
                                    memo='{$in['memo']}'";
                  if ($db->query($sql))
                      return $this->layer("등록되었습니다.");
                  
                  return $this->layerNotReload("등록에 실패하였습니다.");
                break;
              case "delete_holiday" : 
                if (empty($in['stamp']))
                    return $this->layerNotReload("삭제할 공휴일을 선택하세요.");
                
                foreach ($in['stamp'] as $stamp) {
                    $db->query("DELETE FROM wm_subscription_holiday WHERE stamp='{$stamp}'");
                }
                
                $this->layer("삭제되었습니다.");
                break;
        }
        exit;
    }
}