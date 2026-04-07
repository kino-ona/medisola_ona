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
 * @link      http://www.godo.co.kr
 */
namespace Controller\Front\Main;

/**
 * 메인 페이지
 *
 * @author Shin Donggyu <artherot@godo.co.kr>
 */
class IndexController extends \Bundle\Controller\Front\Main\IndexController
{
    public function index()
    {
        $ourMenu = \App::load('\\Component\\OurMenu\\OurMenu');
        $getData = $ourMenu->getOurMenus();
        foreach ($getData as $key => $val) {
            $getData[$key]['ourMenuImage'] = $ourMenu->getOurMenuImageData($val['imageUrlWeb']);
            $tags = explode(",", $val['tags']);
            $getData[$key]['tags'] = $tags;
        }
        $data = array(
            "data1" => "sample data1",
            "data2" => "sample data2",
            "data3" => "sample data3",
            "ourMenu" => $getData,
        );
        $this->setData($data);
//        exit();
    }
}