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

use App;
use Session;

/**
 * Class 회원 관리
 * @package Bundle\Component\Member
 * @author  yjwee
 */
class Member extends \Bundle\Component\Member\Member
{
	/**
     * 로그인 처리 함수
     *
     * @param $memId
     * @param $memPw
     *
     * @throws AlertRedirectException
     * @throws Exception
     */
    public function login($memId, $memPw)
    {
        // 부모 호출
        parent::login($memId, $memPw);

        /* 선물하기 장바구니 merge */
        if (gd_is_login()) {
			$memNo = Session::get("member.memNo");
			$cartGift = App::load(\Component\GiftOrder\CartGift::class);
			$cartGift->setMergeCart($memNo);
			$cartGift->setMergeGuestCart($memNo);
        }
    }

    public function join($params)
    {
        // Fill missing or empty fields with default values
        if (!isset($params['sexFl']) || empty($params['sexFl'])) {
            $params['sexFl'] = 'm'; // default to male
        }
        
        if (!isset($params['calendarFl']) || empty($params['calendarFl'])) {
            $params['calendarFl'] = 's'; // default to solar calendar
        }
        
        // birthDt가 이미 설정되어 있으면 (카카오 로그인 등) 디폴트로 덮어쓰지 않음
        if (empty($params['birthDt'])) {
            if (!isset($params['birthYear']) || empty($params['birthYear'])) {
                $params['birthYear'] = '1990'; // default birth year
            }

            if (!isset($params['birthMonth']) || empty($params['birthMonth'])) {
                $params['birthMonth'] = '01'; // default birth month
            }

            if (!isset($params['birthDay']) || empty($params['birthDay'])) {
                $params['birthDay'] = '01'; // default birth day
            }
        }

        $member = parent::join($params);

        if(isset($params['wanban']) && $params['wanban'] == 'true') {
            $this->update(
                $member->getMemNo(),
                'memNo',
                ['groupSno', 'joinedVia'],
                [20, 'wanban'] // 20 is 완반 그룹 sno, groupSno is 20
            );
        }

        if(isset($params['utmCampaign']) && !empty($params['utmCampaign'])) {
            $this->update(
                $member->getMemNo(),
                'memNo',
                ['joinedVia'],
                [$params['utmCampaign']]
            );
        }
        
        return $member;
    }
}