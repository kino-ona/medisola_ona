<?php
namespace Controller\Front;

use Globals;
use Session;
use Response;
use Request;

/**
 * 테스트용
 */
class LandingController extends \Controller\Front\Controller
{
    /**
     * {@inheritdoc}
     */
    public function index()
    {
        $setData = 'This is landing page';
        $this->setData('setData', $setData);
    }
}