<?php

/**
 * This is commercial software, only users who have purchased a valid license
 * and accept to the terms of the License Agreement can install and use this
 * program.
 *
 * Do not edit or add to this file if you wish to upgrade Godomall5 to newer
 * versions in the future.
 *
 * @copyright Copyright (c) 2015 GodoSoft.
 * @link http://www.godo.co.kr
 */

namespace Component\Order;

use DateTime;
use Framework\Utility\DateTimeUtils;
use Framework\Utility\NumberUtils;

class OrderSalesStatistics extends \Bundle\Component\Order\OrderSalesStatistics
{
  public function fetchSalesByMemberType($searchData)
  {
    $sDate = new DateTime($searchData['orderYMD'][0]);
    $eDate = new DateTime($searchData['orderYMD'][1]);
    $splitFirstOrder = $searchData['splitFirstOrder'] ?? false;

    // 입력받은 날짜를 그대로 사용 (Ymd 형식)
    $formattedSDate = $sDate->format('Ymd');
    $formattedEDate = $eDate->format('Ymd');

    // 첫주문/재주문 구분 여부에 따라 회원구분 필드 동적 생성
    if ($splitFirstOrder) {
      $memberGroupField = "
        CASE
          WHEN oss.memNo = 0 THEN '비회원'
          WHEN COALESCE(o.firstSaleFl, 'n') = 'y' THEN '회원-첫주문'
          ELSE '회원-재주문'
        END";
    } else {
      $memberGroupField = "IF(oss.memNo = 0, '비회원', '회원')";
    }

    $query = "
        SELECT
            LEFT(orderYMD, 6) AS '년월',
            {$memberGroupField} AS '회원구분',
			      COUNT(DISTINCT(oss.orderIP)) AS 'IP구매자수',
			      COUNT(DISTINCT(oss.memNo)) as '회원구매자수',
            COUNT(DISTINCT(oss.orderNo)) as '주문수',
            SUM(goodsPrice) AS '상품판매가',
            SUM(goodsDcPrice) AS '상품할인',
            SUM(goodsPrice - goodsDcPrice) AS '상품결제금액',
            SUM(deliveryPrice) AS '배송비',
            SUM(deliveryDcPrice) AS '배송비할인',
            SUM(deliveryPrice - deliveryDcPrice) AS '배송비결제금액',
            SUM(goodsPrice - goodsDcPrice + deliveryPrice - deliveryDcPrice) AS '총결제금액',
            SUM(
                CASE
                    WHEN type = 'goods' THEN refundGoodsPrice + refundUseDeposit + refundUseMileage
                    ELSE 0
                END
            ) AS '상품환불금액',
            SUM(
                CASE
                    WHEN type = 'delivery' THEN refundDeliveryPrice + refundUseDeposit + refundUseMileage
                    ELSE 0
                END
            ) AS '배송비환불금액',
            SUM(refundFeePrice) AS '환불수수료',
            SUM(
                (CASE
                    WHEN type = 'goods' THEN refundGoodsPrice + refundUseDeposit + refundUseMileage
                    ELSE 0
                END) +
                (CASE
                    WHEN type = 'delivery' THEN refundDeliveryPrice + refundUseDeposit + refundUseMileage
                    ELSE 0
                END) - refundFeePrice
            ) AS '환불총액',
            SUM(goodsPrice - goodsDcPrice + deliveryPrice - deliveryDcPrice
              - (CASE
                    WHEN type = 'goods' THEN refundGoodsPrice + refundUseDeposit + refundUseMileage
                    ELSE 0
                END)
              - (CASE
                    WHEN type = 'delivery' THEN refundDeliveryPrice + refundUseDeposit + refundUseMileage
                    ELSE 0
                END)
              + refundFeePrice
            ) AS '매출'
        FROM es_orderSalesStatistics oss
        LEFT JOIN es_order o ON (oss.orderNo = o.orderNo)
        WHERE orderYMD BETWEEN ? AND ?
        GROUP BY 1, 2;
      ";

    $arrBind = [];
    $this->db->bind_param_push($arrBind, 'i', $formattedSDate);
    $this->db->bind_param_push($arrBind, 'i', $formattedEDate);

    $result = $this->db->query_fetch($query, $arrBind);

    $monthKeys = [];

    $sMonth = substr($formattedSDate, 0, 6);
    $eMonth = substr($formattedEDate, 0, 6);

    $i = 0; // for safety
    $curMonth = $sMonth;
    while ($curMonth < $eMonth) {
      $monthKeys[] = $curMonth;
      $curMonth = substr(date('Ym', strtotime($curMonth . '01 +1 month')), 0, 6);

      if ($i++ > 30) { // for safety - 30 months max
        break;
      }
      $i++;
    }
    $monthKeys[] = $eMonth;

    // 첫주문/재주문 구분 여부에 따라 회원 타입 배열 동적 생성
    if ($splitFirstOrder) {
      $memberTypes = ['회원-첫주문', '회원-재주문', '비회원'];
    } else {
      $memberTypes = ['회원', '비회원'];
    }

    $itemKeys = ['매출', '회원구매자수', 'IP구매자수', '주문수', '객단가'];

    $returnData = [];
    foreach ($memberTypes as $memberType) {
      $returnData[$memberType] = [];
      foreach ($itemKeys as $itemKey) {
        if ($itemKey == '회원구매자수' && $memberType == '비회원') {
          continue;
        }

        if ($itemKey == 'IP구매자수' && ($memberType == '회원' || $memberType == '회원-첫주문' || $memberType == '회원-재주문')) {
          continue;
        }
        $returnData[$memberType][$itemKey]['회원구분'] = $memberType;
        $returnData[$memberType][$itemKey]['항목'] = $itemKey;
        foreach ($monthKeys as $monthKey) {
          $returnData[$memberType][$itemKey][$monthKey] = 0;
        }
      }
    }

    foreach ($result as $row) {
      $memberType = $row['회원구분'];
      $monthKey = $row['년월'];

      foreach ($itemKeys as $itemKey) {
        if ($itemKey == '회원구매자수' && $memberType == '비회원') {
          continue;
        }

        if ($itemKey == 'IP구매자수' && ($memberType == '회원' || $memberType == '회원-첫주문' || $memberType == '회원-재주문')) {
          continue;
        }

        if ($itemKey == '객단가') {
          $returnData[$memberType][$itemKey][$monthKey] = NumberUtils::moneyFormat($row['매출'] / $row['주문수']);
          continue;
        } else if (!$row[$itemKey]) {
          continue;
        }
        $returnData[$memberType][$itemKey][$monthKey] = NumberUtils::moneyFormat($row[$itemKey]);
      }
    }

    // flatten the array for the frontend
    $returnDataFlat = [];
    foreach ($memberTypes as $memberType) {
      $firstCategoryRow = true;
      foreach ($itemKeys as $itemKey) {
        if ($firstCategoryRow) {
          $returnData[$memberType][$itemKey]['_extraData']['rowSpan']['회원구분'] = count($itemKeys) - 1;
          $firstCategoryRow = false;
        }
        $returnDataFlat[] = $returnData[$memberType][$itemKey];
      }
    }

    return $returnDataFlat;
  }

