<?php

namespace Component\DietFinder;

use Component\Database\DBTableField;

/**
 * 나만의 식단 플랜 컴포넌트
 * - addGoods 데이터를 V0 MenuItem 형식으로 변환
 * - 영양 라인 계산 및 필터링
 * - responseSno 기반 추천 메뉴 조회
 */
class DietFinder
{
    protected $db = null;
    protected $arrBind = [];
    protected $arrWhere = [];

    /**
     * 조건 ID → 질환식 매핑 (조건 ID별 1:1)
     * diseaseType: addGoods의 disease_type 컬럼과 매칭하여 메뉴 필터링
     * label: UI에 표시되는 질환식 이름
     */
    protected static $diseaseDietMap = [
        'diabetes' => [
            'id' => 'diabetes-care', 'diseaseType' => '당뇨케어', 'label' => '당뇨식',
            'color' => '#3B82F6', 'reason' => '혈당 관리에 최적화된 저당 중심 식단',
            'benefits' => ['혈당 안정', '인슐린 감수성 개선', '에너지 균형'],
        ],
        'gestational-diabetes' => [
            'id' => 'gest-diabetes-care', 'diseaseType' => '당뇨케어', 'label' => '임신성당뇨식',
            'color' => '#EC4899', 'reason' => '임신 중 혈당 관리와 태아 영양을 고려한 식단',
            'benefits' => ['혈당 안정', '태아 영양 공급', '엽산·철분 보충'],
        ],
        'kidney-pre-dialysis' => [
            'id' => 'kidney-pre-care', 'diseaseType' => '신장튼튼', 'label' => '신장튼튼식',
            'color' => '#8B5CF6', 'reason' => '신장 기능 보존을 위한 저염·저단백 식단',
            'benefits' => ['신장 보호', '나트륨 관리', '전해질 균형'],
        ],
        'kidney-dialysis' => [
            'id' => 'kidney-dial-care', 'diseaseType' => '신장케어', 'label' => '신장케어식',
            'color' => '#7C3AED', 'reason' => '투석 환자를 위한 단백질 보충·체액 관리 식단',
            'benefits' => ['단백질 보충', '체액 균형', '칼륨·인 관리'],
        ],
        'cancer' => [
            'id' => 'cancer-care', 'diseaseType' => '암케어', 'label' => '암케어식',
            'color' => '#F472B6', 'reason' => '체력 유지와 면역력 강화를 위한 고단백 식단',
            'benefits' => ['면역력 강화', '체력 유지', '영양 보충'],
        ],
        'breast-cancer' => [
            'id' => 'breast-cancer-care', 'diseaseType' => '핑크리본', 'label' => '핑크리본',
            'color' => '#EC4899', 'reason' => '유방암 케어를 위한 항염·면역 강화 식단',
            'benefits' => ['항염 효과', '면역력 강화', '호르몬 균형'],
        ],
    ];

    public function __construct()
    {
        if (!is_object($this->db)) {
            $this->db = \App::load('DB');
        }
    }

    /**
     * 나만의 식단 플랜용 메뉴 목록 조회 (V0 MenuItem 형식)
     * @param int $goodsNo 상품번호 (미사용, 하위 호환성용)
     * @param array|null $goodsViewAddGoods $goodsView['addGoods'] 데이터 (선택)
     * @return array V0 MenuItem 형식의 메뉴 배열
     */
    public function getMenuItemsForCustomDiet($goodsNo, $goodsViewAddGoods = null)
    {   // goodsView['addGoods']가 전달되면 해당 데이터 사용
        if ($goodsViewAddGoods !== null) {
            $addGoods = $this->extractMenuAddGoodsFromGoodsView($goodsViewAddGoods);
        } else {
            // 하위 호환성: DB에서 직접 조회
            $addGoods = $this->getAddGoodsByGoodsNo($goodsNo);
        }

        $self = $this;
        return array_map(function($item) use ($self) {
            return [
                'id' => $item['addGoodsNo'],
                'name' => $item['goodsNm'],
                'nameEn' => $item['name_en'] ?? '',
                'image' => $self->buildImageUrl($item),
                'detailImage' => $self->buildDetailImageUrl($item),
                'description' => $item['description'] ?? $item['goodsDescription'] ?? '',
                'category' => $self->parseCategories($item['category'] ?? ''),
                'foodStyle' => $self->parseTags($item['food_style'] ?? ''),
                'mealType' => $self->parseTags($item['meal_type'] ?? ''),
                'calories' => (int)($item['nutrition_calories'] ?? 0),
                'protein' => (float)($item['nutrition_protein'] ?? 0),
                'carbs' => (float)($item['nutrition_carbs'] ?? 0),
                'fat' => (float)($item['nutrition_fat'] ?? 0),
                'saturatedFat' => (float)($item['nutrition_saturated_fat'] ?? 0),
                'transFat' => (float)($item['nutrition_trans_fat'] ?? 0),
                'sodium' => (int)($item['nutrition_sodium'] ?? 0),
                'sugar' => (float)($item['nutrition_sugar'] ?? 0),
                'cholesterol' => (int)($item['nutrition_cholesterol'] ?? 0),
                'omega3' => (int)($item['nutrition_omega3'] ?? 0),
                'fiber' => (float)($item['nutrition_fiber'] ?? 0),
                'weight' => (int)($item['product_weight'] ?? 0),
                'lines' => $self->calculateLines($item),
                'tags' => $self->parseTags($item['nutrition_tags'] ?? ''),
                'mainIngredients' => $self->parseJsonArray($item['main_ingredients'] ?? null),
                'allergens' => $self->parseJsonArray($item['allergens'] ?? null),
                'diseaseType' => $self->parseTags($item['disease_type'] ?? ''),
                'isNew' => (bool)($item['is_new'] ?? 0),
                'healthIndicators' => $self->calculateHealthIndicators($item),
                'recommendReasons' => json_decode($item['recommend_reasons'] ?? '{}', true),
                'addGoodsNo' => $item['addGoodsNo'],
                'goodsPrice' => (int)($item['goodsPrice'] ?? 0),
                'limitCnt' => (int)($item['limitCnt'] ?? 0),
                'premiumMultiplier' => (int)($item['premiumMultiplier'] ?? 1),
                'soldOutFl' => $item['soldOutFl'] ?? 'n',
                'stockUseFl' => $item['stockUseFl'] ?? '0',
                'stockCnt' => (int)($item['stockCnt'] ?? 0),
            ];
        }, $addGoods);
    }

    /**
     * goodsView['addGoods']에서 "메뉴" 그룹의 addGoodsList 추출
     * @param array $goodsViewAddGoods goodsView['addGoods'] 배열
     * @return array addGoodsList 배열
     */
    protected function extractMenuAddGoodsFromGoodsView($goodsViewAddGoods)
    {
        if (!is_array($goodsViewAddGoods)) {
            return [];
        }

        foreach ($goodsViewAddGoods as $group) {
            if (isset($group['title']) && $group['title'] === '메뉴' && !empty($group['addGoodsList'])) {
                return $group['addGoodsList'];
            }
        }

        return [];
    }

    /**
     * goodsNo에 연결된 addGoods 목록 조회
     * es_goods.addGoods JSON 필드에서 "메뉴" 타이틀 그룹의 추가상품 조회
     * @param int $goodsNo 상품번호
     * @return array addGoods 배열
     */
    protected function getAddGoodsByGoodsNo($goodsNo)
    {
        // 1. es_goods에서 addGoods JSON 필드 조회
        $arrBind = [];
        $this->db->bind_param_push($arrBind, 'i', $goodsNo);

        $strSQL = "SELECT addGoods FROM " . DB_GOODS . " WHERE goodsNo = ?";
        $goodsData = $this->db->query_fetch($strSQL, $arrBind, false);

        if (empty($goodsData['addGoods'])) {
            return [];
        }

        // 2. JSON 파싱 및 "메뉴" 그룹 찾기
        $addGoodsJson = json_decode(stripslashes($goodsData['addGoods']), true);
        if (!is_array($addGoodsJson)) {
            return [];
        }

        $menuAddGoodsNos = [];
        foreach ($addGoodsJson as $group) {
            if (isset($group['title']) && $group['title'] === '메뉴' && !empty($group['addGoods'])) {
                $menuAddGoodsNos = $group['addGoods'];
                break;
            }
        }

        if (empty($menuAddGoodsNos)) {
            return [];
        }

        // 3. addGoodsNo 배열로 es_addGoods 조회
        $placeholders = implode(',', array_fill(0, count($menuAddGoodsNos), '?'));
        $arrBind = [];
        foreach ($menuAddGoodsNos as $addGoodsNo) {
            $this->db->bind_param_push($arrBind, 'i', $addGoodsNo);
        }

        $strSQL = "SELECT * FROM " . DB_ADD_GOODS . "
                   WHERE addGoodsNo IN ({$placeholders})
                   AND applyFl = 'y' AND viewFl = 'y'
                   ORDER BY FIELD(addGoodsNo, " . implode(',', $menuAddGoodsNos) . ")";

        $result = $this->db->query_fetch($strSQL, $arrBind);
        return gd_htmlspecialchars_stripslashes($result) ?: [];
    }

