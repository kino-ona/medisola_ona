<?php

/**
 * This is commercial software, only users who have purchased a valid license
 * and accept to the terms of the License Agreement can install and use this
 * program.
 *
 * Do not edit or add to this file if you wish to upgrade Godomall5 to newer
 * versions in the future.
 *
 * @copyright ⓒ 2016, NHN godo: Corp.
 * @link http://www.godo.co.kr
 */

namespace Component\Goods;

/**
 * 상품 Class
 * - 상품 공통정보 상단/하단 분리 처리
 *
 * @package Component\Goods
 */
class Goods extends \Bundle\Component\Goods\Goods
{
    /**
     * 상품 상세 정보 조회 (getGoodsView override)
     * - commonContent를 commonContentTop / commonContentBottom으로 분리
     *
     * @param int $goodsNo 상품번호
     * @return array 상품 정보
     * @throws \Exception
     */
    public function getGoodsView($goodsNo)
    {
        // 부모 메소드 호출
        $getData = parent::getGoodsView($goodsNo);

        // 공통정보 상단/하단 분리 처리
        if (gd_is_plus_shop(PLUSSHOP_CODE_COMMONCONTENT) === true) {
            $commonContent = \App::load('\\Component\\Goods\\CommonContent');
            $commonContentData = $commonContent->getCommonContent($getData['goodsNo'], $getData['scmNo']);

            $getData['commonContentTop'] = $commonContentData['top'];
            $getData['commonContentBottom'] = $commonContentData['bottom'];
            // 하위 호환성을 위해 기존 commonContent도 유지 (bottom 내용)
            $getData['commonContent'] = $commonContentData['bottom'];
        }

        return $getData;
    }
}