  public function fetchSalesByWeekType($searchData)
  {
    $sDate = new DateTime($searchData['orderYMD'][0]);
    $eDate = new DateTime($searchData['orderYMD'][1]);
    $splitFirstOrder = $searchData['splitFirstOrder'] ?? false;

    // 입력받은 날짜를 그대로 사용 (Ymd 형식)
    $formattedSDate = $sDate->format('Ymd');
    $formattedEDate = $eDate->format('Ymd');

    // 첫주문/재주문 구분 여부에 따라 회원구분 필드 동적 생성
    if ($splitFirstOrder) {
      $memberGroupField = ",
        CASE
          WHEN oss.memNo = 0 THEN '비회원'
          WHEN COALESCE(o.firstSaleFl, 'n') = 'y' THEN '회원-첫주문'
          ELSE '회원-재주문'
        END AS '회원구분'";
      $orderJoin = "LEFT JOIN es_order o ON (oss.orderNo = o.orderNo)";
      $groupBy = "GROUP BY 1, 2, 3";
    } else {
      $memberGroupField = "";
      $orderJoin = "";
      $groupBy = "GROUP BY 1, 2";
    }

    $query = "
          SELECT
          LEFT(oss.orderYMD, 6) AS '년월',
    			REGEXP_SUBSTR(COALESCE(og.optionInfo, og2.optionInfo), '[0-9]+(?=주)') AS 'N주식단'
          {$memberGroupField},
          COUNT(DISTINCT(oss.orderIP)) AS 'IP구매자수',
          COUNT(DISTINCT(oss.memNo)) as '회원구매자수',
          COUNT(DISTINCT(oss.orderNo)) as '주문수',
          SUM(oss.goodsPrice) AS '상품판매가',
          SUM(oss.goodsDcPrice) AS '상품할인',
          SUM(oss.goodsPrice - oss.goodsDcPrice) AS '상품결제금액',
          SUM(oss.deliveryPrice) AS '배송비',
          SUM(oss.deliveryDcPrice) AS '배송비할인',
          SUM(oss.deliveryPrice - deliveryDcPrice) AS '배송비결제금액',
          SUM(oss.goodsPrice - oss.goodsDcPrice + oss.deliveryPrice - oss.deliveryDcPrice) AS '총결제금액',
          SUM(
              CASE
              WHEN type = 'goods' THEN oss.refundGoodsPrice + oss.refundUseDeposit + oss.refundUseMileage
              ELSE 0
              END
          ) AS '상품환불금액',
          SUM(
              CASE
              WHEN type = 'delivery' THEN oss.refundDeliveryPrice + oss.refundUseDeposit + oss.refundUseMileage
              ELSE 0
              END
          ) AS '배송비환불금액',
          SUM(oss.refundFeePrice) AS '환불수수료',
          SUM(
              (CASE
                WHEN type = 'goods' THEN oss.refundGoodsPrice + oss.refundUseDeposit + oss.refundUseMileage
                ELSE 0
                END) +
              (CASE
                WHEN type = 'delivery' THEN oss.refundDeliveryPrice + oss.refundUseDeposit + oss.refundUseMileage
                ELSE 0
                END) - refundFeePrice
          ) AS '환불총액',
          SUM(oss.goodsPrice - oss.goodsDcPrice + oss.deliveryPrice - oss.deliveryDcPrice
              - (CASE
                  WHEN type = 'goods' THEN oss.refundGoodsPrice + oss.refundUseDeposit + oss.refundUseMileage
                  ELSE 0
                END)
              - (CASE
                  WHEN type = 'delivery' THEN oss.refundDeliveryPrice + oss.refundUseDeposit + oss.refundUseMileage
                  ELSE 0
                END)
              + oss.refundFeePrice
              ) AS '매출'
        FROM es_orderSalesStatistics oss
        LEFT JOIN es_orderGoods og ON (oss.type = 'goods' AND oss.kind = 'order' AND  oss.relationSno = og.sno )
		    LEFT JOIN es_orderGoods og2 ON (oss.type = 'goods' AND oss.kind = 'refund' AND  oss.relationSno = og2.handleSno )
        {$orderJoin}
        WHERE
        (
          (oss.type = 'goods' AND
            (
              oss.goodsPrice != 0
              OR oss.deliveryPrice != 0
              OR oss.refundGoodsPrice != 0
              OR oss.refundUseDeposit != 0
              OR oss.refundUseMileage != 0
              OR oss.refundDeliveryPrice != 0
            )
          )
          OR oss.type != 'goods'
        )
        AND oss.orderYMD BETWEEN ? AND ?
        {$groupBy};
      ";

    $arrBind = [];
    $this->db->bind_param_push($arrBind, 'i', $formattedSDate);
    $this->db->bind_param_push($arrBind, 'i', $formattedEDate);

    $result = $this->db->query_fetch($query, $arrBind);

    $monthKeys = [];
    $sMonth = substr($formattedSDate, 0, 6);
    $eMonth = substr($formattedEDate, 0, 6);
    $i = 0; // for safety
    $curMonth = $sMonth;
    while ($curMonth < $eMonth) {
      $monthKeys[] = $curMonth;
      $curMonth = substr(date('Ym', strtotime($curMonth . '01 +1 month')), 0, 6);

      if ($i++ > 30) { // for safety - 30 months max
        break;
      }
      $i++;
    }
    $monthKeys[] = $eMonth;

    //weekTypes are the all unique values of the N주식단 column
    $weekTypes = [];
    foreach ($result as $row) {
      $weekTypes[$row['N주식단']] = $row['N주식단'];
    }
    $weekTypes = array_values($weekTypes);

    // sort the category keys ascending and null last
    usort($weekTypes, function ($a, $b) {
      if ($a == NULL) {
        return 1;
      } else if ($b == NULL) {
        return -1;
      } else {
        return $a - $b;
      }
    });

    // 첫주문/재주문 구분 여부에 따라 회원 타입 배열 동적 생성
    if ($splitFirstOrder) {
      $memberTypes = ['회원-첫주문', '회원-재주문', '비회원'];
    } else {
      $memberTypes = [null]; // 구분 없음
    }

    $itemKeys = ['매출', '회원구매자수', 'IP구매자수', '주문수', '객단가'];

    $returnData = [];

    if ($splitFirstOrder) {
      // 첫주문/재주문 구분 시: [weekType][memberType][itemKey]
      foreach ($weekTypes as $weekType) {
        foreach ($memberTypes as $memberType) {
          foreach ($itemKeys as $itemKey) {
            $returnData[$weekType][$memberType][$itemKey]['N주식단'] = $weekType != NULL ? $weekType . '주식단' : '기타';
            $returnData[$weekType][$memberType][$itemKey]['회원구분'] = $memberType;
            $returnData[$weekType][$memberType][$itemKey]['항목'] = $itemKey;
            foreach ($monthKeys as $monthKey) {
              $returnData[$weekType][$memberType][$itemKey][$monthKey] = 0;
            }
          }
        }
      }

      foreach ($result as $row) {
        $weekType = $row['N주식단'];
        $memberType = $row['회원구분'];
        $monthKey = $row['년월'];

        foreach ($itemKeys as $itemKey) {
          if ($itemKey == '객단가') {
            $returnData[$weekType][$memberType][$itemKey][$monthKey] = NumberUtils::moneyFormat($row['매출'] / $row['주문수']);
            continue;
          } else if (!$row[$itemKey]) {
            continue;
          }
          $returnData[$weekType][$memberType][$itemKey][$monthKey] = NumberUtils::moneyFormat($row[$itemKey]);
        }
      }

      // flatten the array for the frontend
      $returnDataFlat = [];
      foreach ($weekTypes as $weekType) {
        $firstWeekRow = true;
        foreach ($memberTypes as $memberType) {
          $firstMemberRow = true;
          foreach ($itemKeys as $itemKey) {
            if ($firstWeekRow) {
              $returnData[$weekType][$memberType][$itemKey]['_extraData']['rowSpan']['N주식단'] = count($memberTypes) * count($itemKeys);
              $firstWeekRow = false;
            }
            if ($firstMemberRow) {
              $returnData[$weekType][$memberType][$itemKey]['_extraData']['rowSpan']['회원구분'] = count($itemKeys);
              $firstMemberRow = false;
            }
            $returnDataFlat[] = $returnData[$weekType][$memberType][$itemKey];
          }
        }
      }
    } else {
      // 기존 로직: [weekType][itemKey]
      foreach ($weekTypes as $weekType) {
        $returnData[$weekType] = [];
        foreach ($itemKeys as $itemKey) {
          $returnData[$weekType][$itemKey]['N주식단'] = $weekType != NULL ? $weekType . '주식단' : '기타';
          $returnData[$weekType][$itemKey]['항목'] = $itemKey;
          foreach ($monthKeys as $monthKey) {
            $returnData[$weekType][$itemKey][$monthKey] = 0;
          }
        }
      }

      foreach ($result as $row) {
        $weekType = $row['N주식단'];
        $monthKey = $row['년월'];

        foreach ($itemKeys as $itemKey) {
          if ($itemKey == '객단가') {
            $returnData[$weekType][$itemKey][$monthKey] = NumberUtils::moneyFormat($row['매출'] / $row['주문수']);
            continue;
          } else if (!$row[$itemKey]) {
            continue;
          }
          $returnData[$weekType][$itemKey][$monthKey] = NumberUtils::moneyFormat($row[$itemKey]);
        }
      }

      // flatten the array for the frontend
      $returnDataFlat = [];
      foreach ($weekTypes as $weekType) {
        $firstCategoryRow = true;
        foreach ($itemKeys as $itemKey) {
          if ($firstCategoryRow) {
            $returnData[$weekType][$itemKey]['_extraData']['rowSpan']['N주식단'] = count($itemKeys);
            $firstCategoryRow = false;
          }
          $returnDataFlat[] = $returnData[$weekType][$itemKey];
        }
      }
    }

    return $returnDataFlat;
  }

