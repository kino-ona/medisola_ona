<?php
namespace Controller\Front\Subscription;

use App;
use Session;
use Request;
use Exception;
use Framework\Utility\ArrayUtils;

class LayerCouponApplyController extends \Controller\Front\Controller 
{
    public function index()
    {
        try {
            if (!Request::isAjax()) {
                throw new Exception('Ajax ' . __('전용 페이지 입니다.'));
            }

            // 로그인 체크
            if(Session::has('member')) {
                $coupon = App::load(\Component\Coupon\Coupon::class);
                $post = Request::post()->toArray();
                
                if ($post['cartSno']) {
                    $obj = App::load(\Component\Subscription\Cart::class);
                    
                    $info = $obj->getCartInfo($post['cartSno']);
                    $post = array_merge($post, $info);
                }
                
                $goodsPriceArr = [
                    'goodsCnt'=>$post['goodsCnt'],
                    'goodsPriceSum'=>$post['goodsPriceSum'],
                    'optionPriceSum'=>$post['optionPriceSum'],
                    'optionTextPriceSum'=>$post['optionTextPriceSum'],
                    'addGoodsPriceSum'=>$post['addGoodsPriceSum'],
                ];

                // 상품상세에서 다른 옵션에 적용된 쿠폰 설정
                if($post['couponApplyNotNo']) {
                    $exceptMemberCouponNoArr = array_column($post['couponApplyNotNo'],'value');
                    $exceptMemberCouponNoString = implode(INT_DIVISION,$exceptMemberCouponNoArr);
                    $exceptMemberCouponNoArr = explode(INT_DIVISION,$exceptMemberCouponNoString);
                    $exceptMemberCouponNoArr = ArrayUtils::removeEmpty($exceptMemberCouponNoArr);
                }
                if($post['couponApplyNo']) {
                    // 이번 옵션에 사용된 쿠폰
                    $nowMemberCouponNoArr = explode(INT_DIVISION,$post['couponApplyNo']);
                    // 사용된 쿠폰 배열에서 이번 옵션에 사용된 쿠폰 회원쿠폰고유번호는 제거하여 변경 시 노출되도록 함
                    $removeMemberCouponNoArr = [];
                    foreach($nowMemberCouponNoArr as $val) {
                        $removeMemberCouponNoArr[] = array_search($val,$exceptMemberCouponNoArr);
                    }
                    // 조건 필터링
                    foreach ($removeMemberCouponNoArr as $val) {
                        unset($exceptMemberCouponNoArr[$val]);
                    }
                }
                // 해당 상품의 사용가능한 회원쿠폰 리스트
                $memberCouponArrData = $coupon->getGoodsMemberCouponList($post['goodsNo'],Session::get('member.memNo'),Session::get('member.groupSno'),$exceptMemberCouponNoArr,$nowMemberCouponNoArr);

                if(is_array($memberCouponArrData)){
                    $memberCouponNoArr = array_column($memberCouponArrData,'memberCouponNo');
                    if ($memberCouponNoArr) {
                        $memberCouponNoString = implode(INT_DIVISION, $memberCouponNoArr);
                        // 해당 상품의 사용가능한 회원쿠폰 리스트를 보기용으로 변환
                        $convertMemberCouponArrData = $coupon->convertCouponArrData($memberCouponArrData);
                        // 해당 상품의 사용가능한 회원쿠폰의 정율도 정액으로 계산된 금액
                        $convertMemberCouponPriceArrData = $coupon->getMemberCouponPrice($goodsPriceArr, $memberCouponNoString);
                    }
                }
                
                /* 정기결제 장바구니에 속해있는 쿠폰 제외 START */
                $db = App::load('DB');
                
                $couponList = [];
                $memNo = Session::get("member.memNo");
                $sql = "SELECT memberCouponNo FROM wm_subscription_cart WHERE memNo='{$memNo}' AND memberCouponNo <> ''";
                if ($list = $db->query_fetch($sql)) {
                    foreach ($list as $li) {
                       $couponNo = explode(INT_DIVISION, $li['memberCouponNo']);
                       $couponList = array_merge($couponList, $couponNo);
                    }
                }
                
               
                $couponList = array_unique($couponList);
                foreach ($memberCouponArrData as $k => $v) {
                    if (in_array($v['memberCouponNo'], $couponList)) {
                        unset($memberCouponArrData[$k]);
                    } else {    
                        $memberCouponArrData[$k] = $v;
                    }
                }
                
                $memberCouponArrData = array_values($memberCouponArrData);
                /* 정기결제 장바구니에 속해있는 쿠폰 제외 END */
                
                $this->setData('memberCouponArrData', $memberCouponArrData);
                $this->setData('convertMemberCouponArrData', $convertMemberCouponArrData);
                $this->setData('convertMemberCouponPriceArrData', $convertMemberCouponPriceArrData);
                $this->setData('goodsNo', $post['goodsNo']);
                $this->setData('couponApplyNo', $post['couponApplyNo']);
                $this->setData('exceptMemberCouponNoArr', $exceptMemberCouponNoArr);
                $this->setData('nowMemberCouponNoArr', $nowMemberCouponNoArr);
                $this->setData('optionKey', $post['optionKey']);
            } else {
                $this->json([
                    'error' => 10,
                    'message' => __('로그인하셔야 해당 서비스를 이용하실 수 있습니다.'),
                ]);
            }
        } catch (Exception $e) {
            $this->json([
                'error' => 0,
                'message' => $e->getMessage(),
            ]);
        }
    }
}