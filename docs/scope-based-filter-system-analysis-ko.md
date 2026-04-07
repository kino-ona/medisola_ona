# 나만의 식단 플랜 필터 시스템 구조 분석 및 개선 계획

## 1. 현재 구조 상세 분석

### 1.1 데이터 흐름 전체 구조

```
[백엔드] PHP
├─ menuData: 전체 메뉴 데이터 (100개)
├─ recommendedLines: 추천 영양 라인 (예: 고단백, 저나트륨)
└─ recommendedDiseaseDiets: 추천 질환식 (예: 당뇨케어)

↓

[프론트엔드] JavaScript State
├─ state.menuData: 전체 메뉴 배열
├─ state.recommendedLines: 추천 라인 배열 or null
├─ state.recommendedDiseaseDiets: 추천 질환식 배열 or null
├─ state.categoryFilter: 현재 선택된 카테고리 ('all', 'seafood', 'meat', ...)
└─ state.advancedFilters: 고급 필터 (protein, sodium, calories)
```

### 1.2 현재 렌더링 흐름 (발견된 핵심 문제)

#### Phase 1: 필터 칩 생성
```javascript
extractDynamicFilters()
  → state.menuData 전체 스캔 (100개)
  → ['mealType', 'category'] 필드에서 고유값 추출
  → 결과: ['all', '해산물', '육류', '밥류', '면류', ...]
  → 문제: 추천 라인에 없는 카테고리도 포함됨!
```

#### Phase 2: 메뉴 필터링
```javascript
getFilteredMenus()
  → state.menuData 전체에서 필터링
  → 3개 레이어 적용:
     1. Advanced filters (protein >= minProtein, sodium <= maxSodium, ...)
     2. Category filter (categoryFilter !== 'all'이면 해당 카테고리만)
     3. Disease filter (질환식 모드면 diseaseType 있는 것만)
  → 결과: 필터 조건에 맞는 메뉴 배열
```

#### Phase 3: 라인별 그룹핑
```javascript
getGroupedMenus()
  → filtered = getFilteredMenus() 결과 받음
  → NUTRITION_LINES 7개 전체를 순회하며
  → 각 라인의 filter() 함수로 매칭 여부 확인
  → 결과: { 'high-protein': {menus: [...]}, 'low-sodium': {menus: [...]}, ... }
```

#### Phase 4: 렌더링 (여기서 추천 라인 필터 적용!)
```javascript
renderMenuSections()
  → groups = getGroupedMenus()
  → recLineIds = getRecommendedLineIds()  // ['high-protein', 'low-sodium']
  
  → if (recLineIds.length > 0):
      // ★★★ 여기서 비로소 추천 라인만 렌더링! ★★★
      recLineIds.forEach(lineId => {
        renderMenuSection(groups[lineId])  // 추천 라인만
      })
    else:
      // 추천 없으면 모든 라인 렌더링
      NUTRITION_LINES.forEach(line => {
        renderMenuSection(groups[line.id])
      })
```

### 1.3 핵심 문제점 발견

**현재 구조의 치명적 결함:**

```
예시 시나리오:
- 전체 메뉴: 100개
  - 고단백 라인: 15개 (category: seafood, meat, rice)
  - 저나트륨 라인: 20개 (category: seafood, soup, side)
  - 저당 라인: 18개 (category: soup, side, snack)
  - 기타: 47개

- 사용자 퀴즈 결과: 추천 라인 = [고단백, 저나트륨]

현재 동작:
1. extractDynamicFilters() 
   → 전체 100개 스캔
   → 필터 칩: [전체, seafood, meat, rice, soup, side, snack, ...]
   → ❌ 문제: 'snack'은 저당 라인에만 있는데 칩에 표시됨

2. 사용자가 'snack' 필터 선택
   → getFilteredMenus() 실행
   → state.menuData 전체에서 category='snack' 검색
   → 저당 라인 메뉴 18개 필터링됨

3. getGroupedMenus() 실행
   → 저당 라인으로 그룹핑: {'low-sugar': {menus: [18개]}}

4. renderMenuSections() 실행
   → recLineIds = ['high-protein', 'low-sodium']
   → groups['low-sugar']는 렌더링 안 함 (recLineIds에 없음)
   → ❌ 결과: 빈 화면! "조건에 맞는 메뉴가 없습니다"

사용자 경험:
- 'snack' 필터가 칩에 보임 (클릭 가능)
- 클릭하면 아무것도 안 나옴
- 혼란스러운 UX!
```

