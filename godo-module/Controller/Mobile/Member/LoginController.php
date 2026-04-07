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
 namespace Controller\Mobile\Member;

use Component\Medisola\MedisolaAuthorizeApi;

 /**
  * Class LoginController
  * @package Bundle\Controller\Mobile\Member
  * @author  yjwee
  */
class LoginController extends \Bundle\Controller\Mobile\Member\LoginController
{
  public function index()
    {
        $request = \App::getInstance('request');
        $session = \App::getInstance('session');
        $appAuthorize = $session->get(MedisolaAuthorizeApi::SESSION_APP_AUTHORIZE);
        if(isset($appAuthorize)) {
          if(time() < $appAuthorize['expire_at']) {
            $this->redirect('./medisolaAuthorize.php');
            exit();
          } else {
            $session->del(MedisolaAuthorizeApi::SESSION_APP_AUTHORIZE);
          }
        }

        parent::index();

        $utmCampaign = $request->get()->get('utm_campaign');
        if (!empty($utmCampaign)) {
            $session->set('utm_campaign', $utmCampaign);
        }
    }
}