    /**
     * 영양 라인 계산 (확정 7개 라인)
     * - 고단백: 단백질 20g 이상 (per serving)
     * - 저나트륨: 나트륨 650mg 이하 (per serving)
     * - 오메가3: 오메가3 1,000mg 이상 (per serving, DB 단위: mg)
     * - 550kcal 라인: 열량 550kcal 이상 (per serving)
     * - 저당: 당류 100g당 5g 미만 (per 100g)
     * - 저포화지방: 포화지방 100g당 1.5g 미만 (per 100g)
     * - 저콜레스테롤: 콜레스테롤 100g당 20mg 미만 (per 100g)
     * @param array $item addGoods 데이터
     * @return array 라인 배열
     */
    public function calculateLines($item)
    {
        $lines = [];

        // 기본 영양소 추출 (per serving)
        $protein = floatval($item['nutrition_protein'] ?? 0);
        $sodium = intval($item['nutrition_sodium'] ?? 999);
        $omega3 = intval($item['nutrition_omega3'] ?? 0);   // mg 단위
        $calories = intval($item['nutrition_calories'] ?? 999);
        $sugar = floatval($item['nutrition_sugar'] ?? 999);
        $saturatedFat = floatval($item['nutrition_saturated_fat'] ?? 999);
        $cholesterol = intval($item['nutrition_cholesterol'] ?? 999);
        $weight = intval($item['product_weight'] ?? 0);     // 제품 중량(g)

        // per-serving 기준 라인
        if ($protein >= 20) $lines[] = '고단백';
        if ($sodium > 0 && $sodium <= 650) $lines[] = '저나트륨';
        if ($omega3 >= 1000) $lines[] = '오메가3';           // 1,000mg 이상
        if ($calories >= 550) $lines[] = '550kcal 라인';

        // per-100g 환산 기준 라인 (제품 중량 필요)
        if ($weight > 0) {
            // 저당: 100g당 5g 미만
            if ($sugar >= 0 && $sugar < 999) {
                $sugarPer100g = ($sugar / $weight) * 100;
                if ($sugarPer100g < 5) $lines[] = '저당';
            }
            // 저포화지방: 100g당 1.5g 미만
            if ($saturatedFat >= 0 && $saturatedFat < 999) {
                $satFatPer100g = ($saturatedFat / $weight) * 100;
                if ($satFatPer100g < 1.5) $lines[] = '저포화지방';
            }
            // 저콜레스테롤: 100g당 20mg 미만
            if ($cholesterol >= 0 && $cholesterol < 999) {
                $cholPer100g = ($cholesterol / $weight) * 100;
                if ($cholPer100g < 20) $lines[] = '저콜레스테롤';
            }
        }

        return $lines;
    }

    /**
     * 건강 지표 계산
     * @param array $item addGoods 데이터
     * @return array 건강 지표 배열
     */
    public function calculateHealthIndicators($item)
    {
        return [
            'lowSugar' => ($item['nutrition_sugar'] ?? 999) <= 4,
            'omega3Rich' => ($item['nutrition_omega3'] ?? 0) >= 1000,
            'highProtein' => ($item['nutrition_protein'] ?? 0) >= 20,
            'lowCholesterol' => ($item['nutrition_cholesterol'] ?? 999) <= 50,
        ];
    }

    /**
     * 질환/목표에 따른 추천 영양 라인 조회
     * @param array $conditions 질환 배열 ['당뇨', '고혈압', '고지혈증', '심혈관질환', '신장질환']
     * @param string $goal 건강 목표 '체중관리'/'근육증가'/'혈당관리'/'심혈관건강'
     * @return array 추천 라인 배열 [['line' => 'lowSugar', 'lineKr' => '저당', 'priority' => 1, 'reason' => '혈당 관리'], ...]
     */
    public function getRecommendedLinesByCondition($conditions, $goal = null)
    {
        // camelCase → kebab-case 매핑
        $keyMap = [
            'lowSugar' => 'low-sugar', 'lowSodium' => 'low-sodium', 'omega3' => 'omega3',
            'lowSaturatedFat' => 'low-saturated-fat', 'lowCholesterol' => 'low-cholesterol',
            'highProtein' => 'high-protein', 'cal550' => 'cal-550',
        ];

        // lineDb (generateNutritionReport와 동일)
        $lineDb = [
            'high-protein'      => ['label' => '고단백 라인', 'color' => '#EF4444', 'benefits' => ['근육 보호', '포만감 유지', '기초대사량 향상']],
            'low-sodium'        => ['label' => '저나트륨 라인', 'color' => '#3B82F6', 'benefits' => ['혈압 관리', '부종 감소', '신장 보호']],
            'omega3'            => ['label' => 'Omega-3 풍부', 'color' => '#06B6D4', 'benefits' => ['혈관 건강', '항염 효과', '두뇌 기능']],
            'cal-550'           => ['label' => '550kcal 라인', 'color' => '#F97316', 'benefits' => ['에너지 공급', '근육 성장 지원', '활동량 보충']],
            'low-sugar'         => ['label' => '저당 라인', 'color' => '#F59E0B', 'benefits' => ['혈당 안정', '인슐린 감수성', '에너지 안정']],
            'low-saturated-fat' => ['label' => '저포화지방 라인', 'color' => '#EC4899', 'benefits' => ['혈관 건강', '심장 보호', 'LDL 감소']],
            'low-cholesterol'   => ['label' => '저콜레스테롤 라인', 'color' => '#14B8A6', 'benefits' => ['혈관 건강', 'LDL 감소', '심장 보호']],
        ];

        $recommendations = [];

        // 질환별 추천
        if (in_array('당뇨', $conditions)) {
            $recommendations[] = ['line' => 'lowSugar', 'priority' => 1, 'reason' => '혈당 관리'];
        }

        if (in_array('신장질환', $conditions)) {
            $recommendations[] = ['line' => 'lowSodium', 'priority' => 1, 'reason' => '신장 부담 감소'];
        }

        // 건강 목표별 추천
        if ($goal === '체중관리') {
            $recommendations[] = ['line' => 'highProtein', 'priority' => 1, 'reason' => '포만감 증가'];
            $recommendations[] = ['line' => 'lowSugar', 'priority' => 2, 'reason' => '당류 제한'];
            $recommendations[] = ['line' => 'lowSaturatedFat', 'priority' => 3, 'reason' => '심혈관 건강'];
        }

        if ($goal === '근육증가') {
            $recommendations[] = ['line' => 'highProtein', 'priority' => 1, 'reason' => '근육 합성'];
            $recommendations[] = ['line' => 'cal550', 'priority' => 2, 'reason' => '충분한 칼로리'];
            $recommendations[] = ['line' => 'omega3', 'priority' => 3, 'reason' => '운동 후 회복'];
        }

        if ($goal === '체중증가') {
            $recommendations[] = ['line' => 'cal550', 'priority' => 1, 'reason' => '충분한 칼로리'];
            $recommendations[] = ['line' => 'highProtein', 'priority' => 2, 'reason' => '근육량 증가'];
            $recommendations[] = ['line' => 'lowSaturatedFat', 'priority' => 3, 'reason' => '양질의 지방'];
        }

        if ($goal === '임신수유부') {
            $recommendations[] = ['line' => 'omega3', 'priority' => 1, 'reason' => '두뇌 발달'];
            $recommendations[] = ['line' => 'highProtein', 'priority' => 2, 'reason' => '태아 발달'];
            $recommendations[] = ['line' => 'cal550', 'priority' => 3, 'reason' => '충분한 칼로리'];
        }

        if ($goal === '성장관리') {
            $recommendations[] = ['line' => 'cal550', 'priority' => 1, 'reason' => '에너지 공급'];
            $recommendations[] = ['line' => 'highProtein', 'priority' => 2, 'reason' => '성장 지원'];
            $recommendations[] = ['line' => 'omega3', 'priority' => 3, 'reason' => '두뇌 발달'];
        }

        if ($goal === '노화관리') {
            $recommendations[] = ['line' => 'omega3', 'priority' => 1, 'reason' => '인지 기능 유지'];
            $recommendations[] = ['line' => 'highProtein', 'priority' => 2, 'reason' => '근감소증 예방'];
            $recommendations[] = ['line' => 'lowSugar', 'priority' => 3, 'reason' => '대사 건강'];
        }

        // 우선순위순 정렬 및 중복 제거
        usort($recommendations, function($a, $b) {
            return $a['priority'] - $b['priority'];
        });

        // 동일 line 중복 제거 후 통합 형식으로 변환
        $result = [];
        $seenLines = [];
        foreach ($recommendations as $rec) {
            if (in_array($rec['line'], $seenLines)) continue;
            $seenLines[] = $rec['line'];

            $lineId = $keyMap[$rec['line']] ?? $rec['line'];
            $info = $lineDb[$lineId] ?? null;
            if (!$info) continue;

            $result[] = [
                'id' => $lineId,
                'label' => $info['label'],
                'color' => $info['color'],
                'reason' => $rec['reason'],
                'benefits' => $info['benefits'],
            ];
        }

        return array_slice($result, 0, 3);
    }

