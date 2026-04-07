<?php
namespace Component\Wib;

use Component\Wib\WibSql;
use Framework\Utility\ComponentUtils;

class WibMember 
{
    protected $db = null;
    public $wibSql = null;
    
    public function __construct() 
    {  
        $this->wibSql = new WibSql();
        if (!is_object($this->db)) {
            $this->db = \App::load('DB');
        }
    }
    
    /**
     * 회원등급코드 저장
     */
    public function memberCodeSave($sno, $selfCode)
    {
        if(!$sno || !$selfCode){
            return false;
        }
        
        $query['selfCode'] = [$selfCode,'s'];

        
        $data = [
            'es_memberGroup',
            $query,
            ['sno' => [$sno, 'i']]
        ];
        $this->wibSql->WibUpdate($data);
        
    }
    
    /**
     * 회원등급코드 체크
     * sno있으면 해당 컬럼 제외
     */
    public function selfCodeCheck($sno, $selfCode)
    {
        if(!$selfCode){
            return false;
        }
        
        $where = '';
        $where .= "WHERE selfCOde = '{$selfCode}' ";
        if($sno){
            $where .= " AND sno != {$sno} ";
        }
        
        $query = "SELECT COUNT(*) cnt FROM es_memberGroup {$where}";
        $cnt = $this->wibSql->WibNobind($query)['cnt'];
        
        if($cnt > 0){
            return false;
        }else{
            return true;
        }
    }
    
    /**
     * 회원가입시 코드 입력하면 그 값으로 매칭해서 등급업그레이드
     * memberAdmin->_getResultByApplyGroupGrade() 사용
     */
    public function updateMemberGroup($memNo, $selfCode)
    {
        if(!$memNo || !$selfCode){
            return false;
        }
        
        $query = "SELECT * FROM es_memberGroup WHERE selfCode = '{$selfCode}' AND sno != 1";
        $data = $this->wibSql->WibNobind($query);
        
        $cfgGroup = gd_policy('member.group');

        if ($data['sno']) {
            
            $arrBind = [];
            
            $this->db->bind_param_push($arrBind, 'i', $data['sno']);
            $this->db->bind_param_push($arrBind, 's', date('Y-m-d'));
            if (empty($cfgGroup['calcKeep']) === false) {
                $this->db->bind_param_push($arrBind, 's', date('Y-m-d', strtotime('+' . $cfgGroup['calcKeep'] . ' month')));
            } else {
                $this->db->bind_param_push($arrBind, 's', '0000-00-00');
            }
            $this->db->bind_param_push($arrBind, 'i', $memNo);
            $updateResult = $this->db->set_update_db(DB_MEMBER, 'groupSno=?, groupModDt=?, groupValidDt=?', 'memNo = ?', $arrBind, false);

            if ($updateResult == 1) {
                
                //쿠폰 발급 하기
                $couponPolicy = ComponentUtils::getPolicy('member.group');
                if ($couponPolicy['couponConditionManual'] == 'y') {
                    //회원등급을직접수정시발급인가?(Y)
                    $group = \App::load('Component\Member\Group\GroupDAO');
                    $groupConfig = $group->selectGroup($data['sno'])['groupCoupon'];

                    if (!empty($groupConfig)) {
                        //업데이트된 회원 등급에 쿠폰 혜택이 있는가?(Y)
                        $applyCoupon = true;
                    } else {
                        $applyCoupon = false;
                    }
                }

                //쿠폰 지급 하는 코드
                if ($applyCoupon === true) {
                    $applyCouponList = explode(INT_DIVISION, $groupConfig);
                    foreach ($applyCouponList as $couponValue) {
                        $coupon = new \Component\Coupon\CouponAdmin;
                        \Request::post()->set('couponNo', $couponValue);
                        \Request::post()->set('couponSaveAdminId', '회원등급 쿠폰 혜택');
                        \Request::post()->set('managerNo', Session::get('manager.sno'));
                        \Request::post()->set('memberCouponStartDate', $coupon->getMemberCouponStartDate($couponValue));
                        \Request::post()->set('memberCouponEndDate', $coupon->getMemberCouponEndDate($couponValue));
                        \Request::post()->set('memberCouponState', 'y');

                        $memberArr[] = $memNo;

                        $coupon->saveMemberCouponSms($memberArr);
                        unset($memberArr);
                    }
                }
            } else {
                return false;
            }
        }

    }
}
