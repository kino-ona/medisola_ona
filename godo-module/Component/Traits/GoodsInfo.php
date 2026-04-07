<?php

namespace Component\Traits;

use App;
use Request;
use Framework\Utility\SkinUtils;

/**
* 상품정보 조회 관련 
*
* @author webnmobile
*/
trait GoodsInfo
{
	/**
	* 상품정보 추출 
	*
	* @param Array $goodsNoList 상품번호 목록 
	*
	* @return Array 상품정보
	*/
	public function getGoods($goodsNoList = [])
	{
		$list = [];
		if ($goodsNoList) {
			$goodsBenefit = App::load(\Component\Goods\GoodsBenefit::class);
			
			if (!is_array($goodsNoList))
				$goodsNoList = [$goodsNoList];
			
			$goods = App::load(\Component\Goods\Goods::class);
		
			foreach ($goodsNoList as $goodsNo) {
				$sql = "SELECT delFl FROM " . DB_GOODS.  " WHERE goodsNo = ?";
				$row = $this->db->query_fetch($sql, ["i", $goodsNo], false);
				if ($row['delFl'] == 'y')
					continue;
				
				$info = $goods->getGoodsView($goodsNo);
				if ($info) {
					if ($info['brandCd']) {
						$sql = "SELECT cateNm FROM " . DB_CATEGORY_BRAND . " WHERE cateCd LIKE ?";
						$row = $this->db->query_fetch($sql, ["s", $info['brandCd']], false);
						$info['brandNm'] = $row['cateNm'];
					}
 					/* 리스트 이미지 */
					$imageList = $goods->getGoodsImage($goodsNo, ["main"]);
					if ($imageList) {
						$val = $imageList[0];
						$info['goodsImageSrc'] = SkinUtils::imageViewStorageConfig($val['imageName'], $info['imagePath'], $info['imageStorage'], 500, 'goods')[0];
					}
					
					/* 메인 상세 이미지 */
					$imageList = $goods->getGoodsImage($goodsNo, ["detail"]);
					if ($imageList) {
						$val = $imageList[0];
						$info['goodsImageMainSrc'] = SkinUtils::imageViewStorageConfig($val['imageName'], $info['imagePath'], $info['imageStorage'], 500, 'goods')[0];
					}
					
					$list[] = $info;
				}
			} // endforeach 
		} // endif 
		
		return $list;
	}
}