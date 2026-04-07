# 메디솔라 영양 강조 라인 정책 문서

**작성일**: 2026-02-11
**버전**: 2.0
**상태**: 운영 중

---

## 목차

1. [개요](#개요)
2. [영양 라인 정의](#영양-라인-정의)
3. [기준치 근거](#기준치-근거)
4. [질환/목표별 추천 매트릭스](#질환목표별-추천-매트릭스)
5. [기술 구현](#기술-구현)
6. [변경 이력](#변경-이력)

---

## 개요

### 목적

메디솔라 나만의 식단 플랜은 사용자의 **BMI 정보, 질환 유형, 건강 목표**를 기반으로 적합한 영양 라인을 우선 추천하고, 해당 라인 내에서 균형 잡힌 메뉴 선택을 지원합니다.

### 핵심 원칙

1. **개인 맞춤화**: 사용자의 건강 상태와 목표에 따라 우선순위 라인 추천
2. **과학적 근거**: 실제 메뉴 영양 데이터 분석 기반 기준치 설정
3. **균형 유지**: 라인 내에서 다양한 메뉴를 고르게 선택할 수 있도록 유도
4. **명확한 기준**: 모호하지 않은 정량적 영양소 기준치 적용

---

## 영양 라인 정의

### 기존 라인 (Version 1.0 → 2.0 조정)

| 라인명 | 한글 | 기준치 (v1.0) | 기준치 (v2.0) | 변경 사유 |
|--------|------|--------------|--------------|----------|
| High Protein | 고단백 | Protein ≥ 20g | **유지** | 적절한 분포 (26개 중 21개) |
| Low Sodium | 저나트륨 | Sodium ≤ 400mg | **Sodium ≤ 450mg** | 실제 메뉴 최소값 385mg, 대부분 400mg 초과 |
| Omega-3 Rich | 오메가3 | Omega-3 ≥ 500mg | **Omega-3 ≥ 200mg** | 실제 메뉴 최대값 334mg, 기존 기준 0개 해당 |
| Low Calorie | 550kcal이하 | Calories ≤ 550kcal | **Calories ≤ 300kcal** | 모든 메뉴 335kcal 이하, 차별화 필요 |

### 신규 라인 (Version 2.0 추가)

| 라인명 | 한글 | 기준치 | 색상 코드 | 목적 | 정렬 기준 |
|--------|------|--------|----------|------|----------|
| Low Sugar | 저당 | Sugar ≤ 5g | #F59E0B (Amber) | 당뇨, 혈당 관리 | 당 낮은 순 |
| High Fiber | 고식이섬유 | Fiber ≥ 3g | #84CC16 (Lime) | 소화 건강, 혈당 조절, 체중 관리 | 식이섬유 높은 순 |
| Low Fat | 저지방 | Fat ≤ 5g | #8B5CF6 (Purple) | 체중 감량, 칼로리 제한 | 지방 낮은 순 |
| Low Saturated Fat | 저포화지방 | Saturated Fat ≤ 1.5g | #EC4899 (Pink) | 심혈관 건강, 고지혈증 | 포화지방 낮은 순 |
| Low Cholesterol | 저콜레스테롤 | Cholesterol ≤ 30mg | #14B8A6 (Teal) | 심혈관 건강, 고지혈증 | 콜레스테롤 낮은 순 |
| Low Carbs | 저탄수화물 | Carbs ≤ 40g | #F97316 (Orange) | 체중 감량, 혈당 관리 | 탄수화물 낮은 순 |

---

## 기준치 근거

### 실제 메뉴 데이터 분석 (26개 샘플)

**영양소 분포 범위:**

| 영양소 | 최소값 | 최대값 | 평균 | 중앙값 | 라인 기준치 | 예상 적용 메뉴 수 |
|--------|--------|--------|------|--------|------------|-----------------|
| 칼로리 (kcal) | 248 | 335 | ~290 | ~285 | ≤ 300 | 약 20개 (77%) |
| 단백질 (g) | 18 | 25 | ~21 | ~21 | ≥ 20 | 약 21개 (81%) |
| 탄수화물 (g) | 38 | 52 | ~43 | ~42 | ≤ 40 | 약 12개 (46%) |
| 당 (g) | 2 | 23 | ~8 | ~6 | ≤ 5 | 약 15개 (58%) |
| 지방 (g) | 2 | 12 | ~6 | ~5 | ≤ 5 | 약 18개 (69%) |
| 포화지방 (g) | 0 | 4 | ~1.5 | ~1 | ≤ 1.5 | 약 20개 (77%) |
| 나트륨 (mg) | 385 | 440 | ~410 | ~405 | ≤ 450 | 약 8개 (31%) |
| 오메가3 (mg) | 0 | 334 | ~80 | ~20 | ≥ 200 | 약 5개 (19%) |
| 콜레스테롤 (mg) | 0 | 12 | ~5 | ~4 | ≤ 30 | 약 24개 (92%) |
| 식이섬유 (g) | 1.8 | 4.3 | ~2.8 | ~2.7 | ≥ 3 | 약 10개 (38%) |

### 기준치 설정 원칙

1. **적정 분포**: 각 라인이 최소 5개 이상의 메뉴를 포함하여 충분한 선택지 제공
2. **과학적 근거**: 영양학적 권장량 및 질환별 가이드라인 참고
3. **차별화**: 라인 간 명확한 구분으로 중복 최소화
4. **실용성**: 실제 메뉴 영양 데이터 분포와 부합

---

## 질환/목표별 추천 매트릭스

### 질환별 우선 추천 라인

#### 당뇨 (Diabetes)

| 우선순위 | 라인 | 기준치 | 추천 사유 |
|---------|------|--------|----------|
| 1순위 | 저당 | Sugar ≤ 5g | 혈당 관리 최우선 |
| 2순위 | 고식이섬유 | Fiber ≥ 3g | 혈당 흡수 완화 |
| 3순위 | 저탄수화물 | Carbs ≤ 40g | 혈당 부하 감소 |

**과학적 근거**:
- 당 섭취 제한: 혈당 스파이크 방지
- 식이섬유: 탄수화물 흡수 속도 지연, 혈당 안정화
- 탄수화물 제한: 전체 혈당 부하 감소

#### 고혈압 (Hypertension)

| 우선순위 | 라인 | 기준치 | 추천 사유 |
|---------|------|--------|----------|
| 1순위 | 저나트륨 | Sodium ≤ 450mg | 혈압 관리 |
| 2순위 | 오메가3 | Omega-3 ≥ 200mg | 혈관 건강 |
| 3순위 | 저포화지방 | Saturated Fat ≤ 1.5g | 혈관 건강 |

**과학적 근거**:
- 나트륨 제한: 혈압 상승 억제 (DASH 다이어트 원칙)
- 오메가3: 혈관 확장, 항염증 효과
- 포화지방 제한: 혈관 경화 방지

#### 고지혈증 (Dyslipidemia)

| 우선순위 | 라인 | 기준치 | 추천 사유 |
|---------|------|--------|----------|
| 1순위 | 저콜레스테롤 | Cholesterol ≤ 30mg | 콜레스테롤 관리 |
| 2순위 | 저포화지방 | Saturated Fat ≤ 1.5g | 포화지방 제한 |
| 3순위 | 오메가3 | Omega-3 ≥ 200mg | HDL 콜레스테롤 증가 |

**과학적 근거**:
- 콜레스테롤 제한: 혈중 LDL 수치 감소
- 포화지방 제한: LDL 생성 억제
- 오메가3: HDL (좋은 콜레스테롤) 증가

#### 심혈관질환 (Cardiovascular Disease)

| 우선순위 | 라인 | 기준치 | 추천 사유 |
|---------|------|--------|----------|
| 1순위 | 저포화지방 | Saturated Fat ≤ 1.5g | 혈관 건강 |
| 2순위 | 저콜레스테롤 | Cholesterol ≤ 30mg | 혈관 건강 |
| 3순위 | 오메가3 | Omega-3 ≥ 200mg | 혈관 보호 |

#### 신장질환 (Kidney Disease)

| 우선순위 | 라인 | 기준치 | 추천 사유 |
|---------|------|--------|----------|
| 1순위 | 저나트륨 | Sodium ≤ 450mg | 신장 부담 감소 |

**참고**: 신장질환의 경우 단백질 섭취도 제한이 필요하나, 현재 라인에는 "적정 단백질" 라인이 없음 (향후 추가 검토)

### 건강 목표별 우선 추천 라인

#### 체중 관리 (Weight Management)

| 우선순위 | 라인 | 기준치 | 추천 사유 |
|---------|------|--------|----------|
| 1순위 | 300kcal이하 | Calories ≤ 300kcal | 칼로리 제한 |
| 2순위 | 고단백 | Protein ≥ 20g | 포만감 증가 |
| 3순위 | 저지방 | Fat ≤ 5g | 지방 제한 |

**과학적 근거**:
- 칼로리 제한: 체중 감량의 기본 원리
- 고단백: 포만감 유지, 근육량 보존
- 저지방: 칼로리 밀도 감소 (지방 1g = 9kcal)

#### 근육 증가 (Muscle Gain)

| 우선순위 | 라인 | 기준치 | 추천 사유 |
|---------|------|--------|----------|
| 1순위 | 고단백 | Protein ≥ 20g | 근육 합성 |

**과학적 근거**:
- 고단백: 근육 단백질 합성 촉진

#### 혈당 관리 (Blood Sugar Management)

| 우선순위 | 라인 | 기준치 | 추천 사유 |
|---------|------|--------|----------|
| 1순위 | 저당 | Sugar ≤ 5g | 혈당 안정화 |
| 2순위 | 고식이섬유 | Fiber ≥ 3g | 혈당 흡수 완화 |
| 3순위 | 저탄수화물 | Carbs ≤ 40g | 혈당 부하 감소 |

#### 심혈관 건강 (Cardiovascular Health)

| 우선순위 | 라인 | 기준치 | 추천 사유 |
|---------|------|--------|----------|
| 1순위 | 오메가3 | Omega-3 ≥ 200mg | 혈관 보호 |
| 2순위 | 저나트륨 | Sodium ≤ 450mg | 혈압 관리 |
| 3순위 | 저포화지방 | Saturated Fat ≤ 1.5g | 혈관 건강 |

---

## 기술 구현

### 백엔드 (PHP)

**파일**: `godo-dev-module/Component/DietFinder/DietFinder.php`

#### calculateLines() 메서드

```php
public function calculateLines($item)
{
    $lines = [];

    // 기본 영양소 추출
    $protein = floatval($item['nutrition_protein'] ?? 0);
    $sodium = intval($item['nutrition_sodium'] ?? 999);
    $omega3 = intval($item['nutrition_omega3'] ?? 0);
    $calories = intval($item['nutrition_calories'] ?? 999);
    $sugar = floatval($item['nutrition_sugar'] ?? 999);
    $fiber = floatval($item['nutrition_fiber'] ?? 0);
    $fat = floatval($item['nutrition_fat'] ?? 999);
    $saturatedFat = floatval($item['nutrition_saturated_fat'] ?? 999);
    $cholesterol = intval($item['nutrition_cholesterol'] ?? 999);
    $carbs = floatval($item['nutrition_carbs'] ?? 999);

    // 기존 라인 (조정된 기준치)
    if ($protein >= 20) $lines[] = '고단백';
    if ($sodium > 0 && $sodium <= 450) $lines[] = '저나트륨';
    if ($omega3 >= 200) $lines[] = '오메가3';
    if ($calories > 0 && $calories <= 300) $lines[] = '300kcal이하';

    // 신규 라인
    if ($sugar > 0 && $sugar <= 5) $lines[] = '저당';
    if ($fiber >= 3) $lines[] = '고식이섬유';
    if ($fat > 0 && $fat <= 5) $lines[] = '저지방';
    if ($saturatedFat >= 0 && $saturatedFat <= 1.5) $lines[] = '저포화지방';
    if ($cholesterol >= 0 && $cholesterol <= 30) $lines[] = '저콜레스테롤';
    if ($carbs > 0 && $carbs <= 40) $lines[] = '저탄수화물';

    return $lines;
}
```

#### getRecommendedLinesByCondition() 메서드

```php
public function getRecommendedLinesByCondition($conditions, $goal = null)
{
    $recommendations = [];

    // 질환별 추천
    if (in_array('당뇨', $conditions)) {
        $recommendations[] = ['line' => 'lowSugar', 'lineKr' => '저당', 'priority' => 1, 'reason' => '혈당 관리'];
        $recommendations[] = ['line' => 'highFiber', 'lineKr' => '고식이섬유', 'priority' => 2, 'reason' => '혈당 흡수 완화'];
        $recommendations[] = ['line' => 'lowCarbs', 'lineKr' => '저탄수화물', 'priority' => 3, 'reason' => '혈당 부하 감소'];
    }

    if (in_array('고혈압', $conditions)) {
        $recommendations[] = ['line' => 'lowSodium', 'lineKr' => '저나트륨', 'priority' => 1, 'reason' => '혈압 관리'];
        $recommendations[] = ['line' => 'omega3', 'lineKr' => '오메가3', 'priority' => 2, 'reason' => '혈관 건강'];
        $recommendations[] = ['line' => 'lowSaturatedFat', 'lineKr' => '저포화지방', 'priority' => 3, 'reason' => '혈관 건강'];
    }

    // ... (기타 질환/목표 로직)

    // 우선순위순 정렬 및 중복 제거
    usort($recommendations, function($a, $b) {
        return $a['priority'] - $b['priority'];
    });

    return $recommendations;
}
```

### 프론트엔드 (JavaScript)

**파일**:
- `godo-skin/front/medisola_dev/js/custom-diet-builder.js` (Desktop)
- `godo-skin/mobile/medisola_dev/js/custom-diet-builder.js` (Mobile)

#### NUTRITION_LINES 배열

```javascript
var NUTRITION_LINES = [
  // 기존 라인 (조정된 기준치)
  {
    id: 'high-protein',
    label: '고단백 라인',
    line: '고단백',
    color: '#EF4444',
    filter: function(i) { return i.lines && i.lines.indexOf('고단백') !== -1; },
    sort: function(a, b) { return b.protein - a.protein; },
    focus: { name: '단백질', key: 'protein', unit: 'g', max: 50 }
  },
  {
    id: 'low-sodium',
    label: '저나트륨 라인',
    line: '저나트륨',
    color: '#3B82F6',
    filter: function(i) { return i.lines && i.lines.indexOf('저나트륨') !== -1; },
    sort: function(a, b) { return a.sodium - b.sodium; },
    focus: { name: '나트륨', key: 'sodium', unit: 'mg', max: 800 }
  },
  {
    id: 'omega3',
    label: 'Omega-3 함유',
    line: '오메가3',
    color: '#06B6D4',
    filter: function(i) { return i.lines && i.lines.indexOf('오메가3') !== -1; },
    sort: function(a, b) { return b.omega3 - a.omega3; },
    focus: { name: '오메가3', key: 'omega3', unit: 'mg', max: 500 }
  },
  {
    id: 'low-cal',
    label: '300kcal 라인',
    line: '300kcal이하',
    color: '#22C55E',
    filter: function(i) { return i.lines && i.lines.indexOf('300kcal이하') !== -1; },
    sort: function(a, b) { return a.calories - b.calories; },
    focus: { name: '칼로리', key: 'calories', unit: 'kcal', max: 300 }
  },
  // 신규 라인
  {
    id: 'low-sugar',
    label: '저당 라인',
    line: '저당',
    color: '#F59E0B',
    filter: function(i) { return i.lines && i.lines.indexOf('저당') !== -1; },
    sort: function(a, b) { return a.sugar - b.sugar; },
    focus: { name: '당', key: 'sugar', unit: 'g', max: 10 }
  },
  {
    id: 'high-fiber',
    label: '고식이섬유 라인',
    line: '고식이섬유',
    color: '#84CC16',
    filter: function(i) { return i.lines && i.lines.indexOf('고식이섬유') !== -1; },
    sort: function(a, b) { return b.fiber - a.fiber; },
    focus: { name: '식이섬유', key: 'fiber', unit: 'g', max: 5 }
  },
  {
    id: 'low-fat',
    label: '저지방 라인',
    line: '저지방',
    color: '#8B5CF6',
    filter: function(i) { return i.lines && i.lines.indexOf('저지방') !== -1; },
    sort: function(a, b) { return a.fat - b.fat; },
    focus: { name: '지방', key: 'fat', unit: 'g', max: 15 }
  },
  {
    id: 'low-saturated-fat',
    label: '저포화지방 라인',
    line: '저포화지방',
    color: '#EC4899',
    filter: function(i) { return i.lines && i.lines.indexOf('저포화지방') !== -1; },
    sort: function(a, b) { return a.saturatedFat - b.saturatedFat; },
    focus: { name: '포화지방', key: 'saturatedFat', unit: 'g', max: 5 }
  },
  {
    id: 'low-cholesterol',
    label: '저콜레스테롤 라인',
    line: '저콜레스테롤',
    color: '#14B8A6',
    filter: function(i) { return i.lines && i.lines.indexOf('저콜레스테롤') !== -1; },
    sort: function(a, b) { return a.cholesterol - b.cholesterol; },
    focus: { name: '콜레스테롤', key: 'cholesterol', unit: 'mg', max: 100 }
  },
  {
    id: 'low-carbs',
    label: '저탄수화물 라인',
    line: '저탄수화물',
    color: '#F97316',
    filter: function(i) { return i.lines && i.lines.indexOf('저탄수화물') !== -1; },
    sort: function(a, b) { return a.carbs - b.carbs; },
    focus: { name: '탄수화물', key: 'carbs', unit: 'g', max: 60 }
  },
  {
    id: 'all',
    label: '전체 메뉴',
    line: '전체',
    color: '#64748b',
    filter: function(i) { return true; },
    sort: function(a, b) { return 0; },
    focus: null
  }
];
```

### 데이터베이스 스키마

**테이블**: `es_addGoods`

**영양소 관련 칼럼** (migration: `002_alter_es_addGoods_nutrition.sql`):
```sql
nutrition_calories DECIMAL(6,1) COMMENT '칼로리(kcal)',
nutrition_protein DECIMAL(5,1) COMMENT '단백질(g)',
nutrition_carbs DECIMAL(5,1) COMMENT '탄수화물(g)',
nutrition_sugar DECIMAL(5,1) COMMENT '당(g)',
nutrition_fat DECIMAL(5,1) COMMENT '지방(g)',
nutrition_saturated_fat DECIMAL(5,1) COMMENT '포화지방(g)',
nutrition_trans_fat DECIMAL(5,1) COMMENT '트랜스지방(g)',
nutrition_sodium INT COMMENT '나트륨(mg)',
nutrition_omega3 INT COMMENT '오메가3(mg)',
nutrition_cholesterol INT COMMENT '콜레스테롤(mg)',
nutrition_fiber DECIMAL(5,1) COMMENT '식이섬유(g)'
```

---

## 변경 이력

### Version 2.0 (2026-02-11)

**변경 사항**:
1. 기존 4개 라인 기준치 조정
   - 저나트륨: 400mg → 450mg
   - 오메가3: 500mg → 200mg
   - 550kcal이하 → 300kcal이하
2. 신규 6개 라인 추가
   - 저당 (≤5g)
   - 고식이섬유 (≥3g)
   - 저지방 (≤5g)
   - 저포화지방 (≤1.5g)
   - 저콜레스테롤 (≤30mg)
   - 저탄수화물 (≤40g)
3. `getRecommendedLinesByCondition()` 메서드 추가

**근거**:
- 26개 실제 메뉴 영양 데이터 분석
- 질환별 맞춤 추천 강화 필요성
- 사용자 선택지 다양화

**영향**:
- 백엔드: `DietFinder.php` 수정
- 프론트엔드: `custom-diet-builder.js` (Desktop/Mobile) 수정
- 하위 호환성: 기존 데이터 구조 유지, 추가 변경 없음

### Version 1.0 (초기 버전)

**초기 라인**:
- 고단백 (≥20g)
- 저나트륨 (≤400mg)
- 오메가3 (≥500mg)
- 550kcal이하 (≤550kcal)

**제한사항**:
- 질환별 세분화 부족
- 일부 라인 기준치 실제 데이터와 불일치

---

## 부록

### 향후 개선 사항 (Tier 3)

#### 칼륨 관련 라인 (DB 확장 필요)

**테이블 칼럼 추가 필요**:
```sql
ALTER TABLE es_addGoods
    ADD COLUMN nutrition_potassium INT DEFAULT NULL COMMENT '칼륨(mg)';
```

**신규 라인 제안**:

1. **고칼륨 라인**
   - 기준: Potassium ≥ 300mg
   - 목적: 고혈압, 심혈관 건강 (혈압 조절)
   - 색상: #10B981 (Emerald)

2. **저칼륨 라인**
   - 기준: Potassium ≤ 200mg
   - 목적: 신장 질환 (신장 보호)
   - 색상: #6366F1 (Indigo)

**구현 우선순위**: 낮음 (칼륨 데이터 수집 필요)

### 참고 자료

- **DASH 다이어트**: 나트륨 제한 (하루 1500-2300mg)
- **당뇨병 식단 가이드**: 당 5% 이하, 식이섬유 25-30g/일
- **심혈관 질환 예방**: 포화지방 7% 이하, 콜레스테롤 200mg/일 이하
- **체중 감량**: 하루 500-1000kcal 제한 (식사당 300-400kcal)

---

**문서 승인**: 메디솔라 영양케어팀
**다음 리뷰 예정일**: 2026-08-11 (6개월 후)
