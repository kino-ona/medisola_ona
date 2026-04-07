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

use Component\Database\DBTableField;
use Framework\Utility\DateTimeUtils;

/**
 * 상품 공통정보 관리 Class
 * - 상품 공통정보 상단/하단 분리 기능 추가
 *
 * @package Component\Goods
 */
class CommonContent extends \Bundle\Component\Goods\CommonContent
{
    /**
     * 상품 공통정보 조회 (상단/하단 분리)
     *
     * @param int $goodsNo 상품번호
     * @param int $scmNo   공급사번호
     * @return array ['top' => string, 'bottom' => string]
     */
    public function getCommonContent($goodsNo, $scmNo)
    {
        $arrField = DBTableField::setTableField('tableCommonContent',['commonTargetFl', 'commonCd', 'commonExGoods', 'commonExCategory', 'commonExBrand', 'commonExScm', 'commonHtmlContentSameFl', 'commonHtmlContent', 'commonHtmlContentMobile', 'commonPositionType']);
        $arrBind = $arrWhere = $retContent = [];
        $retContentTop = [];
        $retContentBottom = [];

        $arrWhere[] = '(`commonStatusFl` = ? OR (`commonStatusFl` = ? AND (? BETWEEN `commonStartDt` AND `commonEndDt`)))';
        $arrWhere[] = '`commonUseFl` = ?';
        $this->db->bind_param_push($arrBind, 's', 'n');
        $this->db->bind_param_push($arrBind, 's', 'y');
        $this->db->bind_param_push($arrBind, 's', DateTimeUtils::dateFormat('Y-m-d H:i', 'now'));
        $this->db->bind_param_push($arrBind, 's', 'y');

        $this->db->strField = implode(', ', $arrField);
        $this->db->strWhere = implode(' AND ', gd_isset($arrWhere));
        $this->db->strOrder = 'sno ASC';

        $query = $this->db->query_complete();
        $strSQL = 'SELECT ' . array_shift($query) . ' FROM ' . DB_COMMON_CONTENT . implode(' ', $query);
        $data = $this->db->query_fetch($strSQL, $arrBind, true);

        if (empty($data) === false) {
            $cate = \App::load('\\Component\\Category\\Category');

            $cateCd = $cate->getCateCd($goodsNo);
            $brandCd = $cate->getCateCd($goodsNo, 'brand');

            foreach ($data as $val) {
                $val = gd_htmlspecialchars_stripslashes($val);
                $viewCommonContent = true;

                $commonExGoods = explode(INT_DIVISION, $val['commonExGoods']);
                $commonExCategory = explode(INT_DIVISION, $val['commonExCategory']);
                $commonExBrand = explode(INT_DIVISION, $val['commonExBrand']);
                $commonExScm = explode(INT_DIVISION, $val['commonExScm']);

                // 예외
                if (in_array($goodsNo, $commonExGoods) === true) {
                    $viewCommonContent = false;
                }
                if (in_array($scmNo, $commonExScm) === true) {
                    $viewCommonContent = false;
                }
                if (empty($commonExCategory) === false && empty($cateCd) === false) {
                    foreach ($commonExCategory as $value) {
                        if (in_array($value, $cateCd)) {
                            $viewCommonContent = false;
                            break;
                        }
                    }
                }
                if (empty($commonExBrand) === false && empty($brandCd) === false) {
                    foreach ($commonExBrand as $value) {
                        if (in_array($value, $brandCd)) {
                            $viewCommonContent = false;
                            break;
                        }
                    }
                }

                //특정
                if ($viewCommonContent === true) {
                    if ($val['commonTargetFl'] != 'all') {
                        $viewCommonContent = false;
                    }
                    $commonCd = explode(INT_DIVISION, $val['commonCd']);

                    if ($val['commonTargetFl'] != 'all' && empty($commonCd) === false) {
                        switch ($val['commonTargetFl']) {
                            case 'goods':
                                if (in_array($goodsNo, $commonCd) === true) {
                                    $viewCommonContent = true;
                                }
                                break;
                            case 'category':
                                foreach ($commonCd as $value) {
                                    if (in_array($value, $cateCd) === true) {
                                        $viewCommonContent = true;
                                        break;
                                    }
                                }
                                break;
                            case 'brand':
                                foreach ($commonCd as $value) {
                                    if (in_array($value, $brandCd) === true) {
                                        $viewCommonContent = true;
                                        break;
                                    }
                                }
                                break;
                            case 'scm':
                                if (in_array($scmNo, $commonCd) === true) {
                                    $viewCommonContent = true;
                                }
                                break;
                        }
                    }
                }

                if ($viewCommonContent === true) {
                    $content = '';
                    if (\Request::isMobile()) {
                        if ($val['commonHtmlContentSameFl'] == 'y') {
                            $content = stripslashes($val['commonHtmlContent']);
                        } else {
                            $content = stripslashes($val['commonHtmlContentMobile']);
                        }
                    } else {
                        $content = stripslashes($val['commonHtmlContent']);
                    }

                    // 노출 위치에 따라 분리 저장 (기본값: bottom)
                    $positionType = isset($val['commonPositionType']) ? $val['commonPositionType'] : 'bottom';
                    if ($positionType === 'top') {
                        $retContentTop[] = $content;
                    } else {
                        $retContentBottom[] = $content;
                    }
                }
            }
        }

        return [
            'top' => @implode('', $retContentTop),
            'bottom' => @implode('', $retContentBottom)
        ];
    }
}
