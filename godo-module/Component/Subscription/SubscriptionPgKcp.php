<?php
namespace Component\Subscription;

class SubscriptionPgKcp extends \Component\Subscription\SubscriptionPg 
{
    /* PG 설정 추출 */
    public function getPgCfg()
    {
        $cfg = $this->getCfg();
        if ($cfg['useMode'] == 'real') {
            $cfg['gwUrl']    = "paygw.kcp.co.kr";
            $cfg['jsUrl']    = "https://pay.kcp.co.kr/plugin/payplus_web.jsp";
        } else {
            $cfg['gwUrl']    = "testpaygw.kcp.co.kr";
            $cfg['siteCd'] = "BA001";
            $cfg['siteKey'] = "2T5.LgLrH--wbufUOvCqSNT__";
            $cfg['groupId'] = "BA0011000348";
            $cfg['mallNm'] = "TEST MALL";
            $cfg['jsUrl']    = "https://testpay.kcp.co.kr/plugin/payplus_web.jsp";
            if ($this->isMobile) { // 모바일인 경우 
                $cfg['siteCd'] = "A52Q7";
                $cfg['groupId'] = "A52Q71000489";
            }
        }
        $cfg['modulePath'] = dirname(__FILE__) . "/../../../subscription_module/kcp";
        $cfg['pg_gate'] = "subscription/kcp/pg_gate.html";
        return $cfg;
    }
}