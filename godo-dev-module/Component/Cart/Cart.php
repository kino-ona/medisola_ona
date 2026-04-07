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
 * @link http://www.godo.co.kr
 */

namespace Component\Cart;

use Component\Database\DBTableField;
use Component\Mall\Mall;
use Component\Member\Util\MemberUtil;
use Component\Validator\Validator;
use Cookie;
use Exception;
use Framework\Utility\ArrayUtils;

use Request;
use Session;


/**
 * 장바구니 class
 *
 * 상품과 추가상품을 분리하는 작업에서 추가상품을 기존과 동일하게 상품에 종속시켜놓은 이유는
 * 상품과 같이 배송비 및 다양한 조건들을 아직은 추가상품에 설정할 수 없어서
 * 해당 상품으로 부터 할인/적립등의 조건을 상속받아서 사용하기 때문이다.
 * 따라서 추후 추가상품쪽에 상품과 동일한 혜택과 기능이 추가되면
 * 장바구니 테이블에서 상품이 별도로 담길 수 있도록 개발되어져야 한다.
 *
 * @author Shin Donggyu <artherot@godo.co.kr>
 */
class Cart extends \Bundle\Component\Cart\Cart
{
	/**
	 * 장바구니 담기 (상품코드/옵션코드/상품수량/적용쿠폰 배열)
	 * 상품을 장바구니에 담습니다.
	 *
	 * @param array $arrData 상품 정보 [mode, scmNo, cartMode, goodsNo[], optionSno[], goodsCnt[], couponApplyNo[]]
	 *
	 * @return array
	 * @throws Exception
	 */
	public function saveInfoCart($arrData, $tempCartPolicyDirectOrder = 'n', $channel = '')
	{
		\Logger::channel('order')->info(__METHOD__ . ' saveInfoCart param $arrData : ', [$arrData]);
		// 적용한 쿠폰이 있을 경우 중복 사용 체크
		if ($this->isWrite !== true && $this->isWriteMemberCartAdd !== true) { //수기 주문의 경우 체크하지 않음
			if (empty($arrData['couponApplyNo']) === false && count($arrData['couponApplyNo']) > 0) {
				if (method_exists($this, 'validateApplyCoupon') === true) {
					$resValidateApplyCoupon = $this->validateApplyCoupon($arrData['couponApplyNo']);
					if ($resValidateApplyCoupon['status'] === false) {
						throw new Exception($resValidateApplyCoupon['msg']);
					}
				}
			}
		}

		// 상품상세의 쿠폰 필드명을 장바구니 쿠폰 필드명으로 변경
		$arrData['memberCouponNo'] = $arrData['couponApplyNo'];
		unset($arrData['couponApplyNo']);

		// 장바구니 테이블 필드
		$arrExclude = [
			'siteKey',
			'memNo',
			'directCart',
		];
		$fieldData = DBTableField::setTableField('tableCart', null, $arrExclude);

		if ($tempCartPolicyDirectOrder == 'y') { // 페이코 네이버 체크아웃바로구매일때는 무조건 directOrderFl값 y로 처리해주기
			$this->cartPolicy['directOrderFl'] = 'y';
		}

		// 마이앱 로그인뷰 스크립트
		$myappBuilderInfo = gd_policy('myapp.config')['builder_auth'];
		if ($this->useMyapp && empty($myappBuilderInfo['clientId']) === false && empty($myappBuilderInfo['secretKey']) === false) {
			// 기존 바로 구매 상품 삭제
			$this->setDeleteDirectCart();

			// 비회원 주문하기 클릭 후 재 진입시 로그인 페이지 이동하지 않는 현상 수정
			MemberUtil::logoutGuest();
		}

		if ($arrData['cartMode'] == 'd' && $this->cartPolicy['directOrderFl'] == 'y' && $channel == 'related') {
			// 기존 바로 구매 상품 삭제
			$this->setDeleteDirectCart();

			// 비회원 주문하기 클릭 후 재 진입시 로그인 페이지 이동하지 않는 현상 수정
			MemberUtil::logoutGuest();
		}

		// 상품 번호를 기준으로 장바구니에 담을 상품의 배열을 처리함
		foreach ($arrData['goodsNo'] as $goodsIdx => $goodsNo) {
			foreach ($fieldData as $field) {
				if ($field == 'componentGoodsNo') {
					continue;
				}

				if ($field == 'addGoodsPrices') {
					$getData[$field] = $arrData['add_goods_total_price'][$goodsIdx];
				} else {
					$getData[$field] = $arrData[$field][$goodsIdx];
				}

				// 구성 상품을 추가 상품 처리 로직 추가 by medisola - 갯수가 0개 이상인 componentGoodsNo만 저장
				if ($arrData['isDefaultComponents'][$goodsIdx] == '0') {
					if ($field == 'addGoodsNo' && isset($arrData['componentGoodsNo'])) {
						$filteredGoodsNo = array_values(array_filter($arrData['componentGoodsNo'][$goodsIdx], function ($value, $key) use ($arrData, $goodsIdx) {
							return intval($arrData['componentGoodsCnt'][$goodsIdx][$key]) > 0;
						}, ARRAY_FILTER_USE_BOTH));

						$getData[$field] = array_merge($getData[$field] ?? [], $filteredGoodsNo);
						$getData['componentGoodsNo'] = $filteredGoodsNo;
					}

					if ($field == 'addGoodsPrices' && isset($arrData['componentGoodsAddedPrice'])) {
						$filteredGoodsAddedPrice = array_filter($arrData['componentGoodsAddedPrice'][$goodsIdx], function ($value, $key) use ($arrData, $goodsIdx) {
							return intval($arrData['componentGoodsCnt'][$goodsIdx][$key]) > 0;
						}, ARRAY_FILTER_USE_BOTH);

						$getData[$field] = array_merge($getData[$field] ?? [], $filteredGoodsAddedPrice);
					}

					if ($field == 'addGoodsCnt' && isset($arrData['componentGoodsCnt'])) {
						$filteredGoodsCnt = array_filter($arrData['componentGoodsCnt'][$goodsIdx], function ($value) {
							return intval($value) > 0;
						});
						$getData[$field] = array_merge($getData[$field] ?? [], $filteredGoodsCnt);
						$getData['addGoodsCnt'] = array_map(function ($value) {
							return intval($value);
						}, $getData[$field]);
					}
				}
				// 구성 상품을 추가 상품 처리 로직 추가 by medisola
			}
			$getData['mallSno'] = Mall::getSession('sno');
			$getData['scmNo'] = $arrData['scmNo'];
			$getData['cartMode'] = $arrData['cartMode'];
			$getData['linkMainTheme'] = $arrData['linkMainTheme'];
			$getData['goodsDeliveryFl'] = $arrData['goodsDeliveryFl'];
			$getData['sameGoodsDeliveryFl'] = $arrData['sameGoodsDeliveryFl'];

			// 상품 상세 페이지에서 배송비 항목을 노출 안함 처리하면 선불/착불이 넘어오지 않아 체크 후 선불/착불 입력
			if (!$arrData['deliveryCollectFl']) {
				$arrData['deliveryCollectFl'] = $this->getGoodsDeliveryCollectFl($goodsNo);
			}
			$getData['deliveryCollectFl'] = $arrData['deliveryCollectFl'];
			$getData['deliveryMethodFl'] = $arrData['deliveryMethodFl'];
			$getData['goodsPrice'] = $arrData['set_total_price'];
			if (is_array($arrData['useBundleGoods']) === false) {
				$getData['useBundleGoods'] = $arrData['useBundleGoods'];
			}
			//수기주문 - 회원 장바구니 추가를 통한 상품 주문시 실제 cart sno를 끌고간다.
			//기존 적용되어있던 memberCouponsno 를 삭제할 목적으로 사용
			if ($this->isWrite === true && $this->isWriteMemberCartAdd === true) {
				$getData['preRealCartSno'] = $arrData['preRealCartSno'][$goodsIdx];
			}

			// 구성 상품 데이터 가공 - 원래 saveGoodsToCart 메소드 안에서 이루어지는 작업이나
			// 커스터마이제이션을 위해 여기서 처리 by medisola
			if (gd_isset($getData['componentGoodsNo'])) {
				$getData['componentGoodsNo'] = ArrayUtils::removeEmpty($getData['componentGoodsNo']);
				$getData['componentGoodsNo'] = json_encode($getData['componentGoodsNo']);
			}

			if (gd_isset($getData['addGoodsPrices'])) {
				$getData['addGoodsPrices'] = ArrayUtils::removeEmpty($getData['addGoodsPrices']);
				$getData['addGoodsPrices'] = json_encode($getData['addGoodsPrices']);
			}
			// 구성 상품을 추가 상품 처리 로직 추가 by medisola

			// 장바구니에 담기
			$arrayRtn[] = $this->saveGoodsToCart($getData);

			$this->setInflowGoods($goodsNo);
		}

		if (($arrData['goodsDeliveryFl'] == 'y' || ($arrData['goodsDeliveryFl'] != 'y' && $arrData['sameGoodsDeliveryFl'] == 'y')) && empty($arrData['deliveryCollectFl']) === false && empty($arrData['deliveryMethodFl']) === false) {
			foreach ($arrayRtn as $cartSno) {
				unset($getData);
				$getData['deliveryCollectFl'] = gd_isset($arrData['deliveryCollectFl']);
				$getData['deliveryMethodFl'] = gd_isset($arrData['deliveryMethodFl']);
				$cartInfo = $this->getCartInfo($cartSno, 'mallSno, siteKey, memNo, directCart, goodsNo');

				$arrBind = $this->db->get_binding(DBTableField::getBindField('tableCart', ['deliveryCollectFl', 'deliveryMethodFl']), $getData, 'update');
				$strWhere = 'mallSno = ? AND directCart = ? AND goodsNo = ?';
				$this->db->bind_param_push($arrBind['bind'], 'i', $cartInfo['mallSno']);
				$this->db->bind_param_push($arrBind['bind'], 's', $cartInfo['directCart']);
				$this->db->bind_param_push($arrBind['bind'], 'i', $cartInfo['goodsNo']);
				if (gd_is_login() === true) {
					$this->db->bind_param_push($arrBind['bind'], 'i', $cartInfo['memNo']);
					$strWhere .= ' AND memNo = ?';
				} else {
					$this->db->bind_param_push($arrBind['bind'], 's', $cartInfo['siteKey']);
					$strWhere .= ' AND siteKey = ?';
				}

				$this->db->set_update_db($this->tableName, $arrBind['param'], $strWhere, $arrBind['bind']);
			}
		}

		$requestUrl = \Request::server()->all()['SERVER_NAME'] . \Request::server()->all()['REQUEST_URI'];
		$fbExtensionV2 = \App::Load('\\Component\\Facebook\\FacebookExtensionV2');
		$fbExtensionV2->fbPixelDefault("AddToCart", $requestUrl, $arrData['goodsNo'], $arrData['set_total_price'], $arrData['event_id']);

		if ($arrData['firstDelivery'] > 0) {
			// 만약 현재 요일이 체크한 요일보다 클 경우 다음달로 계산
			$d = date('d', time());
			if ($d > $arrData['firstDelivery']) {
				$timestamp = strtotime('first day of +1 month');
				$Ym = date('Ym', $timestamp);
			} else {
				$Ym = date('Ym', time());
			}

			$Ymd = $Ym . $arrData['firstDelivery'];
			if ($arrayRtn) {
				foreach ($arrayRtn as $key => $value) {
					$sql = "UPDATE es_cart SET firstDelivery = ? WHERE sno = ?";
					$arrBind = [];
					$this->db->bind_param_push($arrBind, 'i', (int)$Ymd);
					$this->db->bind_param_push($arrBind, 'i', $value);
					$this->db->bind_query($sql, $arrBind);
				}
			}
		}

		return $arrayRtn;
	}

