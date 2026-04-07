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

/**
 * Class 회원가입 정보입력
 * @package Bundle\Controller\Front\Member
 * @author  yjwee
 */
class JoinController extends \Bundle\Controller\Front\Member\JoinController
{
    public function index()
    {
        parent::index();
        $postValue = \Request::post()->all();
        $this->setData("memberCode", $postValue["memberCode"]);

        $session = \App::getInstance('session');
        if ($session->has('utm_campaign')) {
            $utmCampaign = $session->get('utm_campaign');
            $this->setData('utmCampaign', $utmCampaign);
        }
    }
}