### 1.4 추가 발견: 질환식 모드도 동일한 문제

```javascript
getGroupedMenusByDisease()
  → filtered = getFilteredMenus()  // 여전히 state.menuData 전체에서
  → state.recommendedDiseaseDiets.forEach(dd => {
      // 추천된 질환식에 해당하는 메뉴만 그룹핑
    })
  
질환식 모드 시나리오:
- 추천 질환식: [당뇨케어]
- 필터 칩: 전체 메뉴의 모든 카테고리 표시
- 사용자가 "신장튼튼"에만 있는 카테고리 선택
- 결과: 빈 화면!
```

## 2. 근본 원인 분석

### 2.1 설계 의도 vs 실제 구현

**설계 의도 (추정):**
1. 추천 라인/질환식이 있으면 → 해당 메뉴만 보여주기
2. 필터는 보여지는 메뉴 내에서만 작동

**실제 구현:**
1. 필터링 단계: 전체 메뉴 대상
2. 그룹핑 단계: 전체 라인 대상
3. 렌더링 단계: **여기서 비로소** 추천 라인만 선택

**결과:**
- 필터와 렌더링의 스코프가 불일치
- 사용자에게 보이지 않는 메뉴의 카테고리가 필터에 노출

### 2.2 왜 이런 구조가 되었나?

코드 히스토리 추정:
```
1단계: 초기 구현
- 모든 메뉴를 라인별로 그룹핑하여 표시
- 필터는 전체 메뉴 대상

2단계: 추천 기능 추가
- renderMenuSections()에 recLineIds 로직 추가
- 렌더링 시에만 추천 라인 필터링
- BUT: extractDynamicFilters()는 수정 안 함

3단계: 질환식 기능 추가
- getGroupedMenusByDisease() 추가
- 마찬가지로 렌더링 단계에서만 필터링
- extractDynamicFilters()는 여전히 수정 안 함
```

**결론: 기능이 레이어별로 추가되면서 스코프 일관성이 깨짐**

## 3. 사용자 우려사항 검증

### 질문 1: 현재 필터 항목이 전체 메뉴 대상인가?
**답: 맞습니다 (확인됨)**

```javascript
// custom-diet-builder.js Line 65-81
function extractDynamicFilters() {
  var valueSet = {};
  state.menuData.forEach(function(item) {  // ← 전체 메뉴!
    ['mealType', 'category'].forEach(function(field) {
      // ...
    });
  });
}
```

### 질문 2: 추천 라인 메뉴가 필터로 제외될 가능성?
**답: 반대 문제가 있습니다**

- 추천 라인 메뉴가 필터로 제외되는 것은 아님
- **추천 라인 밖의 메뉴**가 필터에 의해 검색되지만, 렌더링은 안 됨
- 결과: 클릭 가능하지만 결과가 없는 필터 칩

## 4. 근본적 개선 방안

### 4.1 핵심 원칙

```
필터 스코프 = 렌더링 스코프 = 추천 라인/질환식 메뉴
```

**구체적 규칙:**
1. `extractDynamicFilters()` → 추천 라인/질환식 메뉴에서만 카테고리 추출
2. `getFilteredMenus()` → 추천 라인/질환식 메뉴에서만 필터링
3. `getGroupedMenus()` → 결과를 라인별 그룹핑
4. `renderMenuSections()` → 추천 라인만 렌더링 (현재 로직 유지)

### 4.2 구조적 개선 설계

#### 새 함수: getScopeMenus()

