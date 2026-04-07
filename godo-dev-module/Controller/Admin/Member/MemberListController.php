<?php

namespace Controller\Admin\Member;

use Component\Member\Util\MemberUtil;


class MemberListController extends \Bundle\Controller\Admin\Member\MemberListController {
    public function index() {
		parent::index();

        $joinedViaOptions = MemberUtil::getDistinctJoinedViaWithCount();
        $this->setData('joinedViaOptions', $joinedViaOptions);
    }
}