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

use Bundle\Component\Godo\GodoKakaoServerApi;
use Component\Member\Member;
use Framework\Security\Token;
use Request;
use Session;

/**
 * Class WanbanJoinController
 * @package Controller\Front\Member
 * @author  Claude
 */
class WanbanJoinController extends \Controller\Front\Controller
{
    public function index()
    {
        // Check if user came from agreement page or Kakao login
        if (!Session::has('wanban')) {
            throw new \Exception('잘못된 접근입니다.');
        }

        // Check for Kakao profile data from previous step
        $kakaoProfile = [];
        if (Session::has(GodoKakaoServerApi::SESSION_USER_PROFILE)) {
            $kakaoProfile = Session::get(GodoKakaoServerApi::SESSION_USER_PROFILE);
        }
        
        // Generate birth date options with age restrictions (14+ minimum)
        $joinPolicy = gd_policy('member.join');
        $limitAge = ($joinPolicy['under14ConsentFl'] === 'y') ? 14 : 
                   (($joinPolicy['under14Fl'] === 'no') ? $joinPolicy['limitAge'] : 14);
        
        $DateYear = [];
        $DateYearMarri = [];
        $DateMonth = [];
        $DateDay = [];
        $startYear = (!empty($limitAge)) ? (int)date("Y") - $limitAge : (int)date("Y");
        $startYearMarri = (int)date("Y");
        $endYear = 1900;
        $fixFront = '';
        for ($i=$startYear; $i>=$endYear; $i--) {
            $DateYear[$i] = $i;
        }
        for ($i=$startYearMarri; $i>=$endYear; $i--) {
            $DateYearMarri[$i] = $i;
        }
        for ($j=1; $j<=12; $j++) {
            if ($j < 10) {
                $fixFront = 0;
            }
            $DateMonth[$fixFront.$j] = $fixFront.$j;
            $fixFront = '';
        }
        for ($k=1; $k<=31; $k++) {
            if ($k < 10) {
                $fixFront = 0;
            }
            $DateDay[$fixFront.$k] = $fixFront.$k;
            $fixFront = '';
        }
        
        // Set up join action URL with SSL
        $siteLink = new \Component\SiteLink\SiteLink();
        $joinActionUrl = $siteLink->link('../member/member_ps.php', 'ssl');
        
        // Set data for template
        $this->setData('token', Token::generate('token'));
        $this->setData('joinActionUrl', $joinActionUrl);
        $this->setData('birthYearOptions', $birthYearOptions);
        $this->setData('birthMonthOptions', $birthMonthOptions);
        $this->setData('birthDayOptions', $birthDayOptions);

        $this->setData('countryPhone', $countryPhone);
        $this->setData('DateYear', $DateYear);
        $this->setData('DateYearMarri', $DateYearMarri);
        $this->setData('DateMonth', $DateMonth);
        $this->setData('DateDay', $DateDay);
        
        // Set Kakao profile data for form pre-filling if available
        if (!empty($kakaoProfile)) {
            $this->setData('kakaoProfile', json_encode($kakaoProfile));
            $this->setData('isKakaoJoin', true);
            
            // Pre-fill name if available from Kakao
            if (isset($kakaoProfile['properties']['nickname'])) {
                $this->setData('prefillName', $kakaoProfile['properties']['nickname']);
            }
        } else {
            $this->setData('kakaoProfile', '{}');
            $this->setData('isKakaoJoin', false);
        }
        
        // Set wanban-specific data
        $this->setData('wanbanFl', 'y');
        $this->setData('wanban', 'true');
        $this->setData('groupSno', '20');
        $this->setData('joinedVia', 'wanban');
    }
}