  public function fetchSalesByGoods($searchData)
  {
    $sDate = new DateTime($searchData['orderYMD'][0]);
    $eDate = new DateTime($searchData['orderYMD'][1]);
    $splitFirstOrder = $searchData['splitFirstOrder'] ?? false;

    // 입력받은 날짜를 그대로 사용 (Ymd 형식)
    $formattedSDate = $sDate->format('Ymd');
    $formattedEDate = $eDate->format('Ymd');

    // 첫주문/재주문 구분 여부에 따라 회원구분 필드 동적 생성
    if ($splitFirstOrder) {
      $memberGroupField = ",
        CASE
          WHEN oss.memNo = 0 THEN '비회원'
          WHEN COALESCE(o.firstSaleFl, 'n') = 'y' THEN '회원-첫주문'
          ELSE '회원-재주문'
        END AS '회원구분'";
      $orderJoin = "LEFT JOIN es_order o ON (oss.orderNo = o.orderNo)";
      $groupBy = "GROUP BY 1, 2, 3, 4, 5";
      $orderBy = "ORDER BY cg.cateSort asc, 4, 5, 2, 3, 1";
    } else {
      $memberGroupField = "";
      $orderJoin = "";
      $groupBy = "GROUP BY 1, 2, 3, 4";
      $orderBy = "ORDER BY cg.cateSort asc, 4, 2, 3, 1";
    }

    $query = "
          SELECT  LEFT(oss.orderYMD, 6) AS '년월',
          cg.cateNm '카테고리',
          COALESCE(og.goodsNm, og2.goodsNm) '상품명',
    	    REGEXP_SUBSTR(COALESCE(og.optionInfo, og2.optionInfo), '[0-9]+(?=주)') AS 'N주식단'
          {$memberGroupField},
          COUNT(DISTINCT(oss.orderIP)) AS 'IP구매자수',
          COUNT(DISTINCT(oss.memNo)) as '회원구매자수',
          COUNT(DISTINCT(oss.orderNo)) as '주문수',
          SUM(oss.goodsPrice) AS '상품판매가',
          SUM(oss.goodsDcPrice) AS '상품할인',
          SUM(oss.goodsPrice - oss.goodsDcPrice) AS '상품결제금액',
          SUM(oss.deliveryPrice) AS '배송비',
          SUM(oss.deliveryDcPrice) AS '배송비할인',
          SUM(oss.deliveryPrice - deliveryDcPrice) AS '배송비결제금액',
          SUM(oss.goodsPrice - oss.goodsDcPrice + oss.deliveryPrice - oss.deliveryDcPrice) AS '총결제금액',
          SUM(
              CASE
              WHEN type = 'goods' THEN oss.refundGoodsPrice + oss.refundUseDeposit + oss.refundUseMileage
              ELSE 0
              END
          ) AS '상품환불금액',
          SUM(
              CASE
              WHEN type = 'delivery' THEN oss.refundDeliveryPrice + oss.refundUseDeposit + oss.refundUseMileage
              ELSE 0
              END
          ) AS '배송비환불금액',
          SUM(oss.refundFeePrice) AS '환불수수료',
          SUM(
              (CASE
                WHEN type = 'goods' THEN oss.refundGoodsPrice + oss.refundUseDeposit + oss.refundUseMileage
                ELSE 0
                END) +
              (CASE
                WHEN type = 'delivery' THEN oss.refundDeliveryPrice + oss.refundUseDeposit + oss.refundUseMileage
                ELSE 0
                END) - refundFeePrice
          ) AS '환불총액',
          SUM(oss.goodsPrice - oss.goodsDcPrice + oss.deliveryPrice - oss.deliveryDcPrice
              - (CASE
                  WHEN type = 'goods' THEN oss.refundGoodsPrice + oss.refundUseDeposit + oss.refundUseMileage
                  ELSE 0
                END)
              - (CASE
                  WHEN type = 'delivery' THEN oss.refundDeliveryPrice + oss.refundUseDeposit + oss.refundUseMileage
                  ELSE 0
                END)
              + oss.refundFeePrice
              ) AS '매출'
        FROM es_orderSalesStatistics oss
		  LEFT JOIN es_orderGoods og ON (oss.type = 'goods' AND oss.kind = 'order' AND  oss.relationSno = og.sno )
		  LEFT JOIN es_orderGoods og2 ON (oss.type = 'goods' AND oss.kind = 'refund' AND  oss.relationSno = og2.handleSno )
	    LEFT JOIN es_categoryGoods cg on (cg.cateCd = COALESCE(og.cateCd, og2.cateCd) and COALESCE(og.goodsType, og2.goodsType) = 'goods')
        {$orderJoin}
        WHERE
        (
          (oss.type = 'goods' AND
            (
              oss.goodsPrice != 0
              OR oss.deliveryPrice != 0
              OR oss.refundGoodsPrice != 0
              OR oss.refundUseDeposit != 0
              OR oss.refundUseMileage != 0
              OR oss.refundDeliveryPrice != 0
            )
          )
          OR oss.type != 'goods'
        )
        AND oss.orderYMD BETWEEN ? AND ?
        {$groupBy}
        {$orderBy}
      ";

    $arrBind = [];
    $this->db->bind_param_push($arrBind, 'i', $formattedSDate);
    $this->db->bind_param_push($arrBind, 'i', $formattedEDate);

    $result = $this->db->query_fetch($query, $arrBind);

    $monthKeys = [];
    $sMonth = substr($formattedSDate, 0, 6);
    $eMonth = substr($formattedEDate, 0, 6);
    $i = 0; // for safety
    $curMonth = $sMonth;
    while ($curMonth < $eMonth) {
      $monthKeys[] = $curMonth;
      $curMonth = substr(date('Ym', strtotime($curMonth . '01 +1 month')), 0, 6);

      if ($i++ > 30) { // for safety - 30 months max
        break;
      }
      $i++;
    }
    $monthKeys[] = $eMonth;

    //goodsKeys are the all unique values of the N주식단 column
    $categoryKeys = [];
    if ($splitFirstOrder) {
      foreach ($result as $row) {
        $categoryKeys[$row['카테고리']][$row['상품명']][$row['N주식단']][$row['회원구분']] = $row['회원구분'];
      }
    } else {
      foreach ($result as $row) {
        $categoryKeys[$row['카테고리']][$row['상품명']][$row['N주식단']] = $row['N주식단'];
      }
    }

    $nullCategory = $categoryKeys[NULL];
    unset($categoryKeys[NULL]);
    $categoryKeys[NULL] = $nullCategory;

    // 첫주문/재주문 구분 여부에 따라 회원 타입 배열 동적 생성
    if ($splitFirstOrder) {
      $memberTypes = ['회원-첫주문', '회원-재주문', '비회원'];
    } else {
      $memberTypes = [null]; // 구분 없음
    }

    $itemKeys = ['매출', '회원구매자수', 'IP구매자수', '주문수', '객단가'];

    $returnData = [];

    if ($splitFirstOrder) {
      // 첫주문/재주문 구분 시: [category][goods][weekType][memberType][itemKey]
      foreach ($categoryKeys as $categoryKey => $goodsKeys) {
        foreach ($goodsKeys as $goodsKey => $weekTypes) {
          foreach ($weekTypes as $weekType => $memberTypeList) {
            usort($memberTypeList, function ($a, $b) {
              $order = ['회원-첫주문' => 1, '회원-재주문' => 2, '비회원' => 3];
              return ($order[$a] ?? 99) - ($order[$b] ?? 99);
            });
            foreach ($memberTypeList as $memberType) {
              foreach ($itemKeys as $itemKey) {
                $returnData[$categoryKey][$goodsKey][$weekType][$memberType][$itemKey]['카테고리'] = $categoryKey ? $categoryKey : '없음';
                $returnData[$categoryKey][$goodsKey][$weekType][$memberType][$itemKey]['상품명'] = $goodsKey ? $goodsKey : '없음';
                $returnData[$categoryKey][$goodsKey][$weekType][$memberType][$itemKey]['N주식단'] = $weekType != NULL ? $weekType . '주식단' : '';
                $returnData[$categoryKey][$goodsKey][$weekType][$memberType][$itemKey]['회원구분'] = $memberType;
                $returnData[$categoryKey][$goodsKey][$weekType][$memberType][$itemKey]['항목'] = $itemKey;
                foreach ($monthKeys as $monthKey) {
                  $returnData[$categoryKey][$goodsKey][$weekType][$memberType][$itemKey][$monthKey] = 0;
                }
              }
            }
          }
        }
      }

      foreach ($result as $row) {
        $categoryKey = $row['카테고리'];
        $goodsKey = $row['상품명'];
        $weekType = $row['N주식단'];
        $memberType = $row['회원구분'];
        $monthKey = $row['년월'];

        foreach ($itemKeys as $itemKey) {
          if ($itemKey == '객단가') {
            $returnData[$categoryKey][$goodsKey][$weekType][$memberType][$itemKey][$monthKey] = NumberUtils::moneyFormat($row['매출'] / $row['주문수']);
            continue;
          } else if (!$row[$itemKey]) {
            continue;
          }
          $returnData[$categoryKey][$goodsKey][$weekType][$memberType][$itemKey][$monthKey] = NumberUtils::moneyFormat($row[$itemKey]);
        }
      }

      // flatten the array for the frontend
      $returnDataFlat = [];
      foreach ($categoryKeys as $categoryKey => $goodsKeys) {
        $firstCategoryRow = true;
        $countTotal = 0;
        foreach ($goodsKeys as $goodsKey => $weekTypes) {
          foreach ($weekTypes as $weekType => $memberTypeList) {
            $countTotal += count($memberTypeList) * count($itemKeys);
          }
        }
        foreach ($goodsKeys as $goodsKey => $weekTypes) {
          $firstGoodsRow = true;
          $countOfGoodsRows = 0;
          foreach ($weekTypes as $weekType => $memberTypeList) {
            $countOfGoodsRows += count($memberTypeList) * count($itemKeys);
          }
          foreach ($weekTypes as $weekType => $memberTypeList) {
            $firstWeekRow = true;
            foreach ($memberTypeList as $memberType) {
              $firstMemberRow = true;
              foreach ($itemKeys as $itemKey) {
                if ($firstCategoryRow) {
                  $returnData[$categoryKey][$goodsKey][$weekType][$memberType][$itemKey]['_extraData']['rowSpan']['카테고리'] = $countTotal;
                  $firstCategoryRow = false;
                }
                if ($firstGoodsRow) {
                  $returnData[$categoryKey][$goodsKey][$weekType][$memberType][$itemKey]['_extraData']['rowSpan']['상품명'] = $countOfGoodsRows;
                  $firstGoodsRow = false;
                }
                if ($firstWeekRow) {
                  $returnData[$categoryKey][$goodsKey][$weekType][$memberType][$itemKey]['_extraData']['rowSpan']['N주식단'] = count($memberTypeList) * count($itemKeys);
                  $firstWeekRow = false;
                }
                if ($firstMemberRow) {
                  $returnData[$categoryKey][$goodsKey][$weekType][$memberType][$itemKey]['_extraData']['rowSpan']['회원구분'] = count($itemKeys);
                  $firstMemberRow = false;
                }
                $returnDataFlat[] = $returnData[$categoryKey][$goodsKey][$weekType][$memberType][$itemKey];
              }
            }
          }
        }
      }
    } else {
      // 기존 로직: [category][goods][weekType][itemKey]
      foreach ($categoryKeys as $categoryKey => $goodsKeys) {
        foreach ($goodsKeys as $goodsKey => $weekTypes) {

          usort($weekTypes, function ($a, $b) {
            if ($a == NULL) {
              return 1;
            } else if ($b == NULL) {
              return -1;
            } else {
              return $a - $b;
            }
          });

          $returnData[$goodsKey] = [];
          foreach ($weekTypes as $weekType) {
            foreach ($itemKeys as $itemKey) {
              $returnData[$categoryKey][$goodsKey][$weekType][$itemKey]['카테고리'] = $categoryKey ? $categoryKey : '없음';
              $returnData[$categoryKey][$goodsKey][$weekType][$itemKey]['상품명'] = $goodsKey ? $goodsKey : '없음';
              $returnData[$categoryKey][$goodsKey][$weekType][$itemKey]['N주식단'] = $weekType != NULL ? $weekType . '주식단' : '';
              $returnData[$categoryKey][$goodsKey][$weekType][$itemKey]['항목'] = $itemKey;
              foreach ($monthKeys as $monthKey) {
                $returnData[$categoryKey][$goodsKey][$weekType][$itemKey][$monthKey] = 0;
              }
            }
          }
        }
      }

      foreach ($result as $row) {
        $categoryKey = $row['카테고리'];
        $goodsKey = $row['상품명'];
        $weekType = $row['N주식단'];
        $monthKey = $row['년월'];

        foreach ($itemKeys as $itemKey) {
          if ($itemKey == '객단가') {
            $returnData[$categoryKey][$goodsKey][$weekType][$itemKey][$monthKey] = NumberUtils::moneyFormat($row['매출'] / $row['주문수']);
            continue;
          } else if (!$row[$itemKey]) {
            continue;
          }
          $returnData[$categoryKey][$goodsKey][$weekType][$itemKey][$monthKey] = NumberUtils::moneyFormat($row[$itemKey]);
        }
      }

      // flatten the array for the frontend
      $returnDataFlat = [];
      foreach ($categoryKeys as $categoryKey => $goodsKeys) {
        $firstCategoryRow = true;
        $countOfWeekTypes = array_reduce($goodsKeys, function ($carry, $item) {
          return $carry + count($item);
        }, 0);
        foreach ($goodsKeys as $goodsKey => $weekTypes) {
          $firstGoodsRow = true;
          foreach ($weekTypes as $weekType) {
            $firstWeekRow = true;
            foreach ($itemKeys as $itemKey) {
              if ($firstCategoryRow) {
                $returnData[$categoryKey][$goodsKey][$weekType][$itemKey]['_extraData']['rowSpan']['카테고리'] =  $countOfWeekTypes * count($itemKeys);
                $firstCategoryRow = false;
              }
              if ($firstGoodsRow) {
                $returnData[$categoryKey][$goodsKey][$weekType][$itemKey]['_extraData']['rowSpan']['상품명'] = count($itemKeys) * count($weekTypes);
                $firstGoodsRow = false;
              }
              if ($firstWeekRow) {
                $returnData[$categoryKey][$goodsKey][$weekType][$itemKey]['_extraData']['rowSpan']['N주식단'] = count($itemKeys);
                $firstWeekRow = false;
              }
              $returnDataFlat[] = $returnData[$categoryKey][$goodsKey][$weekType][$itemKey];
            }
          }
        }
      }
    }

    return $returnDataFlat;
  }

