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
 * Class JoinAgreementController
 * @package Bundle\Controller\Front\Member
 * @author  yjwee
 */
class JoinAgreementController extends \Bundle\Controller\Front\Member\JoinAgreementController
{
    public function index()
    {
        parent::index();
        $getValue = \Request::get()->all();
        $this->setData('memberCode', $getValue['mem-code']);

        // Handle utm_campaign parameter from GET or session
        $utmCampaign = \Request::get()->get('utm_campaign');
        if (!empty($utmCampaign)) {
            \Session::set('utm_campaign', $utmCampaign);
        } elseif (\Session::has('utm_campaign')) {
            $utmCampaign = \Session::get('utm_campaign');
        }
        
        if (!empty($utmCampaign)) {
            $this->setData('utmCampaign', $utmCampaign);
        }
    }
}