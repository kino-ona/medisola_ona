<?php

namespace Bundle\Component\VisitStatistics;

use Bundle\Component\Page\Page;
use Component\Mall\Mall;
use DateTime;
use Framework\Utility\DateTimeUtils;
use Framework\StaticProxy\Proxy\Session;
use Framework\Utility\ComponentUtils;
use Framework\Utility\StringUtils;
use Request;

class VisitAnalysis
{
    /* dev */
    protected $devSummaryUrl = 'https://alpha-godo-api.godo.co.kr/godomall5/statistic/summary/';
    protected $devTermUrl = 'https://alpha-godo-api.godo.co.kr/godomall5/statistic/term/';
    protected $devKeywordUrl = 'https://alpha-godo-api.godo.co.kr/godomall5/statistic/keyword/';

    /* real */
    protected $summaryUrl = 'https://godo-api.godo.co.kr/godomall5/statistic/summary/';
    protected $termUrl = 'https://godo-api.godo.co.kr/godomall5/statistic/term/';
    protected $keywordUrl = 'https://godo-api.godo.co.kr/godomall5/statistic/keyword/';

    protected $db;

    /**
     * VisitSAnalysis constructor.
     *
     * @param null $date YYYY-mm-dd
     */
    public function __construct($date = null)
    {
        if (!is_object($this->db)) {
            $this->db = \App::load('DB');
        }

        $globals = \App::getInstance('globals');

        $this->license = $globals->get('gLicense');
        $this->shopSno = $this->license['godosno'];
    }

    /**
     * 방문자 분석 - 전체 및 검색 상점 검색 일별 방문현황 데이터
     *
     * @param array $searchDate [0]시작일,[1]종료일
     *
     * @return array $getDataArr 일별 방문현황 정보
     *
     * @throws \Exception
     * @author sueun-choi
     */
    public function getVisitTotalData($searchData)
    {
        // search 날짜 세팅 YYYY-MM-DD 00:00:00.000000
        $sDate = new DateTime($searchData['date'][0]);
        $eDate = new DateTime($searchData['date'][1]);
        $dateDiff = date_diff($sDate, $eDate);

        if ($searchData['date'][0] > $searchData['date'][1]) {
            throw new \Exception(__('시작일이 종료일보다 클 수 없습니다.'));
        }

        if ($searchData['device'] != 'all') {
            if ($searchData['device'] == 'pc') {
                $searchData['device'] = 'P';
            } else {
                $searchData['device'] = 'M';
            }
        } else {
            unset($searchData['device']);
        }

        if ($searchData['country'] == 1) {
            $searchData['country'] = 'kr';
        } elseif ($searchData['country'] == 2) {
            $searchData['country'] = 'en';
        } elseif ($searchData['country'] == 3) {
            $searchData['country'] = 'cn';
        } elseif ($searchData['country'] == 4) {
            $searchData['country'] = 'jp';
        } else {
            unset($searchData['country']);
        }

        if (empty($searchData['inflow']) === false && $searchData['inflow'] != 'all') {
            $searchData['engine'] = $searchData['inflow'];
        }
        unset($searchData['inflow']);

        // 상단 요약_일별 방문 현황
        $getDataJson['top'] = $this->getVisitAnalysisDataByApi('top', $searchData);

        // 차트, 하단 table
        $getDataJson['down'] = $this->getVisitAnalysisDataByApi('down', $searchData);

        return $getDataJson;
    }

    /**
     * 방문자 분석 - 일별 방문현황 데이터 메인용
     *
     * @param array $searchDate [0]시작일,[1]종료일
     *
     * @return array $getDataArr 일별 방문현황 정보
     *
     * @throws \Exception
     * @author sueun-choi
     */
    public function getVisitTotalDataByMain($searchData)
    {
        // search 날짜 세팅 YYYY-MM-DD 00:00:00.000000
        $sDate = new DateTime($searchData['date'][0]);
        $eDate = new DateTime($searchData['date'][1]);

        if ($searchData['mall'] == 1) {
            $searchData['country'] = 'kr';
        } elseif ($searchData['mall'] == 2) {
            $searchData['country'] = 'en';
        } elseif ($searchData['mall'] == 3) {
            $searchData['country'] = 'cn';
        } elseif ($searchData['mall'] == 4) {
            $searchData['country'] = 'jp';
        } else {
            unset($searchData['mall']);
        }
        unset($searchData['mall']);

        // 상단 요약_일별 방문 현황
        $getDataJson['top'] = $this->getVisitAnalysisDataByApi('top', $searchData);

        return $getDataJson;
    }

