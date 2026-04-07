<?php

namespace Controller\Front\Subscription;

use App;
use Request;
use UserFilePath;

class CronController extends \Controller\Front\Controller 
{
    private function safeLog($logPath, $line)
    {
        // Use error suppression only for PHP 5.x compatibility
        @file_put_contents($logPath, $line . "\n", FILE_APPEND);
    }

    public function index()
    {
        $date = date("Ymd");
        // File log (plain lines)
        $logDir = UserFilePath::data('log', 'subscription');
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        $logPath = $logDir . DIRECTORY_SEPARATOR . 'cron-' . $date . '.log';
        $runId = sprintf('subcron_%s_%s', date('Ymd_His'), substr(md5(uniqid('', true)), 0, 8));
        $runStart = microtime(true);
        $this->safeLog($logPath, date('c') . " RUN_START run_id={$runId} date={$date}");

        $db = App::load('DB');
        $obj = App::load(\Component\Subscription\Subscription::class);
        $hash = Request::post()->get("seed");

        /* 결제 목록 추출 */
        $list = $obj->setMode("batch_pay_list")
                      ->setDate($date)
                      ->get();
        $batchPayCount = is_array($list) ? count($list) : 0;
        $this->safeLog($logPath, date('c') . " LIST_SUMMARY run_id={$runId} mode=batch_pay count={$batchPayCount}");
         if ($list) {
             $obj->setMode("batch_pay");
             foreach ($list as $li) {
                $itemStart = microtime(true);
                $this->safeLog(
                    $logPath,
                    date('c') . " ITEM_SOURCE run_id={$runId} mode=batch_pay idx={$li['idx']} uid={$li['uid']} memNo={$li['memNo']} schedule_stamp={$li['schedule_stamp']} isPayed={$li['isPayed']} isStop={$li['isStop']} orderNo={$li['orderNo']} smsStamp={$li['smsStamp']}"
                );
				$mrow = $db->fetch("select count(memNo) as cnt from ".DB_MEMBER." where memNo='{$li['memNo']}'");
				
				if (empty($mrow['cnt'])) {
                    $this->safeLog(
                        $logPath,
                        date('c') . " ITEM_RESULT run_id={$runId} mode=batch_pay status=skip reason=mem_missing idx={$li['idx']} uid={$li['uid']} memNo={$li['memNo']} schedule_stamp={$li['schedule_stamp']}"
                    );
                    continue;
                }

    		    $result = $obj->setIdx($li['idx'])->setBoolean(true)->pay_procss($li['idx']);
                $orderNo = null;
                $idx = (int) $li['idx'];
                $row = $db->fetch("SELECT orderNo FROM wm_subscription_schedule_list WHERE idx='{$idx}'");
                if (!empty($row['orderNo'])) {
                    $orderNo = $row['orderNo'];
                }
                $this->safeLog(
                    $logPath,
                    date('c') . " ITEM_RESULT run_id={$runId} mode=batch_pay status=" . ($result ? 'success' : 'fail') . " idx={$li['idx']} uid={$li['uid']} memNo={$li['memNo']} schedule_stamp={$li['schedule_stamp']} orderNo={$orderNo} duration_ms=" . (int) ((microtime(true) - $itemStart) * 1000)
                );

             }
         }

        /* SMS 전송 목록 추출 */
        $list = $obj->setMode("batch_sms_list")
                      ->setDate($date)
                      ->get();
        $batchSmsCount = is_array($list) ? count($list) : 0;
        $this->safeLog($logPath, date('c') . " LIST_SUMMARY run_id={$runId} mode=batch_sms count={$batchSmsCount}");
        if ($list) {
            $obj->setMode("batch_sms");
            foreach ($list as $li) {
                $this->safeLog(
                    $logPath,
                    date('c') . " ITEM_SOURCE run_id={$runId} mode=batch_sms idx={$li['idx']} uid={$li['uid']} memNo={$li['memNo']} schedule_stamp={$li['schedule_stamp']} isPayed={$li['isPayed']} isStop={$li['isStop']} orderNo={$li['orderNo']} smsStamp={$li['smsStamp']}"
                );
                $itemStart = microtime(true);
				$mrow = $db->fetch("select count(memNo) as cnt from ".DB_MEMBER." where memNo='{$li['memNo']}'");
				
				if (empty($mrow['cnt'])) {
                    $this->safeLog(
                        $logPath,
                        date('c') . " ITEM_RESULT run_id={$runId} mode=batch_sms status=skip reason=mem_missing idx={$li['idx']} uid={$li['uid']} memNo={$li['memNo']} schedule_stamp={$li['schedule_stamp']}"
                    );
                    continue;
                }

                $result = $obj->setIdx($li['idx'])->sms_procss($li['idx']);
                $this->safeLog(
                    $logPath,
                    date('c') . " ITEM_RESULT run_id={$runId} mode=batch_sms status=" . ($result ? 'success' : 'fail') . " idx={$li['idx']} uid={$li['uid']} memNo={$li['memNo']} schedule_stamp={$li['schedule_stamp']} duration_ms=" . (int) ((microtime(true) - $itemStart) * 1000)
                );

            }
        }
		
        /* 자동 연장 처리 */
        $list = $obj->setMode("batch_auto_extend_list")
                      ->setDate($date)
                      ->get();
        $batchAutoExtendCount = is_array($list) ? count($list) : 0;
        $this->safeLog($logPath, date('c') . " LIST_SUMMARY run_id={$runId} mode=batch_auto_extend count={$batchAutoExtendCount}");
        if ($list) {
            $obj->setMode("batch_auto_extend");
            foreach ($list as $li) {
                $this->safeLog(
                    $logPath,
                    date('c') . " ITEM_SOURCE run_id={$runId} mode=batch_auto_extend idx={$li['idx']} uid={$li['uid']} memNo={$li['memNo']} schedule_stamp={$li['schedule_stamp']} isPayed={$li['isPayed']} isStop={$li['isStop']} orderNo={$li['orderNo']} smsStamp={$li['smsStamp']}"
                );
                $itemStart = microtime(true);
                $result = $obj->setIdx($li['idx'])->process();
                $this->safeLog(
                    $logPath,
                    date('c') . " ITEM_RESULT run_id={$runId} mode=batch_auto_extend status=" . ($result ? 'success' : 'fail') . " idx={$li['idx']} uid={$li['uid']} schedule_stamp={$li['schedule_stamp']} duration_ms=" . (int) ((microtime(true) - $itemStart) * 1000)
                );
            }
        }
        
        $this->safeLog(
            $logPath,
            date('c') . " RUN_END run_id={$runId} duration_ms=" . (int) ((microtime(true) - $runStart) * 1000)
        );
        exit('OK');
    }
}