  public function fetchMemSalesByMember($searchData)
  {
    $sDate = new DateTime($searchData['orderYMD'][0]);
    $eDate = new DateTime($searchData['orderYMD'][1]);
    $nWeek = $searchData['nWeek'];

    $formattedSDate = $sDate->format('Ymd');
    $formattedEDate = $eDate->format('Ymd');

    $query = "
        SELECT  
          oss.memNo AS '회원번호',
          m.memNm AS '회원명',
          COUNT(DISTINCT(oss.orderNo)) as '주문수',
          m.phone AS '전화번호', 
          m.cellPhone AS '핸드폰번호',
          m.email AS '이메일',
          lo.lastOrderDt AS '마지막주문일시',
          m.privateApprovalFl AS '개인정보활용동의',
          mg.groupNm AS '회원등급',
          GROUP_CONCAT(DISTINCT oi.orderName) AS '주문자명',
          GROUP_CONCAT(DISTINCT oi.orderCellPhone) AS '주문전화번호',
          GROUP_CONCAT(
            CASE
              WHEN og2.handleSno IS NULL THEN RIGHT(oss.orderYMD, 6)
            END
            ORDER BY oss.orderYMD ASC
          ) AS '주문년월', 
          GROUP_CONCAT(
            CASE
              WHEN og2.handleSno IS NULL THEN REGEXP_SUBSTR(og.optionInfo, '[0-9]+(?=주)')
            END
            ORDER BY oss.orderYMD ASC
          ) AS 'N주식단',
          cg.cateNm '카테고리',
          GROUP_CONCAT(DISTINCT COALESCE(og.goodsNm, og2.goodsNm)) '상품명',
          SUM(oss.goodsPrice) AS '상품판매가', 
          SUM(oss.goodsDcPrice) AS '상품할인', 
          SUM(oss.goodsPrice - oss.goodsDcPrice) AS '상품결제금액', 
          SUM(oss.deliveryPrice) AS '배송비', 
          SUM(oss.deliveryDcPrice) AS '배송비할인', 
          SUM(oss.deliveryPrice - deliveryDcPrice) AS '배송비결제금액', 
          SUM(oss.goodsPrice - oss.goodsDcPrice + oss.deliveryPrice - oss.deliveryDcPrice) AS '총결제금액', 
          SUM(
              CASE 
              WHEN type = 'goods' THEN oss.refundGoodsPrice + oss.refundUseDeposit + oss.refundUseMileage
              ELSE 0 
              END
          ) AS '상품환불금액', 
          SUM(
              CASE 
              WHEN type = 'delivery' THEN oss.refundDeliveryPrice + oss.refundUseDeposit + oss.refundUseMileage
              ELSE 0 
              END
          ) AS '배송비환불금액', 
          SUM(oss.refundFeePrice) AS '환불수수료', 
          SUM(
              (CASE 
                WHEN type = 'goods' THEN oss.refundGoodsPrice + oss.refundUseDeposit + oss.refundUseMileage
                ELSE 0 
                END) + 
              (CASE 
                WHEN type = 'delivery' THEN oss.refundDeliveryPrice + oss.refundUseDeposit + oss.refundUseMileage
                ELSE 0 
                END) - refundFeePrice
          ) AS '환불총액',
          SUM(oss.goodsPrice - oss.goodsDcPrice + oss.deliveryPrice - oss.deliveryDcPrice
              - (CASE 
                  WHEN type = 'goods' THEN oss.refundGoodsPrice + oss.refundUseDeposit + oss.refundUseMileage
                  ELSE 0 
                END)
              - (CASE 
                  WHEN type = 'delivery' THEN oss.refundDeliveryPrice + oss.refundUseDeposit + oss.refundUseMileage
                  ELSE 0 
                END)
              + oss.refundFeePrice
              ) AS '매출'
        FROM es_orderSalesStatistics oss
		    LEFT JOIN es_orderGoods og ON (oss.type = 'goods' AND oss.kind = 'order' AND  oss.relationSno = og.sno AND og.deliveryCompleteDt != '0000-00-00 00:00:00' )
		    LEFT JOIN es_orderGoods og2 ON (oss.type = 'goods' AND oss.kind = 'refund' AND  oss.relationSno = og2.handleSno )
	      LEFT JOIN es_categoryGoods cg on (cg.cateCd = COALESCE(og.cateCd, og2.cateCd) and COALESCE(og.goodsType, og2.goodsType) = 'goods')
        JOIN es_member m on (m.memNo = oss.memNo)
		    LEFT OUTER JOIN es_memberGroup mg on (mg.sno = m.groupSno)
        JOIN es_orderInfo oi on (oi.orderNo = oss.orderNo)
        JOIN (
          select oss.memNo, MAX(oi.regDt) lastOrderDt
          from es_orderSalesStatistics oss
            JOIN es_orderInfo oi on (oi.orderNo = oss.orderNo)
            group by oss.memNo) lo on (oss.memNo = lo.memNo)
        WHERE 
        (
          (oss.type = 'goods' AND 
            ( 
              oss.goodsPrice != 0 
              OR oss.deliveryPrice != 0 
              OR oss.refundGoodsPrice != 0 
              OR oss.refundUseDeposit != 0
              OR oss.refundUseMileage != 0 
              OR oss.refundDeliveryPrice != 0
            )
          ) 
          OR oss.type != 'goods'
        )
        AND oss.orderYMD BETWEEN ? AND ?
        AND REGEXP_SUBSTR(COALESCE(og.optionInfo, og2.optionInfo), '[0-9]+(?=주)') >= ?
        Group by oss.memNo
        order by 3 desc;
      ";

    $arrBind = [];
    $this->db->bind_param_push($arrBind, 'i', $formattedSDate);
    $this->db->bind_param_push($arrBind, 'i', $formattedEDate);
    $this->db->bind_param_push($arrBind, 'i', $nWeek);

    $result = $this->db->query_fetch($query, $arrBind);

    return $result;
  }

