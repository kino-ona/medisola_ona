<?php

namespace Controller\Admin\Goods;

use App;
use Request;
use Exception;
use Globals;
use Component\Wm\FirstDelivery;

class FirstDeliveryGoodsController extends \Controller\Admin\Controller
{
	public function index()
	{
		$this->callMenu('goods' , 'firstDelivery' , 'first_delivery_goods');
		
		try {
            $in = \Request::request()->all();
            
            // --- 모듈 호출
            $cate = \App::load('\\Component\\Category\\CategoryAdmin');
            $brand = \App::load('\\Component\\Category\\BrandAdmin');
            $goods = \App::load('\\Component\\Goods\\GoodsAdmin');

            /* 운영자별 검색 설정값 */
            $searchConf = \App::load('\\Component\\Member\\ManagerSearchConfig');
            $searchConf->setGetData();

            $getData = $goods->getAdminListBatch('image');

            $getIcon = $goods->getManageGoodsIconInfo();
            $page = \App::load('\\Component\\Page\\Page'); // 페이지 재설정

            $this->getView()->setDefine('goodsSearchFrm',  Request::getDirectoryUri() . '/goods_list_search4.php');

            $this->addScript([
                'jquery/jquery.multi_select_box.js',
            ]);


            //정렬 재정의
            $getData['search']['sortList'] = array(
                'g.goodsNo desc' => sprintf(__('등록일 %1$s'), '↓'),
                'g.goodsNo asc' => sprintf(__('등록일 %1$s'), '↑'),
                'goodsNm asc' => sprintf(__('상품명 %1$s'), '↓'),
                'goodsNm desc' => sprintf(__('상품명 %1$s'), '↑'),
                'companyNm asc' => sprintf(__('공급사 %1$s'), '↓'),
                'companyNm desc' => sprintf(__('공급사 %1$s'), '↑'),
                'fixedPrice asc' => sprintf(__('정가 %1$s'), '↓'),
                'fixedPrice desc' => sprintf(__('정가 %1$s'), '↑'),
                'costPrice asc' => sprintf(__('매입가 %1$s'), '↓'),
                'costPrice desc' => sprintf(__('매입가 %1$s'), '↑'),
                'goodsPrice asc' => sprintf(__('판매가 %1$s'), '↓'),
                'goodsPrice desc' => sprintf(__('판매가 %1$s'), '↑'),
            );


			$first = \App::load(FirstDelivery::class);
			$getData['data'] = $first -> getFirstCfgGoodsData($getData['data']);
			

            $this->setData('goods', $goods);
            $this->setData('cate', $cate);
            $this->setData('brand', $brand);
            $this->setData('data', $getData['data']);
            $this->setData('search', $getData['search']);
            $this->setData('checked', $getData['checked']);
            $this->setData('selected', $getData['selected']);
            $this->setData('batchAll', gd_isset($getData['batchAll']));
            $this->setData('getIcon', $getIcon);
            $this->setData('page', $page);

        } catch (Exception $e) {
            throw $e;
        }
	}
}