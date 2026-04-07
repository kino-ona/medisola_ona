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
 * Class WanbanJoinAgreementController
 * @package Controller\Mobile\Member
 * @author  Claude
 */
class WanbanJoinAgreementController extends \Bundle\Controller\Mobile\Controller
{
    public function index()
    {
        /** @var \Controller\Front\Member\WanbanJoinAgreementController $front */
        $front = \App::load('\\Controller\\Front\\Member\\WanbanJoinAgreementController');
        $front->index();
        $this->setData($front->getData());
    }
}