    /**
     * 태그 파싱 (콤마 구분 문자열 → 배열)
     * @param string $tags 콤마 구분 태그 문자열
     * @return array 태그 배열
     */
    public function parseTags($tags)
    {
        if (empty($tags)) {
            return [];
        }
        return array_map('trim', explode(',', $tags));
    }

    /**
     * 질환 조건 ID 기반 추천 질환식 목록 반환
     * @param array $rawConditions 조건 ID 배열 (예: ['diabetes', 'kidney-pre-dialysis'])
     * @return array 질환식 정보 배열 (비질환 조건만 있으면 빈 배열)
     */
    public function getRecommendedDiseaseDiets($rawConditions)
    {
        $result = [];
        foreach ($rawConditions as $condId) {
            if (isset(self::$diseaseDietMap[$condId])) {
                $result[] = self::$diseaseDietMap[$condId];
            }
        }
        return $result;
    }

    /**
     * 카테고리 파싱 (콤마 구분 문자열 → 배열)
     * @param string $categories 콤마 구분 카테고리 문자열
     * @return array 카테고리 배열
     */
    public function parseCategories($categories)
    {
        if (empty($categories)) {
            return [];
        }
        return array_map('trim', explode(',', $categories));
    }

    /**
     * JSON 배열 파싱 (TEXT 필드에 저장된 JSON 배열 → PHP 배열)
     * @param string $json JSON 문자열
     * @return array 파싱된 배열
     */
    public function parseJsonArray($json)
    {
        if (empty($json)) {
            return [];
        }
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * 이미지 URL 생성 (Godo Storage 사용)
     * @param array $item addGoods 데이터
     * @return string 이미지 URL (CDN 경로 포함)
     */
    public function buildImageUrl($item)
    {
        // imageStorage 기본값 설정
        $imageStorage = $item['imageStorage'] ?? 'local';

        // 1. imagePath + subImageNm 조합 (우선 사용)
        if (!empty($item['imagePath']) && !empty($item['subImageNm'])) {
            $filePath = $item['imagePath'] . $item['subImageNm'];

            $storage = \Bundle\Component\Storage\Storage::disk(
                \Bundle\Component\Storage\Storage::PATH_CODE_ADD_GOODS,
                $imageStorage
            );
            return $storage->getHttpPath($filePath);
        }

        // 2. imagePath + imageNm 조합 (subImageNm이 없을 경우)
        if (!empty($item['imagePath']) && !empty($item['imageNm'])) {
            $filePath = $item['imagePath'] . $item['imageNm'];

            // Godo Storage를 사용해 CDN URL 생성
            $storage = \Bundle\Component\Storage\Storage::disk(
                \Bundle\Component\Storage\Storage::PATH_CODE_ADD_GOODS,
                $imageStorage
            );
            return $storage->getHttpPath($filePath);
        }

        // 3. addGoodsNo로 경로 구성 (imagePath가 없는 경우)
        if (!empty($item['addGoodsNo']) && !empty($item['imageNm'])) {
            $filePath = $item['addGoodsNo'] . '/' . $item['imageNm'];

            $storage = \Bundle\Component\Storage\Storage::disk(
                \Bundle\Component\Storage\Storage::PATH_CODE_ADD_GOODS,
                $imageStorage
            );
            return $storage->getHttpPath($filePath);
        }

        // 4. 기본 이미지
        return '/data/goods/default_goods.png';
    }

    /**
     * 상세 이미지 URL 생성 (goodsDescription 첫 번째 img src 추출)
     * 골라담기 layer_option.html의 goodsDescriptionImage 패턴과 동일
     * @param array $item addGoods 데이터
     * @return string|null 이미지 URL (없으면 null)
     */
    public function buildDetailImageUrl($item)
    {
        $desc = $item['goodsDescription'] ?? '';
        if (!empty($desc)) {
            // Godo 에디터 저장 시 addslashes + HTML entity 인코딩이 조합되어 있으므로
            // stripslashes + html_entity_decode로 정규화 후 단순 regex 매칭
            $normalized = html_entity_decode(stripslashes($desc), ENT_QUOTES, 'UTF-8');
            if (preg_match('/<img[^>]*src\s*=\s*["\']([^"\']+)["\'][^>]*>/i', $normalized, $matches)) {
                return $matches[1];
            }
        }

        // 폴백: imageNm 기반 URL
        $imageStorage = $item['imageStorage'] ?? 'local';
        if (!empty($item['imagePath']) && !empty($item['imageNm'])) {
            $filePath = $item['imagePath'] . $item['imageNm'];
            $storage = \Bundle\Component\Storage\Storage::disk(
                \Bundle\Component\Storage\Storage::PATH_CODE_ADD_GOODS,
                $imageStorage
            );
            return $storage->getHttpPath($filePath);
        }

        return null;
    }

    /**
     * 퀴즈 응답 조회
     * @param int $responseSno 응답 번호
     * @return array|null 응답 데이터
     */
    protected function getQuizResponse($responseSno)
    {
        $arrBind = [];
        $this->db->bind_param_push($arrBind, 'i', $responseSno);

        $strSQL = "SELECT * FROM ms_diet_quiz_responses WHERE sno = ?";
        $result = $this->db->query_fetch($strSQL, $arrBind, false);

        if ($result) {
            $condRaw = $result['conditions'] ?? '[]';
            $result['conditions'] = json_decode($condRaw, true) ?? json_decode(stripslashes($condRaw), true) ?? [];

            $linesRaw = $result['recommended_lines'] ?? '[]';
            $result['recommended_lines'] = json_decode($linesRaw, true) ?? json_decode(stripslashes($linesRaw), true) ?? [];
        }

        return $result;
    }

    /**
     * responseSno에서 조건 정보 추출
     * @param int $responseSno 응답 번호
     * @return array 조건 배열
     */
    public function getConditionsFromResponse($responseSno)
    {
        $response = $this->getQuizResponse($responseSno);
        if (!$response) {
            return [];
        }

        $rawLines = $response['recommended_lines'] ?? [];
        $recommendedLines = [];
        $recommendedDiseaseDiets = [];

        // 새 중첩 구조 {lines: [...], diseaseDiets: [...]} vs 레거시 flat 배열
        if (isset($rawLines['lines'])) {
            $recommendedLines = $rawLines['lines'];
            $recommendedDiseaseDiets = $rawLines['diseaseDiets'] ?? [];
        } else {
            $recommendedLines = $rawLines;
        }

        // 폴백: DB에 추천 라인이 비어있으면 통합 알고리즘으로 재생성
        $rawConditions = $response['conditions'] ?? [];
        if (empty($recommendedLines) && !empty($rawConditions)) {
            $reportData = $this->generateNutritionReport([
                'gender' => $response['gender'] ?? 'male',
                'birthYear' => $response['birthYear'] ?? 1990,
                'height' => $response['height'] ?? 170,
                'weight' => $response['weight'] ?? 65,
                'conditions' => $rawConditions,
            ]);
            $recommendedLines = $reportData['recommendedLines'] ?? [];
            $recommendedDiseaseDiets = $reportData['recommendedDiseaseDiets'] ?? [];
        }

        // 질환식 폴백: 라인은 있는데 질환식이 비어있으면 조건에서 재생성
        if (empty($recommendedDiseaseDiets) && !empty($rawConditions)) {
            $recommendedDiseaseDiets = $this->getRecommendedDiseaseDiets($rawConditions);
        }

        return [
            'conditions' => $rawConditions,
            'recommendedLines' => $recommendedLines,
            'recommendedDiseaseDiets' => $recommendedDiseaseDiets,
            'userData' => [
                'age' => $response['age'] ?? null,
                'gender' => $response['gender'] ?? null,
                'goal' => $response['goal'] ?? null,
                'birthYear' => isset($response['birthYear']) ? (int)$response['birthYear'] : null,
                'height' => isset($response['height']) ? (float)$response['height'] : null,
                'weight' => isset($response['weight']) ? (float)$response['weight'] : null,
            ],
        ];
    }

    /**
     * 상품 옵션 정보 조회 (10식/20식/40식)
     * @param int $goodsNo 상품번호
     * @return array 옵션 배열
     */
    public function getGoodsOptions($goodsNo)
    {
        $arrBind = [];
        $this->db->bind_param_push($arrBind, 'i', $goodsNo);

        $strSQL = "SELECT sno, optionValue1, optionPrice, stockCnt
                   FROM " . DB_GOODS_OPTION . "
                   WHERE goodsNo = ? AND optionViewFl = 'y' AND optionSellFl = 'y'
                   ORDER BY sno ASC";

        $result = $this->db->query_fetch($strSQL, $arrBind);
        return gd_htmlspecialchars_stripslashes($result);
    }

    /**
     * 영양 분석 결과 데이터 생성
     * 현재는 규칙 기반으로 생성하지만, 추후 AI API로 대체 가능하도록 설계
     *
     * @param array $data ['gender', 'birthYear', 'height', 'weight', 'conditions']
     * @return array 리포트 데이터 (bmi, coachingMessages, statusCards, strategies, recommendedLines, conditionLabels, brandMessage)
     */
    public function generateNutritionReport($data)
    {
        $gender = $data['gender'] ?? 'male';
        $birthYear = intval($data['birthYear'] ?? 1990);
        $height = floatval($data['height'] ?? 170);
        $weight = floatval($data['weight'] ?? 65);
        $rawConditions = $data['conditions'] ?? [];
        if (is_string($rawConditions)) {
            $rawConditions = json_decode($rawConditions, true) ?: [];
        }

        $age = intval(date('Y')) - $birthYear;
        $heightM = $height / 100;
        $bmiValue = round($weight / ($heightM * $heightM), 1);

        // BMI 상태
        if ($bmiValue < 18.5) { $bmiStatus = '저체중'; $bmiStatusKey = 'underweight'; }
        elseif ($bmiValue < 23) { $bmiStatus = '정상'; $bmiStatusKey = 'normal'; }
        elseif ($bmiValue < 25) { $bmiStatus = '과체중'; $bmiStatusKey = 'overweight'; }
        else { $bmiStatus = '비만'; $bmiStatusKey = 'obese'; }

        // Mifflin-St Jeor BMR
        if ($gender === 'male') {
            $bmr = (10 * $weight) + (6.25 * $height) - (5 * $age) + 5;
        } else {
            $bmr = (10 * $weight) + (6.25 * $height) - (5 * $age) - 161;
        }

        // PAL(신체활동계수): 질환자 → 저활동(1.2), 비질환자 → 중간활동(1.55)
        $diseaseIds = ['diabetes', 'gestational-diabetes', 'kidney-pre-dialysis', 'kidney-dialysis', 'cancer', 'breast-cancer', 'cholesterol'];
        $hasDisease = !empty(array_intersect($rawConditions, $diseaseIds));
        $pal = $hasDisease ? 1.2 : 1.375;
        $dailyCalories = round($bmr * $pal / 100) * 100; // 100단위 반올림

        // 목표 기반 칼로리 조정: 비만 감량 -500, 일반 감량 -200, 증량 +200
        if (in_array('weight-loss', $rawConditions)) {
            $dailyCalories -= ($bmiValue >= 25) ? 500 : 200;
        } elseif (in_array('weight-gain', $rawConditions) || in_array('muscle-gain', $rawConditions)) {
            $dailyCalories += 200;
        }

        $dailyCalories = max(1200, min(3000, $dailyCalories)); // 1200~3000 제한
        $proteinG = round($weight * 1.4);

        // ── 코칭 메시지 ──
        $gLabel = $gender === 'male' ? '남성' : '여성';
        $coachingMessages = [];

        // 건강 목표/질환 라벨 조립 (기본 소개 메시지용)
        $goalLabelMap = [
            'weight-loss' => '체중 감량', 'general' => '균형잡힌 식단', 'weight-gain' => '체중 증량',
            'muscle-gain' => '근육 증량', 'pregnancy' => '임신·수유부 관리',
            'growth' => '성장 관리', 'aging' => '노화 관리',
            'diabetes' => '당뇨 관리', 'gestational-diabetes' => '임신성 당뇨 관리',
            'kidney-pre-dialysis' => '신장 질환(투석 전) 관리', 'kidney-dialysis' => '신장 질환(투석 중) 관리',
            'cancer' => '암 투병 영양 관리', 'breast-cancer' => '유방암 케어',
            'cholesterol' => '고콜레스테롤 관리',
        ];
        $goalLabels = [];
        foreach ($rawConditions as $c) {
            if (isset($goalLabelMap[$c])) $goalLabels[] = $goalLabelMap[$c];
        }
        $goalText = count($goalLabels) > 0 ? implode(', ', $goalLabels) : '균형잡힌 식단';

        // 한국어 을/를 조사 판별
        $lastChar = mb_substr($goalText, -1, 1, 'UTF-8');
        $charCode = mb_ord($lastChar, 'UTF-8');
        $particle = '를';
        if ($charCode >= 0xAC00 && $charCode <= 0xD7A3) {
            $particle = (($charCode - 0xAC00) % 28 > 0) ? '을' : '를';
        }

        // ① 기본 소개 + 건강 목표 (자연어 문장)
        $coachingMessages[] = "{$gLabel}, 만 {$age}세, {$height}cm / {$weight}kg 기준으로 분석한 결과입니다. 현재 BMI는 {$bmiValue}로 <strong>{$bmiStatus}</strong> 범위에 해당하며, <strong>{$goalText}</strong>{$particle} 목표로 하고 있습니다.";

        // ② 연령대별 조언 — [TEMPORARILY HIDDEN]
        // if ($age < 30) $coachingMessages[] = '20대는 활동량이 많아 에너지 소비가 높은 시기입니다. 이 시기에 올바른 식습관을 형성하면 30대 이후 건강 관리에 큰 도움이 됩니다.';
        // elseif ($age < 40) $coachingMessages[] = '30대부터는 기초대사량이 매년 1~2%씩 감소합니다. 근손실 방지와 대사 유지를 위한 단백질 섭취가 중요합니다.';
        // elseif ($age < 50) $coachingMessages[] = '40대는 심혈관 건강에 주의가 필요한 시기입니다. 나트륨과 포화지방 조절, 오메가3 섭취가 핵심입니다.';
        // elseif ($age < 60) $coachingMessages[] = '50대는 골밀도 감소와 근감소증 예방이 중요합니다. 충분한 단백질과 칼슘, 비타민D 섭취를 권장합니다.';
        // else $coachingMessages[] = '60대 이상은 소화 기능과 영양 흡수율이 저하되는 시기입니다. 소화가 잘되는 양질의 단백질과 충분한 수분 섭취가 필요합니다.';

        // ③ 성별 × 연령대 조언 — [TEMPORARILY HIDDEN]
        // if ($gender === 'female' && $age < 50) $coachingMessages[] = '여성은 월경주기에 따른 철분 손실에 주의가 필요합니다. 철분이 풍부한 식품과 함께 비타민C를 섭취하면 흡수율이 높아집니다.';
        // elseif ($gender === 'female' && $age >= 50) $coachingMessages[] = '폐경 이후에는 에스트로겐 감소로 골다공증 위험이 높아집니다. 칼슘과 비타민D, 단백질 섭취를 강화해야 합니다.';
        // elseif ($gender === 'male' && $age >= 40) $coachingMessages[] = '40대 이후 남성은 복부 지방 축적과 전립선 건강에 주의가 필요합니다. 항산화 영양소와 양질의 단백질 섭취를 늘리세요.';
        // else $coachingMessages[] = '남성은 근육량 유지를 위해 체중 kg당 1.2~1.6g의 단백질 섭취를 권장합니다.';

        // ④ 건강 목표별 맞춤 코칭
        $goalCoachingMap = [
            'weight-loss' => "고객님의 목표인 '체중 감량'을 위해 충분한 단백질 섭취로 근손실을 방지하면서, 지방과 당류 섭취를 적정 수준으로 조절하는 것이 중요합니다. 또한 운동 및 식이요법을 병행하여 평소 섭취량보다 하루 약 500kcal를 줄이면, 주당 약 0.5kg, 한달에 약 2kg의 체중 감량을 기대할 수 있습니다. 이러한 점진적인 체중 감량 방법이 권장됩니다.<br><br>고객님의 키, 체중, 목표 등을 고려 시, 하루 약 <strong>{$dailyCalories}kcal</strong>, 단백질 약 <strong>{$proteinG}g</strong> 권장드립니다. 이를 바탕으로 아래와 같이 메디쏠라 식단 안내드립니다.",
            'weight-gain' => "고객님의 목표인 체중 증량을 위해서는 충분한 열량을 섭취하고, 단백질을 체중 1kg당 약 1.2–2.0g 수준으로 보충하여 근육 증가를 돕는 것이 중요합니다. 또한 견과류, 올리브유 등 건강한 지방원을 활용하여 에너지 섭취를 보충하는 것이 도움이 됩니다. 평소 섭취량보다 하루 약 250–500 kcal를 추가로 섭취하고 운동을 병행하여 주당 약 0.2–0.5 kg의 점진적인 체중 증가를 목표로 하는 것이 권장됩니다.",
            'muscle-gain' => "고객님의 목표인 '근육 증량'을 위해 체중 kg당 1.3~2.0g의 단백질을 섭취하고, 충분한 칼로리를 확보하는 것이 중요합니다.",
            'general' => "고객님의 목표인 '균형잡힌 식단'을 위해 탄수화물, 단백질, 지방의 균형 잡힌 섭취와 충분한 식이섬유, 오메가3를 챙기는 것이 중요합니다.",
            'pregnancy' => "고객님의 목표인 '임신·수유부 관리'를 위해 양질의 단백질과 DHA/EPA, 엽산, 철분을 충분히 섭취하는 것이 중요합니다.",
            'growth' => "고객님의 목표인 '성장 관리'를 위해 충분한 칼로리와 양질의 단백질, 칼슘, 오메가3를 균형 있게 섭취하는 것이 중요합니다.",
            'aging' => "고객님의 목표인 '노화 관리'를 위해 근감소증 예방을 위한 단백질과 혈관 건강을 위한 오메가3, 소화 건강을 위한 식이섬유를 충분히 섭취하는 것이 중요합니다.",
        ];

        // ⑤ 질환별 맞춤 코칭
        $diseaseCoachingMap = [
            'diabetes' => "당뇨는 안정적인 혈당관리가 필요합니다. 아래와 같은 식사관리 목표가 필요합니다.<br>1. 안정적인 혈당관리<br>2. 단순당 제한<br><br>안정적인 혈당관리를 위해 식사는 일정한 시간에 맞춰 규칙적으로 섭취하고, 간식은 식후 2~3시간 간격을 두고 섭취하는 것이 중요합니다. 또한, 사탕, 초콜릿, 가당음료와 같은 단순당 함량이 높은 식품은 제한하는 것이 좋습니다.<br>이를 기반으로 개인의 혈당 조절 상태에 맞는 하루 권장 열량과 탄수화물 섭취량, 단백질 필요량을 설정하고, 혈당 변동을 최소화하는 식단 구성이 필요합니다.",
            'gestational-diabetes' => "임신성 당뇨는 태아와 산모 모두를 위해 안정적인 혈당 유지가 중요합니다. 아래와 같은 식사관리 목표가 필요합니다.<br>1. 식후 혈당 상승 최소화<br>2. 균형 잡힌 탄수화물 분배<br><br>임신 중 안정적인 혈당관리를 위해 하루 3끼 식사와 2~3회의 간식을 소량씩 나누어 섭취하는 것이 중요합니다. 특히 아침에는 혈당 상승이 크므로 아침 식사 시 탄수화물 섭취량을 조절해 주세요. 또한, 설탕, 주스 등 혈당을 빠르게 올리는 식품은 제한하고, 통곡물, 채소 등 식이섬유가 풍부한 식품을 선택하는 것이 좋습니다.<br>이러한 기준을 바탕으로 개인의 상태에 맞는 하루 권장 열량과 탄수화물 분배, 단백질 필요량을 설정하고 이에 맞는 식단 구성이 필요합니다.",
            'kidney-pre-dialysis' => "신장질환(투석 전)은 신장 기능 저하를 늦추는 것이 중요합니다. 아래와 같은 식사관리 목표가 필요합니다.<br>1. 단백질 적정 섭취<br>2. 나트륨 제한<br><br>신장 부담을 줄이기 위해 단백질 섭취는 과도하지 않게 조절하며, 가공식품이나 국물류 섭취를 줄여 나트륨 섭취를 제한하는 것이 중요합니다. 또한, 개인 상태에 따라 칼륨과 인 섭취 조절이 필요할 수 있으므로 식품 선택에 주의가 필요합니다.<br>이에 따라 신장 기능 단계에 맞는 하루 단백질 제한량과 열량 기준을 설정하고, 전해질 균형을 고려한 식단 구성이 필요합니다.",
            'kidney-dialysis' => "투석 중에는 영양 손실이 증가하므로 충분한 영양 공급이 중요합니다. 아래와 같은 식사관리 목표가 필요합니다.<br>1. 충분한 단백질 섭취<br>2. 수분 및 전해질 조절<br><br>투석으로 손실되는 단백질을 보충하기 위해 양질의 단백질(육류, 생선, 계란 등)을 충분히 섭취하는 것이 중요합니다. 또한, 체액 과다를 방지하기 위해 수분 섭취를 조절하고, 칼륨과 인이 높은 식품은 제한이 필요합니다.<br>이를 기반으로 투석 환자에 맞는 증가된 단백질 요구량과 열량 기준을 반영하여 식단을 구성하는 것이 필요합니다.",
            'cancer' => "암 환자는 질환 자체와 항암치료로 인해 식욕 저하, 염증 반응 증가, 대사 변화 등이 발생하여 영양불량과 근육 손실이 쉽게 나타날 수 있습니다. 이에 따라 다음과 같은 식사관리 목표가 필요합니다.<br>1. 체중 및 근육량 유지<br>2. 충분한 에너지 및 단백질 섭취<br><br>영양불량을 예방하기 위해 식사는 가능한 범위 내에서 자주 나누어 섭취하고, 특히 단백질이 풍부한 식품을 충분히 섭취하는 것이 중요합니다. 이는 근육 감소를 예방하고 치료 과정에서의 회복과 예후 개선에 도움을 줄 수 있습니다.<br>이를 기반으로 영양불량 예방과 근육량 유지를 위한 충분한 열량과 단백질 요구량을 설정하고, 개인의 섭취 가능 상태를 반영한 식단 구성이 필요합니다.",
            'breast-cancer' => "유방암 케어를 위해 항염·항산화 식품과 양질의 단백질을 충분히 섭취하고, 면역력 강화와 호르몬 균형을 위한 오메가3를 챙기세요.",
            'cholesterol' => "고콜레스테롤 관리를 위해서는 단순히 지방을 줄이기보다는 어떤 지방을 선택하는지가 중요합니다. 아래와 같은 식사관리 목표가 필요합니다.<br>1. 포화지방 및 트랜스지방 제한<br>2. 식이섬유 섭취 증가<br><br>포화지방(버터, 지방이 많은 육류, 가공식품 등)과 트랜스지방 섭취를 줄이는 것은 LDL 콜레스테롤 감소에 도움이 됩니다. 반면, 채소, 과일, 통곡물, 콩류에 풍부한 식이섬유(특히 수용성 식이섬유)는 콜레스테롤 흡수를 감소시켜 혈중 지질 개선에 기여합니다. 또한, 동물성 지방 대신 식물성 기름, 견과류, 생선과 같은 불포화지방산을 선택하는 것이 심혈관 건강에 도움이 됩니다.<br>이를 기반으로 포화지방 비율을 낮추고 식이섬유 섭취를 강화한 하루 권장 열량과 단백질 기준을 설정하며, 혈중 지질 개선을 위한 식단 구성이 필요합니다.",
        ];

        // 건강 목표 코칭 (첫 번째 건강 목표만)
        foreach ($rawConditions as $c) {
            if (isset($goalCoachingMap[$c])) {
                $coachingMessages[] = $goalCoachingMap[$c];
                break;
            }
        }

        // 질환 코칭 (모든 질환에 대해)
        foreach ($rawConditions as $c) {
            if (isset($diseaseCoachingMap[$c])) {
                $coachingMessages[] = $diseaseCoachingMap[$c];
            }
        }

        // ── 상태 카드 ──
        $statusCards = [];
        if ($bmiStatusKey === 'overweight' || $bmiStatusKey === 'obese') {
            $statusCards[] = ['icon' => 'activity', 'title' => '체중 상태 관리', 'description' => "현재 BMI가 {$bmiStatus} 범위입니다. 칼로리 조절과 규칙적인 활동이 필요합니다.", 'color' => '#F59E0B'];
            $statusCards[] = ['icon' => 'shield', 'title' => '근육 보호 필요', 'description' => '체중 감량 시 근손실을 최소화하기 위해 단백질 섭취를 충분히 하세요.', 'color' => '#3B82F6'];
        } elseif ($bmiStatusKey === 'underweight') {
            $statusCards[] = ['icon' => 'trendingUp', 'title' => '체중 증가 필요', 'description' => '건강한 체중 증가를 위해 양질의 칼로리와 단백질 섭취를 늘리세요.', 'color' => '#3B82F6'];
            $statusCards[] = ['icon' => 'flame', 'title' => '칼로리 섭취 증가', 'description' => '하루 필요 칼로리 이상을 규칙적으로 섭취하는 것이 중요합니다.', 'color' => '#EF4444'];
        } else {
            $statusCards[] = ['icon' => 'heart', 'title' => '건강 체중 유지', 'description' => '현재 정상 체중 범위입니다. 균형 잡힌 식단으로 유지하세요.', 'color' => '#10B981'];
            $statusCards[] = ['icon' => 'shield', 'title' => '균형 잡힌 영양', 'description' => '다양한 영양소를 골고루 섭취하여 건강을 유지하세요.', 'color' => '#3B82F6'];
        }

        // ── 전략 풀 (Strategy Pool) ──
        // 각 전략은 하나의 영양 라인(linkedLine)과 1:1로 연결됨
        // 전략 = "왜/무엇을 해야 하는가", 라인 = "어떤 제품으로 해결하는가"
        $strategyPool = [
            'protein-up' => [
                'icon' => 'trendingUp', 'label' => '단백질 섭취 강화',
                'target' => "최소 {$proteinG}g/일",
                'color' => '#EF4444', 'bgColor' => 'rgba(239,68,68,0.1)',
                'linkedLine' => 'high-protein',
            ],
            'sugar-down' => [
                'icon' => 'trendingDown', 'label' => '당류 섭취 조절',
                'target' => '25g 이하/일',
                'color' => '#F59E0B', 'bgColor' => 'rgba(245,158,11,0.1)',
                'linkedLine' => 'low-sugar',
            ],
            'omega3-up' => [
                'icon' => 'fish', 'label' => '오메가3 섭취 증가',
                'target' => '500mg 이상/일',
                'color' => '#06B6D4', 'bgColor' => 'rgba(6,182,212,0.1)',
                'linkedLine' => 'omega3',
            ],
            'sodium-down' => [
                'icon' => 'shield', 'label' => '나트륨 섭취 제한',
                'target' => '1500mg 이하/일',
                'color' => '#3B82F6', 'bgColor' => 'rgba(59,130,246,0.1)',
                'linkedLine' => 'low-sodium',
            ],
            'calorie-up' => [
                'icon' => 'flame', 'label' => '충분한 칼로리 확보',
                'target' => '1식 550kcal 라인 권장',
                'color' => '#F97316', 'bgColor' => 'rgba(249,115,22,0.1)',
                'linkedLine' => 'cal-550',
            ],
            'cholesterol-down' => [
                'icon' => 'heart', 'label' => '콜레스테롤 섭취 제한',
                'target' => '300mg 이하/일',
                'color' => '#14B8A6', 'bgColor' => 'rgba(20,184,166,0.1)',
                'linkedLine' => 'low-cholesterol',
            ],
            'saturated-fat-down' => [
                'icon' => 'shield', 'label' => '포화지방 섭취 제한',
                'target' => '총 칼로리의 7% 이하',
                'color' => '#EC4899', 'bgColor' => 'rgba(236,72,153,0.1)',
                'linkedLine' => 'low-saturated-fat',
            ],
        ];

        // ── 영양 라인 DB ──
        $lineDb = [
            'high-protein'      => ['label' => '고단백 라인', 'color' => '#EF4444', 'reason' => '양질의 단백질로 근손실을 방지하고 체력을 유지합니다.', 'benefits' => ['근육 보호', '포만감 유지', '기초대사량 향상']],
            'low-sodium'        => ['label' => '저나트륨 라인', 'color' => '#3B82F6', 'reason' => '저염식으로 혈압과 신장 건강을 보호합니다.', 'benefits' => ['혈압 관리', '부종 감소', '신장 보호']],
            'omega3'            => ['label' => 'Omega-3 풍부', 'color' => '#06B6D4', 'reason' => '오메가3 지방산으로 혈관 건강과 염증 조절을 돕습니다.', 'benefits' => ['혈관 건강', '항염 효과', '두뇌 기능']],
            'cal-550'           => ['label' => '550kcal 라인', 'color' => '#F97316', 'reason' => '충분한 칼로리 공급으로 활동량이 많거나 성장기에 필요한 에너지를 확보합니다.', 'benefits' => ['에너지 공급', '근육 성장 지원', '활동량 보충']],
            'low-sugar'         => ['label' => '저당 라인', 'color' => '#F59E0B', 'reason' => '당류를 최소화하여 혈당 변동을 줄입니다.', 'benefits' => ['혈당 안정', '인슐린 감수성', '에너지 안정']],
            'low-saturated-fat' => ['label' => '저포화지방 라인', 'color' => '#EC4899', 'reason' => '포화지방을 줄여 심혈관 건강을 보호합니다.', 'benefits' => ['혈관 건강', '심장 보호', 'LDL 감소']],
            'low-cholesterol'   => ['label' => '저콜레스테롤 라인', 'color' => '#14B8A6', 'reason' => '콜레스테롤 섭취를 제한하여 혈중 수치를 관리합니다.', 'benefits' => ['혈관 건강', 'LDL 감소', '심장 보호']],
        ];

        // ── 조건별 전략 + 추천 라인 통합 매핑 ──
        // 전략 선택 → 전략의 linkedLine이 자동으로 추천 라인이 됨
        // reason: 해당 조건에서 이 전략/라인이 필요한 이유
        $condStrategyMap = [
            'weight-loss' => [
                ['id' => 'protein-up', 'reason' => '체지방 감량 중 근손실을 최소화하기 위해 가장 우선적으로 추천됩니다.'],
                ['id' => 'sugar-down', 'reason' => '당류 섭취를 줄여 체중 감량과 대사 건강을 돕습니다.'],
                ['id' => 'saturated-fat-down', 'reason' => '포화지방 섭취를 줄여 심혈관 건강을 보호합니다.'],
            ],
            'muscle-gain' => [
                ['id' => 'protein-up', 'reason' => '근육 합성에 필요한 양질의 단백질을 충분히 공급합니다.'],
                ['id' => 'calorie-up', 'reason' => '근육 성장에 필요한 충분한 칼로리를 확보합니다.'],
                ['id' => 'omega3-up', 'reason' => '항염 효과로 운동 후 회복을 돕고 근육 성장을 지원합니다.'],
                ['id' => 'saturated-fat-down', 'reason' => '근육 성장 중 심혈관 건강 유지를 위해 포화지방을 제한합니다.'],
            ],
            'diabetes' => [
                ['id' => 'sugar-down', 'reason' => '혈당 지수가 낮은 재료로 혈당 관리에 도움이 됩니다.'],
                ['id' => 'sodium-down', 'reason' => '당뇨 합병증 예방을 위해 나트륨 섭취를 조절합니다.'],
            ],
            'gestational-diabetes' => [
                ['id' => 'sugar-down', 'reason' => '임신 중 혈당을 안정적으로 유지하기 위해 당류를 제한합니다.'],
                ['id' => 'protein-up', 'reason' => '태아 발달과 모체 건강을 위해 양질의 단백질을 공급합니다.'],
            ],
            'kidney-pre-dialysis' => [
                ['id' => 'sodium-down', 'reason' => '신장 부담을 줄이기 위해 나트륨을 제한합니다.'],
                ['id' => 'saturated-fat-down', 'reason' => '신장 기능 보존을 위해 포화지방 섭취를 조절합니다.'],
            ],
            'kidney-dialysis' => [
                ['id' => 'protein-up', 'reason' => '투석 중 손실되는 단백질을 보충합니다.'],
                ['id' => 'sodium-down', 'reason' => '투석 환자의 체액 균형을 위해 나트륨을 제한합니다.'],
            ],
            'cancer' => [
                ['id' => 'protein-up', 'reason' => '면역력 강화와 체력 회복을 위해 양질의 단백질을 공급합니다.'],
                ['id' => 'calorie-up', 'reason' => '치료 중 체력 유지와 체중 감소 방지를 위해 충분한 칼로리를 확보합니다.'],
                ['id' => 'omega3-up', 'reason' => '항염 효과로 면역 기능 지원과 회복을 돕습니다.'],
            ],
            'breast-cancer' => [
                ['id' => 'protein-up', 'reason' => '면역력 강화와 체력 유지를 위해 양질의 단백질을 공급합니다.'],
                ['id' => 'omega3-up', 'reason' => '항염 효과와 호르몬 균형을 위해 오메가3를 보충합니다.'],
                ['id' => 'sugar-down', 'reason' => '염증 억제와 면역 기능 유지를 위해 당류 섭취를 제한합니다.'],
            ],
            'cholesterol' => [
                ['id' => 'omega3-up', 'reason' => 'EPA/DHA가 혈중 중성지방을 낮추고 혈관 건강을 개선합니다.'],
                ['id' => 'cholesterol-down', 'reason' => '콜레스테롤 섭취를 줄여 LDL 수치 관리를 돕습니다.'],
                ['id' => 'saturated-fat-down', 'reason' => '포화지방 섭취를 줄여 LDL 콜레스테롤을 관리합니다.'],
            ],
            'general' => [
                ['id' => 'protein-up', 'reason' => '균형 잡힌 영양 섭취의 기본이 되는 단백질을 충분히 공급합니다.'],
                ['id' => 'sugar-down', 'reason' => '당류 섭취를 조절하여 대사 건강을 유지합니다.'],
                ['id' => 'omega3-up', 'reason' => '혈관 건강과 두뇌 기능 유지를 위해 오메가3를 보충합니다.'],
            ],
            'weight-gain' => [
                ['id' => 'calorie-up', 'reason' => '건강한 체중 증가를 위해 충분한 칼로리를 확보합니다.'],
                ['id' => 'protein-up', 'reason' => '근육량 증가와 체력 향상을 위해 양질의 단백질을 공급합니다.'],
                ['id' => 'saturated-fat-down', 'reason' => '건강한 체중 증가를 위해 포화지방 대신 양질의 지방을 선택합니다.'],
            ],
            'pregnancy' => [
                ['id' => 'omega3-up', 'reason' => '태아 두뇌 발달과 모체의 혈관 건강을 위해 DHA/EPA를 보충합니다.'],
                ['id' => 'protein-up', 'reason' => '태아 발달과 모체 건강 유지를 위해 양질의 단백질을 공급합니다.'],
                ['id' => 'calorie-up', 'reason' => '태아 성장과 모체 건강 유지를 위해 충분한 칼로리를 확보합니다.'],
            ],
            'growth' => [
                ['id' => 'calorie-up', 'reason' => '성장기에 필요한 충분한 에너지를 확보합니다.'],
                ['id' => 'protein-up', 'reason' => '뼈와 근육 성장에 필수적인 단백질을 충분히 공급합니다.'],
                ['id' => 'omega3-up', 'reason' => '두뇌 발달과 집중력 향상을 위해 오메가3를 보충합니다.'],
            ],
            'aging' => [
                ['id' => 'omega3-up', 'reason' => '혈관 건강과 인지 기능 유지를 위해 오메가3를 보충합니다.'],
                ['id' => 'protein-up', 'reason' => '근감소증 예방과 체력 유지를 위해 양질의 단백질을 공급합니다.'],
                ['id' => 'sugar-down', 'reason' => '노화에 따른 대사 건강 유지를 위해 당류 섭취를 조절합니다.'],
            ],
        ];

        // ── 전략 + 추천 라인 통합 빌드 ──
        // 하나의 매핑에서 전략과 라인을 동시에 생성 → 항상 동기화 보장
        $strategies = [];
        $recommendedLines = [];
        $addedStrategyIds = [];
        $addedLineIds = [];

        foreach ($rawConditions as $condId) {
            $mappings = $condStrategyMap[$condId] ?? [];
            foreach ($mappings as $m) {
                $stratId = $m['id'];
                if (in_array($stratId, $addedStrategyIds)) continue;
                $addedStrategyIds[] = $stratId;

                $strat = $strategyPool[$stratId] ?? null;
                if (!$strat) continue;

                // 전략 카드 추가
                $strategies[] = [
                    'icon' => $strat['icon'],
                    'label' => $strat['label'],
                    'target' => $strat['target'],
                    'color' => $strat['color'],
                    'bgColor' => $strat['bgColor'],
                ];

                // 연결된 영양 라인 추가
                $lineId = $strat['linkedLine'];
                if (!in_array($lineId, $addedLineIds) && isset($lineDb[$lineId])) {
                    $addedLineIds[] = $lineId;
                    $lineInfo = $lineDb[$lineId];
                    $recommendedLines[] = [
                        'id' => $lineId,
                        'label' => $lineInfo['label'],
                        'color' => $lineInfo['color'],
                        'reason' => $m['reason'],
                        'benefits' => $lineInfo['benefits'],
                    ];
                }
            }
        }

        // 기본 폴백: 조건이 적어 3개 미만일 때 omega3 자동 추가
        if (!in_array('omega3-up', $addedStrategyIds) && count($strategies) < 3) {
            $strat = $strategyPool['omega3-up'];
            $strategies[] = [
                'icon' => $strat['icon'], 'label' => $strat['label'],
                'target' => $strat['target'], 'color' => $strat['color'], 'bgColor' => $strat['bgColor'],
            ];
            if (!in_array('omega3', $addedLineIds) && isset($lineDb['omega3'])) {
                $o = $lineDb['omega3'];
                $recommendedLines[] = ['id' => 'omega3', 'label' => $o['label'], 'color' => $o['color'], 'reason' => $o['reason'], 'benefits' => $o['benefits']];
            }
        }

        $strategies = array_slice($strategies, 0, 3);
        $recommendedLines = array_slice($recommendedLines, 0, 3);

        // ── 추천 질환식 ──
        $recommendedDiseaseDiets = $this->getRecommendedDiseaseDiets($rawConditions);

        // ── 조건 라벨 ──
        $condLabelMap = [
            'diabetes' => '당뇨', 'gestational-diabetes' => '임신성 당뇨',
            'kidney-pre-dialysis' => '신장질환(투석 전)', 'kidney-dialysis' => '신장질환(투석 중)',
            'cancer' => '암', 'breast-cancer' => '유방암', 'cholesterol' => '고지혈증',
            'weight-loss' => '체중 감량', 'general' => '균형잡힌 식단', 'weight-gain' => '체중 증량',
            'muscle-gain' => '근육 증량', 'pregnancy' => '임신·수유부 관리',
            'growth' => '성장 관리', 'aging' => '노화 관리',
        ];
        $conditionLabels = [];
        foreach ($rawConditions as $c) {
            $conditionLabels[] = $condLabelMap[$c] ?? $c;
        }

        // ── 하루 필요 영양소 (매크로 + 미시 영양소 목표) ──
        $proteinCal = $proteinG * 4;
        $proteinPct = round($proteinCal / $dailyCalories * 100);
        $fatPct = 30;
        $fatCal = round($dailyCalories * $fatPct / 100);
        $fatG = round($fatCal / 9);
        $carbsPct = 100 - $proteinPct - $fatPct;
        $carbsCal = $dailyCalories - $proteinCal - $fatCal;
        $carbsG = round($carbsCal / 4);

        // 조건별 나트륨 제한 조정
        $sodiumLimit = 2000;
        foreach ($rawConditions as $c) {
            if (in_array($c, ['kidney-pre-dialysis', 'kidney-dialysis'])) {
                $sodiumLimit = 1500;
                break;
            }
        }

        $dailyNutrients = [
            'calories' => $dailyCalories,
            'proteinG' => $proteinG,
            'macros' => [
                ['label' => '탄수화물', 'g' => $carbsG, 'pct' => $carbsPct, 'color' => '#3B82F6'],
                ['label' => '단백질', 'g' => $proteinG, 'pct' => $proteinPct, 'color' => '#EF4444'],
                ['label' => '지방', 'g' => $fatG, 'pct' => $fatPct, 'color' => '#F59E0B'],
            ],
            'micros' => [
                ['label' => '당류 섭취', 'value' => '25g 이하', 'desc' => 'WHO 권장 기준', 'icon' => 'trendingDown', 'color' => '#8B5CF6'],
                ['label' => '나트륨 섭취', 'value' => $sodiumLimit . 'mg 이하', 'desc' => ($sodiumLimit === 1500 ? '신장 질환 기준' : '일반 성인 기준'), 'icon' => 'shield', 'color' => '#3B82F6'],
                ['label' => '오메가-3 지방산', 'value' => '500mg 이상', 'desc' => '혈관 건강 유지', 'icon' => 'fish', 'color' => '#0EA5E9'],
            ],
        ];

        // ── 브랜드 메시지 ──
        $brandMessage = [
            'text' => "메디쏠라는 단순히 식단을 추천하지 않습니다.<br>당신의 건강 상태와 목표를 이해하고,<br>과학적 근거에 기반한 <strong>맞춤 케어</strong>를 제공합니다.",
            'tagline' => '추천이 아니라, 케어를 제공합니다.',
        ];

        // ── CTA (BMI + 전략 기반 동적 subtext) ──
        $bmiChangeMap = [
            'underweight' => '건강 체중 회복',
            'normal' => '체성분 균형 최적화',
            'overweight' => '체지방 감소',
            'obese' => '체중 조절 및 대사 개선',
        ];

        $strategyChangeMap = [
            'protein-up' => '근력 향상',
            'sugar-down' => '혈당 안정화',
            'omega3-up' => '혈관 건강 개선',
            'sodium-down' => '혈압 안정',
            'calorie-up' => '에너지 증가',
            'cholesterol-down' => 'LDL 콜레스테롤 감소',
            'saturated-fat-down' => '혈관 건강 개선',
        ];

        $ctaPrimary = $bmiChangeMap[$bmiStatusKey] ?? '건강 개선';
        $ctaSecondary = '';
        foreach ($addedStrategyIds as $sid) {
            if (isset($strategyChangeMap[$sid])) {
                $ctaSecondary = $strategyChangeMap[$sid];
                break;
            }
        }

        if (!$ctaSecondary) {
            $ctaSecondary = '에너지 안정감 증가';
        }

        $cta = [
            'label' => '이 전략으로 식단 플랜 시작',
            'subtext' => "예상 4주 후 변화: {$ctaPrimary} + {$ctaSecondary}",
        ];

        return [
            'bmi' => [
                'value' => $bmiValue,
                'status' => $bmiStatus,
                'statusKey' => $bmiStatusKey,
                'dailyCalories' => $dailyCalories,
                'proteinG' => $proteinG,
                'age' => $age,
            ],
            'coachingMessages' => $coachingMessages,
            'statusCards' => $statusCards,
            'dailyNutrients' => $dailyNutrients,
            'strategies' => $strategies,
            'recommendedLines' => $recommendedLines,
            'recommendedDiseaseDiets' => $recommendedDiseaseDiets,
            'conditionLabels' => $conditionLabels,
            'brandMessage' => $brandMessage,
            'cta' => $cta,
        ];
    }

    /**
     * 퀴즈 응답 저장 및 추천 라인 자동 계산
     * @param array $data 프론트엔드 퀴즈 데이터
     * @return int|false 저장된 responseSno, 실패 시 false
     */
    public function saveQuizResponse($data)
    {
        // V0 조건 ID → 한글 매핑 (getRecommendedLinesByCondition 호환)
        $conditionMap = [
            'diabetes' => '당뇨',
            'gestational-diabetes' => '당뇨',
            'kidney-pre-dialysis' => '신장질환',
            'kidney-dialysis' => '신장질환',
            'cancer' => '암투병',
            'breast-cancer' => '유방암',
            'cholesterol' => '고콜레스테롤',
        ];

        $goalMap = [
            'weight-loss' => '체중관리',
            'muscle-gain' => '근육증가',
            'weight-gain' => '체중증가',
            'pregnancy' => '임신수유부',
            'growth' => '성장관리',
            'aging' => '노화관리',
            'general' => null,
        ];

        // 조건/목표 추출
        $conditions = [];
        $goal = null;
        $rawConditions = $data['conditions'] ?? [];

        foreach ($rawConditions as $condId) {
            if (isset($conditionMap[$condId])) {
                $conditions[] = $conditionMap[$condId];
            }
            if (isset($goalMap[$condId])) {
                $goal = $goalMap[$condId];
            }
        }
        $conditions = array_unique($conditions);

        // 추천 라인: 프론트엔드에서 전달받은 리포트 데이터 우선 사용 (통합 알고리즘 일관성)
        // 프론트엔드가 전달하지 않으면 레거시 함수로 폴백
        if (!empty($data['recommendedLines'])) {
            $recommendedLines = is_string($data['recommendedLines'])
                ? json_decode($data['recommendedLines'], true)
                : $data['recommendedLines'];
            if (!is_array($recommendedLines)) $recommendedLines = [];
        } else {
            $recommendedLines = $this->getRecommendedLinesByCondition($conditions, $goal);
        }

        // 추천 질환식
        if (!empty($data['recommendedDiseaseDiets'])) {
            $recommendedDiseaseDiets = is_string($data['recommendedDiseaseDiets'])
                ? json_decode($data['recommendedDiseaseDiets'], true)
                : $data['recommendedDiseaseDiets'];
            if (!is_array($recommendedDiseaseDiets)) $recommendedDiseaseDiets = [];
        } else {
            $recommendedDiseaseDiets = $this->getRecommendedDiseaseDiets($rawConditions);
        }

        // 세션 정보
        $memNo = 0;
        try {
            $session = \App::getInstance('session');
            $memNo = $session->get('member.memNo') ?: 0;
        } catch (\Exception $e) {
            $memNo = 0;
        }
        $sessionKey = session_id() ?: uniqid('quiz_', true);

        // 연령대 계산
        $birthYear = isset($data['birthYear']) ? intval($data['birthYear']) : null;
        $currentAge = $birthYear ? (date('Y') - $birthYear) : null;
        $age = null;
        if ($currentAge !== null) {
            if ($currentAge < 30) $age = '20대';
            elseif ($currentAge < 40) $age = '30대';
            elseif ($currentAge < 50) $age = '40대';
            elseif ($currentAge < 60) $age = '50대';
            else $age = '60대이상';
        }

        $gender = $data['gender'] ?? null;
        $conditionsJson = json_encode($rawConditions, JSON_UNESCAPED_UNICODE);
        $recommendedLinesJson = json_encode([
            'lines' => $recommendedLines,
            'diseaseDiets' => $recommendedDiseaseDiets,
        ], JSON_UNESCAPED_UNICODE);
        $height = isset($data['height']) ? number_format((float)$data['height'], 1, '.', '') : null;
        $weight = isset($data['weight']) ? number_format((float)$data['weight'], 1, '.', '') : null;

        $arrBind = [];
        $this->db->bind_param_push($arrBind, 'i', $memNo);
        $this->db->bind_param_push($arrBind, 's', $sessionKey);
        $this->db->bind_param_push($arrBind, 's', $age);
        $this->db->bind_param_push($arrBind, 's', $gender);
        $this->db->bind_param_push($arrBind, 's', $goal);
        $this->db->bind_param_push($arrBind, 's', $conditionsJson);
        $this->db->bind_param_push($arrBind, 's', $recommendedLinesJson);
        $this->db->bind_param_push($arrBind, 'i', $birthYear);
        $this->db->bind_param_push($arrBind, 's', $height);
        $this->db->bind_param_push($arrBind, 's', $weight);

        $strSQL = "INSERT INTO ms_diet_quiz_responses
                   (memNo, sessionKey, age, gender, goal, conditions, recommended_lines, birthYear, height, weight)
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $this->db->bind_query($strSQL, $arrBind);

        return $this->db->insert_id();
    }
}
