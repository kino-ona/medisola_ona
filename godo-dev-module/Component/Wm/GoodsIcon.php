<?php

namespace Component\Wm;

use Request;
use App;

class GoodsIcon
{
    private $db;

    public function __construct()
    {
        $this->db = App::load(\DB::class);
    }

    public function getGoodsIcon($goodsNo)
    {
        $arrBind = [];

        $this->db->strWhere = 'goodsNo = ?';
        $this->db->strOrder = 'sort asc';

        $this->db->bind_param_push($arrBind, 'i', $goodsNo);

        $query = $this->db->query_complete();
        $strSQL = 'SELECT ' . array_shift($query) . ' FROM wm_goodsIcon' . implode(' ', $query);

        return $this->db->query_fetch($strSQL, $arrBind);

    }

    public function registerGoodsIcon1($arrData)
    {
        // 아이콘명 체크
        if (gd_isset($arrData['iconNm']) === null || gd_isset($arrData['iconNm']) == '') {
            throw new \Exception(__('아이콘 이름은 필수 항목입니다.'), 500);
        } else {
            if (gd_is_html($arrData['iconNm']) === true) {
                throw new \Exception(__('아이콘 이름에 태크는 사용할 수 없습니다.'), 500);
            }
        }

        $Files = \Request::files()->toArray();

        $server = \Request::server()->toArray();
        $upload_path = $server['DOCUMENT_ROOT']."/data/icon/goods_view_icon";

        $ex_name = explode("/",$Files['iconImage']['type'],2);
  
        if(strtolower($ex_name[1])=="gif" || strtolower($ex_name[1])=="jpeg" || strtolower($ex_name[1])=="jpg" || strtolower($ex_name[1])=="png"){

            $tmp = time()."_".uniqid();

            $user_file=  $tmp.".".$ex_name[1];
            $path =  $upload_path."/".$user_file;
            if(move_uploaded_file($Files['iconImage']['tmp_name'], $path)){
                $strSQL = "SELECT sort FROM wm_goodsIcon WHERE goodsNo = '" . $arrData['goodsNo'] . "' ORDER BY sort desc LIMIT 1";
                $sort = $this->db->fetch($strSQL);

                $sort = $sort['sort'] + 1;

                $arrBind = [];
                $sql = "INSERT INTO wm_goodsIcon SET iconNm = ?, iconImage = ?, sort = ?, goodsNo = ?, regDt = ?";
                $this->db->bind_param_push($arrBind, 's', $arrData['iconNm']);
                $this->db->bind_param_push($arrBind, 's', $user_file);
                $this->db->bind_param_push($arrBind, 'i', $sort);
                $this->db->bind_param_push($arrBind, 'i', $arrData['goodsNo']);
                $this->db->bind_param_push($arrBind, 's', date('Y-m-d H:m:s'));

                $this->db->bind_query($sql, $arrBind);


                $arrBind = [];
                $strSQL = "UPDATE es_goods SET useGoodsViewIcon = ? WHERE goodsNo = ?";
                $this->db->bind_param_push($arrBind, 'i', 1);
                $this->db->bind_param_push($arrBind, 'i', $arrData['goodsNo']);
                $this->db->bind_query($strSQL, $arrBind);

            }else {
                throw new \Exception(__('이미지 저장에 실패하였습니다.'));

            }

        }else if(strtolower($ex_name[1])=="svg+xml") {
            $tmp = time() . "_" . uniqid();

            $user_file = $tmp . ".svg";
            $path = $upload_path . "/" . $user_file;
            if (move_uploaded_file($Files['iconImage']['tmp_name'], $path)) {
                $strSQL = "SELECT sort FROM wm_goodsIcon WHERE goodsNo = '" . $arrData['goodsNo'] . "' ORDER BY sort desc LIMIT 1";
                $sort = $this->db->fetch($strSQL);

                $sort = $sort['sort'] + 1;

                $arrBind = [];
                $sql = "INSERT INTO wm_goodsIcon SET iconNm = ?, iconImage = ?, sort = ?, goodsNo = ?, regDt = ?";
                $this->db->bind_param_push($arrBind, 's', $arrData['iconNm']);
                $this->db->bind_param_push($arrBind, 's', $user_file);
                $this->db->bind_param_push($arrBind, 'i', $sort);
                $this->db->bind_param_push($arrBind, 'i', $arrData['goodsNo']);
                $this->db->bind_param_push($arrBind, 's', date('Y-m-d H:m:s'));

                $this->db->bind_query($sql, $arrBind);


                $arrBind = [];
                $strSQL = "UPDATE es_goods SET useGoodsViewIcon = ? WHERE goodsNo = ?";
                $this->db->bind_param_push($arrBind, 'i', 1);
                $this->db->bind_param_push($arrBind, 'i', $arrData['goodsNo']);
                $this->db->bind_query($strSQL, $arrBind);
            }

        }
    }

