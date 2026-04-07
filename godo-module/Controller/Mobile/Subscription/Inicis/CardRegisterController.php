<?php

namespace Controller\Mobile\Subscription\Inicis;

use App;
use Request;
use Framework\Debug\Exception\AlertRedirectException;

class CardRegisterController extends \Controller\Mobile\Controller
{
    public function index()
    {
        header('Set-Cookie: same-site-cookie=foo; SameSite=Lax');
        header('Set-Cookie: cross-site-cookie=bar; SameSite=None; Secure');
        setcookie('samesite-test', '1', 0, '/; samesite=strict');

        $obj = App::load("\Component\Subscription\Subscription");
        $httpUtil = App::load("\Component\Subscription\Inicis\HttpClient");
        $util = App::load("\Component\Subscription\Inicis\INIStdPayUtil");
        $pg = $obj->getPgInstance();
        $pgCards = $pg->getPgCards(); // 카드사 코드
        $pgBanks = $pg->getPgBanks(); // 은행 코드
        $subCfg = $obj->getCfg();
        $db = App::load('DB');

        $post = Request::post()->toArray();
        $get = Request::get()->toArray();
        $in = array_merge($post, $get);


        $ordno = $in['orderid'];
        //$password = $in['merchantreserved'];
        $billing_key = "";

        $merchantData = explode("||",$in['merchantreserved']);
        $password = $merchantData[0];
        $memNo = $merchantData[1];

        $period = $merchantData[2];
        $deliveryEa = $merchantData[3];
        $cartSno = $merchantData[4];

        if (!empty($cartSno))
            $cartSno_ = explode(",", $cartSno);

        $bool = true;
        //--- 로그 생성
        $settlelog = '';
        $settlelog .= '====================================================' . PHP_EOL;
        $settlelog .= 'PG명 : 이니시스 정기결제(모바일)' . PHP_EOL;
        $settlelog .= '주문번호 : ' . $ordno . PHP_EOL;
        $settlelog .= '거래번호 : ' . $in['tid'] . PHP_EOL;
        $settlelog .= "결과코드 : " . $in["resultcode"] . PHP_EOL;
        $settlelog .= '결과내용 : ' . strip_tags($in['resultmsg']) . PHP_EOL;;
        $settlelog .= '카드사 코드 : ' . $in['cardcd'] . ' - ' . $pgCards[$in['cardcd']] . PHP_EOL;
        if ($in['resultcode'] == '00') { //  성공
            $settlelog .= "거래상태 : 거래성공" . PHP_EOL;
            $billing_key = $in['billkey'];
            $bool = true;
        } else { // 실패
            $settlelog .= "거래상태 : 거래실패" . PHP_EOL;
            $bool = false;
        }

        $settlelog .= '====================================================' . PHP_EOL;


        if ($bool && $billing_key && $password) { // 빌링키 발급 성공 시

            $bankNm = $pgCards[$in['cardcd']];
            $hash = $obj->getPasswordHash($password);
            $payKey = $obj->encrypt($billing_key);
            //$memNo = \Session::get("member.memNo");
            $bankNm = $db->escape($bankNm);

            if (empty($bankNm))
                $bankNm = $pgCards[$in['cardcd']];

            $settlelog = $db->escape($settlelog);

            $sql = "INSERT INTO wm_subscription_cards 
                            SET 
                                memNo='{$memNo}',
                                regStamp='" . time() . "',
                                cardNm='{$bankNm}',
                                payKey='{$payKey}',
                                settleLog='{$settlelog}',
                                password='{$hash}'";

            if ($db->query($sql)) {
                $returnUrl = \Session::get("cardReturnUrl");
                \Session::set("cardReturnUrl", "../../subscription/card_list.php");
                $url = "../../subscription/card_list.php";
                if ($returnUrl)
                    $url = $returnUrl;


                if (!empty($period)) {
                    $url = "../../subscription/order.php";
                    ?>

                    <form method="post" action="<?= $url ?>" name="card_register">
                        <input type="hidden" name="period" value="<?= $period ?>">
                        <input type="hidden" name="deliveryEa" value="<?= $deliveryEa ?>">
                        <?php foreach ($cartSno_ as $k) { ?>
                            <input type="hidden" name="cartSno[]" value="<?= $k ?>">
                        <?php } ?>
                    </form>
                    <script>
                        document.card_register.submit();
                    </script>

                    <?php
                } else {
                    $this->js("window.location.href='{$url}';");
                }
                
                exit;
            }
        }

        if ($get['isMypage']) {
            $url = "../../subscription/card_list.php";
        } else {
            $url="../../subscription/order.php";
        }

        $this->js("alert('카드등록이 취소되었습니다.'); window.location.href='{$url}';");
    }
}