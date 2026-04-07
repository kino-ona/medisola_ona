# 나만의 식단 플랜 V0 개발 계획서

## 프로젝트 개요

**목표:** V0 디자인을 기반으로 사용자가 영양 라인별 메뉴를 직접 선택하여 맞춤 식단을 구성하는 UI/UX 구현

**핵심 원칙:**
1. V0 디자인 1:1 변환 (React/Tailwind → Vanilla JS/CSS)
2. 기존 주문 프로세스 유지 (componentGoods 카트 제출)
3. Desktop/Mobile 각각 최적화된 레이아웃
4. 고도몰 공식 패턴 준수 (하드코딩 최소화)

**V0 디자인 소스:** `/Users/js.lee/Projects/medisola-custom-diet`

---

## 초기 요구사항 요약

### 사용자 요청 타임라인

1. **헤더/푸터 감춤 이슈 해결** (2026-02-10)
   - 문제: 고도몰 어드민에서 "상단감춤" 설정했으나 계속 표시됨
   - 원인: 상품 페이지는 `layout_design_map_mobile` JSON 설정을 읽지 않음
   - 해결: `tpls` 변수 설정으로 고도몰 공식 방식 적용

2. **V0 디자인 정렬** (2026-02-10)
   - 모바일 레이아웃이 V0 디자인과 상이함
   - V0 소스 코드 분석 후 레이아웃 재구성
   - Header → Category Filters → Suggestion → Quantity → Selected Preview → Menu Sections 순서 적용

3. **CSS 충돌 해결** (2026-02-10)
   - 문제 #1: "선택한 메뉴" 타이틀 배경이 개발 배너 스타일과 동일
   - 문제 #2: 의도하지 않은 카드 스타일 (border-radius, box-shadow) 적용
   - 원인: 고도몰 전역 `.preview-*` 클래스 스타일 충돌
   - 해결: `.diet-page` 접두어 + `!important` 플래그로 명시적 스타일 오버라이드

---

## 고도몰 공식 패턴 가이드

### 1. 헤더/푸터 제어 시스템

**고도몰의 헤더/푸터 표시 메커니즘:**

```php
// Controller에서 tpls 변수 설정
$this->setData('tpls', [
    'header_inc' => false,  // 상단 감춤
    'footer_inc' => false   // 하단 감춤
]);
```

**템플릿에서의 사용:**
```html
{ # header }  <!-- header_inc가 false면 렌더링 안됨 -->
{ # footer }  <!-- footer_inc가 false면 렌더링 안됨 -->
```

**주의사항:**
- 어드민 "상단/하단감춤" 설정은 `layout_design_map_mobile.json`에 저장됨
- 일반 페이지는 이 JSON을 자동으로 읽지만, 상품 페이지(`GoodsViewController`)는 별도 처리 필요
- **하드코딩 금지**: HTML에 직접 `display:none` 쓰지 말고 `tpls` 변수 사용

