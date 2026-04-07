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
 * @link      http://www.godo.co.kr
 */
namespace Component\Member;

use Component\Member\Util\MemberUtil;
use Component\Member\MemberSnsService;
use Logger;
use Request;
use Session;
use Cookie;
use Encryptor;

/**
 * Class PageAccess
 * @package Bundle\Component\Member
 * @author  yjwee
 */
class PageAccess extends \Bundle\Component\Member\PageAccess
{
    public static function setCertification()
    {
        if (\App::getController()->getRootDirecotory() != 'admin') {
            if (!Session::has('member')) {
                // 일반 자동 로그인 시도
                try {
                    MemberUtil::loginByCookie();
                } catch (\Exception $e) {
                    Logger::warning(__METHOD__ . ', auto login failed: ' . $e->getMessage());
                }
                
                // SNS 자동 로그인 시도 (일반 자동 로그인이 실패한 경우)
                if (!Session::has('member')) {
                    try {
                        self::loginBySnsCookie();
                    } catch (\Exception $e) {
                        Logger::warning(__METHOD__ . ', SNS auto login failed: ' . $e->getMessage());
                    }
                }
            }
            parent::setCertification();
        }
    }
    
    /**
     * SNS 자동 로그인 처리 (UUID 기반)
     */
    private static function loginBySnsCookie()
    {
        if (!Cookie::has('GD5ATL_SNS')) {
            return;
        }
        
        $snsCookieData = json_decode(Cookie::get('GD5ATL_SNS'), true);
        if (empty($snsCookieData) || !is_array($snsCookieData)) {
            return;
        }
        
        if (!isset($snsCookieData['type']) || $snsCookieData['type'] !== 'sns') {
            return;
        }
        
        if (!isset($snsCookieData['uuid']) || !isset($snsCookieData['snsType'])) {
            return;
        }
        
        try {
            $uuid = Encryptor::decrypt($snsCookieData['uuid']);
            $snsType = $snsCookieData['snsType'];
            
            $memberSnsService = new MemberSnsService();
            /**
             * IMPORTANT:
             * Bundle\Component\Policy\SnsLoginPolicy::getAppId()는 현재 facebook 키만 보장합니다.
             * 여기서 kakao/payco/naver/... 등을 setThirdPartyAppType()로 넘기면 getAppId($key)에서
             * undefined index가 발생하거나 appId가 null로 조회되어 UUID 기반 로그인 조회가 실패하고,
             * 그 결과 아래 catch에서 쿠키가 삭제되어 "자동로그인이 안 된다"로 보이게 됩니다.
             *
             * SNS 자동로그인(GD5ATL_SNS)은 현재 kakao UUID 기반으로 쓰이므로 appId는 기본값(godo)을 사용합니다.
             * facebook 같이 별도 appId가 필요한 케이스만 추후 지원 필요 시 분기 추가하세요.
             */
            if ($snsType === 'facebook') {
                $memberSnsService->setThirdPartyAppType($snsType);
            }
            $memberSnsService->loginBySns($uuid);
            
            Logger::info(__METHOD__ . ', SNS auto login success', ['snsType' => $snsType, 'uuid' => substr($uuid, 0, 10) . '...']);
        } catch (\Exception $e) {
            Logger::warning(__METHOD__ . ', SNS auto login failed: ' . $e->getMessage());
            // 자동 로그인 실패 시 쿠키 삭제
            // path 를 '/'로 고정하여 만료시각을 과거로 내려 확실히 제거한다.
            Cookie::set('GD5ATL_SNS', '', time() - 42000, '/');
        }
    }
}