    public function registerGoodsIcon2($arrData)
    {
        $Goods = \App::load('\\Component\\Goods\\Goods');
        foreach ($arrData['goodsIconCd'] as $key => $val) {
            $tmp['goodsIcon'] = $Goods->getGoodsIcon($val);
            foreach ($tmp['goodsIcon'] as $key2 => $val2){
                $data[$key] = $val2;
                $data[$key]['goodsIconCd'] = $key2;
            }
        }
        foreach ($data as $key => $val){
            $strSQL = "SELECT sort FROM wm_goodsIcon WHERE goodsNo = '" . $arrData['goodsNo'] . "' ORDER BY sort desc LIMIT 1";
            $sort = $this->db->fetch($strSQL);

            $sort = $sort['sort'] + 1;

            $arrBind = [];
            $sql = "INSERT INTO wm_goodsIcon SET goodsIconCd = ? , iconNm = ?, iconImage = ?, sort = ?, goodsNo = ?, regDt = ?";
            $this->db->bind_param_push($arrBind, 's', $val['goodsIconCd']);
            $this->db->bind_param_push($arrBind, 's', $val['iconNm']);
            $this->db->bind_param_push($arrBind, 's', $val['iconImage']);
            $this->db->bind_param_push($arrBind, 'i', $sort);
            $this->db->bind_param_push($arrBind, 'i', $arrData['goodsNo']);
            $this->db->bind_param_push($arrBind, 's', date('Y-m-d H:m:s'));

            $this->db->bind_query($sql, $arrBind);
        }

        $arrBind = [];
        $strSQL = "UPDATE es_goods SET useGoodsViewIcon = ? WHERE goodsNo = ?";
        $this->db->bind_param_push($arrBind, 'i', 1);
        $this->db->bind_param_push($arrBind, 'i', $arrData['goodsNo']);
        $this->db->bind_query($strSQL, $arrBind);

    }

    public function deleteGoodsIcon($sno)
    {
        foreach ($sno as $key => $val){
            $strSQL = "SELECT goodsNo FROM wm_goodsIcon WHERE sno = '" . $val . "'";
            $goodsNo = $this->db->fetch($strSQL);

            $arrBind = [];
            $query = "DELETE FROM wm_goodsIcon WHERE sno = ?";
            $this->db->bind_param_push($arrBind, 'i', $val);
            $this->db->bind_query($query, $arrBind);
        }
        
        $strSQL = "SELECT sort FROM wm_goodsIcon WHERE goodsNo = '" . $goodsNo['goodsNo'] . "' ORDER BY sort asc LIMIT 1";
        $sort = $this->db->fetch($strSQL);

        if(!$sort['sort']){
            $arrBind = [];
            $strSQL = "UPDATE es_goods SET useGoodsViewIcon = ? WHERE goodsNo = ?";
            $this->db->bind_param_push($arrBind, 'i', 0);
            $this->db->bind_param_push($arrBind, 'i', $goodsNo['goodsNo']);
            $this->db->bind_query($strSQL, $arrBind);
        }

    }

    public function changeIconSort($goodsNo, $data)
    {
        foreach ($data as $key => $val){
            $arrBind = [];
            $strSQL = "UPDATE wm_goodsIcon SET sort = ? WHERE goodsNo = ? AND sno = ?";
            $this->db->bind_param_push($arrBind, 'i', $val['sort']);
            $this->db->bind_param_push($arrBind, 'i', $goodsNo);
            $this->db->bind_param_push($arrBind, 'i', $val['sno']);
            $this->db->bind_query($strSQL, $arrBind);
        }
    }



}