<?php

namespace Controller\Front;

class TestController extends \Controller\Front\Controller {

    protected $db;

    public function index() {

        $this->db = \App::load('DB');

		exit;
        $sql = " 
			select *, date_format(from_unixtime(schedule_stamp), '%Y-%m-%d') schedule_date 
			from wm_subscription_schedule_list 
			where date_format(from_unixtime(schedule_stamp), '%Y-%m-%d') < '2026-01-15' and isStop = 1 and orderNo = ''
			order by schedule_stamp desc
			limit 0, 100
		";
		$result = $this->db->query_fetch($sql);
		gd_debug($result);
	


//        $sql = "SELECT * FROM es_manager WHERE managerId = 'mintweb'";
//        $manager = $this->db->fetch($sql);

//        gd_Debug($manager);


//        $sql = "UPDATE es_managerIp SET ipManagerSecurity = '182.216.219.157' WHERE managerSno = '71'";
//        $this->db->fetch($sql);

//        $sql = "SELECT * FROM es_managerIp";
//        $result = $this->db->query_fetch($sql);

        // 182.216.219.157
        // 180.226.6.118
//        gd_Debug($result);

        /*$sql = "UPDATE es_order SET orderStatus = 'r3' WHERE orderNo = '2511241850018798'";
        $this->db->query($sql);

        $sql = "SELECT * FROM es_order WHERE orderNo = '2511241850018798'";
        $data = $this->db->query_fetch($sql);

        gd_debug($data);

        $sql = "SELECT * FROM es_orderGoods WHERE orderNo = '2511241850018798'";
        $data = $this->db->query_fetch($sql);

        gd_Debug($data);*/

        exit;

    }

}