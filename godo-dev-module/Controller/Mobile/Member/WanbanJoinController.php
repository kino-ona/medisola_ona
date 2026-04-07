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
 * Class WanbanJoinController
 * @package Controller\Mobile\Member
 * @author  Claude
 */
class WanbanJoinController extends \Bundle\Controller\Mobile\Controller
{
    public function index()
    {
        /** @var \Controller\Front\Member\WanbanJoinController $front */
        $front = \App::load('\\Controller\\Front\\Member\\WanbanJoinController');
        $front->index();
        $this->setData($front->getData());
    }
}