	/**
	 * 장바구니/주문서 접근시 최상위 로딩 getCartGoodsData 함수의 최상위에서 로그인 상태면 체크
	 * 로그인상태면 memNo기준으로 장바구니 정보를 가져와서 현재 기준의 siteKey로 업데이트하고 장바구니에 동일상품을 합친다
	 *
	 * @param interger $memNo 회원고유번호
	 *
	 * @author Jae-Won Noh <jwno@godo.co.kr>
	 * Refactored by Conan Kim <kmakugo@gmail.com>
	 */
	public function setMergeCart($memNo)
	{
		$session = \App::getInstance('session');
		if ($session->get('related_goods_order') ==  'y') {
			return;
		}

		// 바로 구매로 넘어온경우
		if (Request::getFileUri() == 'payco_checkout.php' || Request::getFileUri() == 'naver_pay.php') {
			$this->cartPolicy['directOrderFl'] = 'y';
		}

		if (!$memNo) { // 비회원
			$arrBind = [
				's',
				Session::get('siteKey'), // siteKey is unique key for each session of site
			];
		} else {
			if ($this->isWrite === true) { // 직접 주문 작성
				$arrBind = [
					'is',
					$memNo,
					$this->siteKey,
				];
				$isWriteAddSql = " AND siteKey = ?";
			} else { // 회원
				$arrBind = [
					'i',
					$memNo,
				];
				$isWriteAddSql = '';
			}
		}

		$strDirectSQL = " AND optionText = ''";
		if (Request::getFileUri() != 'cart.php' && Request::getFileUri() != 'order_ps.php') {
			if ($this->cartPolicy['directOrderFl'] == 'y') {
				$strDirectSQL .= " AND directCart = 'n'";
			}
		} else {
			$strDirectSQL .= " AND directCart = 'n'";
		}

		$strSQL = "SELECT count(goodsNo) as cnt, goodsNo, optionSno, optionText, componentGoodsNo FROM " . $this->tableName;
		if (!$memNo) {
			$strSQL .= " WHERE siteKey = ?" . $strDirectSQL;
		} else {
			$strSQL .= " WHERE memNo = ?" . $strDirectSQL . $isWriteAddSql;
		}
		// 골라담은 상품이 일치하지 않으면 merge하지 않게 하기 위해 componentGoodsNo 추가, 아래 조건 절 동일
		$strSQL .= " GROUP BY goodsNo, optionSno, optionText, componentGoodsNo";
		$cartData = $this->db->query_fetch($strSQL, $arrBind);

		foreach ($cartData as $key => $val) {
			if ($val['cnt'] > 1) {
				if (!$memNo) { // 비회원
					$arrBind = [
						'iisss',
						$val['goodsNo'],
						$val['optionSno'],
						$val['optionText'],
						stripslashes($val['componentGoodsNo']),
						Session::get('siteKey'),
					];
				} else {
					if ($this->isWrite === true) { // 직접 주문 작성
						$arrBind = [
							'iissis',
							$val['goodsNo'],
							$val['optionSno'],
							$val['optionText'],
							stripslashes($val['componentGoodsNo']),
							$memNo,
							$this->siteKey,
						];
					} else { // 회원
						$arrBind = [
							'iissi',
							$val['goodsNo'],
							$val['optionSno'],
							$val['optionText'],
							stripslashes($val['componentGoodsNo']),
							$memNo,
						];
					}
				}
				$strSQL = "SELECT * FROM " . $this->tableName . " WHERE goodsNo = ? AND optionSno = ? AND optionText = ? AND componentGoodsNo = ?";
				if (!$memNo) {
					$strSQL .= " AND siteKey = ?" . $strDirectSQL;
				} else {
					$strSQL .= " AND memNo = ?" . $strDirectSQL . $isWriteAddSql;
				}
				$strSQL .= " ORDER BY directCart DESC, regDt ASC, modDt ASC";
				$mergeList = $this->db->query_fetch($strSQL, $arrBind);

				$tempCnt = 0;
				$tempOptionText = '';
				$tempAddNo = '';
				$tempAddCnt = '';
				$tempArrayAdd = [];
				$deliveryCollectFl = '';
				$deliveryMethodFl = '';
				$firstDelivery = 0;
				foreach ($mergeList as $k => $v) {
					if ($v['optionText']) continue;
					if ($this->cartPolicy['sameGoodsFl'] == 'p') {
						$tempCnt += $v['goodsCnt'];
						if ($v['addGoodsNo'] != '' && $v['addGoodsNo'] != null) {
							$tempAddNo = json_decode(gd_htmlspecialchars_stripslashes($v['addGoodsNo']), true);
							$tempAddCnt = json_decode(gd_htmlspecialchars_stripslashes($v['addGoodsCnt']), true);
							foreach ($tempAddNo as $num => $kval) {
								if ($tempArrayAdd[$kval]) {
									$tempArrayAdd[$kval] += $tempAddCnt[$num];
								} else {
									$tempArrayAdd[$kval] = $tempAddCnt[$num];
								}
							}
						}
					} else {
						if ($tempCnt == 0) {
							$tempCnt = $v['goodsCnt'];
						}
						if ($v['addGoodsNo'] != '' && $v['addGoodsNo'] != null) {
							$tempAddNo = json_decode(gd_htmlspecialchars_stripslashes($v['addGoodsNo']), true);
							$tempAddCnt = json_decode(gd_htmlspecialchars_stripslashes($v['addGoodsCnt']), true);
							foreach ($tempAddNo as $num => $kval) {
								if (!$tempArrayAdd[$kval]) {
									$tempArrayAdd[$kval] = $tempAddCnt[$num];
								}
							}
						}
					}

					$tempOptionText = $v['optionText'];

					if ($memNo && $v['memberCouponNo'] != '') { // 회원
						// 사용쿠폰이있는경우 memberCoupon테이블에서 정보 삭제
						$memberCouponArray = array();
						$memberCouponArray = explode(INT_DIVISION, $v['memberCouponNo']);
						if (count($memberCouponArray) > 0) {
							foreach ($memberCouponArray as $memberCouponArrayKey => $memberCouponArrayValue) {
								$arrCouponBind = [
									'ssi',
									'y',
									'0000-00-00 00:00:00',
									$memberCouponArrayValue,
								];
								if ($this->isWrite === true) {
									$memberCouponStateQuery = "orderWriteCouponState = ?,";
								} else {
									$memberCouponStateQuery = "memberCouponState = ?,";
								}
								$this->db->set_update_db(DB_MEMBER_COUPON, $memberCouponStateQuery . ' memberCouponCartDate = ?', 'memberCouponNo = ?', $arrCouponBind);
							}
						}

						if ($this->isWrite === true) {
							//수기주문에서 회원 장바구니추가로 사용된 쿠폰정보를 삭제처리
							//(이는 가사용 쿠폰을 실제 사용으로 바꿔주기 위해 존재하는 데이터)
							$owMemberCartSnoData = Cookie::get('owMemberCartSnoData');
							$owMemberRealCartSnoData = Cookie::get('owMemberRealCartSnoData');
							$owMemberCartCouponNoData = Cookie::get('owMemberCartCouponNoData');

							if (trim($owMemberCartSnoData) !== '') {
								$owMemberCartSnoDataArr = explode(",", $owMemberCartSnoData);
								$owMemberRealCartSnoDataArr = explode(",", $owMemberRealCartSnoData);
								$owMemberCartCouponNoDataArr = explode(",", $owMemberCartCouponNoData);

								if (count($owMemberCartSnoDataArr) > 0) {
									$cartSnoIndex = array_search($v['sno'], $owMemberCartSnoDataArr);
									if ($cartSnoIndex === 0 || (int)$cartSnoIndex > 0) {
										unset($owMemberCartSnoDataArr[$cartSnoIndex]);
										unset($owMemberRealCartSnoDataArr[$cartSnoIndex]);
										unset($owMemberCartCouponNoDataArr[$cartSnoIndex]);
									}
								}
								$owMemberCartSnoDataArrNew = implode(",", $owMemberCartSnoDataArr);
								$owMemberRealCartSnoDataArrNew = implode(",", $owMemberRealCartSnoDataArr);
								$owMemberCartCouponNoDataArrNew = implode(",", $owMemberCartCouponNoDataArr);

								Cookie::set('owMemberCartSnoData', $owMemberCartSnoDataArrNew, 0, '/', '', false, false);
								Cookie::set('owMemberRealCartSnoData', $owMemberRealCartSnoDataArrNew, 0, '/', '', false, false);
								Cookie::set('owMemberCartCouponNoData', $owMemberCartCouponNoDataArrNew, 0, '/', '', false, false);
							}
						}
					}

					// 두번째 레코드부터는 데이터만 가지고 삭제
					if ($k > 0) {
						$arrDeleteBind = [];
						$arrDeleteBind['param'] = 'sno = ?';
						$this->db->bind_param_push($arrDeleteBind['bind'], 'i', $v['sno']);
						$this->db->set_delete_db($this->tableName, $arrDeleteBind['param'], $arrDeleteBind['bind']);

						// 웹앤모바일 2023-08-17 첫배송일 최근 데이터로 변경
						if ((int)$v['firstDelivery'] > 0) {
							$firstDelivery = $v['firstDelivery'];
						}
					}

					//배송비 결제 방법, 배송방식 의 경우 가장 최근의 결제방법으로 변경처리한다.
					$deliveryCollectFl = $v['deliveryCollectFl'];
					$deliveryMethodFl = $v['deliveryMethodFl'];
				}

				if (count($tempArrayAdd) > 0) {
					$tempAddNo = json_encode(ArrayUtils::removeEmpty(array_keys($tempArrayAdd)));
					$tempAddCnt = json_encode(ArrayUtils::removeEmpty(array_values($tempArrayAdd)));
				}

				// 해당 상품의 구매가능(최대/최소) 수량 체크
				$checkCnt = $this->getBuyableStock($val['goodsNo'], $tempCnt);

				if (!$memNo) { // 비회원
					$arrUpdateBind = [
						'issssssii',
						$checkCnt,
						$tempOptionText,
						$tempAddNo,
						$tempAddCnt,
						'',
						$deliveryCollectFl,
						$deliveryMethodFl,
						$firstDelivery,
						$mergeList[0]['sno'],
					];
					$this->db->set_update_db($this->tableName, 'goodsCnt = ?, optionText = ?, addGoodsNo = ?, addGoodsCnt = ?, memberCouponNo = ?, deliveryCollectFl = ?, deliveryMethodFl = ? , firstDelivery = ? ', 'sno = ?', $arrUpdateBind);					
				} else {
					$arrUpdateBind = [
						'issssssisi',
						$checkCnt,
						$tempOptionText,
						$tempAddNo,
						$tempAddCnt,
						'',
						$deliveryCollectFl,
						$deliveryMethodFl,
						$firstDelivery,
						Session::get('siteKey'),
						$mergeList[0]['sno'],
					];
					$this->db->set_update_db($this->tableName, 'goodsCnt = ?, optionText = ?, addGoodsNo = ?, addGoodsCnt = ?, memberCouponNo = ?, deliveryCollectFl = ?, deliveryMethodFl = ? , firstDelivery = ?, siteKey = ? ', 'sno = ?', $arrUpdateBind);
				}

				unset($checkCnt);
				unset($tempOptionText);
				unset($tempAddNo);
				unset($tempAddCnt);
			}
		}
	}

