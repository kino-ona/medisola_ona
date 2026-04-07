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
namespace Controller\Front\Member;

use Session;

/**
 * Class WanbanJoinAgreementController
 * @package Controller\Front\Member
 * @author  Claude
 */
class WanbanJoinAgreementController extends \Bundle\Controller\Front\Member\JoinAgreementController
{
    public function index()
    {
        // Call Bundle method to get all standard join agreement functionality
        parent::index();
        
        // Add Wanban-specific session tracking
        Session::set('wanban', 'true');
        
        // Add Wanban-specific template data
        $this->setData('wanbanFl', 'y');
        $this->setData('wanban', 'true');
    }
}