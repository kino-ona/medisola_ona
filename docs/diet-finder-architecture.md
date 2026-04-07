# 나만의 식단 찾기 — 시스템 아키텍처 & 구현 현황

> 최종 업데이트: 2025-03-09
> 소스: `DietFinder.php` (693줄), `diet-quiz.js` (866줄), `custom-diet-builder.js` (~1000줄)

---

## 1. 시스템 개요

V0 참조 프로젝트를 기반으로 고도몰 플랫폼에 구현한 "나만의 식단 찾기" 기능.
온보딩 퀴즈 → 영양 분석 결과 → 메뉴 선택 → 주문 요약의 4단계 사용자 여정을 PHP 백엔드 + jQuery IIFE SPA로 구현.

---

## 2. 전체 레이어 구조

```
┌─────────────────────────────────────────────────────────┐
│                    Frontend (godo-skin)                   │
│  ┌──────────────────┐  ┌──────────────────────────────┐ │
│  │  diet-quiz.js     │  │  custom-diet-builder.js      │ │
│  │  (DietQuiz IIFE)  │  │  (CustomDietBuilder IIFE)    │ │
│  │  866줄, 8스텝 SPA │  │  ~1000줄, 메뉴선택+요약     │ │
│  └────────┬─────────┘  └──────────────┬───────────────┘ │
│           │ AJAX                       │ Template var     │
├───────────┼────────────────────────────┼─────────────────┤
│           ▼    Controllers (godo-dev-module)             │
│  ┌──────────────────┐  ┌──────────────────────────────┐ │
│  │ DietQuizPsCtrl   │  │ GoodsViewController          │ │
│  │ DietQuizReportPs │  │ (Front + Mobile)              │ │
│  └────────┬─────────┘  └──────────────┬───────────────┘ │
│           │                            │                  │
├───────────┼────────────────────────────┼─────────────────┤
│           ▼    Component Layer                           │
│  ┌───────────────────────────────────────────────────┐  │
│  │              DietFinder.php (693줄)                │  │
│  │  ┌─────────────┐ ┌────────────┐ ┌──────────────┐ │  │
│  │  │ 메뉴 데이터  │ │ 추천 엔진  │ │ 리포트 생성  │ │  │
│  │  │ getMenuItems │ │ getRecomm  │ │ generateRpt  │ │  │
│  │  │ calcLines    │ │ ByCondition│ │ savQuizResp  │ │  │
│  │  └──────┬──────┘ └─────┬──────┘ └──────┬───────┘ │  │
│  └─────────┼──────────────┼────────────────┼─────────┘  │
├────────────┼──────────────┼────────────────┼────────────┤
│            ▼              ▼                ▼   Database   │
│  ┌──────────────┐ ┌────────────────┐ ┌────────────────┐ │
│  │ es_addGoods   │ │ es_goods       │ │ms_diet_quiz_   │ │
│  │ (영양+메뉴)   │ │ (상품마스터)   │ │responses(퀴즈) │ │
│  └──────────────┘ └────────────────┘ └────────────────┘ │
└─────────────────────────────────────────────────────────┘
```

---

## 3. 사용자 여정 데이터 흐름

### [1단계] 퀴즈 진입

```
/guide/diet_quiz.php?goodsNo=XXX
→ DietQuizController → goodsNo를 템플릿 전달
→ diet_quiz.html → DietQuiz.init({goodsNo, isMobile})
```

### [2단계] 퀴즈 8스텝 (클라이언트 SPA)

```
Entry → Gender(자동이동) → BirthYear → Height → Weight → Condition → Analysis → Report
```

- state 객체: `{ gender, birthYear, height, weight, conditions[] }`
- 모든 화면 전환은 `setStep()` → `render()` 라우터로 처리

### [3단계] Analysis 화면 (병렬 처리)

```
┌─ 4단계 애니메이션 (800ms/step, 총 3.2초)
└─ AJAX POST → diet_quiz_report_ps.php
   → DietFinder::generateNutritionReport()
   → JSON { bmi, coachingMessages, statusCards, strategies, recommendedLines }

※ 둘 다 완료 시 → Report 화면 전환
```

- `fetchReportData()`: AJAX 호출, 완료 시 `state.isReportReady = true`
- `startAnalysisAnimation()`: 4스텝 UI 애니메이션, 완료 시 `state._animationDone = true`
- 양쪽 모두 완료되면 `setStep('report')` 자동 호출

### [4단계] Report 화면 (백엔드 데이터 렌더링)

- `state.reportData`를 순수 렌더링 (프론트엔드 연산 없음)
- BMI 시각화 + 코칭 메시지 + 전략 카드 + 추천 라인 표시
- CTA 클릭 → `saveAndRedirect()`

### [5단계] 퀴즈 저장 + 리다이렉트