	/**
	 * 장바구니 수정 (상품코드/옵션코드/상품수량/적용쿠폰 배열)
	 * 상품을 장바구니에 담습니다.
	 *
	 * @param array $arrData 상품 정보 [mode, scmNo, cartMode, goodsNo[], optionSno[], goodsCnt[], couponApplyNo[]]
	 *
	 * @return array
	 */
	public function updateInfoCart($arrData)
	{

		// 장바구니 번호를 통해 첫배송일 확인
		$this->db->strField = "firstDelivery";
		$this->db->strWhere = "sno = '{$arrData['sno']}'";
		$query = $this->db->query_complete();
		$sql = "SELECT" . array_shift($query) . "FROM " . DB_CART . implode(' ', $query);
		$firstDelivery = $this->db->fetch($sql);


		// Validation - 상품 수량 체크
		foreach ($arrData['goodsCnt'] as $goodsCnt) {
			if (Validator::number($goodsCnt, 1, null, true) === false) {
				throw new Exception(__('상품 수량 이상으로 장바구니에 해당 상품을 담을 수 없습니다.'));
			}
		}

		// 상품상세의 쿠폰 필드명을 장바구니 쿠폰 필드명으로 변경
		$arrData['memberCouponNo'] = $arrData['couponApplyNo'];
		unset($arrData['couponApplyNo']);
		unset($arrData['useBundleGoods']);
		// 장바구니 테이블 필드
		$arrExclude = [
			'siteKey',
			'memNo',
			'directCart',
			/*'deliveryCollectFl',
            'deliveryMethodFl',*/
			'memberCouponNo',
			'scmNo',
			'cartMode',
			'linkMainTheme',
		];

		$fieldData = DBTableField::setTableField('tableCart', null, $arrExclude);

		$goods = \App::load(\Component\Goods\Goods::class);

		// 상품 번호를 기준으로 장바구니에 담을 상품의 배열을 처리함
		foreach ($arrData['goodsNo'] as $goodsIdx => $goodsNo) {
			foreach ($fieldData as $field) {
				if (in_array($field, ['deliveryCollectFl', 'deliveryMethodFl']) === true) {
					if (empty($arrData[$field]) === false) {
						$getData[$field] = gd_isset($arrData[$field]);
					} else {
						unset($fieldData[$field]);
					}
				} else {
					$getData[$field] = $arrData[$field][$goodsIdx];
				}
			}

			if (gd_isset($getData['optionText']) && empty($getData['optionText']) === false) {
				$getData['optionText'] = ArrayUtils::removeEmpty($getData['optionText']);
				$getData['optionText'] = json_encode($getData['optionText'], JSON_UNESCAPED_UNICODE);
			}

			// 추가 상품
			if (gd_isset($getData['addGoodsNo']) && empty($getData['addGoodsNo']) === false) {
				$getData['addGoodsNo'] = ArrayUtils::removeEmpty($getData['addGoodsNo']);
				$getData['addGoodsCnt'] = ArrayUtils::removeEmpty($getData['addGoodsCnt']);
				$getData['addGoodsNo'] = json_encode($getData['addGoodsNo']);
				$getData['addGoodsCnt'] = json_encode($getData['addGoodsCnt']);
			}


			$arrBind = $this->db->get_binding(DBTableField::getBindField('tableCart', $fieldData), $getData, 'update');
			$strWhere = 'sno = ?';
			$this->db->bind_param_push($arrBind['bind'], 'i', $arrData['sno']);
			$this->db->set_update_db($this->tableName, $arrBind['param'], $strWhere, $arrBind['bind']);

			if (($arrData['goodsDeliveryFl'] == 'y' || ($arrData['goodsDeliveryFl'] != 'y' && $arrData['sameGoodsDeliveryFl'] == 'y')) && empty($getData['deliveryCollectFl']) === false && empty($getData['deliveryMethodFl']) === false) {
				unset($getData);
				$getData['deliveryCollectFl'] = gd_isset($arrData['deliveryCollectFl']);
				$getData['deliveryMethodFl'] = gd_isset($arrData['deliveryMethodFl']);
				$cartInfo = $this->getCartInfo($arrData['sno'], 'mallSno, siteKey, memNo, directCart, goodsNo');

				$arrBind = $this->db->get_binding(DBTableField::getBindField('tableCart', ['deliveryCollectFl', 'deliveryMethodFl']), $getData, 'update');
				$strWhere = 'mallSno = ? AND directCart = ? AND goodsNo = ?';
				$this->db->bind_param_push($arrBind['bind'], 'i', $cartInfo['mallSno']);
				$this->db->bind_param_push($arrBind['bind'], 's', $cartInfo['directCart']);
				$this->db->bind_param_push($arrBind['bind'], 'i', $cartInfo['goodsNo']);
				if (gd_is_login() === true) {
					$this->db->bind_param_push($arrBind['bind'], 'i', $cartInfo['memNo']);
					$strWhere .= ' AND memNo = ?';
				} else {
					$this->db->bind_param_push($arrBind['bind'], 's', $cartInfo['siteKey']);
					$strWhere .= ' AND siteKey = ?';
				}

				$this->db->set_update_db($this->tableName, $arrBind['param'], $strWhere, $arrBind['bind']);
			}

			// 장바구니 변경 갯수 상품 업데이트
			$goods->setCartGoodsCount($goodsNo);
		}



		// 첫배송일 값이 존재할 경우 업데이트
		if ($firstDelivery['firstDelivery']) {
			if ($arrData['firstDelivery'] > 0 && $arrData['mode'] == 'cartUpdate') {
				// 만약 현재 요일이 체크한 요일보다 클 경우 다음달로 계산
				$d = date('d', time());
				if ($d > $arrData['firstDelivery']) {
					$timestamp = strtotime('first day of +1 month');
					$Ym = date('Ym', $timestamp);
				} else {
					$Ym = date('Ym', time());
				}

				$Ymd = $Ym . $arrData['firstDelivery'];
				$sql = "UPDATE es_cart SET firstDelivery = ? WHERE sno = ?";
				$arrBind = [];
				$this->db->bind_param_push($arrBind, 'i', (int)$Ymd);
				$this->db->bind_param_push($arrBind, 'i', $arrData['sno']);
				$this->db->bind_query($sql, $arrBind);
			} else {
				$sql = "UPDATE es_cart SET firstDelivery = ? WHERE sno = ?";
				$arrBind = [];
				$this->db->bind_param_push($arrBind, 'i', $firstDelivery['firstDelivery']);
				$this->db->bind_param_push($arrBind, 'i', $arrData['sno']);
				$this->db->bind_query($sql, $arrBind);
			}
		}
	}