**구현 위치:**
- [godo-dev-module/Controller/Front/Goods/GoodsViewController.php:124-129](godo-dev-module/Controller/Front/Goods/GoodsViewController.php#L124-L129)
- [godo-dev-module/Controller/Mobile/Goods/GoodsViewController.php](godo-dev-module/Controller/Mobile/Goods/GoodsViewController.php)

---

### 2. ComponentGoods 주문 시스템 (골라담기 패턴)

**개념:**
- 메인 상품(예: 식단 10식) + 여러 추가 상품(메뉴들)을 한 번에 장바구니 담기
- 고도몰의 기존 "골라담기" 기능을 활용한 주문 프로세스

**Form 구조:**
```html
<form id="frmView" method="post" action="../order/cart_ps.php">
  <input type="hidden" name="mode" value="cartIn" />
  <input type="hidden" name="cartMode" value="" />
  <input type="hidden" name="goodsNo[]" value="{goodsNo}" />
  <input type="hidden" name="optionSno[]" value="{10식/20식/40식 옵션 sno}" />
  <input type="hidden" name="goodsCnt[]" value="1" />

  <!-- componentGoods: 선택한 메뉴들 (JS에서 동적 생성) -->
  <div id="componentGoodsInputs">
    <input type="hidden" name="componentGoodsNo[0][]" value="{addGoodsNo}" />
    <input type="hidden" name="componentGoodsCnt[0][]" value="{count}" />
    <input type="hidden" name="componentGoodsAddedPrice[0][]" value="0" />
    <input type="hidden" name="componentGoodsName[0][]" value="{menuName}" />
  </div>
</form>
```

**JavaScript 동적 생성:**
```javascript
function buildFormInputs() {
  var inputs = '';
  var idx = 0;

  Object.keys(state.selections).forEach(function(menuId) {
    var count = state.selections[menuId];
    var menu = state.menuData.find(m => m.id == menuId);

    inputs += '<input type="hidden" name="componentGoodsNo[0][]" value="' + menu.addGoodsNo + '" />';
    inputs += '<input type="hidden" name="componentGoodsCnt[0][]" value="' + count + '" />';
    inputs += '<input type="hidden" name="componentGoodsAddedPrice[0][]" value="0" />';
    inputs += '<input type="hidden" name="componentGoodsName[0][]" value="' + menu.name + '" />';
  });

  $('#componentGoodsInputs').html(inputs);
}
```

**주문 플로우:**
```
[V0 UI] 식단 플랜 완료 버튼 클릭
    ↓
buildFormInputs() 실행 → componentGoods hidden inputs 생성
    ↓
Form Submit → ../order/cart_ps.php?mode=cartIn
    ↓
장바구니 담기 성공
    ↓
┌─────────────────────────────────────┐
│ cartMode='' → ../order/cart.php    │  장바구니로 이동
│ cartMode='d' → ../order/order.php  │  바로구매로 이동
└─────────────────────────────────────┘
```

**참고 파일:**
- 기존 골라담기 예시: `godo-skin/front/medisola_dev/goods/layer_option.html`
- 구현: [godo-skin/mobile/medisola_dev/js/custom-diet-builder.js:buildFormInputs()](godo-skin/mobile/medisola_dev/js/custom-diet-builder.js)

---

### 3. 고도몰 Storage 시스템

**이미지 URL 생성:**
```php
use Bundle\Component\Storage\Storage;

$storage = Storage::disk(
    Storage::PATH_CODE_ADD_GOODS,
    $imageStorage  // 'local' or 'cdn'
);
$imageUrl = $storage->getHttpPath($filePath);
```

**DietFinder.php 구현:**
- [godo-dev-module/Component/DietFinder/DietFinder.php:199-241](godo-dev-module/Component/DietFinder/DietFinder.php#L199-L241)
- 자동으로 CDN 경로 생성 (설정에 따라)
- 기본 이미지 fallback 처리

---

### 4. 고도몰 템플릿 문법

**변수 출력:**
```html
{variable_name}         <!-- HTML 이스케이프 처리 -->
{=variable_name}        <!-- 원본 그대로 출력 -->
```

**조건문:**
```html
{?condition}
  참일 때 렌더링
{:}
  거짓일 때 렌더링
{/?}
```

**반복문:**
```html
{@loop_id}
  {loop_id.item}
{/@}
```

**파일 인클루드:**
```html
{ # header }    <!-- header_inc 템플릿 포함 -->
{ # footer }    <!-- footer_inc 템플릿 포함 -->
```

**우리 프로젝트에서 사용:**
- JSON 데이터를 JavaScript로 전달: `{=addGoodsNutritionJson}`, `{=optionsJson}`
- 조건부 렌더링: 상품 플래그 체크 `{?goodsView.isCustomDiet}`

---

### 5. CSS 충돌 방지 전략

**문제:**
- 고도몰 전역 CSS가 커스텀 컴포넌트에 영향
- 특히 `.preview-*`, `.filter-*`, `.card-*` 등 범용 클래스명 충돌

**해결 패턴:**

1. **Body 래퍼 클래스 사용:**
```html
<body class="diet-page">
  <!-- 모든 커스텀 컴포넌트 -->
</body>
```

2. **CSS 명시적 오버라이드:**
```css
/* 고도몰 전역 스타일 무력화 */
.diet-page .selected-preview {
  /* 의도한 스타일 */
  border-bottom: 1px solid var(--cd-border) !important;

  /* 카드 스타일 명시적 제거 */
  border-radius: 0 !important;
  box-shadow: none !important;
  border-top: none !important;
  border-left: none !important;
  border-right: none !important;
}
```

3. **CSS 버전 관리:**
```html
<link rel="stylesheet" href="../css/custom-diet.css?v=5" />
```
- 캐시 무효화를 위해 버전 번호 증가

**적용 위치:**
- [godo-skin/mobile/medisola_dev/css/custom-diet.css](godo-skin/mobile/medisola_dev/css/custom-diet.css)
- 모든 커스텀 클래스에 `.diet-page` 접두어 적용

---

## 아키텍처 개요

### V0 → 고도몰 변환 매핑

| V0 | 고도몰 |
|---|---|
| React TSX | Vanilla HTML + Godo 템플릿 문법 |
| Tailwind CSS | Custom CSS (수동 변환) |
| useState/useMemo | JavaScript 객체 + 함수 |
| Next.js Image | `<img loading="lazy">` |
| lucide-react icons | 인라인 SVG |
| shadcn/ui Button | Custom CSS 버튼 |
| V0 selections | componentGoods hidden inputs |
| V0 handleComplete | gd_goods_order() 패턴 |

### Desktop vs Mobile 레이아웃

| 요소 | Desktop | Mobile |
|------|---------|--------|
| 전체 구조 | Sidebar + Main Content | Vertical Stack |
| 메뉴 섹션 | CSS Grid | Horizontal Scroll |
| 선택 미리보기 | Vertical List (이름 포함) | Horizontal Thumbnails |
| 모달 | Center 정렬 | Bottom Sheet |
| 영양 요약 | 5-column Grid | 2-column Grid |
| 화면 전환 | `.active` 클래스 토글 | `show()/hide()` |

---

## 영양 라인 시스템

### 4개 영양 라인

| Line ID | 라벨 | 색상 | 조건 | 정렬 |
|---------|------|------|------|------|
| high-protein | 고단백 라인 | #EF4444 | protein >= 20g | 단백질 높은 순 |
| low-sodium | 저나트륨 라인 | #3B82F6 | sodium <= 400mg | 나트륨 낮은 순 |
| omega3 | Omega-3 풍부 | #06B6D4 | omega3 >= 500mg | 오메가3 높은 순 |
| low-cal | 550kcal 라인 | #22C55E | calories <= 550kcal | 칼로리 낮은 순 |

### 카테고리 필터

| Category | 라벨 | 색상 |
|----------|------|------|
| all | 전체 | - |
| seafood | 해산물 | #3B82F6 |
| meat | 육류 | #EF4444 |
| rice | 밥류 | #F59E0B |
| noodle | 면류 | #F97316 |

### 고급 필터

| 필터 | 범위 | 기본값 |
|------|------|--------|
| 최소 단백질 | 0-40g | 0g |
| 최대 나트륨 | 200-700mg | 700mg |
| 최대 칼로리 | 300-700kcal | 700kcal |

---

## 파일 구조

### 신규 생성 파일

```
godo-dev-module/
├── Component/DietFinder/
│   └── DietFinder.php              # 메뉴 데이터 로드/변환

godo-skin/mobile/medisola_dev/
├── goods/
│   └── goods_view_custom_diet.html # 모바일 HTML 템플릿
├── css/
│   └── custom-diet.css             # 모바일 CSS
└── js/
    └── custom-diet-builder.js      # 모바일 JS

godo-skin/front/medisola_dev/
├── goods/
│   └── goods_view_custom_diet.html # 데스크톱 HTML 템플릿
├── css/
│   └── custom-diet.css             # 데스크톱 CSS
└── js/
    └── custom-diet-builder.js      # 데스크톱 JS

migrations/
├── 002_alter_es_addGoods_nutrition.sql  # 영양 정보 컬럼
├── 003_alter_es_addGoods_custom_diet.sql # V0 UI용 추가 필드
└── 004_alter_goods_isCustomDiet.sql      # 상품 플래그
```

### 수정 파일

```
godo-dev-module/Controller/Front/Goods/GoodsViewController.php
godo-dev-module/Controller/Mobile/Goods/GoodsViewController.php
```

---

## 주문 프로세스 통합

### 기존 주문 플로우 (유지)

```
[V0 UI] 메뉴 선택 완료
    ↓
[buildFormInputs()] componentGoods hidden inputs 생성
    ↓
Form Submit → ../order/cart_ps.php (mode=cartIn)
    ↓
장바구니 담기 성공
    ↓
┌─────────────────────────────────────┐
│ cartMode='' → ../order/cart.php    │  장바구니로 이동
│ cartMode='d' → ../order/order.php  │  바로구매로 이동
└─────────────────────────────────────┘
```

### Form 구조 (layer_option.html 패턴)

```html
<form id="frmView" method="post" action="../order/cart_ps.php">
  <input type="hidden" name="mode" value="cartIn" />
  <input type="hidden" name="cartMode" value="" />
  <input type="hidden" name="goodsNo[]" value="{goodsNo}" />
  <input type="hidden" name="optionSno[]" value="{10식/20식/40식 옵션}" />
  <input type="hidden" name="goodsCnt[]" value="1" />

  <!-- componentGoods (JS에서 동적 생성) -->
  <div id="componentGoodsInputs">
    <input type="hidden" name="componentGoodsNo[0][]" value="{addGoodsNo}" />
    <input type="hidden" name="componentGoodsCnt[0][]" value="{count}" />
    <input type="hidden" name="componentGoodsAddedPrice[0][]" value="0" />
    <input type="hidden" name="componentGoodsName[0][]" value="{menuName}" />
  </div>
</form>
```

---

## 구현 Phase

### Phase 1: DB 준비 ✅ (완료)

- [x] Migration 002: `es_addGoods` 영양 정보 컬럼
  - `nutrition_calories`, `nutrition_protein`, `nutrition_carbs`, `nutrition_fat`, `nutrition_sodium`, `nutrition_sugar`, `nutrition_cholesterol`, `nutrition_omega3`
- [x] Migration 003: `es_addGoods` V0 UI용 추가 필드
  - `name_en`, `category`, `product_weight`, `nutrition_tags`, `disease_type`, `is_new`, `recommend_reasons`
- [x] Migration 004: `es_goods.isCustomDiet` 플래그
  - 나만의 식단 플랜 상품 식별용

**파일 위치:**
- [migrations/002_alter_es_addGoods_nutrition.sql](migrations/002_alter_es_addGoods_nutrition.sql)
- [migrations/003_alter_es_addGoods_custom_diet.sql](migrations/003_alter_es_addGoods_custom_diet.sql)
- [migrations/004_alter_goods_isCustomDiet.sql](migrations/004_alter_goods_isCustomDiet.sql)

---

### Phase 2: 백엔드 구현 ✅ (완료)

#### DietFinder 컴포넌트
- [x] `getMenuItemsForCustomDiet()` - addGoods → V0 MenuItem 형식 변환
  - `goodsView['addGoods']` 배열에서 "메뉴" 그룹 추출
  - 영양 정보 매핑 및 라인 계산
- [x] `calculateLines()` - 4가지 영양 라인 자동 판정
  - 고단백 (protein >= 20g)
  - 저나트륨 (sodium <= 400mg)
  - 오메가3 (omega3 >= 500mg)
  - 550kcal이하 (calories <= 550)
- [x] `calculateHealthIndicators()` - 건강 지표 배지
  - 저당 (sugar <= 4g)
  - 오메가3 풍부 (omega3 >= 1000mg)
  - 고단백 (protein >= 20g)
  - 저콜레스테롤 (cholesterol <= 50mg)
- [x] `buildImageUrl()` - 고도몰 Storage 시스템 활용
- [x] `getGoodsOptions()` - 10식/20식/40식 옵션 조회
- [x] `getConditionsFromResponse()` - responseSno 기반 추천 로직 (V1.1 예정)

**파일 위치:**
- [godo-dev-module/Component/DietFinder/DietFinder.php](godo-dev-module/Component/DietFinder/DietFinder.php)

#### GoodsViewController 수정
- [x] Front 버전: 나만의 식단 플랜 감지 및 데이터 전달
  - `tpls` 변수로 헤더/푸터 프로그래매틱 제어
  - JSON 데이터 템플릿 전달 (`addGoodsNutritionJson`, `optionsJson`)
  - 템플릿 전환: `goods_view_custom_diet.html`
- [x] Mobile 버전: 동일 로직 적용

**파일 위치:**
- [godo-dev-module/Controller/Front/Goods/GoodsViewController.php:86-134](godo-dev-module/Controller/Front/Goods/GoodsViewController.php#L86-L134)
- [godo-dev-module/Controller/Mobile/Goods/GoodsViewController.php](godo-dev-module/Controller/Mobile/Goods/GoodsViewController.php)

---

### Phase 3: 모바일 프론트엔드 ✅ (완료)

- [x] **HTML 구조** (`goods_view_custom_diet.html`)
  - V0 디자인 순서: Header → Category Filters → Main Content
  - Main Content: Suggestion Card → Quantity Selector → Selected Preview → Menu Sections
  - Bottom CTA Button (고정)
  - Advanced Filters Modal (Bottom Sheet 스타일)

- [x] **CSS 스타일** (`custom-diet.css?v=5`)
  - V0 색상 변수 정의 (`:root`)
  - 모바일 레이아웃: Vertical Stack
  - Sticky Selected Preview with Backdrop Blur
  - 고도몰 전역 CSS 충돌 해결 (`.diet-page` 접두어 + `!important`)

- [x] **JavaScript 로직** (`custom-diet-builder.js`)
  - State 관리: `state` 객체
  - 필터링 로직: `getFilteredMenus()`, 카테고리/고급 필터
  - 렌더링 함수: `renderMenuSections()`, `renderSelectedPreview()`, `renderSummary()`
  - 수량 조절: `adjustQuantity()`, `handleQuantityChange()`
  - 주문 처리: `buildFormInputs()`, `addToCart()`, `buyNow()`

**파일 위치:**
- [godo-skin/mobile/medisola_dev/goods/goods_view_custom_diet.html](godo-skin/mobile/medisola_dev/goods/goods_view_custom_diet.html)
- [godo-skin/mobile/medisola_dev/css/custom-diet.css](godo-skin/mobile/medisola_dev/css/custom-diet.css)
- [godo-skin/mobile/medisola_dev/js/custom-diet-builder.js](godo-skin/mobile/medisola_dev/js/custom-diet-builder.js)

---

### Phase 4: 데스크톱 프론트엔드 ✅ (완료)

- [x] **HTML 구조** (`goods_view_custom_diet.html`)
  - Desktop Layout: Sidebar + Main Content
  - Sidebar: 헤더, Suggestion (Collapsible), Quantity, Selected List, CTA
  - Main: Desktop Header (Category Filters + Filter Button), Menu Grid

- [x] **CSS 스타일** (`custom-diet.css?v=2`)
  - Desktop 전용: `.desktop-layout`, `.desktop-sidebar`, `.main-content`
  - Responsive: `@media (min-width: 1024px)`
  - CSS Grid: Menu Sections

- [x] **JavaScript 로직** (`custom-diet-builder.js`)
  - `isDesktop` 플래그 분기 처리
  - Desktop/Mobile 렌더링 타겟 분리
  - 동일한 State 및 비즈니스 로직 공유

**파일 위치:**
- [godo-skin/front/medisola_dev/goods/goods_view_custom_diet.html](godo-skin/front/medisola_dev/goods/goods_view_custom_diet.html)
- [godo-skin/front/medisola_dev/css/custom-diet.css](godo-skin/front/medisola_dev/css/custom-diet.css)
- [godo-skin/front/medisola_dev/js/custom-diet-builder.js](godo-skin/front/medisola_dev/js/custom-diet-builder.js)

---

### Phase 5: 테스트 및 검증 🚧 (진행 중)

#### 데이터베이스 설정
- [ ] **Migration 실행**
  - PHP MySQL 웹 인터페이스 또는 CLI로 SQL 실행
  - 002, 003, 004 순서대로 적용

- [ ] **테스트 데이터 준비**
  - 기존 상품 중 하나를 `isCustomDiet = 1`로 설정
  - "메뉴" 그룹에 addGoods 추가 (최소 10개 이상)
  - 각 addGoods에 영양 정보 입력

#### UI/UX 테스트
- [ ] **모바일 UI 검증**
  - V0 디자인 비교: 레이아웃, 색상, 간격, 폰트
  - 필터 동작: 카테고리, 고급 필터 슬라이더
  - 메뉴 선택/해제: 카드 클릭, 수량 배지, 선택 미리보기
  - Sticky Selected Preview: 스크롤 시 고정, backdrop blur 효과
  - Bottom CTA: 선택 수량에 따른 활성화/비활성화
  - Advanced Filters Modal: Bottom Sheet 애니메이션

- [ ] **데스크톱 UI 검증**
  - Sidebar 고정, Main Content 스크롤
  - Desktop Header: Category Filters 가로 배치
  - Menu Grid: 2-3 columns 반응형
  - Suggestion Card Collapsible 동작

#### 기능 테스트
- [ ] **수량 변경 테스트**
  - 10식 → 20식 → 40식 전환
  - 선택 메뉴 수량 자동 스케일링 (2배, 4배)
  - 옵션 sno 업데이트 확인
  - 가격 재계산

- [ ] **장바구니 담기**
  - `buildFormInputs()` 실행 → componentGoods 파라미터 생성
  - Form Submit → `../order/cart_ps.php?mode=cartIn`
  - 장바구니 페이지에서 메인 상품 + 선택 메뉴 확인

- [ ] **바로구매**
  - `cartMode='d'` 설정
  - 주문서 페이지로 이동
  - 선택 메뉴 정확히 반영되는지 확인

- [ ] **주문 완료**
  - 결제 프로세스 진행
  - 주문 내역에 메뉴 구성 정확히 기록되는지 확인

#### 예외 처리 테스트
- [ ] 메뉴 데이터 없을 때
- [ ] 모든 메뉴가 필터로 걸러졌을 때
- [ ] 수량 초과 선택 시도
- [ ] 네트워크 오류 시 에러 메시지

---

### Phase 6: 운영 배포 📅 (예정)

#### 백엔드 배포
- [ ] **godo-dev-module → godo-module 복사**
  - `Component/DietFinder/DietFinder.php`
  - `Controller/Front/Goods/GoodsViewController.php`
  - `Controller/Mobile/Goods/GoodsViewController.php`

#### 프론트엔드 배포
- [ ] **medisola_dev → drorganic_24_renewal (Front)**
  - `goods/goods_view_custom_diet.html`
  - `css/custom-diet.css`
  - `js/custom-diet-builder.js`

- [ ] **medisola_dev → dorganic_24_renewal (Mobile)**
  - `goods/goods_view_custom_diet.html`
  - `css/custom-diet.css`
  - `js/custom-diet-builder.js`

#### 데이터베이스 마이그레이션
- [ ] 운영 DB에 Migration 002, 003, 004 실행
- [ ] 운영 상품 데이터 마이그레이션 (영양 정보 입력)

#### 운영 환경 테스트
- [ ] 실제 도메인에서 전체 플로우 재검증
- [ ] 모바일/데스크톱 크로스 브라우저 테스트
- [ ] 결제 테스트 (실제 PG 연동)
- [ ] 성능 모니터링 (로딩 속도, 메모리 사용량)

---

## JavaScript 모듈 구조

### State 객체

```javascript
var state = {
  goodsNo: '',           // 상품 번호
  totalQuantity: 10,     // 선택 수량 (10/20/40)
  selections: {},        // { menuId: count }
  showAdvanced: false,   // 고급 필터 표시
  advancedFilters: {     // 고급 필터 값
    minProtein: 0,
    maxSodium: 700,
    maxCalories: 700
  },
  categoryFilter: 'all', // 카테고리 필터
  selectedMenuItem: null,// 상세 모달 대상
  currentScreen: 'menu', // 현재 화면 (menu/summary)
  menuData: [],          // 메뉴 데이터 (서버에서 전달)
  options: [],           // 옵션 데이터
  selectedOptionSno: null,// 선택된 옵션 sno
  isDesktop: false       // 데스크톱 여부
};
```

### 주요 함수

| 함수 | 설명 |
|------|------|
| `init(config)` | 초기화 및 이벤트 바인딩 |
| `getFilteredMenus()` | 필터링된 메뉴 목록 |
| `getGroupedMenus()` | 영양 라인별 그룹화 |
| `adjustQuantity(id, delta)` | 메뉴 수량 조절 |
| `handleQuantityChange(qty)` | 총 수량 변경 (10/20/40) |
| `renderMenuSections()` | 메뉴 섹션 렌더링 |
| `renderSelectedPreview()` | 선택 미리보기 렌더링 |
| `renderSummary()` | 요약 화면 렌더링 |
| `buildFormInputs()` | componentGoods hidden inputs 생성 |
| `addToCart()` | 장바구니 담기 (AJAX) |
| `buyNow()` | 바로구매 (Form Submit) |

---

## CSS 변수

```css
:root {
  --cd-background: #f8f9fc;
  --cd-foreground: #1a1a2e;
  --cd-card: #ffffff;
  --cd-primary: #3e4e97;
  --cd-primary-foreground: #ffffff;
  --cd-secondary: #f1f5f9;
  --cd-muted-foreground: #64748b;
  --cd-border: #e2e8f0;
  --cd-destructive: #ef4444;
  --cd-radius: 1rem;

  /* 영양 라인 색상 */
  --cd-line-protein: #EF4444;
  --cd-line-sodium: #3B82F6;
  --cd-line-omega3: #06B6D4;
  --cd-line-lowcal: #22C55E;
  --cd-badge-lowsugar: #505050;

  /* Desktop 사이드바 */
  --cd-sidebar-width: 320px;
  --cd-sidebar-width-xl: 384px;
}
```

---

## 테스트 체크리스트

### 메뉴 선택 테스트

- [ ] 메뉴 카드 클릭 시 수량 증가
- [ ] 선택 미리보기에 메뉴 표시
- [ ] 수량 배지 업데이트
- [ ] 총 선택 수량 표시
- [ ] 수량 초과 시 추가 불가

### 필터링 테스트

- [ ] 카테고리 필터 동작
- [ ] 고급 필터 토글
- [ ] 슬라이더 값 변경
- [ ] 필터 적용/초기화
- [ ] 조건에 맞는 메뉴 없을 때 메시지

### 수량 변경 테스트

- [ ] 10식 → 20식 변경
- [ ] 선택 수량 비례 스케일링
- [ ] 옵션 sno 업데이트
- [ ] 가격 재계산

### 주문 테스트

- [ ] 장바구니 담기 → cart.php 이동
- [ ] 바로구매 → order.php 이동
- [ ] componentGoods 파라미터 확인
- [ ] 결제 완료

---

## 기술적 도전과 해결책

### 도전 #1: 헤더/푸터 표시 제어

**문제:**
- 고도몰 어드민에서 "상단감춤" 설정했으나 상품 페이지에서 계속 표시됨
- 일반 페이지는 작동하는데 `goods_view_custom_diet.html`만 안 됨

**원인 분석:**
- 일반 페이지: `layout_design_map_mobile.json` 읽어서 `tpls` 변수 자동 설정
- 상품 페이지: `GoodsViewController`가 JSON 파일 읽지 않음

**시도한 해결책:**
1. ❌ HTML에 `display:none` 하드코딩 → 비권장 패턴
2. ❌ JavaScript로 헤더 숨김 → DOM 깜빡임 발생
3. ✅ **Controller에서 `tpls` 변수 설정** → 고도몰 공식 방식

**최종 솔루션:**
```php
// GoodsViewController.php:124-129
$this->setData('tpls', [
    'header_inc' => false,  // 상단 감춤
    'footer_inc' => false   // 하단 감춤
]);
```

**교훈:**
- 고도몰은 데이터 기반 템플릿 시스템
- 하드코딩보다 프레임워크 메커니즘 활용 필수

---

### 도전 #2: V0 디자인 정렬

**문제:**
- 초기 모바일 레이아웃이 V0 디자인과 순서/구조 상이
- Suggestion Card 위치, Selected Preview 스타일 불일치

**해결 과정:**
1. V0 소스 코드 분석 (`/Users/js.lee/Projects/medisola-custom-diet`)
2. V0의 `menu-selection-screen.tsx` 구조 확인
3. HTML 재구성:
   ```
   V0 순서:
   Header → Category Filters → Main Content (Suggestion → Quantity → Selected Preview → Menu Sections)

   초기 구현:
   Header → Suggestion → Menu Sections (Selected Preview가 Sidebar에만 있음)
   ```
4. CSS 스타일 매칭:
   - Sticky Selected Preview: `position: sticky; top: 0; backdrop-filter: blur(8px)`
   - Category Filters: Horizontal scroll, chip 스타일
   - Advanced Filters: Center modal → Bottom Sheet 스타일로 변경

**결과:**
- 모바일 레이아웃 V0와 1:1 매칭
- Desktop은 Sidebar + Main Grid 레이아웃 유지

---

### 도전 #3: CSS 전역 충돌

**문제 #1: Preview Title 배경**
- "선택한 메뉴" 타이틀 배경이 개발 배너 배경과 동일하게 표시
- 고도몰 전역 CSS의 `.preview-title` 스타일이 적용됨

**문제 #2: 카드 스타일 오염**
- 의도하지 않은 border-radius, box-shadow 적용
- `.preview-*` 클래스에 고도몰 글로벌 카드 스타일 존재

**원인:**
- 고도몰 전역 CSS가 범용 클래스명 (`.preview-*`, `.filter-*`, `.card-*`) 사용
- CSS 우선순위에서 전역 스타일이 커스텀 스타일보다 우선

**해결 전략:**

1. **Body Wrapper Class 추가:**
```html
<body class="diet-page">
  <!-- 전체 커스텀 컴포넌트 -->
</body>
```

2. **CSS 명시적 오버라이드:**
```css
/* BAD: 전역 충돌 발생 */
.selected-preview {
  border-bottom: 1px solid #e2e8f0;
}

/* GOOD: 스코핑 + !important */
.diet-page .selected-preview {
  border-bottom: 1px solid var(--cd-border) !important;

  /* 카드 스타일 명시적 제거 */
  border-radius: 0 !important;
  box-shadow: none !important;
  border-top: none !important;
  border-left: none !important;
  border-right: none !important;
}
```

3. **CSS 버전 캐시 무효화:**
```html
<!-- v=4 → v=5 증가 -->
<link rel="stylesheet" href="../css/custom-diet.css?v=5" />
```

**적용 범위:**
- 모든 커스텀 클래스에 `.diet-page` 접두어 추가
- 충돌 가능성 있는 속성에 `!important` 적용
- 특히 `border-*`, `box-shadow`, `background-*` 주의

**교훈:**
- 대형 레거시 코드베이스에서는 CSS 네임스페이스 필수
- 범용 클래스명 사용 시 항상 충돌 가능성 고려
- `!important` 남용 지양하지만, 레거시 통합에서는 불가피

---

### 도전 #4: ComponentGoods 파라미터 구조

**문제:**
- 고도몰의 "골라담기" 기능 파라미터 구조 문서화 부족
- 어떤 형식으로 데이터를 보내야 하는지 명확하지 않음

**해결:**
- 기존 `layer_option.html` 파일 분석
- Form Submit 시 네트워크 탭에서 파라미터 역공학
- Hidden input 배열 구조 파악:
  ```
  componentGoodsNo[0][] = addGoodsNo
  componentGoodsCnt[0][] = count
  componentGoodsAddedPrice[0][] = 0
  componentGoodsName[0][] = menuName
  ```

**핵심 인사이트:**
- `[0]`은 메인 상품 인덱스 (여러 메인 상품 시 [1], [2]...)
- `[]`는 각 추가 상품 배열
- `componentGoodsAddedPrice`는 추가 가격 (우리는 무료이므로 0)

---

### 도전 #5: 영양 라인 자동 계산

**요구사항:**
- 4가지 영양 라인 (고단백/저나트륨/오메가3/550kcal이하) 자동 판정
- V0의 하드코딩된 라인 데이터를 DB 기반 동적 계산으로 전환

**구현:**
```php
// DietFinder.php:150-165
public function calculateLines($item)
{
    $lines = [];

    $protein = floatval($item['nutrition_protein'] ?? 0);
    $sodium = intval($item['nutrition_sodium'] ?? 999);
    $omega3 = intval($item['nutrition_omega3'] ?? 0);
    $calories = intval($item['nutrition_calories'] ?? 999);

    if ($protein >= 20) $lines[] = '고단백';
    if ($sodium > 0 && $sodium <= 400) $lines[] = '저나트륨';
    if ($omega3 >= 500) $lines[] = '오메가3';
    if ($calories > 0 && $calories <= 550) $lines[] = '550kcal이하';

    return $lines;
}
```

**라인 판정 기준:**
| 라인 | 조건 | 정렬 기준 |
|------|------|-----------|
| 고단백 | protein >= 20g | 단백질 높은 순 |
| 저나트륨 | 0 < sodium <= 400mg | 나트륨 낮은 순 |
| 오메가3 | omega3 >= 500mg | 오메가3 높은 순 |
| 550kcal이하 | 0 < calories <= 550 | 칼로리 낮은 순 |

**JavaScript 필터링:**
```javascript
function getFilteredMenus() {
  return state.menuData.filter(function(menu) {
    // 카테고리 필터
    if (state.categoryFilter !== 'all' && menu.category !== state.categoryFilter) {
      return false;
    }

    // 고급 필터
    if (menu.protein < state.advancedFilters.minProtein) return false;
    if (menu.sodium > state.advancedFilters.maxSodium) return false;
    if (menu.calories > state.advancedFilters.maxCalories) return false;

    return true;
  });
}
```

---

## 완성을 위한 로드맵

### 즉시 실행 (Phase 5 완료)

**우선순위 1: 데이터베이스 설정**
1. Migration 실행
   ```bash
   # SSH 접속 또는 phpMyAdmin 사용
   mysql -u [user] -p [database] < migrations/002_alter_es_addGoods_nutrition.sql
   mysql -u [user] -p [database] < migrations/003_alter_es_addGoods_custom_diet.sql
   mysql -u [user] -p [database] < migrations/004_alter_goods_isCustomDiet.sql
   ```

2. 테스트 상품 설정
   - 기존 정기 결제 상품 중 하나 선택 (예: goodsNo=12345)
   - `UPDATE es_goods SET isCustomDiet = 1 WHERE goodsNo = 12345;`
   - 해당 상품의 addGoods JSON에 "메뉴" 그룹 확인

3. 메뉴 영양 정보 입력
   - "메뉴" 그룹의 각 addGoodsNo에 대해 영양 정보 UPDATE
   - 최소 15개 이상 메뉴 권장 (4가지 라인 각 3개씩)

**우선순위 2: 기본 기능 테스트**
1. 개발 사이트에서 상품 페이지 접속
2. V0 디자인과 비교 (스크린샷)
3. 메뉴 선택/해제 동작 확인
4. 장바구니 담기 테스트
5. 주문서 페이지에서 메뉴 구성 확인

**우선순위 3: 버그 수정 및 개선**
- 발견된 UI 이슈 즉시 수정
- 브라우저 호환성 테스트 (Chrome, Safari, Samsung Internet)
- 성능 최적화 (이미지 lazy loading 확인)

---

### 단기 목표 (1-2주)

#### V1.0 정식 출시
- [ ] 전체 QA 완료
- [ ] 운영 환경 배포 (Phase 6)
- [ ] 실제 고객 사용 모니터링
- [ ] 피드백 수집 및 핫픽스

#### 데이터 마이그레이션
- [ ] 기존 메뉴 데이터에 영양 정보 일괄 입력
  - CSV 파일 준비 또는 API 스크립트 작성
  - 식품의약품안전처 영양 DB 활용 가능
- [ ] 카테고리 태깅 (seafood, meat, rice, noodle)
- [ ] 메뉴 이미지 최적화 (CDN 업로드)

---

### 중기 목표 (1-2개월)

#### V1.1: 식단찾기 퀴즈 연동

**기능:**
- [ ] responseSno 파라미터 처리
  - URL: `goods_view.php?goodsNo=12345&responseSno=789`
- [ ] 퀴즈 결과 기반 추천 라인 표시
  - DietFinder의 `getConditionsFromResponse()` 활용
- [ ] 추천 메뉴 하이라이트
  - 추천 라인 메뉴에 "추천" 배지 표시
  - Suggestion Card에 추천 사유 렌더링

**구현 상태:**
- [x] 백엔드 로직 준비됨 (`getConditionsFromResponse()`)
- [x] Frontend에 `conditionsJson`, `recommendedLinesJson` 전달 중
- [ ] UI 렌더링 로직 구현 필요

**예상 작업:**
```javascript
// renderSuggestionCard() 확장
function renderSuggestionCard() {
  if (!state.recommendedLines || state.recommendedLines.length === 0) {
    $('#mobileSuggestionCard').hide();
    return;
  }

  $('#mobileSuggestionCard').show();

  var html = '<div class="line-recommendations">';
  state.recommendedLines.forEach(function(line, idx) {
    html += '<div class="line-item ' + (idx === 0 ? 'primary' : 'secondary') + '">';
    html += '<div class="line-number">' + (idx + 1) + '</div>';
    html += '<div class="line-info">';
    html += '<p class="line-name">' + line.name + '</p>';
    html += '<p class="line-reason">' + line.reason + '</p>';
    html += '</div></div>';
  });
  html += '</div>';

  $('#suggestionContent').html(html);
}
```

---

#### V1.2: UX 개선

**기능:**
- [ ] 메뉴 상세 모달 개선
  - 영양 정보 차트 (Chart.js 또는 Progress Bar)
  - 재료 정보, 알레르기 정보 표시
  - 관련 메뉴 추천
- [ ] 알레르기 필터링
  - DB 필드 추가: `es_addGoods.allergens` (JSON)
  - Advanced Filters에 알레르기 체크박스 추가
- [ ] 저장된 식단 플랜
  - 로그인 회원의 선택 이력 저장
  - "이전 식단 불러오기" 기능

---

### 장기 목표 (3-6개월)

#### V2.0: AI 기반 추천

**비전:**
- 사용자 건강 데이터 (나이, 성별, 목표, 질환) 기반 AI 추천
- 식단 밸런스 자동 최적화
- 맛 선호도 학습 (리뷰 데이터 활용)

**기술 스택 후보:**
- Python Flask/FastAPI + scikit-learn
- 영양 최적화: Linear Programming (PuLP)
- 추천 시스템: Collaborative Filtering

---

## 세션 재개 가이드

**이 문서를 읽는 AI 또는 개발자를 위한 빠른 시작:**

### 핵심 파일 위치
```
Backend (Development):
- godo-dev-module/Component/DietFinder/DietFinder.php
- godo-dev-module/Controller/Front/Goods/GoodsViewController.php
- godo-dev-module/Controller/Mobile/Goods/GoodsViewController.php

Frontend (Mobile Development):
- godo-skin/mobile/medisola_dev/goods/goods_view_custom_diet.html
- godo-skin/mobile/medisola_dev/css/custom-diet.css?v=5
- godo-skin/mobile/medisola_dev/js/custom-diet-builder.js

Frontend (Desktop Development):
- godo-skin/front/medisola_dev/goods/goods_view_custom_diet.html
- godo-skin/front/medisola_dev/css/custom-diet.css?v=2
- godo-skin/front/medisola_dev/js/custom-diet-builder.js

Database:
- migrations/002_alter_es_addGoods_nutrition.sql
- migrations/003_alter_es_addGoods_custom_diet.sql
- migrations/004_alter_goods_isCustomDiet.sql
```

### 현재 상태 (2026-02-10)
- ✅ Phase 1-4 완료 (DB, 백엔드, 모바일, 데스크톱 프론트엔드)
- 🚧 Phase 5 진행 중 (테스트 및 검증)
- 📅 Phase 6 대기 중 (운영 배포)

### 다음 작업
1. Migration 실행하여 DB 스키마 업데이트
2. 테스트 상품 설정 (isCustomDiet = 1)
3. 개발 환경에서 전체 플로우 테스트
4. 버그 수정 및 최종 QA

### 주요 기술 결정
- **주문 시스템**: ComponentGoods 패턴 (고도몰 기본 골라담기)
- **템플릿 제어**: `tpls` 변수 (하드코딩 금지)
- **CSS 전략**: `.diet-page` 래퍼 + `!important` (레거시 충돌 방지)
- **데이터 전달**: PHP Controller → JSON → JavaScript State

### 문제 해결 참고
- 헤더/푸터 표시 문제 → "기술적 도전과 해결책 #1" 참고
- CSS 충돌 → "기술적 도전과 해결책 #3" 참고
- ComponentGoods 파라미터 구조 → "기술적 도전과 해결책 #4" 참고

---

## 참고 링크

- [V0 디자인 소스](/Users/js.lee/Projects/medisola-custom-diet)
- [V0 Live Demo](https://v0-medisola.vercel.app/)
- [고도몰 개발 가이드](https://devcenter-help.nhn-commerce.com/)
- [고도몰 아키텍처 문서](http://doc.godomall5.godomall.com/Getting_Started/Architecture)
- [프로젝트 CLAUDE.md](CLAUDE.md)

---

## 변경 이력

| 날짜 | 버전 | 변경 내용 | 작성자 |
|------|------|-----------|--------|
| 2026-02-06 | 0.1 | 초안 작성 | js.lee |
| 2026-02-09 | 0.5 | Phase 1-4 완료, 테스트 단계 진입 | js.lee |
| 2026-02-10 | 1.0 | 헤더/푸터 제어, V0 정렬, CSS 충돌 해결 완료<br/>기술 도전 사례 및 고도몰 패턴 가이드 추가<br/>완성 로드맵 및 세션 재개 가이드 추가 | js.lee + Claude |

---

**프로젝트 시작:** 2026-02-06
**최종 업데이트:** 2026-02-10
**현재 상태:** Phase 5 진행 중 (테스트 및 검증)
**담당:** js.lee
**협업:** Claude Code (Sonnet 4.5)

**문서 목적:**
- 프로젝트 전체 맥락 이해
- 세션 중단 후 재개 시 빠른 context 복구
- 기술적 결정 근거 문서화
- 고도몰 개발 패턴 지식 공유
