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
namespace Component\Sms;

use Component\Sms\SmsSender;
use Framework\Utility\ArrayUtils;
use Framework\Utility\ComponentUtils;
use Framework\Utility\StringUtils;
use SplObserver;

/**
 * Class SMS 자동 발송
 * 기존 SMS::smsAutoSend 함수를 대신 사용하는 클래스
 * 기존 smsAutoSend 함수 호출 시 전달하던 파라미터는 setter 함수를 통해 전역변수로 설정하면 된다.
 *
 * @package Bundle\Component\Sms
 * @author  yjwee
 */
class SmsAuto extends \Bundle\Component\Sms\SmsAuto implements \SplSubject
{
    /**
     * SMS 자동발송 함수
     *
     * @param string $kakaoAlrimIgnoreFlag 카카오알림톡 설정 무시 (기본 n:무시안함)
     *
     * @return array|bool 성공, 실패 Count
     */
    public function autoSend_wmExtend($kakaoAlrimIgnoreFlag = 'n' , $orderType)
    {
        //카카오알림톡설정 무시 값으로 넘어오면처리
        if ($kakaoAlrimIgnoreFlag == 'y') {
            $this->kakaoAlrimIgnoreFlag = 'y';
        }


		// 추가알림톡 전송타입/세팅 시작

        if($orderType == 59) {

            $this->kakaoAlrimAutoConfig['order']['PRESENT_NOADDRESS59'] = array(
                'memberSend' => 'y',
                'smsOrderDate' => '999',
                'memberTemplateCode' => 'order_59',
            );

        }

        if($orderType == 58) {
            $this->kakaoAlrimAutoConfig['order']['PRESENT_NOADDRESS58'] = array(
                'memberSend' => 'y',
                'smsOrderDate' => '999',
                'memberTemplateCode' => 'order_58',
            );
        }




        // 추가알림톡 전송타입/세팅 시작
        if($orderType == 57){
            $this->kakaoAlrimAutoConfig['order']['PRESENT_NOADDRESS57'] = array(
                'memberSend' => 'y',
                'smsOrderDate' => '999',
                'memberTemplateCode' => 'order_57',
            );


        }




        // 추가알림톡 전송타입/세팅 시작
        if($orderType == 56){
            $this->kakaoAlrimAutoConfig['order']['PRESENT_NOADDRESS56'] = array(
                'memberSend' => 'y',
                'smsOrderDate' => '999',
                'memberTemplateCode' => 'order_56',
            );


        }


        // 추가알림톡 전송타입/세팅 종료
		
        $logger = \App::getInstance('logger');
        $session = \App::getInstance('session');
        $result = [];
        try {
            if ($session->has(SESSION_GLOBAL_MALL)) {
                throw new \Exception('Not support sending SMS in overseas shops');
            }

            if (gd_is_plus_shop(PLUSSHOP_CODE_KAKAOALRIMLUNA) === true && $this->kakaoAlrimIgnoreFlag == 'n' && $this->kakaoAlrimLunaAutoConfig['useFlag'] == 'y' && $this->kakaoAlrimLunaAutoConfig[strtolower($this->smsType) . 'UseFlag'] == 'y') {

                $this->checkSmsTypeAndCodeWithReceiverKakao('luna');
                $this->checkSmsAutoTypeAndSmsContentWheresKakao('luna');
                $this->checkDefaultSmsAutoCodeAndReceiverKakao('luna');
                if ($this->smsType == \Component\Sms\SmsAutoCode::MEMBER && $this->smsAutoCodeType == \Component\Sms\Code::JOIN) {
                    if ($this->kakaoAlrimLunaAutoConfig[$this->smsType][$this->smsAutoCodeType]['smsDisapproval'] != 'y' && $this->replaceArguments['appFl'] == 'n') {
                        throw new \Exception('Disapproval member');
                    }
                }
                $smsContents = $this->getSmsContentsKakaoLuna($this->smsContentWheres);
                $logger->info(sprintf('Count sms contents.[%d]', count($smsContents)));

                // 카카오알림톡 자동 발송 예약시간 설정
                if ($this->smsType == SmsAutoCode::MEMBER && array_key_exists($this->smsAutoCodeType, Sms::KAKAO_AUTO_RESERVATION_DEFAULT_TIME)) {
                    $this->setSmsAutoSendDate($this->getKakaoAlrimAutoReserveTime($this->smsAutoCodeType,'luna'));
                }
            }
            //기본값들 셋팅 카카오알림 설정에 따른 분기
            //카카오알림톡설정무시가 아니면 & 카카오 알림톡 사용설정일때 & 카카오 알림톡 smsType영역 설정이 사용설정일때
            else if ($this->kakaoAlrimIgnoreFlag == 'n' && $this->kakaoAlrimAutoConfig['useFlag'] == 'y' && $this->kakaoAlrimAutoConfig[strtolower($this->smsType) . 'UseFlag'] == 'y') {
                $this->checkSmsTypeAndCodeWithReceiverKakao();
                $this->checkSmsAutoTypeAndSmsContentWheresKakao();
                $this->checkDefaultSmsAutoCodeAndReceiverKakao_wmExtend();
                if ($this->smsType == \Component\Sms\SmsAutoCode::MEMBER && $this->smsAutoCodeType == \Component\Sms\Code::JOIN) {
                    if ($this->kakaoAlrimAutoConfig[$this->smsType][$this->smsAutoCodeType]['smsDisapproval'] != 'y' && $this->replaceArguments['appFl'] == 'n') {
                        throw new \Exception('Disapproval member');
                    }
                }
                $smsContents = $this->getSmsContentsKakao($this->smsContentWheres);
                $logger->info(sprintf('Count sms contents.[%d]', count($smsContents)));
                // 카카오알림톡 자동 발송 예약시간 설정
                if ($this->smsType == SmsAutoCode::MEMBER && array_key_exists($this->smsAutoCodeType, Sms::KAKAO_AUTO_RESERVATION_DEFAULT_TIME)) {
                    $this->setSmsAutoSendDate($this->getKakaoAlrimAutoReserveTime($this->smsAutoCodeType));
                }
            } else {
                $this->checkSmsTypeAndCodeWithReceiver();
                $this->checkSmsAutoTypeAndSmsContentWheres();
                $this->checkDefaultSmsAutoCodeAndReceiver();
                if ($this->smsType == \Component\Sms\SmsAutoCode::MEMBER && $this->smsAutoCodeType == \Component\Sms\Code::JOIN) {
                    if ($this->smsAutoConfig[$this->smsType][$this->smsAutoCodeType]['smsDisapproval'] != 'y' && $this->replaceArguments['appFl'] == 'n') {
                        throw new \Exception('Disapproval member');
                    }
                }

                $smsContents = $this->getSmsContents($this->smsContentWheres);
                $logger->info(sprintf('Count sms contents.[%d]', count($smsContents)));
            }

            foreach ($smsContents as $index => $item) {
                // 전송가능여부
                $isPossibleSend = true;
                // 발송자
                $receiverData = [];
                // 저장 로그 데이터
                $logData = [];
                $logData['receiver']['smsAutoType'] = $item['smsAutoType'];
                // 발송 문자 내용 replace
                $contents = $item['contents'];
                if (gd_is_plus_shop(PLUSSHOP_CODE_KAKAOALRIMLUNA) === true && $this->kakaoAlrimIgnoreFlag == 'n' && $this->kakaoAlrimLunaAutoConfig['useFlag'] == 'y' && $this->kakaoAlrimLunaAutoConfig[strtolower($this->smsType) . 'UseFlag'] == 'y') {
                    $contents = $this->replaceContentsKakao($contents);
                }
                else if ($this->kakaoAlrimIgnoreFlag == 'n' && $this->kakaoAlrimAutoConfig['useFlag'] == 'y' && $this->kakaoAlrimAutoConfig[strtolower($this->smsType) . 'UseFlag'] == 'y') {
                    $contents = $this->replaceContentsKakao($contents);
                    // 주문배송관련 알림톡 중 페이지링크에 송장번호 치환코드 있는 경우
                    if (empty($item['button']) === false) {
                        $buttons = json_decode($item['button'], true);
                        $replaceCodeButtonFl = false;
                        foreach ($buttons as $key => &$val) {
                            $buttonData[$key]['name'] = $val['name'];
                            $buttonData[$key]['type'] = $val['linkType'];
                            if ($val['linkType'] === 'WL') {
                                if (preg_match("/#{invoiceNo}/", $val['linkMo'])) {
                                    $replaceCodeButtonFl = true;
                                    $val['linkMo'] = $this->replaceContentsKakao($val['linkMo']);
                                }
                                $buttonData[$key]['url_mobile'] = $val['linkMo'];
                            }

                        }
                        if (is_array($buttonData) && count($buttonData) > 0) {
                            $buttonData = json_encode($buttonData, JSON_UNESCAPED_UNICODE | JSON_FORCE_OBJECT);
                        }
                    }
                } else {
                    $contents = $this->replaceContents($contents);
                }
                if (empty($contents)) {
                    $logger->info(sprintf('Impossible send sms index[%d] empty contents.', $index));
                    $isPossibleSend = false;
                }
                // 수신자 가져오기 (회원)
                if ($item['smsAutoType'] == 'member' && $isPossibleSend) {
                    // 수신정보가 일반배열인 경우
                    if ($this->receiverType === 'array') {
                        $receiverData[0]['memNo'] = StringUtils::strIsSet($this->receiver['memNo']);
                        $receiverData[0]['memNm'] = StringUtils::strIsSet($this->receiver['memNm']);
                        $receiverData[0]['smsFl'] = StringUtils::strIsSet($this->receiver['smsFl'], 'n');
                        $receiverData[0]['cellPhone'] = StringUtils::strIsSet($this->receiver['cellPhone']);
                        // 수신동의 회원만 발송
                        if ($this->checkModes['agreeCheck'] === 'y' && $this->receiver['smsFl'] === 'n') {
                            $logger->info(__METHOD__ . ' Receive Reject');
                            $isPossibleSend = false;
                        }
                        $logData['receiver']['type'] = 'each';
                        $logData['receiver']['scmNo'] = StringUtils::strIsSet($this->receiver['scmNo']);
                    } elseif ($this->receiverType === 'multi') {    // 수신정보가 이차배열인 경우
                        $multipleReceiver = $this->getMultipleReceiver();
                        $receiverData = $multipleReceiver['receivers'];
                        // 수신 동의 회원만 발송이여서 정보가 없거나. 다른 이유로 정보가 없는 경우 return
                        if (empty($receiverData)) {
                            $logger->info(__METHOD__ . ' Empty ReceiverData - ' . $item['smsAutoType']);
                            $isPossibleSend = false;
                        }
                        // 로그 내용
                        $logData['receiver']['type'] = 'group';
                        $logData['receiver']['scmNo'] = $multipleReceiver['scmNo'];
                    } elseif ($this->receiverType === 'phone') {
                        $receiverData[0]['memNo'] = '0';
                        $receiverData[0]['memNm'] = '';
                        $receiverData[0]['smsFl'] = 'y';
                        $receiverData[0]['cellPhone'] = $this->receiver;
                        $logData['receiver']['type'] = 'each';
                        // 수신 동의 회원만 발송 관련 로직은 없음 그냥 다 보냄
                    } else {
                        $logger->info(sprintf('Not found receiver type.[%s]', $this->receiverType));
                    }
                }
                // 수신자 가져오기 (운영자 / 공급사)
                if (($item['smsAutoType'] === 'admin' || ($item['smsAutoType'] === 'provider' && empty($this->receiversScmNo) === false)) && $isPossibleSend) {
                    if ($this->smsAutoCodeType == 'SETTLE_BANK') {
                        $receiverData[]['cellPhone'] = $this->receiver;
                    } else {
                        $receiverData = $this->getManagerReceiver($item['smsAutoType']);
                    }
                    $logData['receiver']['type'] = 'group';
                    $logData['receiver']['scmNo'] = $this->receiversScmNo;
                    $logData['receiver']['dbTable'] = 'manager';
                }

                // 마이앱 푸시 대체 발송 타입 (사용안함 : NONE / 대체발송 : ALTERNATE / 함께발송 : TOGETHER)
                if ($this->useMyapp) {
                    $myappAlternativePush = gd_policy('myapp.config')['alternative_push'];
                    $isMyappPushSend = false;

                    $myapp = \App::load('Component\\Myapp\\Myapp');
                    $member = \App::load('\\Component\\Member\\Member');

                    foreach ($receiverData as $k => $v) {
                        $memInfo = $member->getMemberId($receiverData[$k]['memNo']);
                        $myappParams['memNo'] = $receiverData[$k]['memNo'];
                        $myappPushUser['users'][] = $memInfo ? $memInfo['memId'] : '비회원';
                        $appDeviceInfo = $myapp->getAppDeviceInfo($myappParams);

                        // 알림 허용 여부 체크
                        if ($appDeviceInfo[0]['pushEnabled'] === 1) {
                            switch ($myappAlternativePush['alternativeType']) {
                                case 'none': // 자동 SMS or 카카오알림톡 발송
                                    $isMyappPushSend = false;
                                    break;
                                case 'push': // 푸시 대체 발송
                                    $isMyappPushSend = true;
                                    $isPossibleSend = false;
                                    break;
                                case 'all': // 자동 SMS or 카카오 알림톡 && 푸시 대체 발송
                                    $isMyappPushSend = true;
                                    break;
                            }

                            // 마이앱 푸시 대체 발송 
                            if ($isMyappPushSend === true) {
                                $myapp->sendAppPush($appDeviceInfo[0]['pushToken'], $item['subject'], $contents, $myappPushUser['users']);
                            }
                        } else {
                            $isMyappPushSend = false;
                        }
                    }
                }

                // 전송 정보가 없는 경우
                if (empty($receiverData) === true) {
                    $logger->error('Empty ReceiverData - ' . $item['smsAutoType']);
                    $isPossibleSend = false;
                }
                // 전송 가능한경우 SMS 발송
                if ($isPossibleSend) {
                    $logData['smsAutoCode'] = $this->smsAutoCodeType;
                    if ($this->smsAutoSendDate != null) {
                        $logData['reserve']['mode'] = 'reserve';
                        $logData['reserve']['date'] = $this->smsAutoSendDate;
                        $logData['reserve']['dbTable'] = 'member';
                        $logData['reserve']['smsAutoCode'] = $this->smsAutoCodeType;
                        if ($this->smsAutoCodeType == 'ACCOUNT') {
                            $logData['reserve']['orderNo'] = $this->replaceArguments['orderNo'];
                        }
                    }

                    // 공구우먼 확인하기 위해서 SNO가져오기
                    $globals = \App::getInstance('globals');
                    $shopSno = $globals->get('gLicense.godosno');
                    $aOrderSmsTypeCheck = array('ORDER', 'INVOICE_CODE', 'INCASH', 'ACCOUNT', 'REPAY', 'REPAYPART');
                    $aMemberSmsTypeCheck = array('JOIN', 'SLEEP_INFO_TODAY');
                    if( gd_is_plus_shop(PLUSSHOP_CODE_KAKAOALRIMLUNA) === true && ($this->kakaoAlrimIgnoreFlag == 'n' && $this->kakaoAlrimLunaAutoConfig['useFlag'] == 'y' && $this->kakaoAlrimLunaAutoConfig[strtolower($this->smsType) . 'UseFlag'] == 'y') ) {
                        //카카오 알림톡(루나) 발송
                        $oKakao = new \Component\Member\KakaoAlrimLuna;

                        $smsUtil = \App::load('Component\\Sms\\SmsUtil');
                        $aSender = $smsUtil->getSender();
                        $aSmsLog = [
                            'sendFl'            => 'kakaoLuna',
                            'smsType'           => $item['smsType'],
                            'smsDetailType'     => $logData['smsAutoCode'],
                            'sendType'          => 'send',
                            //'subject'           => $item['name'],
                            'contents'          => $item['contents'],
                            'receiverCnt'       => count($receiverData),
                            'replaceCodeType'   => '',
                            'sendDt'            => date('Y-m-d H:i:s'),
                            'smsSendKey'        => '',
                            'smsAutoSendOverFl' => 'none',
                            'code'              => $item['code'],
                            'useParam'              => $item['useParam'],
                        ];

                        // 위에서 예약발송으로 셋팅되었으면
                        if ($this->smsAutoSendDate != null) {
                            $tranDTime = $this->smsAutoSendDate;
                            $aSmsLog['sendType'] = 'res_send';
                            $aSmsLog['reserveDt'] = $tranDTime;
                        }
                        $aLogData = $logData;
                        // 카카오 알림톡의 경우 알림톡 발송 실패시 sms로 재발송처리 여부를 확인하기 위해 smsFl값을 기본n으로 하여 sms재발송을 처리할경우 y로 변경해 중복발송을 방지한다
                        foreach ($receiverData as $k => $v) {
                            $receiverData[$k]['smsFl'] = 'n';
                        }
                        $receiverForSaveSmsSendList = $receiverData;
                        //$lunaSendCode = $this->smsType.'||'.$this->smsAutoCodeType.'||'.$item['smsAutoType'];
                        $oKakao->sendKakaoAlrimLuna($aSmsLog, $aSender, $aLogData, $receiverForSaveSmsSendList, $this->replaceArguments, $contents );

                        $result[] = [
                            'success' => 1,
                            'fail'    => 0,
                        ];
                    }
                    // 공구우먼이면 임시로 kakao콘텐츠로 변경
                    else if (($this->kakaoAlrimIgnoreFlag == 'n' && $this->kakaoAlrimAutoConfig['useFlag'] == 'y' && $this->kakaoAlrimAutoConfig[strtolower($this->smsType) . 'UseFlag'] == 'y') ||
                        ($shopSno == '419067' && (($this->smsType == 'order' && in_array($this->smsAutoCodeType, $aOrderSmsTypeCheck)) || ($this->smsType == 'member' && in_array($this->smsAutoCodeType, $aMemberSmsTypeCheck)) || $this->smsType == 'board'))) {
                        
						
						//카카오 알림톡 발송
                        $oKakao = new \Component\Member\KakaoAlrim;

						$smsUtil = \App::load('Component\\Sms\\SmsUtil');
                        $aSender = $smsUtil->getSender();
                        $aSmsLog = [
                            'sendFl'            => 'kakao',
                            'smsType'           => $item['smsType'],
                            'smsDetailType'     => $logData['smsAutoCode'],
                            'sendType'          => 'send',
                            'subject'           => $item['name'],
                            'contents'          => $item['contents'],
                            'receiverCnt'       => count($receiverData),
                            'replaceCodeType'   => '',
                            'sendDt'            => date('Y-m-d H:i:s'),
                            'smsSendKey'        => '',
                            'smsAutoSendOverFl' => 'none',
                            'code'              => $item['code'],
                        ];
                        // 위에서 예약발송으로 셋팅되었으면
                        if ($this->smsAutoSendDate != null) {
                            $tranDTime = $this->smsAutoSendDate;
                            $aSmsLog['sendType'] = 'res_send';
                            $aSmsLog['reserveDt'] = $tranDTime;
                        }
                        $aLogData = $logData;
                        // 카카오 알림톡의 경우 알림톡 발송 실패시 sms로 재발송처리 여부를 확인하기 위해 smsFl값을 기본n으로 하여 sms재발송을 처리할경우 y로 변경해 중복발송을 방지한다
                        foreach ($receiverData as $k => $v) {
                            $receiverData[$k]['smsFl'] = 'n';
                        }
                        $receiverForSaveSmsSendList = $receiverData;

                        // 특정 sms발송이면 루나소프트로 보내도록 처리
                        // $this->smsType 탭 order주문 member회원 board게시판
                        // $this->smsAutoCodeType order ORDER주문접수 INVOICE_CODE송장번호안내 INCASH입금확인 ACCOUNT입금요청 REPAY환불완료
                        // $this->smsAutoCodeType member JOIN회원가입
                        // $this->smsAutoCodeType board board아래는 다..
                        if ($shopSno == '419067' && (($this->smsType == 'order' && in_array($this->smsAutoCodeType, $aOrderSmsTypeCheck)) || ($this->smsType == 'member' && in_array($this->smsAutoCodeType, $aMemberSmsTypeCheck)) || $this->smsType == 'board')) {
                            $config = $this->kakaoAlrimAutoConfig[$this->smsType][$this->smsAutoCodeType];
                            if ($config['memberSend'] == 'y') {
                                //카카오 알림톡 발송
                                $oKakao->sendKakaoAlrimLunar($aSmsLog, $aLogData, $receiverForSaveSmsSendList, $this->replaceArguments, $this->smsType.'||'.$this->smsAutoCodeType);
                            }
                        } else { 
                            if (empty($item['button']) === false) {
                                $aSmsLog['templateButton'] = $item['button'];
                            }
                            if (empty($buttonData) === false) {
                                $aSmsLog['buttonData'] = $buttonData;
                                unset($buttonData);
                            }
							/*
							gd_debug($aSmsLog); 
							gd_debug($aSender); 
							gd_debug($aLogData); 
							gd_debug($receiverForSaveSmsSendList); 
							gd_debug($this->replaceArguments); 
							gd_debug($contents); 
							*/
							$oKakao->sendKakaoAlrim($aSmsLog, $aSender, $aLogData, $receiverForSaveSmsSendList, $this->replaceArguments, $contents);
                        }

                        $result[] = [
                            'success' => 1,
                            'fail'    => 0,
                        ];
                    } else {
                        $message = new \Component\Sms\SmsMessage($contents);
                        if ($message->exceedSmsLength()) {
                            if ($this->smsAutoConfig['smsAutoSendOver'] == 'lms') {
                                $message = new \Component\Sms\LmsMessage($contents);
                            } elseif ($this->smsAutoConfig['smsAutoSendOver'] == 'division') {
                                $message = new \Component\Sms\DivisionSmsMessage($contents);
                            }
                        }
                        $transport = \App::load(SmsSender::class);
                        if ($this->isAutoSend() || !$this->passwordCheckFl) {
                            $transport->validPassword(\App::load(\Component\Sms\SmsUtil::class)->getPassword());
                        }
                        $transport->setSmsPoint(Sms::getPoint());
                        $transport->setMessage($message);
                        $transport->setSmsType($item['smsType']);
                        $transport->setReceiver($receiverData);
                        $transport->setSendDate($this->smsAutoSendDate);
                        $transport->setTranType($this->smsAutoTranType);
                        $transport->setLogData($logData);
                        $transport->setContentsMask($this->contentsMask);
                        $result[] = $transport->send();
                    }

                    unset($receiverData);
                }
                unset($logData);
            }

        } catch (\Exception $e) {
            $exceptionMessage = __METHOD__ . ', ' . $e->getFile() . '[' . $e->getLine() . '], ' . $e->getMessage();
            $logger->warning($exceptionMessage);
            \Logger::channel('exception')->info($exceptionMessage, $e->getTrace());

            return false;
        }
        $logger->info(__METHOD__ . ' Send Sms Result', $result);

        return $result;
    }










