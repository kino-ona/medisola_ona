<?php

namespace Controller\Front\Member;

use Component\Member\Util\MemberUtil;
use Component\Member\Member;
use Component\SiteLink\SiteLink;
use Component\Medisola\MedisolaAuthorizeApi;

class MedisolaAuthorizeController extends \Bundle\Controller\Front\Member\LoginController
{
    public function index()
    {
        $request = \App::getInstance('request');
        $session = \App::getInstance('session');

        $responseType = $request->get()->get('response_type');
        $clientId = $request->get()->get('client_id');
        $redirectUri = $request->get()->get('redirect_uri');
        $devIssueCodeUri = $request->get()->get('dev_issue_code_uri');

        $appAuthorizeApi = new MedisolaAuthorizeApi();
        $appAuthorize = $session->get(MedisolaAuthorizeApi::SESSION_APP_AUTHORIZE);
        if(isset($appAuthorize) && !isset($responseType) && !isset($clientId) && !isset($redirectUri)) {
            $responseType = $appAuthorize['response_type'];
            $clientId = $appAuthorize['client_id'];
            $redirectUri = $appAuthorize['redirect_uri'];
            $devIssueCodeUri = $appAuthorize['dev_issue_code_uri'];
        }
        // member/login.php에서 세션이 있으면 redirect가 반복적으로 호출되어서 그거 방지.
        $session->del(MedisolaAuthorizeApi::SESSION_APP_AUTHORIZE);
        $appAuthorizeApi->verifyParam($responseType, $clientId, $redirectUri);

        if(MemberUtil::isLogin()) {
            // 이메일 로그인으로 연동할때 인증이 완료되어도 로그인 페이지가 그대로 남아 있는것을 방지하기 위해서 다른 페이지 view 사용
            $this->getView()->setPageName("member/medisola_authorize_done");
            $member = $session->get(Member::SESSION_MEMBER_LOGIN);
            $code = $appAuthorizeApi->issueCode($member['memNo'], $clientId, $devIssueCodeUri);
            if(strpos($redirectUri, '?') === false) {
                $this->setData('redirectUri', urlencode($redirectUri) . '?code=' . $code);
            } else {
                $this->setData('redirectUri', $redirectUri . '&code=' . $code);
            }
        } else {
            parent::index();

            $session->set(MedisolaAuthorizeApi::SESSION_APP_AUTHORIZE, [
                'response_type' => $responseType,
                'client_id' => $clientId,
                'redirect_uri' => $redirectUri,
                'expire_at' => time() + 600,
                'dev_issue_code_uri' => $devIssueCodeUri,
            ]);

            $siteLink = new SiteLink();
            $returnUrl = $siteLink->link('../member/medisolaAuthorize.php', 'ssl');
            $this->setData('returnUrl', $returnUrl);
            $this->setData('kakaoReturnUrl', $returnUrl);
        }
    }
}
