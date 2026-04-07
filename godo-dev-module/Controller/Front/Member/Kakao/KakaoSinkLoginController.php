<?php

namespace Controller\Front\Member\Kakao;

use Bundle\Component\Godo\GodoKakaoServerApi;
use Bundle\Component\Member\MemberDAO;
use Bundle\Component\Member\MemberSnsService;
use Component\Database\DBTableField;

//use Bundle\Component\Member\MemberVO;

/**
 * 카카오 로그인 및 회원가입
 * @package Bundle\Controller\Front\Member\Kakao
 * @author  sojoeng
 */
class KakaoSinkLoginController extends \Bundle\Controller\Front\Controller
{

    //http://medisoladesi44.godomall.com/member/kakao/kakao_sink_login.php
    public function index()
    {
        $request = \App::getInstance('request');
        $session = \App::getInstance('session');
        $logger = \App::getInstance('logger');

        $memberSnsService = new MemberSnsService();

        $logger->info(sprintf('start KakaoSinkLoginController: %s', __METHOD__));
        if ($code = $request->get()->get('code')) {
            echo '<div>code: ' . $code . '</div>';
            if ($endlen = (strpos($request->getRequestUri(), '?'))) {
                $returnURL = $request->getDomainUrl() . substr($request->getRequestUri(), 0, $endlen);
//                echo '<div>returnURL: ' . $returnURL . '</div>';
            }
            $body = array(
                "grant_type" => "authorization_code",
                "client_id" => "614ee6c35e7eda0450ac4d4611f34364",
                "client_secret" => "yFllgApmush3Z2B6UY4Dy2meJEBWvB3L",
                "redirect_uri" => $returnURL,
                "code" => $code,

            );

//            $post_data = json_encode($body);
            $post_data = http_build_query($body, '', '&');
            $url = 'https://kauth.kakao.com/oauth/token';
            $header_data = array(
                'Content-Type: application/x-www-form-urlencoded;charset=utf-8'
            );
            $ch = curl_init($url);
            curl_setopt_array($ch, array(
                CURLOPT_POST => TRUE,
                CURLOPT_RETURNTRANSFER => TRUE,
                CURLOPT_HTTPHEADER => $header_data,
                CURLOPT_POSTFIELDS => $post_data
            ));

            $response = curl_exec($ch);
            $ch . curl_close();
            echo '<div>response: ' . $response . '</div>';
            $response_data = json_decode($response, true);
            $access_token = $response_data['access_token'];
            $refresh_token = $response_data['refresh_token'];
//            echo '<div>access_token: ' . $access_token . '</div>';


            $userInfoUrl = 'https://kapi.kakao.com/v2/user/me';
            $user_info_header_data = array(
                'Authorization: Bearer ' . $access_token,
                'Content-Type: application/x-www-form-urlencoded;charset=utf-8'
            );
            $userInfoCh = curl_init($userInfoUrl);
            curl_setopt_array($userInfoCh, array(
                CURLOPT_POST => TRUE,
                CURLOPT_RETURNTRANSFER => TRUE,
                CURLOPT_HEADER => FALSE,
                CURLOPT_HTTPHEADER => $user_info_header_data,
            ));

            $response_user_info = curl_exec($userInfoCh);
            $userInfoCh . curl_close();
            echo '<div>response_user_info: ' . $response_user_info . '</div>';
            $userInfo = json_decode($response_user_info, true);
            $kakaoUserId = $userInfo['id'];
            $kakaoUserName = $userInfo['kakao_account']['profile']['nickname'];
            $email = $userInfo['kakao_account']['email'];
            if ($kakaoUserId == null) {
                $kakaoUserId = -1;
            }
            $memberSns = $memberSnsService->getMemberSnsByUUID($kakaoUserId);

            if ($memberSns == null) {
                $tableFunctionName = 'tableMember';
                $params = array(
                    "agreementInfoFl" => "y",
                    "privateApprovalFl" => "y",
                    "privateApprovalOptionFl" => "n",
                    "privateOfferFl" => "n",
                    "privateConsignFl" => "n",
                    "foreigner" => "n",
                    "appFl" => "y",
                    "memberFl" => "personal",
                    "memId" => "kakao-".$kakaoUserId,
                    "memNm" => $kakaoUserName,
                    "email" => $email,

                );
                $memberDAO = \App::load('Bundle\\Component\\Member\\MemberDAO');
                \DB::begin_tran();
                $vo = $params;
                echo '<div>is_array: ' . is_array($params) . '</div>';
                if (is_array($params)) {
                    DBTableField::setDefaultData($tableFunctionName, $params);
                    $vo = new \Bundle\Component\Member\MemberVO($params);
                }
                $member = $vo->toArray();
                $member['approvalDt'] = date('Y-m-d H:i:s');
                $member['entryDt'] = date('Y-m-d H:i:s');
                $memNo = $memberDAO->insertMemberByThirdParty($member);
                $memberSnsService->joinBySns($memNo, $kakaoUserId, $access_token, 'kakao');
                \DB::commit();
//                echo '<div>memberSns: isnull</div>';
            } else {
//                echo '<div>memberSns: is not null</div>';
                $memberSnsService->saveToken($kakaoUserId, $access_token, $refresh_token);
            }
            $memberSnsService->loginBySns($kakaoUserId);
            $this->redirect('/main/index.php');

        }
        exit();
    }
}