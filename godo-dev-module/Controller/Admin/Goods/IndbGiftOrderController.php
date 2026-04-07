<?php

namespace Controller\Admin\Goods;

use App;
use Request;
use Framework\Debug\Exception\AlertOnlyException;

/**
* 선물하기 설정 관련 DB 처리 
*
* @author webnmobile
*/
class IndbGiftOrderController extends \Controller\Admin\Controller
{
	public function index()
	{
		try {
			$in = Request::request()->all();
			$db = App::load(\DB::class);

			switch ($in['mode']) {
				/* 선물하기 상품설정 */
				case "goods_set" : 
					if (empty($in['arrGoodsNo']))
						throw new AlertOnlyException("저장할 상품을 선택하세요.");
					
					foreach ($in['arrGoodsNo'] as $goodsNo) {
						$param = [
							'useGift = ?',
						];
						
						$bind = [
							'ii',
							$in['useGift'][$goodsNo]?1:0,
							$goodsNo,
						];
						
						$db->set_update_db(DB_GOODS, $param, "goodsNo = ?", $bind);
					}
					
					return $this->layer("저장하였습니다.");
					break;
				/* 선물하기 사용설정 */
				case "update_use_set" : 
					$param = [
						'isUse = ?',
						'useRange = ?',
						'cardTypes = ?',
						'orderStatus = ?',
						'smsTemplate = ?',
						'smsRequestTemplate = ?',
						'expireDays = ?',
						'expireSmsDays = ?',
						'smsExpireTemplate1 = ?',
						'smsExpireTemplate2 = ?',
						'smsTemplate2 = ?',
					];
					
					$bind = [
						'isssssiisss',
						$in['isUse']?1:0,
						$in['useRange']?$in['useRange']:"all",
						$in['cardTypes'],
						$in['orderStatus']?implode(",", $in['orderStatus']):"",
						$in['smsTemplate'],
						$in['smsRequestTemplate'],
						gd_isset($in['expireDays'], 3),
						gd_isset($in['expireSmsDays'], 1),
						$in['smsExpireTemplate1'],
						$in['smsExpireTemplate2'],
						$in['smsTemplate2'],
					];
					
					$affectedRows = $db->set_update_db("wm_giftSet", $param, "1", $bind);
					
					if ($affectedRows <= 0)
						throw new AlertOnlyException("저장에 실패하였습니다.");
					
					return $this->layer("저장하였습니다.");
					break;
				/* 선물하기 카드 등록 S */
				case "register_card" : 
					if (empty($in['cardType']))
						throw new AlertOnlyException("카드유형을 선택하세요.");
					
					$files = Request::files()->toArray();
					$files = $files['file'];
					if (!$files['tmp_name'][0])
						throw new AlertOnlyException("이미지를 업로드 해 주세요.");
					
					foreach ($files['error'] as $error) {
						if ($error) {
							throw new AlertOnlyException("파일 업로드에 실패하였습니다.");
						}
					}
					
					foreach ($files['type'] as $type) {
						if (!preg_match("/^image/", $type)) {
							throw new AlertOnlyException("이미지 형식의 파일만 업로드 가능합니다.");
						}
					}
					
					$path = dirname(__FILE__) . "/../../../../data/gift_card/".md5($in['cardType']);
					if (!file_exists($path)) {
						mkdir($path, 0777);
					}
					
					$stamp = time();
					foreach ($files['tmp_name'] as $k => $f) {
						$stamp += $k;
						$filename = $stamp."-".$files['name'][$k];
						move_uploaded_file($f, $path . "/".$filename);
					}
					
					return $this->layer("등록되었습니다.");
					break;
				/* 선물하기 카드 등록 E */
				/* 선물하기 카드 수정 S */
				case "update_card" : 
					if (empty($in['uid']))
						throw new AlertOnlyException("삭제할 이미지를 선택해주세요.");
					
					$path = dirname(__FILE__) . "/../../../../data/gift_card/";
					$files = Request::files()->toArray();
					$files = $files['file'];
					
					foreach ($in['uid'] as $uid) {
						if (!$in['filename'][$uid]) continue;
						$data = $in['filename'][$uid]?explode("_", $in['filename'][$uid]):[];
						$img = explode('.' , $data[1] , 2);
						if ($files['tmp_name'][$uid] && !$files['error'][$uid] && preg_match("/^image/", $files['type'][$uid])) {
							$dest = $path . md5($data[0]) . "/".$data[1];
							move_uploaded_file($files['tmp_name'][$uid], $dest);
						}
					}
					
					return $this->layer("수정되었습니다.");
					break;
				/* 선물하기 카드 등록 E */
				/* 선물하기 카드 삭제 S */
				case "delete_card" :
					if (empty($in['uid']))
						throw new AlertOnlyException("삭제할 이미지를 선택해주세요.");
					
					$path = dirname(__FILE__) . "/../../../../data/gift_card/";

					foreach ($in['uid'] as $uid) {
						if (!$in['filename'][$uid]) continue;
						$data = $in['filename'][$uid]?explode("_", $in['filename'][$uid]):[];
						@unlink($path . md5($data[0]) . "/".$data[1]);
					}
					
					return $this->layer("삭제되었습니다.");
					break;
				/* 선물하기 카드 삭제 E */
			}
		} catch (AlertOnlyException $e) {
			throw $e;
		}
		exit;
	}
}