```
AJAX POST → diet_quiz_ps.php
→ DietFinder::saveQuizResponse()
→ INSERT ms_diet_quiz_responses → responseSno 반환
→ redirect → /goods/goods_view.php?goodsNo=XXX&responseSno=YYY
```

### [6단계] 메뉴 선택 화면

```
GoodsViewController::index()
→ getMenuItemsForCustomDiet() → V0 MenuItem[] (20+ 필드)
→ getConditionsFromResponse(responseSno) → 퀴즈 데이터 복원
→ Template에 JSON 주입 → CustomDietBuilder.init()
```

### [7단계] 메뉴 선택 → 주문

```
사용자가 메뉴 선택 (adjustQuantity → updateCardStates)
→ renderSummary() (매크로차트, 기대효과, 구매안내)
→ buildFormInputs() → 장바구니/바로구매
```

---

## 4. 핵심 알고리즘

### 4-1. 영양 라인 분류 (`calculateLines`)

| 라인 | 조건 | 임계값 |
|------|------|--------|
| 고단백 | protein ≥ 20g | 20g |
| 저나트륨 | 0 < sodium ≤ 450mg | 450mg |
| 오메가3 | omega3 ≥ 200mg | 200mg |
| 440kcal 라인 | 0 < calories ≤ 500 | 500kcal (경계) |
| 550kcal 라인 | calories > 500 | 500kcal (경계) |
| 저당 | sugar ≤ 5g | 5g |
| 고식이섬유 | fiber ≥ 3g | 3g |
| 저지방 | 0 < fat ≤ 5g | 5g |
| 저포화지방 | 0 ≤ saturatedFat ≤ 1.5g | 1.5g |
| 저콜레스테롤 | 0 ≤ cholesterol ≤ 30mg | 30mg |
| 저탄수화물 | 0 < carbs ≤ 40g | 40g |

### 4-2. 통합 전략→추천라인 알고리즘 (`generateNutritionReport` 내부)

8개 전략 풀(strategyPool) + 9개 조건 매핑(condStrategyMap)으로 전략 카드와 추천 라인을 동시 결정.
440kcal은 기본값으로 별도 추천하지 않고, 고칼로리가 필요한 경우(근육증량, 암투병)만 550kcal 라인을 추천.
각 전략은 `linkedLine` 필드로 하나의 영양 라인과 1:1 연결.
단일 루프에서 조건별 전략을 순서대로 추가하며, 중복 제거 후 최대 3개 반환.
omega3가 누락되고 3개 미만이면 자동 추가(fallback).
상세 매핑은 `nutrition-report-algorithm.md` 참조.

### 4-3. 영양 분석 결과 생성 (`generateNutritionReport`)

- BMI = weight / (height/100)² → 한국 기준 분류 (23 이상 = 과체중)
- BMR = Mifflin-St Jeor 공식 (성별 분기) → 일일칼로리 = BMR × 1.375 (비질환자) / 1.2 (질환자)
- 코칭 메시지 = f(연령대, 성별, BMI 상태) → 3~4개 단락
- 전략 + 추천라인 = f(conditions) → 통합 알고리즘으로 최대 3개씩 동시 결정

### 4-4. 프론트엔드 메뉴 그룹핑 (`getGroupedMenus`)

- 9개 영양 라인별 `menu.lines` 배열 매칭
- 각 그룹 내 라인별 정렬 (고단백→단백질↓, 저나트륨→나트륨↑ 등)
- 수평 스크롤 섹션으로 렌더링

### 4-5. 경량 DOM 업데이트 (`updateCardStates`)

- 메뉴 선택 시 전체 DOM 재빌드 없이 `.selected` 토글 + 수량 뱃지만 갱신
- `badgePop` (scale spring) + `badgeNumIn` (translateY slide) CSS 애니메이션

---

## 5. 파일 구조 & 의존성 맵

### Backend (godo-dev-module)

| 파일 | 줄 수 | 역할 |
|------|-------|------|
| `Component/DietFinder/DietFinder.php` | 693 | 핵심 비즈니스 로직 전체 |
| `Controller/Front/Guide/DietQuizController.php` | 18 | GET /guide/diet_quiz.php |
| `Controller/Front/Guide/DietQuizPsController.php` | 54 | POST 퀴즈 저장 → responseSno |
| `Controller/Front/Guide/DietQuizReportPsController.php` | 46 | POST 리포트 생성 → JSON |
| `Controller/Mobile/Guide/DietQuizController.php` | — | extends Bundle\Mobile\Controller |
| `Controller/Mobile/Guide/DietQuizPsController.php` | — | extends Front (302 방지) |
| `Controller/Mobile/Guide/DietQuizReportPsController.php` | — | extends Front |
| `Controller/Front/Goods/GoodsViewController.php` | — | 메뉴 데이터 + responseSno 처리 |