    /**
     * 야간발송 여부, 최근 주문 n일 여부 체크, 수신정보 분석, 수신동의 회원 체크, 공급사 정보 확인 함수 - 카카오알림톡용
     *
     * @throws \Exception
     */
    protected function checkDefaultSmsAutoCodeAndReceiverKakao_wmExtend($kind = '')
    {
        $logger = \App::getInstance('logger');
        $this->smsAutoCode = \App::load('\\Component\\Sms\\SmsAutoCode');
        $defaultAutoCode = $this->smsAutoCode->getCodes();
        foreach ($defaultAutoCode[$this->smsType] as $item) {
            if ($item['code'] == $this->smsAutoCodeType) {
                $this->checkModes['orderCheck'] = $item['orderCheck'];
                $this->checkModes['nightCheck'] = $item['nightCheck'];
                $this->checkModes['agreeCheck'] = $item['agreeCheck'];
            }
        }
        if($kind == 'luna'){
            $config = $this->kakaoAlrimLunaAutoConfig[$this->smsType][$this->smsAutoCodeType];
        }else{
            $config = $this->kakaoAlrimAutoConfig[$this->smsType][$this->smsAutoCodeType];
        }

        if ($this->checkModes['orderCheck'] === 'y') {
            $smsOrderDate = StringUtils::strIsSet($config['smsOrderDate'], 15);
            if (isset($this->replaceArguments['orderNo'])) {
                $tmpDate = '20' . substr($this->replaceArguments['orderNo'], 0, 12);    // 주문번호에서 날짜 추출
                $compareDate = strtotime('+' . $smsOrderDate . ' day', strtotime($tmpDate));
                if ($compareDate < time()) {
                    $logger->warning(__METHOD__ . ', smsOrderDate=[' . $smsOrderDate . '], dateByOrderNo=[' . $tmpDate . '], compareDate=[' . $compareDate . ']');
                    throw new \Exception(__('최근') . ' ' . $smsOrderDate . __('일 주문건만 전송이 가능합니다.'));
                }
            }
        }

		if ($this->checkModes['nightCheck'] === 'y') {
            $smsNightSend = StringUtils::strIsSet($config['smsNightSend'], 'n');
            if ($smsNightSend === 'n') {
                $thisHour = date('G', time());
                if (in_array($thisHour, Sms::SMS_FORBID_TIME)) {
                    throw new \Exception(__('야간 발송 금지인 SMS 입니다.'));
                }
            }
        }

        if (is_array($this->receiver)) {
            if (array_key_exists('cellPhone', $this->receiver)) {
                $this->receiverType = 'array';  // 일반 배열
            } else {
                $this->receiverType = 'multi';  // 이차 배열
            }
        } else {
            $this->receiverType = 'phone'; // 전화번호
        }
        $logger->info(sprintf('Check receiver type[%s]', $this->receiverType));

        // 수신정보에서 공급사 정보만 추출
        if ($this->receiverType === 'array') {
            $isCheck = true;
            // 수신동의 회원발송
            if ($this->checkModes['agreeCheck'] === 'y' && StringUtils::strIsSet($this->receiver['smsFl'], 'n') === 'n') {
                $isCheck = false;
                $logger->info(sprintf('Receiver type array. check agreeCheck[%s], smsFl[%s]', $this->checkModes['agreeCheck'], $this->receiver['smsFl']));
            }
            // 공급사 정보 확인
            if (empty($this->receiver['scmNo'])) {
                $logger->info('Receiver type array. check empty scmNo.');
                $isCheck = false;
            }
            // 체크 후 정상인 경우
            if ($isCheck) {
                $this->receiversScmNo = $this->receiver['scmNo'];
                $logger->info('Receiver type array.', [$this->receiversScmNo]);
            }
        }
        if ($this->receiverType === 'multi') {
            $tmpScmNo = [];
            foreach ($this->receiver as $index => $item) {
                $isCheck = true;
                // 수신동의 회원발송
                if ($this->checkModes['agreeCheck'] === 'y' && StringUtils::strIsSet($item['smsFl'], 'n') === 'n') {
                    $isCheck = false;
                }
                // 공급사 정보 확인
                if (empty($item['scmNo'])) {
                    $isCheck = false;
                }
                // 체크 후 정상인 경우
                if ($isCheck) {
                    $tmpScmNo = array_merge($tmpScmNo, $item['scmNo']);
                }
            }
            // 공급사 정보
            $this->receiversScmNo = array_unique($tmpScmNo);
        }
    }

}
