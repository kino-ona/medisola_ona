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
use Framework\Debug\Exception\LayerNotReloadException;
use Logger;
use Message;
use Request;

class OurMenuPsController extends \Bundle\Controller\Admin\Controller
{
    public function index()
    {
        try {
//            Logger::debug(__METHOD__);
//            $this->redirect('our_menu_regist.php');
//            $ourMenuAdmin = new OurMenuAdmin();
            $ourMenuAdmin = \App::load(\Component\OurMenu\OurMenuAdmin::class);
            $ypage = Request::post()->get('ypage');
//            echo '<div >' . $ypage . '</div>';
            switch (Request::post()->get('mode')) {

                case 'insertOurMenuRegist':
                case 'modifyOurMenuRegist':
                    $postValue = Request::post()->toArray();
                    $filesValue = Request::files()->toArray();
                    $ourMenuAdmin->saveOurMenu($postValue, $filesValue);
//                    $this->layer(__('저장이 완료되었습니다.'), 'top.location.href="our_menu_regist.php";');
                    $this->redirect('our_menu_regist.php?result=success');
                    break;
            }

        } catch (Exception $e) {
            throw new LayerNotReloadException($e->getMessage()); //새로고침안됨
//            echo '<div >' . $e . '</div>';

        }
//        exit();
    }
}

