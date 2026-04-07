<?php

/**
 * This is commercial software, only users who have purchased a valid license
 * and accept to the terms of the License Agreement can install and use this
 * program.
 *
 * Do not edit or add to this file if you wish to upgrade Godomall5 to newer
 * versions in the future.
 *
 * @copyright ⓒ 2025, Medisola.
 * @link https://weare.medisola.co.kr
 */

namespace Controller\Admin\Goods;

use App;
use Request;
use Framework\Debug\Exception\LayerException;

/**
 * 영양 정보 관리 처리 컨트롤러
 *
 * @package Controller\Admin\Goods
 */
class NutritionInfoPsController extends \Controller\Admin\Controller
{
    /**
     * 수정 가능한 필드 화이트리스트
     */
    private static $allowedFields = [
        'nutrition_calories',
        'nutrition_protein',
        'nutrition_carbs',
        'nutrition_sugar',
        'nutrition_fat',
        'nutrition_saturated_fat',
        'nutrition_trans_fat',
        'nutrition_sodium',
        'nutrition_omega3',
        'nutrition_cholesterol',
        'nutrition_fiber',
        'product_weight',
        'category',
        'disease_type',
        'nutrition_tags',
        'food_style',
        'meal_type',
        'main_ingredients',
        'allergens',
        'name_en',
        'description',
    ];

    /**
     * 숫자형 필드 (DECIMAL/INT)
     */
    private static $numericFields = [
        'nutrition_calories',
        'nutrition_protein',
        'nutrition_carbs',
        'nutrition_sugar',
        'nutrition_fat',
        'nutrition_saturated_fat',
        'nutrition_trans_fat',
        'nutrition_sodium',
        'nutrition_omega3',
        'nutrition_cholesterol',
        'nutrition_fiber',
        'product_weight',
    ];

    /**
     * JSON 배열로 저장되는 필드
     */
    private static $jsonFields = [
        'main_ingredients',
        'allergens',
    ];

    public function index()
    {
        $postValue = Request::post()->toArray();

        try {
            switch ($postValue['mode']) {
                case 'bulkSave':
                    $this->processBulkSave($postValue);
                    break;

                default:
                    throw new \Exception('잘못된 요청입니다.');
            }
        } catch (\Exception $e) {
            echo json_encode([
                'code' => 0,
                'message' => $e->getMessage(),
            ]);
            exit;
        }
    }

    /**
     * 일괄 저장 처리
     */
    private function processBulkSave($postValue)
    {
        $rows = json_decode($postValue['rows'], true);

        if (empty($rows) || !is_array($rows)) {
            throw new \Exception('저장할 데이터가 없습니다.');
        }

        $db = App::load('DB');
        $savedCount = 0;

        foreach ($rows as $row) {
            $addGoodsNo = (int)$row['addGoodsNo'];
            if ($addGoodsNo <= 0) {
                continue;
            }

            $arrUpdate = [];
            $arrBind = [];

            foreach ($row as $field => $value) {
                if ($field === 'addGoodsNo') {
                    continue;
                }
                if (!in_array($field, self::$allowedFields)) {
                    continue;
                }

                if (in_array($field, self::$numericFields)) {
                    if ($value === '' || $value === null) {
                        $arrUpdate[] = "`{$field}` = NULL";
                    } else {
                        $arrUpdate[] = "`{$field}` = ?";
                        $db->bind_param_push($arrBind, 's', $value);
                    }
                } elseif (in_array($field, self::$jsonFields)) {
                    // 콤마 구분 텍스트를 JSON 배열로 변환
                    if ($value === '' || $value === null) {
                        $arrUpdate[] = "`{$field}` = NULL";
                    } else {
                        $items = array_map('trim', explode(',', $value));
                        $items = array_values(array_filter($items, function($v) { return $v !== ''; }));
                        $jsonValue = !empty($items) ? json_encode($items, JSON_UNESCAPED_UNICODE) : null;
                        if ($jsonValue === null) {
                            $arrUpdate[] = "`{$field}` = NULL";
                        } else {
                            $arrUpdate[] = "`{$field}` = ?";
                            $db->bind_param_push($arrBind, 's', $jsonValue);
                        }
                    }
                } else {
                    $arrUpdate[] = "`{$field}` = ?";
                    $db->bind_param_push($arrBind, 's', $value);
                }
            }

            if (empty($arrUpdate)) {
                continue;
            }

            $db->bind_param_push($arrBind, 'i', $addGoodsNo);
            $db->set_update_db(DB_ADD_GOODS, $arrUpdate, 'addGoodsNo = ?', $arrBind);
            $savedCount++;
        }

        echo json_encode([
            'code' => 200,
            'message' => $savedCount . '개 상품의 영양 정보가 저장되었습니다.',
            'count' => $savedCount,
        ]);
        exit;
    }
}
