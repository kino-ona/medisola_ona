<?php

namespace Controller\Front\GiftOrder;

use App;
use Request;
use Framework\Debug\Exception\AlertOnlyException;
use Component\GiftOrder\GiftOrder;

/**
* 배송정보 입력 
*
* @author webnmobile
*/
class UpdateAddressController extends \Controller\Front\Controller
{
	public function index() 
	{
		try {
			$in = Request::request()->all();
			$db = App::load(\DB::class);
			$giftorder = App::load(GiftOrder::class);
			
			if (empty($in['orderNo']))
				throw new AlertOnlyException("잘못된 접근입니다.");
			
			$required = [
				'receiverName' => '수령자명',
				'receiverZonecode' => '배송지 주소',
				'receiverAddress' => '배송지 주소',
				'receiverAddressSub' => '상세주소',
				'receiverCellPhone' => '연락처',
			];
			
			foreach ($required as $k => $v) {
				if (empty($in[$k])) {
					throw new AlertOnlyException($v."을(를) 입력하세요.");
				}
			}

            /* 2023-01-04 웹앤모바일 받는 사람이 주소 변경했을 때 */
            if ($in['changeData']) {
                $param = [
                    'receiverName = ?',
                    'receiverZonecode = ?',
                    'receiverAddress = ?',
                    'receiverAddressSub = ?',
                    'receiverCellPhone = ?',
                    'orderMemo = ?',
                    'giftUpdateStamp = ?',
                ];

                $bind = [
                    'ssssssis',
                    $in['receiverName'],
                    $in['receiverZonecode'],
                    $in['receiverAddress'],
                    $in['receiverAddressSub'],
                    $in['receiverCellPhone'],
                    $in['orderMemo'],
                    time(),
                    $in['orderNo'],
                ];
            } else {
                $param = [
                    'receiverName = ?',
                    'receiverZonecode = ?',
                    'receiverAddress = ?',
                    'receiverAddressSub = ?',
                    'receiverCellPhone = ?',
                    'orderMemo = ?',
                ];

                $bind = [
                    'sssssss',
                    $in['receiverName'],
                    $in['receiverZonecode'],
                    $in['receiverAddress'],
                    $in['receiverAddressSub'],
                    $in['receiverCellPhone'],
                    $in['orderMemo'],
                    $in['orderNo'],
                ];
            }
            /* 2023-01-04 웹앤모바일 받는 사람이 주소 변경했을 때 끝 */
			
			$affectedRows = $db->set_update_db(DB_ORDER_INFO, $param, "orderNo = ?", $bind);
			if ($affectedRows <= 0) 
					throw new AlertOnlyException("배송지 입력에 실패하였습니다.");
            
			// 받는분의 배송지 입력시 p2 -> p1으로 변경 (사방넷 관련)
			//$giftorder -> GiftStatusReChange($in['orderNo']);
			
            echo "<script>alert('배송지 입력이 완료되었습니다.');parent.location.href='view.php?giftCard=1&orderNo=".$in['orderNo']. "'</script>";
		} catch (AlertOnlyException $e) {
			throw $e;
		}
		exit;
	}
}