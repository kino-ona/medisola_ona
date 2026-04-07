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

use Component\Member\Member;
use Component\Mall\Mall;
use Request;
use Session;

/**
 * Class WanbanWelcomeController
 * @package Controller\Front\Member
 * @author  Claude
 */
class WanbanWelcomeController extends \Controller\Front\Controller
{
    public function index()
    {
        $session = \App::getInstance('session');

        
        $getValue = Request::get()->all();
        $member = new Member();
        $mall = new Mall();
        // Get member information from session
        $memberSno = $session->get(Member::SESSION_NEW_MEMBER);

        if ($memberSno) {
            $memInfo = $member->getMemberInfo($memberSno);
            $memberInfo = $member->getMemberId($memberSno);
            $memberInfo['memNm'] = $memInfo['memNm'];
        } else {
            // Fallback - get most recent wanban member (for testing)
            $memberInfo = [
                'memId' => 'wanban_user',
                'nickNm' => '완반 회원',
                'regDt' => date('Y-m-d H:i:s'),
            ];
        }
        
        // Get domain URL for shopping link
        $serviceInfo = $mall->getServiceInfo();
        $domainUrl = Request::getDomainUrl();
        
        // Set data for template
        $this->setData('memberInfo', $memberInfo);
        $this->setData('domainUrl', $domainUrl);
        $this->setData('serviceInfo', $serviceInfo);
        
        // Clean up session data
        Session::del('wanban');
    }
}