<?php

namespace Component\Wm;

use Request;
use App;

class EarlyDelivery
{
    private $db;

    public function __construct()
    {
        $this->db = App::load(\DB::class);
    }

    public function getEarlyDeliveryData($goodsNo)
    {
        $sql = "SELECT useEarlyDelivery, earlyDeliveryUrl FROM " . DB_GOODS . " WHERE goodsNo = " . $goodsNo;
        return $this->db->fetch($sql);
        
    }

    public function updateEarlyDelivery($in)
    {

        foreach ($in['arrGoodsNo'] as $goodsNo) {
            $param = [
                'useEarlyDelivery = ?',
                'earlyDeliveryUrl = ?',
            ];

            $bind = [
                'isi',
                $in['useEarlyDelivery'][$goodsNo]?$in['useEarlyDelivery'][$goodsNo]:0,
                $in['earlyDeliveryUrl'][$goodsNo]?$in['earlyDeliveryUrl'][$goodsNo]:'',
                $goodsNo,
            ];

            $this->db->set_update_db(DB_GOODS, $param, "goodsNo = ?", $bind);
        }

    }
}