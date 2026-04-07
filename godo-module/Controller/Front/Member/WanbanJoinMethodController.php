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

namespace Controller\Front\Member;

use Bundle\Component\Policy\KakaoLoginPolicy;
use Component\Godo\GodoPaycoServerApi;
use Component\Member\Util\MemberUtil;
use Exception;
use Framework\Debug\Exception\AlertBackException;
use Request;

/**
 * Class 완반X메디쏠라 회원가입 방법 선택
 * @package Bundle\Controller\Front\Member
 * @author  conan
 */
class WanbanJoinMethodController extends \Bundle\Controller\Front\Controller
{
    public function index()
    {

        try {
            $request = \App::getInstance('request');
            $session = \App::getInstance('session');
            $session->del(GodoPaycoServerApi::SESSION_ACCESS_TOKEN);
            $scripts = [];

            $kakaoLoginPolicy = new KakaoLoginPolicy();
            
            $useKakaoLogin = $kakaoLoginPolicy->useKakaoLogin();
            
            if ($useKakaoLogin === false) {
                $this->redirect('../member/join_agreement.php');
            }

            if($useKakaoLogin) {
                MemberUtil::logoutKakao();
                $scripts[] = 'gd_kakao.js';
                $this->setData('returnUrl', $request->getRequestUri());
            }
            
            $this->setData('join', gd_policy('member.join'));
            $this->setData('joinItem', gd_policy('member.joinitem'));            
            $this->setData('useKakaoLogin', $useKakaoLogin);
            $this->addScript($scripts);

        } catch (Exception $e) {
            throw new AlertBackException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