**역할:** 추천 라인/질환식에 속하는 메뉴만 반환

```javascript
function getScopeMenus() {
  // 우선순위 1: 질환식 모드
  if (state.recommendedDiseaseDiets && state.recommendedDiseaseDiets.length > 0) {
    return getMenusMatchingDiseaseDiets(state.recommendedDiseaseDiets);
  }
  
  // 우선순위 2: 영양 강조 라인 모드
  if (state.recommendedLines && state.recommendedLines.length > 0) {
    var lineIds = getRecommendedLineIds();
    if (lineIds.length > 0) {
      return getMenusMatchingLines(lineIds);
    }
  }
  
  // 우선순위 3: 추천 없음 - 전체 메뉴 반환 (폴백)
  return state.menuData;
}
```

#### Helper 함수 1: getMenusMatchingLines(lineIds)

```javascript
function getMenusMatchingLines(lineIds) {
  var scopeMenus = [];
  var seenIds = {};  // 중복 방지
  
  lineIds.forEach(function(lineId) {
    var lineConfig = NUTRITION_LINES.find(function(l) { 
      return l.id === lineId; 
    });
    
    if (!lineConfig || lineConfig.id === 'all') return;
    
    state.menuData.forEach(function(menu) {
      // lineConfig.filter(menu): 메뉴가 이 라인에 속하는지 확인
      // 예: line.filter = function(i) { return i.lines && i.lines.indexOf('고단백') !== -1; }
      if (!seenIds[menu.id] && lineConfig.filter(menu)) {
        scopeMenus.push(menu);
        seenIds[menu.id] = true;
      }
    });
  });
  
  return scopeMenus;
}
```

**동작 예시:**
```
lineIds = ['high-protein', 'low-sodium']
state.menuData = 100개

1. 'high-protein' 라인 처리:
   - NUTRITION_LINES에서 config 찾기
   - config.filter = function(i) { return i.lines && i.lines.indexOf('고단백') !== -1; }
   - menuData 순회하며 filter(menu) 테스트
   - 매칭: 15개 메뉴 → scopeMenus에 추가

2. 'low-sodium' 라인 처리:
   - 마찬가지로 config 찾기
   - 매칭: 20개 메뉴
   - 이미 scopeMenus에 있는 메뉴는 seenIds로 제외 (중복 방지)
   - 새로운 메뉴만 추가

결과: scopeMenus = 35개 (고단백 15개 + 저나트륨 추가 20개)
```

#### Helper 함수 2: getMenusMatchingDiseaseDiets(diseaseDiets)

```javascript
function getMenusMatchingDiseaseDiets(diseaseDiets) {
  var diseaseDietConfigs = window.DISEASE_DIETS || [];
  var scopeMenus = [];
  var seenIds = {};
  
  diseaseDiets.forEach(function(dd) {
    var config = diseaseDietConfigs.find(function(c) { 
      return c.id === dd.id; 
    });
    
    if (!config) return;
    
    state.menuData.forEach(function(menu) {
      // config.filter(menu): 메뉴가 이 질환식에 속하는지 확인
      // 예: config.filter = function(i) { return i.diseaseType && i.diseaseType.indexOf('당뇨케어') !== -1; }
      if (!seenIds[menu.id] && config.filter(menu)) {
        scopeMenus.push(menu);
        seenIds[menu.id] = true;
      }
    });
  });
  
  return scopeMenus;
}
```

### 4.3 기존 함수 수정

#### 수정 1: extractDynamicFilters()

```javascript
// BEFORE
function extractDynamicFilters() {
  var valueSet = {};
  state.menuData.forEach(function(item) {  // ← 전체 메뉴
    // ...
  });
}

// AFTER
function extractDynamicFilters() {
  var valueSet = {};
  var scopeMenus = getScopeMenus();  // ← 스코프 메뉴만!
  scopeMenus.forEach(function(item) {
    ['mealType', 'category'].forEach(function(field) {
      if (item[field] && item[field].length > 0) {
        item[field].forEach(function(v) {
          if (v) valueSet[v] = (valueSet[v] || 0) + 1;
        });
      }
    });
  });
  var filters = [{ value: 'all', label: '전체' }];
  Object.keys(valueSet).sort().forEach(function(k) {
    filters.push({ value: k, label: LABEL_MAP[k] || k });
  });
  return filters;
}
```

