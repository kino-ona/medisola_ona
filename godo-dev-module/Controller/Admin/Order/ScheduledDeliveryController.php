<?php

/**
 * 배분 배송 관리
 * @copyright medisola 
 * @author Conan Kim (gh.kim@medisola.co.kr)
 */

namespace Controller\Admin\Order;

use Exception;
use App;
use Request;
use Session;

/**
 * 상품준비중 리스트
 *
 * @package Bundle\Controller\Admin\Order
 * @author  Jong-tae Ahn <qnibus@godo.co.kr>
 */
class ScheduledDeliveryController extends \Controller\Admin\Controller
{
    /**
     * @var 기본 주문상태
     */
    // private $_currentStatusCode = 'p,g,d,s,r';
    private $_currentStatusCode = 'p';

    /**
     * {@inheritdoc}
     */
    public function index()
    {
        try {
            // --- 메뉴 설정
            $this->callMenu('order', 'order', 'scheduledDelivery');
            $this->addScript(
                [
                    'jquery/jquery.multi_select_box.js',
                    'sms.js'
                ]
            );

            // --- 모듈 호출
            $order = App::load('\\Component\\Order\\OrderAdmin');

            // deliveryCompleteOrderAutomatically 메소드는
            // 관리자 > 메인에 접속시 요청 되어야 하나, orderAdminNew의 메소드가 호출 되는 것으로 유추됨
            // orderAdminNew클래스를 별도 상속해 똑같은 메소드를 추가하면 비효올적일 것 같아.
            // 회차 배송 페이지에 접속될 때 orderAdmin클래스의 메소드가 호출 되는 것으로 대체함.
            $order->deliverCompleteOrderAutomatically();
            
            // Sync all unsynced scheduled deliveries with order goods status
            $orderComponent = App::load('\\Component\\Order\\Order');
            $orderComponent->syncUnsettledDeliveryStatusOfFirstRoundDelivery();

            /* 운영자별 검색 설정값 */
            $searchConf = App::load('\\Component\\Member\\ManagerSearchConfig');
            $searchConf->setGetData();
            $isOrderSearchMultiGrid = gd_isset(Session::get('manager.isOrderSearchMultiGrid'), 'n');
            $this->setData('isOrderSearchMultiGrid', $isOrderSearchMultiGrid);

            // --- 주문 리스트 설정 config 불러오기
            $data = gd_policy('order.defaultSearch');
            gd_isset($data['searchPeriod'], 6);

            // -- _GET 값
            $queryString = Request::get()->toArray();

            //주문리스트 그리드 설정
            $orderAdminGrid = App::load('\\Component\\Order\\OrderAdminGrid');
            $queryString['orderAdminGridMode'] = $orderAdminGrid->getOrderAdminGridMode($queryString['view']);
            // $queryString['orderAdminGridMode'] = 'list_goods_order';
            // list_goods_scheduled_delivery
            $this->setData('orderAdminGridMode', $queryString['orderAdminGridMode']);
            

            if (is_null($queryString['statusMode'])) {
                $queryString['statusMode'] = $this->_currentStatusCode;
            }
            $this->setData('currentStatusCode', $queryString['statusMode']);

            $scheduledDeliveries = $order->fetchScheduledDeliveryList($queryString, $queryString['searchPeriod']);

            //상품준비중 리스트에서 배송정보를 주문별로 처리 할 수 있는지 체크 상태
            $this->setData('search', $scheduledDeliveries['search']);
            $this->setData('checked', $scheduledDeliveries['checked']);
            $this->setData('data', gd_isset($scheduledDeliveries['data']));
            $this->setData('orderGridConfigList', $scheduledDeliveries['orderGridConfigList']);

            $page = App::load('Component\\Page\\Page');
            $this->setData('total', count($scheduledDeliveries['data']));
            $this->setData('page', gd_isset($page));
            $this->setData('pageNum', gd_isset($pageNum));

            // 정규식 패턴 view 파라미터 제거
            $pattern = '/view=[^&]+$|searchFl=[^&]+$|view=[^&]+&|searchFl=[^&]+&/';//'/[?&]view=[^&]+$|([?&])view=[^&]+&/';

            // view 제거된 쿼리 스트링
            $queryString = preg_replace($pattern, '', Request::getQueryString());
            $this->setData('queryString', $queryString);

            // --- 배송 일괄처리 셀렉트박스
            foreach ($order->getOrderStatusAdmin() as $key => $val) {
                if(in_array($key, ['p1', 'g1', 'd1', 'd2', 's1', 'r3']) === true) {
                    switch ($key) {
                        case 'p1':
                            $selectBoxOrderStatus[$key] = __('배송대기');
                            break;
                        default:
                            $selectBoxOrderStatus[$key] = $val;
                            break;
                    }
                }
            }
            $this->setData('selectBoxOrderStatus', $selectBoxOrderStatus);

            // 배송 업체
            $delivery = App::load(\Component\Delivery\Delivery::class);
            $tmpDelivery = $delivery->getDeliveryCompany(null, true);
            $deliveryCom[0] = $searchDeliveryCom[0] = '= ' . __('배송 업체') . ' =';
            $deliverySno = 0;
            if (empty($tmpDelivery) === false) {
                foreach ($tmpDelivery as $key => $val) {
                    // 기본 배송업체 sno
                    if ($key == 0) {
                        $deliverySno = $val['sno'];
                    }
                    if($val['deliveryFl'] === 'y'){
                        //택배 업체일때만
                        $deliveryCom[$val['sno']] = $val['companyName'];
                    }
                    //택배 등 모든 배송수단 포함 - 검색용
                    $searchDeliveryCom[$val['sno']] = $val['companyName'];
                }
                unset($tmpDelivery);
            }
            $this->setData('deliveryCom', gd_isset($deliveryCom));
            $this->setData('deliverySno', gd_isset($deliverySno));

            // 메모 구분
            $orderAdmin = App::load('\\Component\\Order\\OrderAdmin');
            $tmpMemo = $orderAdmin->getOrderMemoList(true);
            $arrMemoVal = [];
            foreach($tmpMemo as $key => $val){
                $arrMemoVal[$val['itemCd']] = $val['itemNm'];
            }
            $this->setData('memoCd', $arrMemoVal);

            // --- 템플릿 정의
            $this->getView()->setDefine('layoutOrderSearchForm', Request::getDirectoryUri() . '/layout_scheduled_delivery_search_form.php');// 검색폼
            $this->getView()->setDefine('layoutOrderList', Request::getDirectoryUri() . '/layout_scheduled_delivery_simple_list.php');// 리스트폼
            

            // --- 템플릿 변수 설정
            $this->setData('statusStandardCode', $order->statusStandardCode);
            $this->setData('statusStandardNm', $order->statusStandardNm);
            $this->setData('statusListCombine', $order->statusListCombine);
            $this->setData('statusListExclude', $order->statusListExclude);
            $this->setData('type', $order->getOrderType());
            $this->setData('channel', $order->getOrderChannel());
            $this->setData('settle', $order->getSettleKind());
            $this->setData('formList', $order->getDownloadFormList());
            $this->setData('statusExcludeCd', $order->statusExcludeCd);
            $this->setData('statusSearchableRange', $order->getOrderStatusList($this->_currentStatusCode));

            // 공급사와 동일한 페이지 사용
            $this->getView()->setPageName('order/scheduled_delivery.php');

        } catch (Exception $e) {
            throw $e;
        }
    }
}