	public function getCartGoodsData($cartIdx = null, $address = null, $tmpOrderNo = null, $isAddGoodsDivision = false, $isCouponCheck = false, $postValue = [], $setGoodsCnt = [], $setAddGoodsCnt = [], $setDeliveryMethodFl = [], $setDeliveryCollectFl = [], $deliveryBasicInfoFl = false)
	{
		$cartGoodsData = parent::getCartGoodsData($cartIdx, $address, $tmpOrderNo, $isAddGoodsDivision, $isCouponCheck, $postValue, $setGoodsCnt, $setAddGoodsCnt, $setDeliveryMethodFl, $setDeliveryCollectFl, $deliveryBasicInfoFl);

		foreach ($cartGoodsData as $key => $val) {
			foreach ($val as $key2 => $val2) {
				foreach ($val2 as $key3 => $cartInfo) {
					$componentGoodsNos = json_decode(stripslashes($cartInfo['componentGoodsNo']), true);
					$addedGoodsPrices = json_decode(stripslashes($cartInfo['addGoodsPrices']), true);
					$addGoods = $cartInfo['addGoods'];

					$priceAddedGoodsName = '';
					foreach ($addGoods as $addGoodsKey => $addGoodsValue) {
						$addGoodsValue['addedPrice'] = $addedGoodsPrices[$addGoodsKey];
						if (in_array($addGoodsValue['addGoodsNo'], $componentGoodsNos)) {
							$cartGoodsData[$key][$key2][$key3]['componentGoods'][] = $addGoodsValue;
							unset($cartGoodsData[$key][$key2][$key3]['addGoods'][$addGoodsKey]);

							if ($addGoodsValue['addedPrice'] > 0) {
								$priceAddedGoodsName .= $addGoodsValue['addGoodsNm'] . ', ';
							}
						}
					}
					if ($priceAddedGoodsName !== '') {
						$priceAddedGoodsName = substr($priceAddedGoodsName, 0, -2);
						$cartGoodsData[$key][$key2][$key3]['priceAddedGoodsName'] = $priceAddedGoodsName;
					}
				}
			}
		}

		return $cartGoodsData;
	}