**효과:**
```
이전: ['전체', 'seafood', 'meat', 'rice', 'soup', 'side', 'snack', ...]
      ↑ 100개 메뉴 전체의 카테고리

이후: ['전체', 'seafood', 'meat', 'rice', 'soup', 'side']
      ↑ 고단백+저나트륨 35개 메뉴의 카테고리만
      ('snack'은 저당 라인에만 있으므로 제외됨)
```

#### 수정 2: getFilteredMenus()

```javascript
// BEFORE
function getFilteredMenus() {
  var isDiseaseMode = state.recommendedDiseaseDiets && state.recommendedDiseaseDiets.length > 0;
  
  return state.menuData.filter(function(item) {  // ← 전체 메뉴
    // 3개 레이어 필터링
  });
}

// AFTER
function getFilteredMenus() {
  var isDiseaseMode = state.recommendedDiseaseDiets && state.recommendedDiseaseDiets.length > 0;
  var scopeMenus = getScopeMenus();  // ← 스코프 메뉴만!
  
  return scopeMenus.filter(function(item) {
    var protein = item.protein != null ? item.protein : 0;
    var sodium = item.sodium != null ? item.sodium : 0;
    var calories = item.calories != null ? item.calories : 0;

    var passesAdvanced =
      protein >= state.advancedFilters.minProtein &&
      (sodium === 0 || sodium <= state.advancedFilters.maxSodium) &&
      (calories === 0 || calories <= state.advancedFilters.maxCalories);

    var passesCategory = true;
    if (state.categoryFilter !== 'all') {
      var found = false;
      if (item.category && item.category.indexOf(state.categoryFilter) !== -1) found = true;
      if (!found && item.foodStyle && item.foodStyle.indexOf(state.categoryFilter) !== -1) found = true;
      if (!found && item.mealType && item.mealType.indexOf(state.categoryFilter) !== -1) found = true;
      passesCategory = found;
    }

    // Disease filter: 이제 redundant하지만 안전을 위해 유지
    var passesDiseaseFilter = true;
    if (isDiseaseMode) {
      if (!item.diseaseType || item.diseaseType.length === 0) {
        passesDiseaseFilter = false;
      }
    } else {
      if (item.diseaseType && item.diseaseType.length > 0) {
        passesDiseaseFilter = false;
      }
    }

    return passesAdvanced && passesCategory && passesDiseaseFilter;
  });
}
```

**효과:**
```
이전:
- categoryFilter = 'snack'
- state.menuData (100개) 검색
- 저당 라인 메뉴 18개 찾음
- renderMenuSections()에서 렌더링 안 됨 (추천 라인 아님)
- 결과: 빈 화면

이후:
- categoryFilter = 'snack'
- scopeMenus (35개: 고단백+저나트륨) 검색
- 'snack' 카테고리 메뉴 0개 (애초에 scopeMenus에 없음)
- 결과: 빈 화면이지만, 'snack' 필터 자체가 칩에 표시 안 됨!
```

## 5. 개선 효과 시뮬레이션

### 시나리오 1: 영양 라인 추천 사용자

```
초기 상태:
- 전체 메뉴: 100개
  - 고단백: 15개 (seafood, meat, rice)
  - 저나트륨: 20개 (seafood, soup, side)
  - 저당: 18개 (soup, side, snack)
  - 기타: 47개
- 추천 라인: [고단백, 저나트륨]

[현재 구현]
1. 필터 칩: [전체, seafood, meat, rice, soup, side, snack, ...]
2. 사용자 'snack' 클릭
3. 결과: 빈 화면 (저당 라인이 렌더링 안 됨)
❌ UX: 클릭 가능한 칩이 무응답

[개선 후]
1. 필터 칩: [전체, seafood, meat, rice, soup, side]
   ('snack'은 아예 칩에 없음)
2. 사용자 'seafood' 클릭
3. 결과: 고단백+저나트륨 중 seafood 메뉴만 표시
✅ UX: 모든 필터 칩이 의미 있는 결과 제공
```

