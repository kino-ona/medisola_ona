# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a **Godo Shopping Mall Skin** project for Medisola, a Korean e-commerce platform focused on health food and nutrition. The repository contains multiple frontend themes/skins for different brand variations.

## Project Structure

### Frontend Architecture
- **Multiple Theme System**: Each brand has its own skin directory under `/front/`
  - `drorganic_24_renewal/`: Main active theme with latest features
  - `medisola_dev/`: Development/testing theme
  - `8design_Copy/`, `awesome/`, `clematis/`, `moment/`: Legacy/alternative themes
- **Mobile Support**: Mobile versions located in `/mobile/` directory
- **Backup System**: Historical backups stored in `/backup/` directory

### Key Directories Per Theme
- `css/`: Stylesheets including `custom.css`, `reset.css`, brand-specific styles
- `js/`: JavaScript files with `gd_` prefix for Godo-specific functionality
- `main/`: Main page templates and layouts
- `goods/`: Product-related pages and functionality
- `member/`: User authentication and membership pages
- `mypage/`: User account management pages
- `order/`: Shopping cart and checkout processes
- `board/`: Community features and reviews
- `outline/`: Header, footer, and shared components

### Global Configuration
- `gnb.js` / `gnb_dev.js`: Global navigation menu configuration with product categories
- Navigation supports multi-level menus with color coding for different product lines

### Technology Stack
- **Platform**: Godo Shopping Mall (Korean e-commerce platform)
- **Frontend**: HTML, CSS, JavaScript (jQuery-based)
- **Analytics**: Amplitude integration for event tracking
- **UI Libraries**: Swiper.js for carousels, custom modal systems
- **Payment Integration**: Korean payment gateways (Naver Pay, KakaoPay, etc.)

### Key Features
- Korean language support
- Product categories: 새벽배송 (dawn delivery), 샐러드 (salads), 건강케어 (health care), 질환케어 (disease care)
- Gift ordering system with custom card selection
- Scheduled delivery functionality
- Plus review system with photo uploads
- Membership tiers and benefits system

## Development Notes

### File Naming Conventions
- Templates use `.html` extension but contain PHP-style template syntax
- Partial templates prefixed with `_` (e.g., `_header.html`, `_footer.html`)
- Godo-specific JavaScript files prefixed with `gd_`
- Layer/modal popups prefixed with `layer_`

### Analytics Implementation
- Amplitude events for user behavior tracking
- Event naming follows pattern: `home_*`, `product_*`, `order_*`
- Track banner clicks, product views, and conversion funnel

### Styling Architecture
- CSS reset and base styles in `reset.css`
- Theme-specific styles in `custom.css`
- Component-specific stylesheets (e.g., `brand.css`, `medisola_intro.css`)
- Responsive design with mobile-first approach

### JavaScript Architecture
- jQuery-based with Godo platform integration
- Modular approach with separate files for different functionalities
- Common utilities in `gd_common.js`
- Platform-specific integrations (Apple Pay, Naver, Kakao) in separate modules

## Godo Template Syntax

### Template Tag Structure
- **Delimiters**: Use `{ }` for template tags
- **Valid formats**: `{ }`, `<!--{ }-->`, `{ }-->`, `<!--{ }`
- **File headers**: Templates start with `{*** description | file_path ***}`

### Variable Assignment
- **Syntax**: `{variable_name}` or `{=variable_name}`
- **Assignment**: Variables assigned in PHP using `$tpl->assign()`
- **Example**: `{title}`, `{goods.goodsNm}`, `{member.memId}`

### Loops
- **Syntax**: `{@loop_id}...{/@}`
- **Navigation variables**:
  - `{index_}`: Current iteration (0-based)
  - `{size_}`: Total loop count
  - `{key_}`: Array key
  - `{value_}`: Array value
- **Nested access**: Use `.` notation (e.g., `{goods.goodsNm}`)

### Conditionals
- **Syntax**: `{?expression}...{:}...{/?}`
- **Multiple conditions**: Use `{:}` for else/elseif blocks
- **Nested conditionals**: Fully supported
- **Example**: `{?goods.goodsDiscount > 0}Sale!{:}Regular{/?}`

