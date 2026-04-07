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

use Exception;
use Framework\Debug\Exception\LayerException;
use Request;

class OurMenuListController extends \Bundle\Controller\Admin\Controller
{

    /**
     * 우리메뉴 리스트
     * [관리자 모드] 우리메뉴 리스트
     *
     * @author su
     * @version 1.0
     * @since 1.0
     * @copyright ⓒ 2016, NHN godo: Corp.
     * @throws LayerException
     * @internal param array $get
     * @internal param array $post
     * @internal param array $files
     */
    public function index()
    {

        // --- 모듈 호출
        try {
            $ourMenuAdmin = \App::load(\Component\OurMenu\OurMenuAdmin::class);
            $getData = $ourMenuAdmin->getOurMenuAdminList();
            $page = \App::load('\\Bundle\\Component\\Page\\Page'); // 페이지 재설정
            // --- 메뉴 설정
            $this->callMenu('promotion', 'ourMenu', 'ourMenuList');
        } catch (Exception $e) {
            echo '<div >' . 'error: ' . $e . '</div>';
            $this->setData('error', gd_isset($e));
            throw new LayerException($e->getMessage());
        }

        // --- 관리자 디자인 템플릿
        $this->setData('data', gd_isset($getData['data']));
        $this->setData('list', gd_isset($getData['list']));
        $this->setData('page', $page);
//        exit();
    }
}