### 시나리오 2: 질환식 추천 사용자

```
초기 상태:
- 전체 메뉴: 100개
  - 당뇨케어: 12개 (seafood, soup, side)
  - 신장튼튼: 10개 (seafood, meat)
  - 일반 메뉴: 78개 (모든 카테고리)
- 추천 질환식: [당뇨케어]

[현재 구현]
1. 필터 칩: [전체, seafood, meat, rice, soup, side, snack, ...]
2. 사용자 'meat' 클릭
3. 결과: 빈 화면 (당뇨케어에 meat 없음)
❌ UX: 혼란

[개선 후]
1. 필터 칩: [전체, seafood, soup, side]
   ('meat'은 신장튼튼에만 있으므로 칩에 없음)
2. 사용자 'seafood' 클릭
3. 결과: 당뇨케어 중 seafood 메뉴만 표시
✅ UX: 명확한 필터링
```

### 시나리오 3: 추천 없는 직접 접근 사용자

```
초기 상태:
- 전체 메뉴: 100개
- 추천 라인: null
- 추천 질환식: null

[현재 구현]
1. 필터 칩: [전체, seafood, meat, rice, soup, side, snack, ...]
2. 모든 라인 그룹 표시
✅ 정상 동작

[개선 후]
1. getScopeMenus() → state.menuData 반환 (폴백)
2. 필터 칩: [전체, seafood, meat, rice, soup, side, snack, ...]
3. 모든 라인 그룹 표시
✅ 동일하게 정상 동작 (하위 호환성 유지)
```

## 6. 엣지 케이스 분석

### Case 1: 메뉴가 여러 라인에 속하는 경우

```
메뉴 A:
- lines: ['고단백', '저나트륨', '오메가3']
- category: ['seafood']

추천 라인: ['고단백', '저나트륨']

getMenusMatchingLines() 동작:
1. '고단백' 라인 처리: 메뉴 A 추가 (seenIds[A] = true)
2. '저나트륨' 라인 처리: 메뉴 A 이미 있음 (seenIds 체크) → skip
3. 결과: 메뉴 A는 scopeMenus에 1번만 포함

✅ 중복 없음 (seenIds로 방지)
```

### Case 2: 추천 라인에 메뉴가 없는 경우

```
추천 라인: ['오메가3']
전체 메뉴 중 오메가3 라인: 0개

getScopeMenus() 결과:
- scopeMenus = []

extractDynamicFilters():
- [].forEach() → valueSet = {}
- filters = [{value: 'all', label: '전체'}]

getFilteredMenus():
- [].filter() → []

renderMenuSections():
- diseaseGroups or groups 모두 빈 상태
- "조건에 맞는 메뉴가 없습니다" 표시

✅ 정상 처리 (기존 empty state 로직 사용)
```

### Case 3: 550kcal 라인 격리 규칙

```
현재 로직 (getGroupedMenus Line 181):
- 550kcal 라인은 단독 운영
- 다른 라인에서 550kcal 메뉴 제외

개선 후:
- getScopeMenus()는 550kcal 메뉴도 포함 (라인 매칭만 확인)
- getGroupedMenus()에서 기존 격리 로직 그대로 적용
- 렌더링 시 550kcal 라인이 추천에 있으면 단독 표시

✅ 기존 비즈니스 로직 유지
```

### Case 4: 고급 필터로 모든 메뉴 제외