### Includes
- **Syntax**: `{#file_id}`
- **Purpose**: Include partial templates
- **Common includes**: `{#header}`, `{#footer}`, `{#_header_search}`

### Comments
- **Inline**: `{code // comment}`
- **Block**: `{* comment *}`

### Template Development Best Practices
- Use meaningful variable names that reflect data structure
- Leverage nested loop navigation for complex data
- Utilize built-in loop variables for dynamic rendering
- Keep template logic simple - complex logic should be in PHP
- Use partial templates for reusable components

### Common Godo Variables
Based on codebase analysis and documentation:

**System Variables:**
- `{=gMall.mallNm}`: Mall name
- `{=gMall.companyNm}`: Company name
- `{=gMall.phone}`, `{=gMall.email}`: Contact information
- `{=gMall.address}`, `{=gMall.zonecode}`: Address details
- `{=gThisPageName}`: Current page identifier
- `{=gMobile.mobileShopFl}`: Mobile shop flag

**Session Variables:**
- `{=gSess.memId}`, `{=gSess.memNm}`: Member ID and name
- `{=gSess.groupSno}`, `{=gSess.groupNm}`: Member group info
- `{=gSess.loginCnt}`: Login count statistics

**Data Variables:**
- `{goods.*}`: Product-related data
- `{member.*}`: User/member information  
- `{order.*}`: Order and cart data
- `{board.*}`: Board/community content
- `{layout.*}`: Layout and theme settings

### Built-in Functions
**String Functions:**
- `{=substr()}`, `{=strlen()}`: String manipulation
- `{=json_encode()}`: JSON conversion
- `{=number_format()}`: Number formatting

**Array Functions:**
- `{=count()}`: Count array elements
- `{=in_array()}`: Check array membership

**Validation Functions:**
- `{=empty()}`, `{=isset()}`: Check variable state

**Godo-Specific Functions:**
- `{=gd_html_image()}`: Render images with proper formatting
- `{=gd_session()}`: Access session data
- `{=dataCartGoodsCnt()}`: Get shopping cart item count
- `{=gd_get_login_name()}`: Get logged-in user display name

## 🚨 CRITICAL DEVELOPMENT RULES

### **MANDATORY DUAL IMPLEMENTATION RULE**
**⚠️ ALWAYS implement features in BOTH desktop AND mobile versions simultaneously:**

1. **Desktop Path**: `/front/medisola_dev/`
2. **Mobile Path**: `/mobile/medisola_dev/`

**When modifying ANY feature:**
- ✅ **HTML templates**: Update both desktop and mobile versions
- ✅ **JavaScript logic**: Sync both `front/medisola_dev/js/` AND `mobile/medisola_dev/js/`
- ✅ **CSS styling**: Apply to both desktop and mobile stylesheets
- ✅ **Template variables**: Ensure data attributes exist in both platforms

**Never assume mobile doesn't have a feature - it always does!**

### **Dual Implementation Checklist**
For every modification, verify:
- [ ] Desktop template updated
- [ ] Mobile template updated  
- [ ] Desktop JavaScript updated
- [ ] Mobile JavaScript updated
- [ ] Both versions tested for consistency

### **File Pair Mapping**
Common file pairs that must be kept in sync:
- `front/medisola_dev/goods/goods_view.html` ↔ `mobile/medisola_dev/goods/goods_view.html`
- `front/medisola_dev/goods/layer_option.html` ↔ `mobile/medisola_dev/goods/layer_option.html`
- `front/medisola_dev/js/gd_goods_view.js` ↔ `mobile/medisola_dev/js/gd_goods_view.js`
- `front/medisola_dev/order/cart.html` ↔ `mobile/medisola_dev/order/cart.html`

**Failure to implement dual changes will break user experience across platforms.**

---

## Testing Guidelines

### **Differential Pricing System Test Plan**

#### **테스트 도구 설정**
- **추천 도구**: Jest (Node.js 환경) 또는 QUnit (브라우저 환경)
- **Mock 라이브러리**: jQuery, underscore.js, Amplitude
- **테스트 구조**:
```
/tests/
├── unit/
│   ├── gd_goods_view.test.js
│   └── premium_pricing.test.js
├── integration/
│   └── differential_pricing.test.js
├── fixtures/
│   └── mock_data.js
└── test-runner.html
```

#### **핵심 테스트 케이스**