    /**
     * 방문현황 상단요약, 하단 정보 출력(일별, 시간대별, 요일별, 월별, 페이지뷰, 유입, 유입검색어)
     *
     * @param Mixed $searchData
     *
     * @return array 방문통계 정보
     *
     * @author sueun-choi
     */
    public function getVisitAnalysisDataByApi($section, $searchData)
    {
        $searchData['startDate'] = DateTimeUtils::dateFormat('Y-m-d', $searchData['date'][0]);
        $searchData['endDate'] = DateTimeUtils::dateFormat('Y-m-d', $searchData['date'][1]);
        unset($searchData['date']);

        if ($section == 'top') {
            // 개발
            // 방문자 분석(일, 시간대, 요일, 월, 페이지뷰 별 현황)
            if($searchData['type'] != 'keyword') {
                $returnData = $this->_connectCurl($this->summaryUrl, $searchData);
            }
        } elseif ($section == 'down') {
            // 개발
            if ($searchData['type'] == 'inflow' || $searchData['type'] == 'keyword') {
                $returnData = $this->_connectCurl($this->keywordUrl, $searchData);
            } else {
                $returnData = $this->_connectCurl($this->termUrl, $searchData);
            }
        }

        return $returnData;

    }

    /**
     * Curl Connect
     *
     * @param string       $curlUrl            url
     * @param string|array $curlData           CURLOPT_POSTFIELDS DATA
     *
     * @return array
     */
    private function _connectCurl($curlUrl, $curlData)
    {
        $curlConnection = curl_init();
        curl_setopt($curlConnection, CURLOPT_URL, $curlUrl . $this->shopSno . '?' . http_build_query($curlData));
        curl_setopt($curlConnection, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlConnection, CURLOPT_SSL_VERIFYPEER, false);
        $responseData = curl_exec($curlConnection);
        curl_close($curlConnection);

        $result = json_decode($responseData, true);
        if ($result['msg'] == 'complete') {
            return $result['data'];
        } else {
            //로그 예시
            \Logger::channel('http')->info('GODO_VISIT_ANALYSIS_SERVER_ERROR', $result['code']);
        }
    }

    /**
     * 방문자통계 - 메인 테이블 / 차트
     * getDayMainTableChartVisit
     *
     * @param $searchDate visitYMD
     *
     * @return array
     * @throws \Exception
     */
    public function getDayMainTableChartVisit($searchDate)
    {
        $searchDate['type'] = 'daily';
        $dayData = $this->getVisitTotalData($searchDate);
        $searchDate['type'] = 'pageview';
        $pvData = $this->getVisitTotalData($searchDate);

        $returnData['visit']['total'] = $dayData['top']['total']['visitTotal'];
        $returnData['visit']['pc'] = $dayData['top']['pc']['visitTotal'];
        $returnData['visit']['mobile'] = $dayData['top']['mobile']['visitTotal'];
        $returnData['visit']['data'] = $dayData['down'];

        $returnData['pv']['total'] = $pvData['top']['total']['pvTotal'];
        $returnData['pv']['pc'] = $pvData['top']['pc']['pvTotal'];
        $returnData['pv']['mobile'] = $pvData['top']['mobile']['pvTotal'];
        $returnData['pv']['data'] = $pvData['down'];

        return $returnData;
    }

    /**
     * 방문자통계v2 - 메인 탭
     * getDayMainTabVisit
     *
     * @param $searchData   orderYMD / mallSno
     *
     * @return array
     * @throws \Exception
     */
    public function getDayMainTabVisit($searchData)
    {
        if ($searchData['mallSno'] == 'all') {
            unset($searchData['mallSno']);
        }

        if (empty($searchData['scmNo']) === false) {
            unset($searchData['scmNo']);
        }

        $sDate = new DateTime($searchData['orderYMD'][0]);
        $eDate = new DateTime($searchData['orderYMD'][1]);
        unset($searchData['orderYMD']);

        $searchData['date'][0] = $sDate->modify('-1 days')->format('Y-m-d');
        $searchData['date'][1] = $eDate->modify('-1 days')->format('Y-m-d');
        $searchData['type'] = 'daily';
        $returnVisitCount = $this->getVisitTotalDataByMain($searchData)['top']['total']['visitTotal'];

        return $returnVisitCount;
    }
}