### Frontend (godo-skin, Desktop + Mobile 동일 코드)

| 파일 | 줄 수 | 역할 |
|------|-------|------|
| `guide/diet_quiz.html` | 33 | 퀴즈 SPA 껍데기 |
| `goods/goods_view_custom_diet.html` | — | 메뉴선택 템플릿 |
| `js/diet-quiz.js` | 866 | 퀴즈 IIFE (8스텝 + AJAX 2개) |
| `js/custom-diet-builder.js` | ~1000 | 메뉴선택 IIFE (선택+요약+주문) |
| `css/diet-quiz.css` | — | 퀴즈 전용 (.dq-*, .dqr-*) |
| `css/custom-diet.css` | — | 메뉴선택 전용 (.cd-*) |

### DB 테이블

| 테이블 | 용도 |
|--------|------|
| `es_addGoods` | 메뉴 마스터 (영양소 11칼럼 + UI 7칼럼 추가) |
| `es_goods` | 상품 마스터 (addGoods JSON으로 메뉴 그룹 참조) |
| `es_goods_option` | 식수 옵션 (10식/20식/40식) |
| `ms_diet_quiz_responses` | 퀴즈 응답 저장 (sno=responseSno) |

---

## 6. AJAX 엔드포인트

### POST /guide/diet_quiz_ps.php — 퀴즈 저장

**요청**:
```
gender=male&birthYear=1990&height=175&weight=70&conditions=["diabetes","weight-loss"]
```

**응답**:
```json
{"success": true, "responseSno": 12345}
```

### POST /guide/diet_quiz_report_ps.php — 리포트 생성

**요청**: 동일

**응답**:
```json
{
  "success": true,
  "report": {
    "bmi": {"value": 22.9, "status": "정상", "statusKey": "normal", "dailyCalories": 2200, "proteinG": 98, "age": 35},
    "coachingMessages": ["남성, 만 35세, 175cm / 70kg 기준으로...", "30대부터는..."],
    "statusCards": [{"icon": "heart", "title": "건강 체중 유지", "description": "...", "color": "#10B981"}],
    "strategies": [{"icon": "trendingUp", "label": "단백질 섭취 강화", "target": "최소 98g/일", "color": "#EF4444"}],
    "recommendedLines": [{"label": "저당 라인", "color": "#8B5CF6", "reason": "...", "benefits": ["혈당 안정", ...]}],
    "conditionLabels": ["당뇨", "체중 감량"],
    "brandMessage": {"text": "메디쏠라는...", "tagline": "추천이 아니라, 케어를 제공합니다."},
    "cta": {"label": "이 전략으로 식단 플랜 시작", "subtext": "..."}
  }
}
```

---

## 7. 구현 완료 현황

### Phase A: 메뉴 선택 화면 ✅

- 10개 영양 라인 분류 + 조건 기반 추천 엔진
- Desktop 사이드바 + Mobile 풀스크린 레이아웃
- 카테고리/고급 필터, 건강지표 배지, 추천사유 표시
- componentGoods 주문 연동 (장바구니/바로구매)
- DB 마이그레이션 001~005

### Phase B: 요약 화면 ✅

- 플랜 이름 카드 (질환별 PLAN_NAMES 매핑)
- 매크로 도넛 차트 (SVG buildSvgDonut)
- 하루 식사 실행 가이드 (아침/점심/저녁/간식 타임라인)
- 기대 효과 (getSummaryExpectedEffects)
- 구매 안내 아코디언 (배송/교환/환불)

### Phase C: 온보딩 퀴즈 ✅

- 8스텝 SPA: Entry → Gender → BirthYear → Height → Weight → Condition → Analysis → Report
- 스크롤 피커 (scroll-snap + 양방향 동기화)
- 조건 선택 (건강관리 3종 + 질환 5종 + 신장 하위옵션)
- 컨트롤러 6개 (Front/Mobile × Quiz/QuizPs/QuizReportPs)

### Phase D: 영양 분석 결과 ✅

- 리포트 데이터 백엔드 단일 소스 (generateNutritionReport)
- 프론트엔드는 순수 렌더러 (state.reportData 표시만)
- Analysis ↔ Report 병렬 로딩 (애니메이션 + AJAX 동시 실행)
- BMI 시각화 바 + 코칭 + 전략 + 추천라인 + 브랜드 메시지
- 메뉴 선택 뱃지 spring 애니메이션 (깜빡임 수정 포함)

### Phase E: 테스트 & 운영 배포 — 미착수

- DB 마이그레이션 실행 (개발 → 운영)
- 메뉴 영양 데이터 입력
- E2E 테스트 (Desktop + Mobile)
- Production 배포 (medisola_dev → drorganic_24_renewal / dorganic_24_renewal)
- Backend 배포 (godo-dev-module → godo-module)
