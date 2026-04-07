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
namespace Controller\Front\Mypage;

use Request;

/**
 * 주문 상세 보기 페이지
 *
 * @author artherot
 * @version 1.0
 * @since 1.0
 * @copyright Copyright (c), Godosoft
 */
class OrderViewController extends \Bundle\Controller\Front\Mypage\OrderViewController
{
    public function index()
    {
        parent::index();
		
		$firstDelivery = \App::load(\Component\Wm\FirstDelivery::class);
		
        /* 2023-06-09 웹앤모바일 선물하기 상품일 때 주소 미노출 수정 */
        $useGift = \App::load('\\Component\\Wm\\UseGift');
        $orderData = $this->getData('orderData');
        foreach ($orderData as $key => $val) {
            foreach ($val as $key2 => $val2) {
                $isGiftOrder = $useGift->getGiftUse($val['orderNo']);
                $orderData[$key]['goods'][0]['isGiftOrder'] = $isGiftOrder['isGiftOrder'];
                $addGiftAddress = $useGift->getAddGiftAddress($val['orderNo']);
                $orderData[$key]['goods'][0]['addGiftAddress'] = $addGiftAddress['addGiftAddress'];
                $orderData[$key]['goods'][0]['giftUpdateStamp'] = $addGiftAddress['giftUpdateStamp'];
            }
        }
        /* 2023-06-09 웹앤모바일 선물하기 상품일 때 주소 미노출 수정 */
		
		foreach($orderData as $key => $value){
            $filteredGoodsCnt = 0;
			foreach($value['goods'] as $key_1 => $goods){
				$firstDate = $firstDelivery -> getFirstDeliveryOrder($goods['sno']);
                if($firstDate){
                    $firstDate = substr($firstDate, 0, 10);
                    $firstDate = substr($firstDate, 5 , 5);
                    $firstDate = str_replace('-' , '월 ' , $firstDate);
                    $firstDate .= '일';

                    $orderData[$key]['goods'][$key_1]['firstDate'] = $firstDate;
                }
				
                if ($goods["isComponentGoods"] != true) {
					$filteredGoodsCnt++;
				}
			}
            $orderData[$key]['orderGoodsCnt'] = $filteredGoodsCnt;
		}
		$this->setData('orderData', $orderData);
    }
}