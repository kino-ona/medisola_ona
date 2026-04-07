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

namespace Controller\Admin\Goods;

use Globals;
use Request;

/**
 * 상품 공통정보 등록/수정 Controller
 * - 노출위치 필드 추가 (상단/하단)
 *
 * @author Bag YJ <kookoo135@godo.co.kr>
 */
class CommonContentRegistController extends \Bundle\Controller\Admin\Goods\CommonContentRegistController
{
    /**
     * index
     *
     * @throws \Exception
     */
    public function index()
    {
        // 부모 메소드 호출
        parent::index();

        $commonContent = \App::load('\\Component\\Goods\\CommonContent');
        $getValue = Request::get()->all();

        // 기존 checked 배열 가져오기
        $checked = $this->getData('checked');
        $getData = $this->getData('data');

        // 노출위치 checked 처리 추가
        if (empty($getValue['sno']) === true) {
            // 신규 등록시 기본값: bottom
            $checked['commonPositionType']['bottom'] = 'checked = "checked"';
        } else {
            // 수정시: 기존 데이터 로드 (기본값: bottom)
            $positionType = isset($getData['commonPositionType']) && $getData['commonPositionType'] ? $getData['commonPositionType'] : 'bottom';
            $checked['commonPositionType'][$positionType] = 'checked = "checked"';
        }

        // 업데이트된 checked 배열 다시 설정
        $this->setData('checked', $checked);
    }
}
