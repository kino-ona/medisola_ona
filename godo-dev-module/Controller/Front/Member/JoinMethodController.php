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
 * Class 회원가입 방법 선택
 * @package Bundle\Controller\Front\Member
 * @author  yjwee
 */
class JoinMethodController extends \Bundle\Controller\Front\Member\JoinMethodController
{
  public function index()
    {
        parent::index();
        
        $request = \App::getInstance('request');
        $session = \App::getInstance('session');
        $utmCampaign = $request->get()->get('utm_campaign');
        if (!empty($utmCampaign)) {
            $session->set('utm_campaign', $utmCampaign);
        }
    }
}