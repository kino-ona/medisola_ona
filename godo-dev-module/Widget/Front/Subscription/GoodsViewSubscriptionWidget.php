<?php
namespace Widget\Front\Subscription;

use App;

class GoodsViewSubscriptionWidget extends \Widget\Front\Widget 
{
    public function index()
    {
        $obj = App::load(\Component\Subscription\Subscription::class);
        $goodsView = $this->getData("goodsView");
        if (!$goodsView['goodsNo'])
            return;

        $goodsPrice = $goodsView['goodsPrice'];
        $goodsNo = $goodsView['goodsNo'];

        if (!$obj->isSubscriptionGoods($goodsNo))
            return;

        $subCfg = $obj->setGoods($goodsNo)
                                ->setMode("getGoodsCfg")
                                ->setPrice($goodsPrice)
                                ->get();

		if ($period = $subCfg['period']) {
			foreach ($period as $k => $v) {
				$v = explode("_", $v);
				$period[$k] = $v;
			}
			
			$subCfg['period'] = $period;
		}
		
        $this->setData('goodsNo', $goodsNo);
        $this->setData("goodsPrice", $goodsPrice);
        $this->setData('goodsView', $goodsView);
        $this->setData($subCfg);
    }
}