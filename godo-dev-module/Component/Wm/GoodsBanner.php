<?php

namespace Component\Wm;

use Request;
use App;

class GoodsBanner
{
    private $db;

    public function __construct()
    {
        $this->db = App::load(\DB::class);
    }

    public function getGoodsBanner($goodsNo)
    {
        $arrBind = [];

        $this->db->strWhere = 'goodsNo = ?';
        $this->db->strOrder = 'sort asc';

        $this->db->bind_param_push($arrBind, 'i', $goodsNo);

        $query = $this->db->query_complete();
        $strSQL = 'SELECT ' . array_shift($query) . ' FROM wm_goodsBanner' . implode(' ', $query);

        return $this->db->query_fetch($strSQL, $arrBind);

    }

    public function registerGoodsBanner($goodsNo)
    {
        $Files = \Request::files()->toArray();

        $server = \Request::server()->toArray();
        $upload_path = $server['DOCUMENT_ROOT']."/data/icon/goods_view_banner";

        $ex_name1 = explode("/",$Files['bannerImage1']['type'],2);
        $ex_name2 = explode("/",$Files['bannerImage2']['type'],2);


        if(strtolower($ex_name1[1])=="gif" || strtolower($ex_name1[1])=="jpeg" || strtolower($ex_name1[1])=="jpg" || strtolower($ex_name1[1])=="png" ||
            strtolower($ex_name2[1])=="gif" || strtolower($ex_name2[1])=="jpeg" || strtolower($ex_name2[1])=="jpg" || strtolower($ex_name2[1])=="png"){

            $tmp1 = time()."_".uniqid();
            $tmp2 = time()."_".uniqid();

            $user_file1 = $tmp1.".".$ex_name1[1];
            $user_file2 = $tmp2.".".$ex_name2[1];

            $path1 =  $upload_path."/".$user_file1;
            $path2 =  $upload_path."/".$user_file2;

            if(move_uploaded_file($Files['bannerImage1']['tmp_name'], $path1) && move_uploaded_file($Files['bannerImage2']['tmp_name'], $path2) ){
                $strSQL = "SELECT sort FROM wm_goodsBanner WHERE goodsNo = '" . $goodsNo . "' ORDER BY sort desc LIMIT 1";
                $sort = $this->db->fetch($strSQL);

                $sort = $sort['sort'] + 1;

                $arrBind = [];
                $sql = "INSERT INTO wm_goodsBanner SET bannerImage1 = ?, bannerImage2 = ?, sort = ?, goodsNo = ?, regDt = ?";
                $this->db->bind_param_push($arrBind, 's', $user_file1);
                $this->db->bind_param_push($arrBind, 's', $user_file2);
                $this->db->bind_param_push($arrBind, 'i', $sort);
                $this->db->bind_param_push($arrBind, 'i', $goodsNo);
                $this->db->bind_param_push($arrBind, 's', date('Y-m-d H:m:s'));

                $this->db->bind_query($sql, $arrBind);


                $arrBind = [];
                $strSQL = "UPDATE es_goods SET useGoodsViewBanner = ? WHERE goodsNo = ?";
                $this->db->bind_param_push($arrBind, 'i', 1);
                $this->db->bind_param_push($arrBind, 'i', $goodsNo);
                $this->db->bind_query($strSQL, $arrBind);

            }else {
                throw new \Exception(__('이미지 저장에 실패하였습니다.'));

            }

        }

    }

    public function deleteGoodsBanner($sno)
    {
        foreach ($sno as $key => $val){
            $strSQL = "SELECT goodsNo FROM wm_goodsBanner WHERE sno = '" . $val . "'";
            $goodsNo = $this->db->fetch($strSQL);

            $arrBind = [];
            $query = "DELETE FROM wm_goodsBanner WHERE sno = ?";
            $this->db->bind_param_push($arrBind, 'i', $val);
            $this->db->bind_query($query, $arrBind);
        }

        $strSQL = "SELECT sort FROM wm_goodsBanner WHERE goodsNo = '" . $goodsNo['goodsNo'] . "' ORDER BY sort asc LIMIT 1";
        $sort = $this->db->fetch($strSQL);

        if(!$sort['sort']){
            $arrBind = [];
            $strSQL = "UPDATE es_goods SET useGoodsViewBanner = ? WHERE goodsNo = ?";
            $this->db->bind_param_push($arrBind, 'i', 0);
            $this->db->bind_param_push($arrBind, 'i', $goodsNo['goodsNo']);
            $this->db->bind_query($strSQL, $arrBind);
        }

    }
    
    public function changeBannerSort($goodsNo, $data)
    {
        foreach ($data as $key => $val){
            $arrBind = [];
            $strSQL = "UPDATE wm_goodsBanner SET sort = ? WHERE goodsNo = ? AND sno = ?";
            $this->db->bind_param_push($arrBind, 'i', $val['sort']);
            $this->db->bind_param_push($arrBind, 'i', $goodsNo);
            $this->db->bind_param_push($arrBind, 'i', $val['sno']);
            $this->db->bind_query($strSQL, $arrBind);   
        }
    }
}