  public function fetchMemSalesByContact($searchData)
  {
    $sDate = new DateTime($searchData['orderYMD'][0]);
    $eDate = new DateTime($searchData['orderYMD'][1]);
    $nWeek = $searchData['nWeek'];

    $formattedSDate = $sDate->format('Ymd');
    $formattedEDate = $eDate->format('Ymd');

    $query = "
        SELECT  
          GROUP_CONCAT(DISTINCT oi.orderName) AS '주문자명',
          oi.orderCellPhone AS '주문연락처',
          COUNT(DISTINCT(oss.orderNo)) as '주문수',
          m.memNo AS '회원번호',
          m.memNm AS '회원명',
          m.phone AS '전화번호', 
          m.cellPhone AS '핸드폰번호',
          COALESCE(m.email, oi.orderEmail) AS '이메일',
          lo.lastOrderDt AS '마지막주문일시',
          m.privateApprovalFl AS '개인정보활용동의',
          mg.groupNm AS '회원등급',
          GROUP_CONCAT(
            CASE
              WHEN og2.handleSno IS NULL THEN RIGHT(oss.orderYMD, 6)
            END
            ORDER BY oss.orderYMD ASC
          ) AS '주문년월', 
          GROUP_CONCAT(
            CASE
              WHEN og2.handleSno IS NULL THEN REGEXP_SUBSTR(og.optionInfo, '[0-9]+(?=주)')
            END
            ORDER BY oss.orderYMD ASC
          ) AS 'N주식단',
          cg.cateNm '카테고리',
          GROUP_CONCAT(DISTINCT COALESCE(og.goodsNm, og2.goodsNm)) '상품명',
          SUM(oss.goodsPrice) AS '상품판매가', 
          SUM(oss.goodsDcPrice) AS '상품할인', 
          SUM(oss.goodsPrice - oss.goodsDcPrice) AS '상품결제금액', 
          SUM(oss.deliveryPrice) AS '배송비', 
          SUM(oss.deliveryDcPrice) AS '배송비할인', 
          SUM(oss.deliveryPrice - deliveryDcPrice) AS '배송비결제금액', 
          SUM(oss.goodsPrice - oss.goodsDcPrice + oss.deliveryPrice - oss.deliveryDcPrice) AS '총결제금액', 
          SUM(
              CASE 
              WHEN type = 'goods' THEN oss.refundGoodsPrice + oss.refundUseDeposit + oss.refundUseMileage
              ELSE 0 
              END
          ) AS '상품환불금액', 
          SUM(
              CASE 
              WHEN type = 'delivery' THEN oss.refundDeliveryPrice + oss.refundUseDeposit + oss.refundUseMileage
              ELSE 0 
              END
          ) AS '배송비환불금액', 
          SUM(oss.refundFeePrice) AS '환불수수료', 
          SUM(
              (CASE 
                WHEN type = 'goods' THEN oss.refundGoodsPrice + oss.refundUseDeposit + oss.refundUseMileage
                ELSE 0 
                END) + 
              (CASE 
                WHEN type = 'delivery' THEN oss.refundDeliveryPrice + oss.refundUseDeposit + oss.refundUseMileage
                ELSE 0 
                END) - refundFeePrice
          ) AS '환불총액',
          SUM(oss.goodsPrice - oss.goodsDcPrice + oss.deliveryPrice - oss.deliveryDcPrice
              - (CASE 
                  WHEN type = 'goods' THEN oss.refundGoodsPrice + oss.refundUseDeposit + oss.refundUseMileage
                  ELSE 0 
                END)
              - (CASE 
                  WHEN type = 'delivery' THEN oss.refundDeliveryPrice + oss.refundUseDeposit + oss.refundUseMileage
                  ELSE 0 
                END)
              + oss.refundFeePrice
              ) AS '매출'
        FROM es_orderSalesStatistics oss
        LEFT JOIN es_orderGoods og ON (oss.type = 'goods' AND oss.kind = 'order' AND  oss.relationSno = og.sno AND og.deliveryCompleteDt != '0000-00-00 00:00:00' )
        LEFT JOIN es_orderGoods og2 ON (oss.type = 'goods' AND oss.kind = 'refund' AND  oss.relationSno = og2.handleSno )
        LEFT JOIN es_categoryGoods cg on (cg.cateCd = COALESCE(og.cateCd, og2.cateCd) and COALESCE(og.goodsType, og2.goodsType) = 'goods')
        LEFT OUTER JOIN es_member m on (m.memNo = oss.memNo)
        LEFT OUTER JOIN es_memberGroup mg on (mg.sno = m.groupSno)
        JOIN es_orderInfo oi on (oi.orderNo = oss.orderNo)
        JOIN (
          select oi.orderCellPhone, MAX(oi.regDt) lastOrderDt
          from es_orderSalesStatistics oss
            JOIN es_orderInfo oi on (oi.orderNo = oss.orderNo)
            group by oi.orderCellPhone) lo on (oi.orderCellPhone = lo.orderCellPhone)
          WHERE 
          (
            (oss.type = 'goods' AND 
              ( 
                oss.goodsPrice != 0 
                OR oss.deliveryPrice != 0 
                OR oss.refundGoodsPrice != 0 
                OR oss.refundUseDeposit != 0
                OR oss.refundUseMileage != 0 
                OR oss.refundDeliveryPrice != 0
              )
            ) 
            OR oss.type != 'goods'
          )
          AND oss.orderYMD BETWEEN ? AND ?
          AND REGEXP_SUBSTR(COALESCE(og.optionInfo, og2.optionInfo), '[0-9]+(?=주)') >= ?
          Group by oi.orderCellPhone
          order by 3 desc;
      ";

    $arrBind = [];
    $this->db->bind_param_push($arrBind, 'i', $formattedSDate);
    $this->db->bind_param_push($arrBind, 'i', $formattedEDate);
    $this->db->bind_param_push($arrBind, 'i', $nWeek);
 

    $result = $this->db->query_fetch($query, $arrBind);

    return $result;
  }
}