**1. 차등 가격 계산 로직**
```javascript
// 1,000원 메뉴 테스트
test('1000원 메뉴 초과시 add_goods 1개 생성', () => {
  const premiumPrice = 1000;
  const addedPrice = 1000;
  const overflowedCount = 2;
  const expected = 2; // 2개 * 1배수
  expect(calculateAddGoodsMultiplier(premiumPrice, addedPrice, overflowedCount)).toBe(expected);
});

// 2,000원 메뉴 테스트  
test('2000원 메뉴 초과시 add_goods 2개 생성', () => {
  const premiumPrice = 2000;
  const addedPrice = 1000;
  const overflowedCount = 1;
  const expected = 2; // 1개 * 2배수
  expect(calculateAddGoodsMultiplier(premiumPrice, addedPrice, overflowedCount)).toBe(expected);
});
```

**2. generatePriceAddedGoodsName() 함수**
```javascript
test('차등 가격 표시 형식 검증', () => {
  // Mock DOM 설정
  const mockData = [
    { name: '메뉴A', premiumPrice: 1000 },
    { name: '메뉴B', premiumPrice: 2000 }
  ];
  const expected = '메뉴A +1,000원, 메뉴B +2,000원';
  expect(generatePriceAddedGoodsName()).toBe(expected);
});
```

**3. 에러 처리 및 Edge Cases**
```javascript
test('0으로 나누기 방지', () => {
  const premiumPrice = 2000;
  const addedPrice = 0;
  const result = calculateAddGoodsMultiplier(premiumPrice, addedPrice, 1);
  expect(result).toBe(1); // fallback to 1
});

test('premiumPrice undefined시 기본값', () => {
  const premiumPrice = undefined;
  expect(parsePremiumPrice(premiumPrice)).toBe(1000);
});
```

**4. DOM 통합 테스트**
```javascript
test('data-premium-price 속성 파싱', () => {
  document.body.innerHTML = `
    <input data-premium-price="2000" name="componentGoodsCnt[0][]" />
  `;
  const input = document.querySelector('input');
  const premiumPrice = parseInt(input.dataset.premiumPrice, 10);
  expect(premiumPrice).toBe(2000);
});
```

**5. 확인 메시지 테스트**
```javascript
test('차등 가격 확인 메시지 형식', () => {
  const limitCnt = 2;
  const premiumPrice = 2000;
  const expected = "이 메뉴는 3개 이상 선택 시 개 당 2,000원의 추가 금액이 발생합니다. 추가할까요?";
  expect(generateConfirmMessage(limitCnt, premiumPrice)).toBe(expected);
});
```

#### **테스트 실행 환경**

**브라우저 테스트 (test-runner.html)**
```html
<!DOCTYPE html>
<html>
<head>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/underscore.js/1.13.1/underscore-min.js"></script>
    <script src="https://code.jquery.com/qunit/qunit-2.19.4.js"></script>
    <link rel="stylesheet" href="https://code.jquery.com/qunit/qunit-2.19.4.css">
</head>
<body>
    <div id="qunit"></div>
    <script src="../front/medisola_dev/js/gd_goods_view.js"></script>
    <script src="tests/unit/premium_pricing.test.js"></script>
</body>
</html>
```

#### **Mock 데이터 예시**
```javascript
// fixtures/mock_data.js
const mockComponentGoods = {
  normalMenu: {
    name: "일반메뉴",
    premiumPrice: 1000,
    limitCnt: 2
  },
  premiumMenu: {
    name: "프리미엄메뉴", 
    premiumPrice: 2000,
    limitCnt: 2
  }
};

const mockAddGoodsOptions = [
  "1000000001§1000§선택 메뉴 추가 금액§..."
];
```

#### **테스트 실행**
1. **브라우저 테스트**: `test-runner.html` 열기
2. **Node.js 테스트**: `npm test` (package.json 설정 후)
3. **CI/CD**: GitHub Actions 또는 로컬 스크립트

#### **데스크톱/모바일 동기화 테스트**
- 양쪽 플랫폼 동일한 테스트 케이스 실행
- 템플릿 변수 일관성 검증  
- JavaScript 로직 동기화 확인

---

When working with this codebase, focus on the `medisola_dev` theme for active development work.