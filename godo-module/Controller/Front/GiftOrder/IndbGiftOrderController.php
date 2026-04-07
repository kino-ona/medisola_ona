<?php

namespace Controller\Front\GiftOrder;

use App;
use Component\Wm\UseGift;
use Request;
use Session;
use Framework\Debug\Exception\AlertOnlyException;
use Component\Database\DBTableField;

/**
* 선물하기 DB처리 관련 
*
* @author webnmobile
*/
class IndbGiftOrderController extends \Controller\Front\Controller
{
	public function index()
	{	
		try {
			$in = Request::request()->all();
			$db = App::load(\DB::class);
			$giftOrder = App::load(\Component\GiftOrder\GiftOrder::class);
            $getBanner = \App::load(UseGift::class);

            switch ($in['mode']) {
				/* 선물 요청하기 */
				case "gift_request" : 
					if (empty($in['goodsNo']))
						throw new AlertOnlyException('잘못된 접근입니다.');
					
					if (empty($in['receiverName']))
						throw new AlertOnlyException("선물받는분 입력하세요.");
					
					if (empty($in['receiverZonecode']) || empty($in['receiverAddress']) || empty($in['receiverAddressSub'])) {
						//throw new AlertOnlyException("주소를 입력하세요.");
					}
					
					$in['orderCellPhone'] = preg_replace("/[^0-9]/", "", $in['orderCellPhone']);
					$in['receiverCellPhone'] = preg_replace("/[^0-9]/", "", $in['receiverCellPhone']);

					if(!preg_match("/^01[0-9]{8,9}$/", $in['orderCellPhone'])) {
						throw new AlertOnlyException("선물요청 휴대전화번호 형식이 올바르지 않습니다.");
					}
					
					if(!preg_match("/^01[0-9]{8,9}$/", $in['receiverCellPhone'])) {
						throw new AlertOnlyException("받는분 휴대전화번호 형식이 올바르지 않습니다.");
					}
					
					$in['memNo'] = Session::get("member.memNo");
					
					$arrBind = $db->get_binding(DBTableField::tableWmGiftRequest(), $in, "insert", array_keys($in));
	
					$db->set_insert_db("wm_giftRequest", $arrBind['param'], $arrBind['bind'], "y");
					$affectedRows = $db->affected_rows();
					if ($affectedRows <= 0)
						throw new AlertOnlyException("요청에 실패하였습니다.");
					
					$idx = $db->insert_id();
						
					$result = $giftOrder->sendGiftRequestSms($idx);
					if (!$result)
						throw new AlertOnlyException("요청에 실패하였습니다.");
					
					if ($in['isMobile']) {
						return $this->js("alert('선물을 요청하였습니다.');parent.close();");
					} else {
						return $this->js("alert('선물을 요청하였습니다.');parent.parent.wmLayer.close();");
					}
					break;
				
				// 약관동의 체크
                case 'agree':
                    $getBanner->updateGiftAgree($in['orderNo']);
                    echo "<script>location.href='order_address.php?giftCard=1&orderNo=".$in['orderNo']. "'</script>";
                    break;
                    
                case 'checkOrderStatus' :
                    $result = $getBanner->checkOrderStatus($in['orderNo']);
                    echo json_encode($result['orderStatus']);
                    break;
			}
		} catch (AlertOnlyException $e) {
			throw $e;
		}
		exit;
	}
}