	/**
	 * 장바구니 비우기 처리 오버라이딩
	 * 
	 * @param string $orderNo 주문번호
	 * @return void
	 * @author Conan Kim <kmakugo@gmail.com>
	 */
	public function setCartRemove($orderNo = null)
	{
		if ($orderNo !== null) {
				$this->directCopyCartLog($orderNo);
		}

		parent::setCartRemove($orderNo);
	}

	/**
	 * 직접 쿼리 복사로 장바구니 로그 저장
	 * 
	 * @param string $orderNo 주문번호
	 * @return void
	 * @author Conan Kim <kmakugo@gmail.com>
	 */
	private function directCopyCartLog($orderNo)
	{
		$arrBind = [];

		$strSQL = "
			INSERT INTO ms_cart_log (
				orderNo, cartSno,
				addGoodsNo, addGoodsCnt, addGoodsPrices, componentGoodsNo,
				optionText, memberCouponNo, useBundleGoods, cartType, firstDelivery,
				logDt
			)
			SELECT
				? as orderNo,
				sno as cartSno,
				addGoodsNo,
				addGoodsCnt,
				addGoodsPrices,
				componentGoodsNo,
				optionText, 
				memberCouponNo, 
				useBundleGoods, 
				cartType, 
				firstDelivery,
				NOW() as logDt
			FROM es_cart
			WHERE tmpOrderNo = ?;
		";

		$this->db->bind_param_push($arrBind, 's', $orderNo);
		$this->db->bind_param_push($arrBind, 's', $orderNo);
		$this->db->bind_query($strSQL, $arrBind);
		$this->db->query($strSQL, $arrBind);
	}
}


