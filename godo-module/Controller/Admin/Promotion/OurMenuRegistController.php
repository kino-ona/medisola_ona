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

namespace Controller\Admin\Promotion;

use Component\Database\DBTableField;
use Exception;
use Framework\Debug\Exception\LayerException;
use Request;

class OurMenuRegistController extends \Bundle\Controller\Admin\Controller
{
    public function index()
    {
        try {
            $ourMenuData = array();
            $ourMenuAdmin = \App::load(\Component\OurMenu\OurMenuAdmin::class);
            // 우리메뉴 리스트 페이지 번호
            $ypage = Request::get()->get('ypage');
            $result = Request::get()->get('result');

            $ourMenuData['mode'] = 'insertOurMenuRegist';

            $totalCount = $ourMenuAdmin->getTotalCount();
            $ourMenuId = Request::get()->get('id');
            if ($ourMenuId > 0) {
                $ourMenuData = $ourMenuAdmin->getOurMenuInfo($ourMenuId, "*");
                $ourMenuData['ourMenuImage'] = $ourMenuAdmin->getOurMenuImageData($ourMenuData['imageUrlWeb']);
                $ourMenuData['mode'] = 'modifyOurMenuRegist';
            }
            // --- 메뉴 설정
            $this->callMenu('promotion', 'ourMenu', 'ourMenuRegist');
        } catch (Exception $e) {
            throw new LayerException($e->getMessage());
        }
        if ($result == 'success') {
//            $this->layer(__('저장이 완료되었습니다.'));
        }

        $checked['displayChannel'][$ourMenuData['displayChannel']] = 'checked="checked"';

        $this->setData('ourMenuData', gd_isset($ourMenuData));
        $this->setData('ypage', gd_isset($ypage, 1));
        $this->setData('totalCount', gd_isset($totalCount));
        $this->setData('checked', gd_isset($checked));
    }
}