```
추천 라인: ['고단백', '저나트륨'] (35개 메뉴)

사용자가 고급 필터 설정:
- 최소 단백질: 40g (대부분 메뉴가 20-30g)
- 최대 나트륨: 200mg (대부분 메뉴가 400-600mg)

getFilteredMenus():
- scopeMenus (35개)에서 필터링
- 조건 만족: 0개

renderMenuSections():
- "조건에 맞는 메뉴가 없습니다"

✅ 사용자가 필터를 완화해야 함 (정상 동작)
```

### Case 5: 레거시 형식 recommendedLines

```
레거시 형식:
recommendedLines = ["고단백 라인", "저나트륨"]

getRecommendedLineIds() 처리 (Line 139-168):
1. rec = "고단백 라인" (string)
2. normalized = "고단백" (" 라인" 제거)
3. NUTRITION_LINES에서 line: "고단백" 찾기
4. id = 'high-protein' 반환

getMenusMatchingLines(['high-protein', 'low-sodium']):
- 정상 처리

✅ 하위 호환성 유지
```

## 7. 성능 영향 분석

### 현재 구조 성능

```
extractDynamicFilters():
- state.menuData 순회 (100개)
- 3개 필드 × 평균 2개 값 = 600 iterations
- O(n × m): n=메뉴 수, m=필드당 값 개수

getFilteredMenus():
- state.menuData 순회 (100개)
- 3개 필터 체크 = 300 operations
- O(n × f): n=메뉴 수, f=필터 개수

Total: ~900 operations (매 필터 변경 시)
```

### 개선 후 성능

```
getScopeMenus():
- lineIds 순회 (2개)
- 각 라인마다 state.menuData 순회 (100개)
- lineConfig.filter() 호출 (100 × 2 = 200)
- O(l × n): l=라인 수, n=메뉴 수
- = 200 operations (한 번만 실행)

extractDynamicFilters():
- scopeMenus 순회 (35개)
- 3개 필드 × 평균 2개 값 = 210 iterations
- O(s × m): s=스코프 메뉴 수, m=필드당 값 개수

getFilteredMenus():
- scopeMenus 순회 (35개)
- 3개 필터 체크 = 105 operations
- O(s × f): s=스코프 메뉴 수, f=필터 개수

Total per filter change:
- getScopeMenus() 호출: 200 operations
- extractDynamicFilters(): 210 iterations
- getFilteredMenus(): 105 operations
- = ~515 operations

vs 현재: ~900 operations
개선: 43% 성능 향상!
```

### 최적화 고려사항

**현재 설계: getScopeMenus()를 매번 호출**

장점:
- 구현 단순
- 상태 관리 불필요
- 버그 위험 낮음

단점:
- 매 필터 변경 시 스코프 재계산

**대안: 스코프 메뉴 캐싱**

```javascript
var _cachedScopeMenus = null;
var _cacheKey = null;

function getScopeMenus() {
  var currentKey = JSON.stringify({
    lines: state.recommendedLines,
    diets: state.recommendedDiseaseDiets
  });
  
  if (_cacheKey === currentKey && _cachedScopeMenus) {
    return _cachedScopeMenus;
  }
  
  _cachedScopeMenus = calculateScopeMenus();
  _cacheKey = currentKey;
  return _cachedScopeMenus;
}
```

**권장사항: 초기 구현에서는 캐싱 안 함**
- 이유 1: 추천 라인/질환식은 세션 동안 변경 안 됨
- 이유 2: 200 operations는 충분히 빠름 (~2-3ms)
- 이유 3: 조기 최적화 회피

향후 성능 문제 발생 시 캐싱 추가 고려

## 8. 구현 우선순위

### Phase 1: 핵심 함수 추가 (필수)

```
1. getMenusMatchingLines(lineIds)
2. getMenusMatchingDiseaseDiets(diseaseDiets)
3. getScopeMenus()
```

위치: `getRecommendedLineIds()` 함수 다음 (Line 169 이후)

### Phase 2: 기존 함수 수정 (필수)

```
1. extractDynamicFilters() - Line 65-81
   - state.menuData → getScopeMenus()

2. getFilteredMenus() - Line 98-135
   - state.menuData → getScopeMenus()
```

