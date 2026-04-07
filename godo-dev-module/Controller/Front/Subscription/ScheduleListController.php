<?php
namespace Controller\Front\Subscription;

use App;
use Request;

class ScheduleListController extends \Controller\Front\Controller 
{
    public function index()
    {
        
        if (!gd_is_login())
            return $this->js("alert('로그인이 필요한 페이지 입니다.');window.location.href='../member/login.php';");

        $obj = App::load(\Component\Subscription\SubscriptionClient::class);
        
        $result = $obj->getScheduleList();

        $this->setData($result);
        
        
        $locale = \Globals::get('gGlobal.locale');
        // 날짜 픽커를 위한 스크립트와 스타일 호출
        $this->addCss([
            'plugins/bootstrap-datetimepicker.min.css',
            'plugins/bootstrap-datetimepicker-standalone.css',
         ]);
         $this->addScript([
             'moment/moment.js',
             'moment/locale/' . $locale . '.js',
             'jquery/datetimepicker/bootstrap-datetimepicker.min.js',
         ]);
         
        // 기본 조회 일자
        $wDate = Request::get()->get('wDate');
        $this->setData('startDate', gd_isset($wDate[0]));
        $this->setData('endDate', gd_isset($wDate[1]));
        $this->setData('wDate', gd_isset($wDate));
    }
}