<?php
namespace Component\Wib;

/**
 * 고도몰 기본쿼리를 편하게 사용할수 있도록 작업
 * select, update, insert 가능 [ 필요할때마다 기능 추가중 ]
 */
class WibSql 
{
    
    protected $db = null;
    
    public $joinTotal;
    
    public function __construct() {  
        if (!is_object($this->db)) {
            $this->db = \App::load('DB');
        }
    }
    
    // 고도 셀렉트 단일형
    public function WibCol($data)
    {
        
    }
    
    /**
     * 고도 셀렉트 기본형
     * 테이블 = $data[0]
     * 셀렉트컬럼 = $data[1]
     * 조건절 = $data[2]
     * @param type $data
     * @return type
     */
    public function WibQuery($data)
    {
        $table = $data[0];
        
        $arrBind = [];
        
       
        $this->db->strField .= implode(",", $data[1]);
        
        if(is_array($data[2])){
            foreach ($data[2] as $key => $value) {
                if($key != 'n'){
                    $que_type = (isset($value[2]))?$value[2]:'and';
                    $this->db->strWhere .= " {$key} = ? {$que_type}";
                }
                $this->db->bind_param_push($arrBind, $value[1], $value[0]);
            }
            $this->db->strWhere = substr($this->db->strWhere, 0 ,-4);
        }
        
        if(isset($data[3])){
            if($data[3][0] != ''){
                $this->db->strOrder = $data[3][0];
            }
        }
        
        if(isset($data[4])){
            $this->db->strLimit = $data[4][0];
        }
                
        $query = $this->db->query_complete();
        $sql = "SELECT " . array_shift($query) . " FROM {$table}" . implode(' ', $query);

        return $this->db->query_fetch($sql, $arrBind, false);
    }
    
    /**
     * left join 쿼리
     * 테이블 = $data[0]
     * 셀렉트컬럼 = $data[1]
     * 조인 = $data[2] [ 0 : 조인문자열, 1 : 조인 테이블 넘버 ]
     * 조건절 = $data[3]
     * order by = $data[4]
     * limit = $data[5]
     * @param type $data
     * @return type
     */
    public function WibJoin($data)
    {
        $table = $data[0];
        $arrBind = [];
        
        $this->db->strField .= implode(",", $data[1]);
        $this->db->strJoin = ' '.$data[2][0];
        
        if(is_array($data[3])){
            foreach ($data[3] as $key => $value) {
                if($key != 'n'){
                    $que_type = (isset($value[2]))?$value[2]:'and';
                    $this->db->strWhere .= " {$key} = ? {$que_type}";
                }
                $this->db->bind_param_push($arrBind, $value[1], $value[0]);
            }
            $this->db->strWhere = substr($this->db->strWhere, 0 ,-4);
        }


        if(isset($data[4])){
            if($data[4][0] != ''){
                $this->db->strOrder = $data[4][0];
            }
        }
        
        //$total_query = $this->db->query_complete();
        //$total = "SELECT count(*) as total FROM {$table}" . implode(' ', $total_query);
        //$this->joinTotal = $this->db->query_fetch($total, $arrBind, false);
        
        if(isset($data[5])){
            $this->db->strLimit = $data[5][0];
        }
        
        $query = $this->db->query_complete();
        $sql = "SELECT " . array_shift($query) . " FROM {$table}" . implode(' ', $query);
        
        $result = $this->db->query_fetch($sql, $arrBind, false);

        return $result;
        
    }
    public function WibJoinTotal($data)
    {
        $table = $data[0];
        $arrBind = [];
        
        $this->db->strField .= ' count(*) as totalCnt';
        $this->db->strJoin = ' '.$data[2][0];
        
        if(is_array($data[3])){
            foreach ($data[3] as $key => $value) {
                if($key != 'n'){
                    $que_type = (isset($value[2]))?$value[2]:'and';
                    $this->db->strWhere .= " {$key} = ? {$que_type}";
                }
                $this->db->bind_param_push($arrBind, $value[1], $value[0]);
            }
            $this->db->strWhere = substr($this->db->strWhere, 0 ,-4);
        }
        
        $query = $this->db->query_complete();
        $sql = "SELECT " . array_shift($query) . " FROM {$table}" . implode(' ', $query);
        
        $result = $this->db->query_fetch($sql, $arrBind, false);

        return $result;
    }
    
    
    /**
     * 고도 인서트 기본형
     * 테이블 = $data[0]
     * 데이터 = $data[1]
     * @param type array $data
     * @return type int insert_id
     */
    public function WibInsert($data)
    {
        $table = $data[0];
        
        $arrBind = [];
        
        // 인서트 데이터 설정
        $insertSet = '';
        foreach ($data[1] as $key => $value) {
            $insertSet .= " {$key} =  ?, ";
            $this->db->bind_param_push($arrBind, $value[1], $value[0]);
        }
        $insertSet = substr($insertSet, 0, -2);
        
        $sql = "INSERT INTO {$table} SET {$insertSet}";
        $this->db->bind_query($sql, $arrBind);
        
        return $this->db->insert_id();
    }
    
    // 고도 업데이트 기본형
    public function WibUpdate($data)
    {
        $table = $data[0];
        
        $arrBind = [];
        
        // 업데이트 데이터 설정
        $updateSet = '';
        foreach ($data[1] as $key => $value) {
            $updateSet .= " {$key} =  ?, ";
            $this->db->bind_param_push($arrBind, $value[1], $value[0]);
        }
        $updateSet = substr($updateSet, 0, -2);
        
        // where 데이터 설정
        $whereSet = '';
        if(is_array($data[2])){
            $whereSet = ' WHERE';
            foreach ($data[2] as $key => $value) {
                $whereSet .= " {$key} = ? and";
                $this->db->bind_param_push($arrBind, $value[1], $value[0]);
            }
        }
        $whereSet = substr($whereSet, 0, -4);
        
        $sql = "UPDATE {$table} SET {$updateSet}  {$whereSet}"; 
        $this->db->bind_query($sql, $arrBind);
    }
    
    // 특별형
    public function WibNobind($wql)
    {
        $result = $this->db->query($wql);
        return $this->db->fetch($result);
    }
    
    // 특별형
    public function WibAll($wql)
    {
        return $this->db->query_fetch($wql);
    }
    
}
