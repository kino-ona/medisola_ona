<?php
namespace Component\Subscription;
use App;
use Request;

trait SubscriptionTrait 
{
    private $crypt_pass = "webnmobileisbest";
    private $crypt_iv = "webnmobileisbest";
    private $crypt_type = "AES-256-CBC";
    
    private $cfg;
    public $isMobile;
    
    public function getCfg()
    {
        return $this->cfg;
    }
    
    public function setCfg()
    {
       if (!is_object($this->db))
          $this->db  = App::load('DB');
     
       if ($tmp = $this->db->fetch("SELECT * FROM wm_subscription_config")) {
           $info = gd_policy('basic.info', DEFAULT_MALL_NUMBER);
           $tmp['mallNm'] = $info['mallNm'];
           
           $server = Request::server()->toArray();
           if (strtoupper($server['HTTPS']) == 'ON')
              $domain = "https://";
           else
              $domain = "http://";

           $domain .= $server['HTTP_HOST'];
           $tmp['domain'] = $domain;
           $tmp['uid'] = $this->getUid();
           
           $tmp['period'] = explode(",", $tmp['period']);
           $tmp['discount'] = explode(",", $tmp['discount']);
           $tmp['deliveryEa'] = explode(",", $tmp['deliveryEa']);

           if(\Request::getRemoteAddress() == '182.216.219.1571') {
               $tmp['discount'][0] = 5;
               /*$tmp['discount'][1] = 5;
               $tmp['discount'][2] = 5;
               $tmp['discount'][3] = 5;
               $tmp['discount'][4] = 5;
               $tmp['discount'][5] = 5;
               $tmp['discount'][6] = 5;
               $tmp['discount'][7] = 5;
               $tmp['discount'][8] = 5;
               $tmp['discount'][9] = 5;
               $tmp['discount'][10] = 5;
               $tmp['discount'][11] = 5;
               $tmp['discount'][12] = 5;
               $tmp['discount'][13] = 5;
               $tmp['discount'][14] = 5;
               $tmp['discount'][15] = 5;
               $tmp['discount'][16] = 5;*/
               //0.0,10,15,25,25,27,28,28,30,30,30,30,30,30,30,30
           }

           $this->cfg = $tmp;
       }
    }
    
    public function getUid()
    {
        return round(microtime(true) * 1000);
    }
    
    /* 암호화 */
   public function encrypt($data = null)
   {
       if ($data) {
          $endata = openssl_encrypt($data , $this->crypt_type, $this->crypt_pass, true, $this->crypt_iv);
          $endata = base64_encode($endata);
          return $endata;
       }
   }
   
   /* 복호화 */
   public function decrypt($data = null)
   {
       if ($data) {
           $data = base64_decode($data);
           $data = openssl_decrypt($data, $this->crypt_type, $this->crypt_pass, true, $this->crypt_iv);
           
           return $data;
       }
   }
   
   public function addMileage($memNo, $amount, $msg = "")
   {
     if ($memNo && $amount) {
       $mileage = App::load("\Component\Mileage\Mileage");
       $data = array(
           'chk' => $memNo,
           'mileageCheckFl' => 'add',
           'mileageValue' => $amount,
           'reasonCd' => '01005003',
           'contents' => $msg
       );

       return $mileage->addMileage($data);
     }
   }

   public function delMileage($memNo, $amount, $msg = "")
   {
     if ($memNo && $amount) {
       $mileage = App::load("\Component\Mileage\Mileage");
       $data = array(
           'chk' => $memNo,
           'mileageCheckFl' => 'remove',
           'mileageValue' => $amount,
           'reasonCd' => '01005001',
           'contents' => $msg
       );

       return $mileage->removeMileage($data);
     }
   }

   public function addDeposit($memNo, $amount, $msg = "")
   {
     if ($memNo && $amount) {
       $deposit = App::load("\Component\Deposit\Deposit");

        $data = array(
            'chk' => $memNo,
            'depositCheckFl' => 'add',
            'depositValue' => $amount,
            'reasonCd' => '01006001',
            'contents' => $msg
        );

        return $deposit->addDeposit($data);
      }
    }

    public function delDeposit($memNo, $amount, $msg = "")
    {
      if ($memNo && $amount) {
          $deposit = App::load("\Component\Deposit\Deposit");

          $data = array(
              'chk' => $memNo,
              'depositCheckFl' => 'remove',
              'removeMethodFl' => "minus",
              'depositValue' => $amount,
              'reasonCd' => '01006003',
              'contents' => $msg
          );

          return $deposit->removeDeposit($data);
        }
    }
   
   /* 비밀번호 해시 */
   public function getPasswordHash($password = null)
   {
       if ($password)
           return password_hash($password, PASSWORD_DEFAULT, ['cost' => 5]);
   }
   
   
   
   /* 스케줄 목록 */
   public function getScheduleList($no = 10, $stamp = 0, $period = null)
   {
       if (empty($stamp))
           $stamp = time();
       
       $stamp = strtotime(date("Ymd", $stamp));
       $hList = $this->getHolidayList();
       $cfg = $this->getCfg();

       $deliveryDays = $cfg['deliveryDays'] ? $cfg['deliveryDays'] : 0;
       
       if (empty($no))
           $no = 1;
       
       $period = $period?$period:"1_week";
       $period = explode("_", $period);
     
       $list = [];
       $list[] = ['stamp' => $stamp];
       if ($no > 1) {
          for ($i = 1; $i < $no; $i++) {
              $n = $i * $period[0];
              $str = "+{$n} {$period[1]}";
              $new_stamp = strtotime($str, $stamp);
              
              $list[] = ['stamp' => $new_stamp];
          }
       }

       foreach ($list as $k => $li) {
          $stamp = $li['stamp'];
          $delivery_stamp = $stamp + (60 * 60 * 24 * $deliveryDays);
            
          // 제외 날짜 목록: 공휴일 + 공휴일 다음날
          $excludeStamps = [];
          foreach ($hList as $h) {
              $excludeStamps[$h['stamp']] = true;
              $excludeStamps[$h['stamp'] + 60 * 60 * 24] = true;
          }

          // 일, 월, 공휴일, 공휴일 다음날 제외
          $safetyLimit = 30;
          while ($safetyLimit-- > 0) {
              $yoil = date("w", $delivery_stamp);
              if ($yoil == 0) {
                  $delivery_stamp += 60 * 60 * 24 * 2;
              } else if ($yoil == 1) {
                  $delivery_stamp += 60 * 60 * 24;
              } else if (isset($excludeStamps[$delivery_stamp])) {
                  $delivery_stamp += 60 * 60 * 24;
              } else {
                  break;
              }
          }
          
          $list[$k]['delivery_stamp'] = $delivery_stamp;
       }

       return $list;
   }
   
   public function getHolidayList()
   {
       $list = [];
       $stamp = strtotime(date("Ymd"));
       if ($tmp = $this->db->query_fetch("SELECT * FROM wm_subscription_holiday WHERE stamp >= '{$stamp}' ORDER BY stamp"))
          $list = $tmp;
       
       return $list;
   }
   
   /* 주문단계 목록 */
   public function getOrderStatusList()
   {
         $status = [];
         if ($tmp = gd_policy('order.status')) {
             foreach ($tmp as $_tmp) {
                 foreach ($_tmp as $k => $v) {
                     $status[$k] = $v['user'];
                 }
              }
          }

          return $status;
   }
}