### Phase 3: 다중 파일 배포 (필수)

**CRITICAL:** 4개 파일 모두 동일하게 수정
- `godo-skin/front/medisola_dev/js/custom-diet-builder.js`
- `godo-skin/mobile/medisola_dev/js/custom-diet-builder.js`
- `godo-skin/front/drorganic_24_renewal/js/custom-diet-builder.js`
- `godo-skin/mobile/dorganic_24_renewal/js/custom-diet-builder.js`

### Phase 4: 테스트 (필수)

테스트 시나리오:
1. 영양 라인 추천 있음 → 필터 칩 확인
2. 질환식 추천 있음 → 필터 칩 확인
3. 추천 없음 → 전체 메뉴 확인
4. 고급 필터 + 카테고리 필터 조합 테스트
5. Desktop + Mobile 동작 일치 확인

## 9. 롤백 계획

### 즉시 롤백 (문제 발생 시)

```javascript
// extractDynamicFilters() Line 67
// var scopeMenus = getScopeMenus();
// scopeMenus.forEach(function(item) {
state.menuData.forEach(function(item) {  // ← 원복

// getFilteredMenus() Line 100
// var scopeMenus = getScopeMenus();
// return scopeMenus.filter(function(item) {
return state.menuData.filter(function(item) {  // ← 원복
```

→ 주석 2줄 변경으로 즉시 원복 가능

### Git 롤백

```bash
git revert <commit-hash>
```

→ 백엔드 변경 없으므로 프론트엔드만 롤백 가능

## 10. 성공 기준

### 기능 검증

- ✅ 추천 라인 있을 때: 필터 칩이 해당 라인 메뉴의 카테고리만 표시
- ✅ 질환식 추천 있을 때: 필터 칩이 해당 질환식 메뉴의 카테고리만 표시
- ✅ 추천 없을 때: 필터 칩이 전체 메뉴의 카테고리 표시 (현재 동작 유지)
- ✅ 카테고리 필터: 스코프 메뉴 내에서만 검색
- ✅ 고급 필터: 스코프 메뉴 내에서만 적용
- ✅ Desktop/Mobile: 동일한 동작

### UX 검증

- ✅ 모든 필터 칩 클릭 시 의미 있는 결과 제공
- ✅ "조건에 맞는 메뉴가 없습니다"는 실제로 메뉴가 없을 때만 표시
- ✅ 필터 칩 수 감소 → 사용자 혼란 감소

### 성능 검증

- ✅ 필터 변경 시 응답 시간 < 50ms
- ✅ 초기 로딩 시간 변화 없음
- ✅ 메모리 사용량 변화 없음

### 호환성 검증

- ✅ 레거시 recommendedLines 형식 지원
- ✅ 550kcal 라인 격리 규칙 유지
- ✅ 기존 empty state 처리 유지

## 11. 결론

### 현재 문제 요약

1. **필터 스코프 불일치**: 필터는 전체 메뉴 대상, 렌더링은 추천 라인만
2. **UX 문제**: 클릭 가능하지만 결과 없는 필터 칩
3. **구조적 결함**: 기능이 레이어별로 추가되며 일관성 상실

### 해결 방안

**핵심: getScopeMenus() 함수 도입**
- 추천 라인/질환식 메뉴만 반환
- 모든 필터링/검색의 기준점
- 폴백으로 전체 메뉴 지원 (하위 호환성)

### 구현 범위

- 3개 새 함수 추가
- 2개 기존 함수 수정 (각 1줄 변경)
- 4개 파일 동일 적용
- 백엔드 변경 없음

### 기대 효과

- **UX 개선**: 모든 필터 칩이 의미 있는 결과 제공
- **성능 개선**: 43% 연산 감소
- **유지보수성 개선**: 명확한 스코프 경계
- **확장성 확보**: 향후 필터 추가 시 일관된 동작

---

**다음 단계: 이 분석을 바탕으로 구현 시작**
