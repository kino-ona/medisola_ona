<?php
namespace Controller\Front\Subscription;

use App;
use Request;
use Session;

class ChangeScheduleController extends \Controller\Front\Controller 
{
    public function index()
    {
        $this->getView()->setDefine("layout", "layout_blank");
        if (!$idx = Request::get()->get("idx"))
            return $this->js("alert('잘못된 접근입니다.');");
        
        if (!gd_is_login())
            return $this->js("alert('로그인이 필요합니다.');");
         
        $memNo = Session::get("member.memNo");
       
       
        $obj = App::load(\Component\Subscription\Subscription::class);
        if (!$info = $obj->getSubscription($idx))
           return $this->js("alert('신청 정보가 존재하지 않습니다.');");
        
        if ($info['memNo'] != $memNo) 
           return $this->js("alert('본인이 신청하신 정보만 수정하실 있습니다.');");
      
        $year = date("Y", $info['schedule_stamp']);
        $month = date("m", $info['schedule_stamp']);
        $day = date("d", $info['schedule_stamp']);
        
        $this->setData("year", $year);
        $this->setData("month", $month);
        $this->setData("day", $day);
        
        $this->setData("idx", $idx);

		$db = App::load('DB');
		$sql = " select * from wm_subscription_schedule_list where uid='{$info['uid']}' and idx < '{$info['idx']}' order by idx desc limit 0, 1 ";
		$row = $db->fetch($sql);
		if (!empty($row)) {
			if (date("Ymd") <= date("Ymd", $row['schedule_stamp'])) {
				$syear = date("Y", $row['schedule_stamp']);
				$smonth = date("m", $row['schedule_stamp']);
				$sday = date("d", $row['schedule_stamp']);
			} else {
				$syear = date("Y");
				$smonth = date("m");
				$sday = date("d");			
			}

			$this->setData("syear", $syear);
			$this->setData("smonth", $smonth);
			$this->setData("sday", $sday);
		} else {
			$syear = date("Y");
			$smonth = date("m");
			$sday = date("d");

			$this->setData("syear", $syear);
			$this->setData("smonth", $smonth);
			$this->setData("sday", $sday);
		}
    }
}