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

namespace Controller\Mobile\Member;

/**
 * Class 완반X메디쏠라 회원가입 방법 선택
 * @package Bundle\Controller\Mobile\Member
 * @author  conan
 */
class WanbanJoinMethodController extends \Bundle\Controller\Mobile\Controller
{
    public function index()
    {
        /** @var \Bundle\Controller\Front\Member\WanbanJoinMethodController $front */
        $front = \App::load('\\Controller\\Front\\Member\\WanbanJoinMethodController');
        $front->index();
        $this->setData($front->getData());
        
        if ($front->getData('useKakaoLogin') === true) {
            $scripts[] = 'gd_kakao.js';
        }
        $this->addScript($scripts);
    }
}
