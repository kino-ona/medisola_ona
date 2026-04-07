/**
 * 나만의 식단 플랜 - V0 → Vanilla JS 변환
 * React hooks → JS 객체/함수
 * V0 디자인 1:1 구현
 */
var CustomDietBuilder = (function() {
  'use strict';

  // ============ CONFIG ============
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
      focus: { name: '오메가3', key: 'omega3', unit: 'g', max: 5 }
    },
    {
      id: 'cal-550',
      label: '550kcal 라인',
      line: '550kcal 라인',
      color: '#F97316',
      filter: function(i) { return i.lines && i.lines.indexOf('550kcal 라인') !== -1; },
      sort: function(a, b) { return a.calories - b.calories; },
      focus: { name: '칼로리', key: 'calories', unit: 'kcal', max: 600 }
    },
    // 신규 라인 (Tier 1)
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
      label: '기타 메뉴',
      line: '기타',
      color: '#64748b',
      filter: function(i) { return true; },
      sort: function(a, b) { return 0; },
      focus: null
    }
  ];

  // ============ STATE ============
  var state = {
    goodsNo: '',
    totalQuantity: 10,
    selections: {},
    showAdvanced: false,
    advancedFilters: { minProtein: 0, maxSodium: 700, maxCalories: 700 },
    categoryFilter: 'all',
    selectedMenuItem: null,
    currentScreen: 'menu',
    menuData: [],
    options: [],
    conditions: [],
    userData: null,
    recommendedLines: null,
    selectedOptionSno: null,
    premiumAddGoods: null,
    goodsPrice: 0
  };

  // ============ HELPERS ============
  function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
  }

  function getSelectedTotal() {
    var total = 0;
    for (var id in state.selections) {
      total += state.selections[id];
    }
    return total;
  }

  function getMenuById(id) {
    for (var i = 0; i < state.menuData.length; i++) {
      if (state.menuData[i].id == id || state.menuData[i].addGoodsNo == id) {
        return state.menuData[i];
      }
    }
    return null;
  }

  function getFilteredMenus() {
    var scopeMenus = getScopeMenus();  // 추천 범위 메뉴만 사용
    return scopeMenus.filter(function(item) {
      var protein = item.protein != null ? item.protein : 0;
      var sodium = item.sodium != null ? item.sodium : 0;
      var calories = item.calories != null ? item.calories : 0;

      var passesAdvanced =
        protein >= state.advancedFilters.minProtein &&
        (sodium === 0 || sodium <= state.advancedFilters.maxSodium) &&
        (calories === 0 || calories <= state.advancedFilters.maxCalories);

      var passesCategory = state.categoryFilter === 'all' ||
                          !item.category ||
                          item.category.length === 0 ||
                          item.category.indexOf(state.categoryFilter) !== -1;

      return passesAdvanced && passesCategory;
    });
  }

  // 추천 라인 → NUTRITION_LINES id 매핑
  // 통합 형식(id 직접), 레거시 형식(lineKr→line 매칭) 모두 지원
  function getRecommendedLineIds() {
    if (!state.recommendedLines || !state.recommendedLines.length) return [];
    var ids = [];
    for (var ri = 0; ri < state.recommendedLines.length; ri++) {
      var rec = state.recommendedLines[ri];

      // 1) id 필드가 있으면 직접 사용 (통합 알고리즘 형식)
      if (rec.id) {
        for (var ni = 0; ni < NUTRITION_LINES.length; ni++) {
          if (NUTRITION_LINES[ni].id === rec.id) {
            ids.push(rec.id);
            break;
          }
        }
        continue;
      }

      // 2) 레거시 형식: lineKr/label로 매칭
      var lineKr = typeof rec === 'string' ? rec : (rec.lineKr || rec.label || '');
      var normalized = lineKr.replace(/ 라인$/, '').replace(/라인$/, '');
      for (var ni = 0; ni < NUTRITION_LINES.length; ni++) {
        var nl = NUTRITION_LINES[ni];
        if (nl.line === lineKr || nl.line === normalized || nl.label === lineKr) {
          ids.push(nl.id);
          break;
        }
      }
    }
    return ids;
  }

  // ============ SCOPE HELPERS ============
  // Helper: 질환식에 속하는 메뉴만 추출
  function getMenusMatchingDiseaseDiets(diseaseDiets) {
    var diseaseDietConfigs = window.DISEASE_DIETS || [];
    var scopeMenus = [];
    var seenIds = {};

    diseaseDiets.forEach(function(dd) {
      var config = diseaseDietConfigs.find(function(c) { return c.id === dd.id; });
      if (!config) return;

      state.menuData.forEach(function(menu) {
        if (!seenIds[menu.id] && config.filter(menu)) {
          scopeMenus.push(menu);
          seenIds[menu.id] = true;
        }
      });
    });

    return scopeMenus;
  }

  // Helper: 영양 강조 라인에 속하는 메뉴만 추출
  function getMenusMatchingLines(lineIds) {
    var scopeMenus = [];
    var seenIds = {};

    lineIds.forEach(function(lineId) {
      var lineConfig = NUTRITION_LINES.find(function(l) { return l.id === lineId; });
      if (!lineConfig || lineConfig.id === 'all') return;

      state.menuData.forEach(function(menu) {
        if (!seenIds[menu.id] && lineConfig.filter(menu)) {
          scopeMenus.push(menu);
          seenIds[menu.id] = true;
        }
      });
    });

    return scopeMenus;
  }

  // 추천 라인/질환식에 속하는 메뉴만 반환 (없으면 전체)
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

    // 우선순위 3: 추천 없음 - 전체 메뉴 반환 (현재 동작 유지)
    return state.menuData;
  }

  function getGroupedMenus() {
    var filtered = getFilteredMenus();
    var groups = {};
    var usedIds = {};

    NUTRITION_LINES.forEach(function(line) {
      if (line.id === 'all') return;

      var lineMenus = filtered.filter(line.filter);
      lineMenus.sort(line.sort);

      if (lineMenus.length > 0) {
        groups[line.id] = {
          line: line,
          menus: lineMenus
        };
        lineMenus.forEach(function(menu) {
          usedIds[menu.id] = true;
        });
      }
    });

    var ungroupedMenus = filtered.filter(function(menu) {
      return !usedIds[menu.id];
    });

    if (ungroupedMenus.length > 0) {
      var allLine = NUTRITION_LINES.find(function(l) { return l.id === 'all'; });
      if (allLine) {
        groups['all'] = {
          line: allLine,
          menus: ungroupedMenus
        };
      }
    }

    if (Object.keys(groups).length === 0 && filtered.length > 0) {
      var allLine = NUTRITION_LINES.find(function(l) { return l.id === 'all'; });
      if (allLine) {
        groups['all'] = {
          line: allLine,
          menus: filtered
        };
      }
    }

    return groups;
  }

  // 서버 옵션 데이터를 파싱하여 [{qty, label, weeksText, optionSno, optionPrice, perMeal}] 반환
  // Godo: optionPrice는 기본 상품가(goodsPrice) 대비 추가/차감 금액
  // 실제 가격 = goodsPrice + optionPrice
  function parseOptionTiers() {
    var base = parseInt(state.goodsPrice, 10) || 0;
    var tiers = [];
    for (var i = 0; i < state.options.length; i++) {
      var opt = state.options[i];
      var val = opt.optionValue1 || '';
      var qtyMatch = val.match(/(\d+)식/);
      var weeksMatch = val.match(/\((\d+주)\)/);
      if (qtyMatch) {
        var qty = parseInt(qtyMatch[1], 10);
        var optDiff = parseInt(opt.optionPrice, 10) || 0;
        var totalPrice = base + optDiff;
        tiers.push({
          qty: qty,
          label: qty + '식',
          weeksText: weeksMatch ? weeksMatch[1] : '',
          optionSno: opt.sno,
          optionPrice: totalPrice,
          perMeal: qty > 0 ? Math.round(totalPrice / qty) : 0
        });
      }
    }
    tiers.sort(function(a, b) { return a.qty - b.qty; });

    return tiers;
  }

  function getOptionPricing(qty) {
    var tiers = parseOptionTiers();
    for (var i = 0; i < tiers.length; i++) {
      if (tiers[i].qty === qty) return tiers[i];
    }
    return tiers.length > 0 ? tiers[0] : { qty: 10, optionSno: null, optionPrice: 0, perMeal: 0, weeksText: '', label: '10식' };
  }

  function renderQuantityButtons() {
    var tiers = parseOptionTiers();
    var html = '';
    for (var i = 0; i < tiers.length; i++) {
      var t = tiers[i];
      var activeClass = t.qty === state.totalQuantity ? ' active' : '';
      var weeksLabel = t.weeksText ? '<span class="qty-sub">' + t.weeksText + '</span>' : '';
      html += '<button class="qty-btn' + activeClass + '" data-qty="' + t.qty + '" onclick="CustomDietBuilder.handleQuantityChange(' + t.qty + ')">' +
        '<span class="qty-value">' + t.label + '</span>' + weeksLabel + '</button>';
    }
    $('#mobileQuantitySelector').html(html);
    $('#desktopQuantitySelector').html(html);
  }

  // 품절 체크
  function isMenuSoldOut(menu) {
    if (menu.soldOutFl === 'y') return true;
    if (menu.stockUseFl === 'y' && menu.stockCnt <= 0) return true;
    return false;
  }

  // 현재 선택된 옵션의 1주 기준 식수 계산
  function getMealsPerWeek() {
    var tier = getOptionPricing(state.totalQuantity);
    var weeksNum = tier.weeksText ? parseInt(tier.weeksText) : 1;
    return Math.floor(state.totalQuantity / weeksNum) || 10;
  }

  // limitCnt 스케일링 (gd_goods_view.js와 동일: limitCntAmplifier = floor(totalQty/mealsPerWeek) || 1)
  function getScaledLimitCnt(menu) {
    var base = menu.limitCnt || 0;
    if (base <= 0) return 0;
    var amplifier = Math.floor(state.totalQuantity / getMealsPerWeek()) || 1;
    return base * amplifier;
  }

  // 메뉴가 프리미엄인지 (limitCnt > 0인 메뉴만 프리미엄 대상)
  function isPremiumMenu(menu) {
    return (menu.limitCnt || 0) > 0;
  }

  // 프리미엄 추가상품 총 수량 (gd_goods_view.js overflow 로직 동일)
  // overflow = max(0, count - scaledLimitCnt)
  // units += overflow × premiumMultiplier
  function getPremiumUnits() {
    if (!state.premiumAddGoods) return 0;
    var units = 0;
    for (var id in state.selections) {
      var menu = getMenuById(id);
      if (!menu) continue;
      var limit = getScaledLimitCnt(menu);
      if (limit <= 0) continue; // limitCnt=0 → 무제한 무료
      var count = state.selections[id];
      var overflow = Math.max(0, count - limit);
      if (overflow > 0) {
        units += overflow * (menu.premiumMultiplier || 1);
      }
    }
    return units;
  }

  // 프리미엄 가격 합계 (단위 수 × 추가가격 단가)
  function calculateTotalPremium() {
    if (!state.premiumAddGoods) return 0;
    var unitPrice = state.premiumAddGoods.goodsPrice || 0;
    if (unitPrice <= 0) return 0;
    return unitPrice * getPremiumUnits();
  }

  // ============ RECOMMENDATION HELPERS ============
  function getConditionsArray() {
    if (!state.conditions) return [];
    if (Array.isArray(state.conditions)) return state.conditions;
    if (state.conditions.conditions && Array.isArray(state.conditions.conditions)) {
      return state.conditions.conditions;
    }
    return [];
  }

  var CONDITION_KEY_MAP = {
    '당뇨': 'diabetes', 'diabetes': 'diabetes',
    '임신성당뇨': 'gestational-diabetes', 'gestational-diabetes': 'gestational-diabetes',
    '신장질환': 'kidney', 'kidney': 'kidney',
    'kidney-pre-dialysis': 'kidney', 'kidney-dialysis': 'kidney',
    '암투병': 'cancer', 'cancer': 'cancer',
    '고콜레스테롤': 'cholesterol', 'cholesterol': 'cholesterol',
    '체중감량': 'weightLoss', 'weight-loss': 'weightLoss', '체중관리': 'weightLoss',
    '근육증가': 'muscleGain', 'muscle-gain': 'muscleGain',
    '균형잡힌식단': 'general', 'general': 'general'
  };

  function getRecommendReason(menu) {
    // 1. DB에 저장된 추천 사유가 있으면 우선 사용
    if (menu.recommendReasons && typeof menu.recommendReasons === 'object') {
      var conditions = getConditionsArray();
      if (conditions.length > 0) {
        for (var i = 0; i < conditions.length; i++) {
          var key = CONDITION_KEY_MAP[conditions[i]] || conditions[i];
          if (menu.recommendReasons[key]) {
            return menu.recommendReasons[key];
          }
        }
      }
      if (menu.recommendReasons.general) return menu.recommendReasons.general;
    }

    // 2. DB 데이터 없으면 → 영양 라인 + 조건 기반 클라이언트 사이드 폴백
    return generateFallbackReason(menu);
  }

  // 메뉴 영양 라인과 사용자 조건에 기반한 폴백 추천 사유 생성
  function generateFallbackReason(menu) {
    if (!menu.lines || menu.lines.length === 0) return null;

    var conditions = getConditionsArray();
    var lines = menu.lines;

    // 조건별 매칭 (V0 getRecommendReason 로직 참조)
    for (var i = 0; i < conditions.length; i++) {
      var cond = conditions[i];
      var condKey = CONDITION_KEY_MAP[cond] || cond;

      if ((condKey === 'diabetes' || condKey === 'gestational-diabetes') && lines.indexOf('저당') !== -1) {
        return '혈당 관리에 도움이 되는 저당 메뉴';
      }
      if ((condKey === 'diabetes' || condKey === 'gestational-diabetes') && lines.indexOf('고식이섬유') !== -1) {
        return '식이섬유가 혈당 흡수를 완화합니다';
      }
      if (condKey === 'kidney' && lines.indexOf('저나트륨') !== -1) {
        return '나트륨 제한으로 신장 부담을 줄여줍니다';
      }
      if (condKey === 'cancer' && lines.indexOf('고단백') !== -1) {
        return '양질의 단백질로 체력 회복을 돕습니다';
      }
      if (condKey === 'cholesterol' && lines.indexOf('저콜레스테롤') !== -1) {
        return '콜레스테롤 관리에 도움이 됩니다';
      }
      if (condKey === 'cholesterol' && lines.indexOf('오메가3') !== -1) {
        return 'HDL 콜레스테롤 개선에 기여합니다';
      }
      if (condKey === 'weightLoss' && lines.indexOf('저당') !== -1) {
        return '당류를 줄여 체중 감량과 대사 건강에 도움';
      }
      if (condKey === 'weightLoss' && lines.indexOf('고단백') !== -1) {
        return '포만감을 높이고 근손실을 방지합니다';
      }
      if (condKey === 'muscleGain' && lines.indexOf('고단백') !== -1) {
        return '근육 합성에 필요한 단백질 공급';
      }
    }

    // 조건 없이 영양 라인만으로 일반 추천 사유
    if (lines.indexOf('고단백') !== -1) return '양질의 단백질이 풍부한 메뉴';
    if (lines.indexOf('저나트륨') !== -1) return '나트륨을 낮춘 건강한 메뉴';
    if (lines.indexOf('오메가3') !== -1) return '오메가-3 지방산이 풍부한 메뉴';
    if (lines.indexOf('550kcal 라인') !== -1) return '충분한 칼로리를 제공하는 든든한 메뉴';
    if (lines.indexOf('저당') !== -1) return '당 함량을 낮춘 건강한 메뉴';
    if (lines.indexOf('고식이섬유') !== -1) return '식이섬유가 풍부한 메뉴';
    if (lines.indexOf('저지방') !== -1) return '지방을 줄인 가벼운 메뉴';

    return null;
  }

  function getLineColor(lineLabel) {
    for (var i = 0; i < NUTRITION_LINES.length; i++) {
      if (NUTRITION_LINES[i].line === lineLabel || NUTRITION_LINES[i].label === lineLabel) {
        return NUTRITION_LINES[i].color;
      }
    }
    return '#64748b';
  }

  // ============ ICON FUNCTIONS (V0) ============
  function getSectionIcon(lineId) {
    switch(lineId) {
      case 'high-protein':
        return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"/></svg>';
      case 'low-sodium':
        // Lucide Droplets (복수 물방울)
        return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 16.3c2.2 0 4-1.83 4-4.05 0-1.16-.57-2.26-1.71-3.19S7.29 6.75 7 5.3c-.29 1.45-1.14 2.84-2.29 3.76S3 11.1 3 12.25c0 2.22 1.8 4.05 4 4.05z"/><path d="M12.56 6.6A10.97 10.97 0 0 0 14 3.02c.5 2.5 2 4.9 4 6.5s3 3.5 3 5.5a6.98 6.98 0 0 1-11.91 4.97"/></svg>';
      case 'omega3':
        // Lucide Fish (전체)
        return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6.5 12c.94-3.46 4.94-6 8.5-6 3.56 0 6.06 2.54 7 6-.94 3.47-3.44 6-7 6s-7.56-2.53-8.5-6Z"/><path d="M18 12v.5"/><path d="M16 17.93a9.77 9.77 0 0 1-3.5.57c-4.41 0-8-2.69-8-6s3.59-6 8-6c.82 0 1.63.09 2.39.26"/><path d="M2 12h2"/></svg>';
      case 'cal-550':
        // Lucide Flame
        return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/></svg>';
      case 'low-sugar':
        // Lucide Leaf (저당)
        return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 20A7 7 0 0 1 9.8 6.9C15.5 4.9 17 3.5 19 2c1 2 2 4.5 2 8 0 5.5-4.78 10-10 10Z"/><path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/></svg>';
      case 'high-fiber':
        // Lucide Wheat (고식이섬유)
        return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 22 16 8"/><path d="M3.47 12.53 5 11l1.53 1.53a3.5 3.5 0 0 1 0 4.94L5 19l-1.53-1.53a3.5 3.5 0 0 1 0-4.94Z"/><path d="M7.47 8.53 9 7l1.53 1.53a3.5 3.5 0 0 1 0 4.94L9 15l-1.53-1.53a3.5 3.5 0 0 1 0-4.94Z"/><path d="M11.47 4.53 13 3l1.53 1.53a3.5 3.5 0 0 1 0 4.94L13 11l-1.53-1.53a3.5 3.5 0 0 1 0-4.94Z"/><path d="M20 2h2v2a4 4 0 0 1-4 4h-2V6a4 4 0 0 1 4-4Z"/></svg>';
      case 'low-fat':
        // Lucide Droplet (저지방 - 단일 물방울)
        return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22a7 7 0 0 0 7-7c0-2-1-3.9-3-5.5s-3.5-4-4-6.5c-.5 2.5-2 4.9-4 6.5C6 11.1 5 13 5 15a7 7 0 0 0 7 7z"/></svg>';
      case 'low-saturated-fat':
        // Lucide ShieldAlert (저포화지방)
        return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>';
      case 'low-cholesterol':
        // Lucide Heart (저콜레스테롤)
        return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>';
      case 'low-carbs':
        // Lucide Activity (저탄수화물)
        return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-2.48a2 2 0 0 0-1.93 1.46l-2.35 8.36a.25.25 0 0 1-.48 0L9.24 2.18a.25.25 0 0 0-.48 0l-2.35 8.36A2 2 0 0 1 4.49 12H2"/></svg>';
      default:
        return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/></svg>';
    }
  }

  function getCategoryIcon(category) {
    switch(category) {
      case 'seafood':
      case '해산물':
        // Fish icon (V0)
        return '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6.5 12c.94-3.46 4.94-6 8.5-6 3.56 0 6.06 2.54 7 6-.94 3.47-3.44 6-7 6s-7.56-2.53-8.5-6Z"/><path d="M18 12v.5"/><path d="M16 17.93a9.77 9.77 0 0 1-3.5.57c-4.41 0-8-2.69-8-6s3.59-6 8-6c.82 0 1.63.09 2.39.26"/><path d="M2 12h2"/></svg>';
      case 'meat':
      case '육류':
        // Beef icon (V0)
        return '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12.5" cy="8.5" r="2.5"/><path d="M12.5 2a6.5 6.5 0 0 0-6.22 4.6c-1.1 3.13-.78 3.9-3.18 6.08A3 3 0 0 0 5 18c4 0 8.4-1.8 11.4-4.3A6.5 6.5 0 0 0 12.5 2Z"/></svg>';
      case 'plant-protein':
      case '식단백':
        // Plant protein icon
        return '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a10 10 0 0 1 10 10 4 4 0 0 1-4 4h-1.5a2.5 2.5 0 0 0-2.5 2.5v1a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-1a2.5 2.5 0 0 0-2.5-2.5H6a4 4 0 0 1-4-4A10 10 0 0 1 12 2Z"/></svg>';
      case 'salad':
      case '샐러드':
        // Salad icon
        return '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12a5 5 0 0 0 5 5 8 8 0 0 1 5 2 8 8 0 0 1 5-2 5 5 0 0 0 5-5V7h-5a8 8 0 0 0-5 2 8 8 0 0 0-5-2H2Z"/><path d="M6 11c1.5 0 3 .5 3 2-2 0-3 0-3-2Z"/><path d="M18 11c-1.5 0-3 .5-3 2 2 0 3 0 3-2Z"/></svg>';
      case 'soup':
      case '국/찌개':
        // Soup icon
        return '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21a9 9 0 0 0 9-9H3a9 9 0 0 0 9 9Z"/><path d="M7 21h10"/><path d="M19.5 12 22 6"/></svg>';
      case 'side':
      case '반찬':
        // Side dish icon
        return '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="8"/><path d="M12 2v7.5"/><path d="m19 5-5.23 5.23"/><path d="M22 12h-7.5"/><path d="m19 19-5.23-5.23"/><path d="M12 14.5V22"/><path d="M10.23 13.77 5 19"/><path d="M9.5 12H2"/><path d="M10.23 10.23 5 5"/></svg>';
      case 'snack':
      case '간식':
        // Snack icon
        return '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 10v12"/><path d="M15 5.88 14 10h5.83a2 2 0 0 1 1.92 2.56l-2.33 8A2 2 0 0 1 17.5 22H4a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2h2.76a2 2 0 0 0 1.79-1.11L12 2h0a3.13 3.13 0 0 1 3 3.88Z"/></svg>';
      case 'drink':
      case '음료':
        // Drink icon
        return '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/><line x1="6" x2="6" y1="1" y2="4"/><line x1="10" x2="10" y1="1" y2="4"/><line x1="14" x2="14" y1="1" y2="4"/></svg>';
      default:
        return '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/></svg>';
    }
  }

  function getNutritionBadgeIcon(type) {
    switch(type) {
      case 'protein':
        return '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"/></svg>';
      case 'sodium':
        // Lucide Droplets (복수 물방울)
        return '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M7 16.3c2.2 0 4-1.83 4-4.05 0-1.16-.57-2.26-1.71-3.19S7.29 6.75 7 5.3c-.29 1.45-1.14 2.84-2.29 3.76S3 11.1 3 12.25c0 2.22 1.8 4.05 4 4.05z"/><path d="M12.56 6.6A10.97 10.97 0 0 0 14 3.02c.5 2.5 2 4.9 4 6.5s3 3.5 3 5.5a6.98 6.98 0 0 1-11.91 4.97"/></svg>';
      case 'omega3':
        // Lucide Fish (전체)
        return '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M6.5 12c.94-3.46 4.94-6 8.5-6 3.56 0 6.06 2.54 7 6-.94 3.47-3.44 6-7 6s-7.56-2.53-8.5-6Z"/><path d="M18 12v.5"/><path d="M16 17.93a9.77 9.77 0 0 1-3.5.57c-4.41 0-8-2.69-8-6s3.59-6 8-6c.82 0 1.63.09 2.39.26"/><path d="M2 12h2"/></svg>';
      case 'lowcal':
        return '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/></svg>';
      default:
        return '';
    }
  }

  // ============ RENDER FUNCTIONS ============
  function renderMenuSection($container, group, isRecommended) {
    var line = group.line;
    var $section = $('<div class="menu-section' + (isRecommended ? ' recommended' : '') + '"></div>');

    // Section Header
    var $header = $('<div class="section-header"></div>');
    var iconHtml = getSectionIcon(line.id);
    var iconClassMap = {
      'high-protein': 'protein', 'low-sodium': 'sodium', 'omega3': 'omega3',
      'cal-550': 'cal550', 'low-sugar': 'lowsugar', 'high-fiber': 'highfiber',
      'low-fat': 'lowfat', 'low-saturated-fat': 'lowsatfat',
      'low-cholesterol': 'lowchol', 'low-carbs': 'lowcarbs', 'all': 'all'
    };
    var iconClass = iconClassMap[line.id] || 'all';

    $header.append('<span class="section-icon ' + iconClass + '">' + iconHtml + '</span>');
    $header.append('<span class="section-title">' + line.label + '</span>');
    $header.append('<span class="section-count">' + group.menus.length + '개</span>');
    if (isRecommended) {
      $header.append('<span class="section-rec-badge">추천</span>');
    }
    $section.append($header);

    // Menu list (Horizontal scroll)
    var $list = $('<div class="menu-list scrollbar-hide"></div>');
    group.menus.forEach(function(menu) {
      $list.append(renderMenuCard(menu, line));
    });
    $section.append($list);

    $container.append($section);
  }

  function renderMenuSections() {
    var $container = $('#menuSections');
    $container.empty();

    var groups = getGroupedMenus();
    var hasMenus = false;
    var recLineIds = getRecommendedLineIds();
    console.log('[renderMenuSections] recLineIds:', recLineIds, 'state.recommendedLines:', state.recommendedLines);

    // 추천 라인이 있으면: 추천 라인만 표시 + 기타 메뉴
    if (recLineIds.length > 0) {
      var shownMenuIds = {};

      // 추천 라인 섹션들 (우선순위 순서)
      for (var ri = 0; ri < recLineIds.length; ri++) {
        var group = groups[recLineIds[ri]];
        if (!group) continue;
        hasMenus = true;
        renderMenuSection($container, group, true);
        group.menus.forEach(function(m) { shownMenuIds[m.id] = true; });
      }

      // 3) 전체 메뉴 (추천 라인에 포함되지 않은 나머지 메뉴)
      var filtered = getFilteredMenus();
      var otherMenus = filtered.filter(function(m) { return !shownMenuIds[m.id]; });
      if (otherMenus.length > 0) {
        hasMenus = true;
        renderMenuSection($container, {
          line: { id: 'all', label: '기타 메뉴', line: '기타', color: '#64748b', focus: null },
          menus: otherMenus
        }, false);
      }

    } else {
      // 추천 라인 없음: 기존 로직 (모든 그룹 표시)
      NUTRITION_LINES.forEach(function(line) {
        var group = groups[line.id];
        if (!group) return;
        hasMenus = true;
        renderMenuSection($container, group, false);
      });
    }

    if (!hasMenus) {
      $container.append('<div class="empty-state">조건에 맞는 메뉴가 없습니다.</div>');
    }
  }

  function renderMenuCard(menu, line) {
    var qty = state.selections[menu.id] || 0;
    var isSelected = qty > 0;
    var isSoldOut = isMenuSoldOut(menu);

    var cardClasses = 'menu-card' + (isSelected ? ' selected' : '') + (isSoldOut ? ' sold-out' : '');
    var $card = $('<div class="' + cardClasses + '" data-id="' + menu.id + '"></div>');

    // 품절 오버레이
    if (isSoldOut) {
      $card.append('<span class="card-soldout-badge">품절</span>');
    }

    // Info button (V0 스타일 - Top Right)
    $card.append(
      '<button class="card-info-btn" onclick="event.stopPropagation(); CustomDietBuilder.showDetail(\'' + menu.id + '\')">' +
      '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>' +
      '</button>'
    );

    // Nutrient focus bar (V0 스타일 - Top)
    if (line && line.focus) {
      var focusValue = menu[line.focus.key] || 0;
      var percentage = Math.min(100, (focusValue / line.focus.max) * 100);
      $card.append(
        '<div class="nutrient-focus">' +
        '<div class="nutrient-header">' +
        '<span class="nutrient-name">' + line.focus.name + '</span>' +
        '<span class="nutrient-value" style="color:' + line.color + ';">' + focusValue + line.focus.unit + '</span>' +
        '</div>' +
        '<div class="nutrient-bar"><div class="nutrient-bar-fill" style="width:' + percentage + '%; background-color:' + line.color + ';"></div></div>' +
        '</div>'
      );
    }

    // Image wrapper (V0 스타일 - 원형 플레이트)
    var $imageWrapper = $('<div class="card-image-wrapper"></div>');
    var imageUrl = menu.image || '/data/goods/default_goods.png';
    $imageWrapper.append('<img src="' + imageUrl + '" alt="' + menu.name + '" class="card-image" loading="lazy">');

    // Nutrition line badges (V0 스타일 - Bottom Left)
    var $badges = $('<div class="card-badges"></div>');
    if (menu.lines && menu.lines.length > 0) {
      if (menu.lines.indexOf('고단백') !== -1) {
        $badges.append('<span class="badge protein" title="고단백">' + getNutritionBadgeIcon('protein') + '</span>');
      }
      if (menu.lines.indexOf('저나트륨') !== -1) {
        $badges.append('<span class="badge sodium" title="저나트륨">' + getNutritionBadgeIcon('sodium') + '</span>');
      }
      if (menu.lines.indexOf('오메가3') !== -1) {
        $badges.append('<span class="badge omega3" title="오메가3">' + getNutritionBadgeIcon('omega3') + '</span>');
      }
      if (menu.lines.indexOf('550kcal 라인') !== -1) {
        $badges.append('<span class="badge cal550" title="550kcal 라인">' + getNutritionBadgeIcon('lowcal') + '</span>');
      }
    }
    $imageWrapper.append($badges);

    // Category icon (V0 스타일 - Bottom Right)
    // 배열의 첫 번째 카테고리 사용
    var firstCategory = Array.isArray(menu.category) && menu.category.length > 0
                        ? menu.category[0]
                        : (menu.category || 'all');
    var catClass = firstCategory;
    var catIcon = getCategoryIcon(firstCategory);
    $imageWrapper.append('<span class="card-category-icon ' + catClass + '">' + catIcon + '</span>');

    // New badge
    if (menu.isNew) {
      $imageWrapper.append('<span class="card-new-badge">New</span>');
    }

    // Quantity badge (V0 스타일 - Top Right of Image)
    if (qty > 0) {
      $imageWrapper.append('<span class="card-qty-badge"><span class="qty-num">' + qty + '</span></span>');
    }

    $card.append($imageWrapper);

    // Menu name
    $card.append('<span class="card-name">' + menu.name + '</span>');

    // Recommendation reason (V0: recommendReason below card name)
    var reason = getRecommendReason(menu);
    if (reason) {
      $card.append('<span class="card-recommend">' + reason + '</span>');
    }

    // 프리미엄 왕관 배지 (limitCnt > 0인 메뉴 = 초과 시 추가금 발생)
    if (isPremiumMenu(menu) && state.premiumAddGoods) {
      var pSurcharge = (state.premiumAddGoods.goodsPrice || 0) * (menu.premiumMultiplier || 1);
      $card.append('<span class="card-premium-badge"><svg class="crown-icon" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M5 16L3 5l5.5 5L12 4l3.5 6L21 5l-2 11H5z"/><path d="M5 19h14v2H5z"/></svg> +' + formatNumber(pSurcharge) + '원</span>');
    }

    // Click handler (터치로 추가, 품절 차단)
    $card.on('click', function(e) {
      if (isSoldOut) return;
      if (!$(e.target).closest('.card-info-btn').length) {
        adjustQuantity(menu.id, 1);
      }
    });

    return $card;
  }

  function renderSelectedPreview() {
    // Mobile Preview
    var $mobileContainer = $('#mobileSelectedItems');
    var $mobileEmpty = $('#mobileEmptyMessage');

    // Desktop List
    var $desktopList = $('#desktopSelectedList');

    var selectedIds = Object.keys(state.selections);

    // Mobile Preview Render
    if ($mobileContainer.length > 0) {
      if (selectedIds.length === 0) {
        $mobileContainer.empty();
        if ($mobileEmpty.length > 0) $mobileEmpty.show();
      } else {
        if ($mobileEmpty.length > 0) $mobileEmpty.hide();
        $mobileContainer.empty();

        selectedIds.forEach(function(id) {
          var menu = getMenuById(id);
          var qty = state.selections[id];
          if (!menu || qty <= 0) return;

          var shortName = menu.name.split(' ')[0];
          var $item = $(
            '<div class="selected-item" data-id="' + id + '">' +
            '<div class="item-image-wrapper">' +
            '<img src="' + (menu.image || '/data/goods/default_goods.png') + '" alt="' + menu.name + '" class="item-image">' +
            '<span class="item-qty">' + qty + '</span>' +
            '</div>' +
            '<span class="item-name">' + shortName + '</span>' +
            '</div>'
          );

          $item.on('click', function() {
            adjustQuantity(id, -1);
          });

          $mobileContainer.append($item);
        });
      }
    }

    // Desktop List Render
    if ($desktopList.length > 0) {
      if (selectedIds.length === 0) {
        $desktopList.html('<div class="empty-state">메뉴 카드를 클릭하여 추가하세요</div>');
      } else {
        $desktopList.empty();

        selectedIds.forEach(function(id) {
          var menu = getMenuById(id);
          var qty = state.selections[id];
          if (!menu || qty <= 0) return;

          var $item = $(
            '<button class="selected-item-desktop" data-id="' + id + '">' +
            '<div class="item-image-wrapper">' +
            '<img src="' + (menu.image || '/data/goods/default_goods.png') + '" alt="' + menu.name + '" class="item-image">' +
            '</div>' +
            '<div class="item-info">' +
            '<p class="item-name">' + menu.name + '</p>' +
            '<p class="item-nutrition">' + (menu.calories || 0) + 'kcal</p>' +
            '</div>' +
            '<div class="item-qty-group">' +
            '<span class="item-qty">' + qty + '</span>' +
            '<svg class="minus-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/></svg>' +
            '</div>' +
            '</button>'
          );

          $item.on('click', function() {
            adjustQuantity(id, -1);
          });

          $desktopList.append($item);
        });
      }
    }
  }

  function renderSuggestionCard() {
    var hasRecommendedLines = state.recommendedLines && state.recommendedLines.length > 0;
    var hasUserData = state.userData && (state.userData.age || state.userData.gender || state.userData.goal);

    if (!hasRecommendedLines && !hasUserData) {
      $('#desktopSuggestionCard').hide();
      $('#mobileSuggestionCard').hide();
      return;
    }

    var html = buildSuggestionContent();

    // Desktop sidebar
    $('#desktopSuggestionContent').html(html);
    $('#desktopSuggestionCard').show();

    // Mobile
    $('#mobileSuggestionContent').html(html);
    $('#mobileSuggestionCard').show();
  }

  // BMI + Harris-Benedict 계산 — DietFinder.php와 동일한 공식, 100단위 반올림
  function calculateBmiData() {
    if (!state.userData || !state.userData.height || !state.userData.weight) return null;

    var u = state.userData;
    var heightM = u.height / 100;
    var bmi = u.weight / (heightM * heightM);
    var age = u.birthYear ? (new Date().getFullYear() - u.birthYear) : 35;

    var status, recommendation;
    if (bmi < 18.5) { status = '저체중'; recommendation = '영양 섭취 강화가 필요합니다'; }
    else if (bmi < 23) { status = '정상'; recommendation = '현재 체중을 유지하세요'; }
    else if (bmi < 25) { status = '과체중'; recommendation = '식이 조절을 권장합니다'; }
    else { status = '비만'; recommendation = '칼로리 제한 식단을 권장합니다'; }

    // Harris-Benedict BMR
    var bmr;
    if (u.gender === 'male') {
      bmr = 88.362 + (13.397 * u.weight) + (4.799 * u.height) - (5.677 * age);
    } else {
      bmr = 447.593 + (9.247 * u.weight) + (3.098 * u.height) - (4.330 * age);
    }
    // PAL: 질환자 → 1.2, 비질환자 → 1.55
    var conditions = getConditionsArray();
    var diseaseIds = ['diabetes', 'gestational-diabetes', 'kidney-pre-dialysis', 'kidney-dialysis', 'cancer', 'cholesterol'];
    var hasDisease = false;
    for (var i = 0; i < conditions.length; i++) {
      if (diseaseIds.indexOf(conditions[i]) >= 0) { hasDisease = true; break; }
    }
    var pal = hasDisease ? 1.2 : 1.55;
    var dailyCalories = Math.max(1200, Math.min(3000, Math.round(Math.round(bmr * pal) / 100) * 100));

    return {
      bmi: bmi.toFixed(1),
      status: status,
      recommendation: recommendation,
      dailyCalories: dailyCalories,
      age: age,
      proteinG: Math.round(dailyCalories * 0.175 / 4)
    };
  }

  function buildSuggestionContent() {
    var html = '<div class="suggestion-card" style="margin:0;border:none;box-shadow:none;border-radius:0;">';
    html += '<div class="card-body">';

    // BMI Status Summary (V0: 3-column grid)
    var bmiData = calculateBmiData();
    if (bmiData) {
      var statusClass = { '저체중': 'underweight', '정상': 'normal', '과체중': 'overweight', '비만': 'obese' }[bmiData.status] || 'normal';

      html += '<div class="bmi-grid">';
      html += '<div class="bmi-item">';
      html += '<p class="bmi-value">' + bmiData.bmi + '</p>';
      html += '<p class="bmi-label">BMI</p>';
      html += '<span class="bmi-badge ' + statusClass + '">' + bmiData.status + '</span>';
      html += '</div>';
      html += '<div class="bmi-item">';
      html += '<p class="bmi-value primary">' + bmiData.dailyCalories + '</p>';
      html += '<p class="bmi-label">필요 kcal</p>';
      html += '</div>';
      html += '<div class="bmi-item">';
      html += '<p class="bmi-value">' + bmiData.proteinG + '</p>';
      html += '<p class="bmi-label">단백질 g</p>';
      html += '</div>';
      html += '</div>';
    }

    // Recommended Lines (추천 영양 솔루션)
    if (state.recommendedLines && state.recommendedLines.length > 0) {
      html += '<div class="recommended-lines">';
      html += '<p class="recommended-label">추천 영양 솔루션</p>';

      state.recommendedLines.forEach(function(line, idx) {
        var lineLabel = typeof line === 'string' ? line : (line.lineKr || line.label || '');
        var lineReason = typeof line === 'object' ? (line.reason || '') : '';
        var lineLabelNorm = lineLabel.replace(/ 라인$/, '').replace(/라인$/, '');
        var lineColor = (line && line.color) ? line.color : getLineColor(lineLabelNorm);
        var itemClass = idx === 0 ? 'primary' : 'secondary';

        html += '<div class="line-item ' + itemClass + '">';
        html += '<div class="line-number" style="background-color:' + lineColor + ';">' + (idx + 1) + '</div>';
        html += '<div class="line-info">';
        html += '<p class="line-name">' + lineLabelNorm + ' 라인</p>';
        if (lineReason) {
          html += '<p class="line-reason">' + lineReason + '</p>';
        }
        html += '</div>';
        html += '</div>';
      });

      html += '</div>';
    }

    // Brand Message
    html += '<p class="brand-message">"추천이 아니라, 케어를 제공합니다."</p>';
    html += '</div>'; // .card-body
    html += '</div>'; // .suggestion-card wrapper

    return html;
  }

  function updateCounts() {
    var selected = getSelectedTotal();
    var total = state.totalQuantity;

    // Mobile counts
    $('#mobileCurrentCount, #mobileCtaSelectedCount').text(selected);
    $('#mobileTotalCount, #mobileCtaTotalCount').text(total);

    // Desktop counts
    $('#desktopCurrentCount').text(selected);
    $('#desktopTotalCount').text(total);

    // Mobile preview count 스타일
    var $mobilePreviewCount = $('#mobileSelectedCount');
    if ($mobilePreviewCount.length > 0) {
      if (selected === total) {
        $mobilePreviewCount.addClass('complete');
      } else {
        $mobilePreviewCount.removeClass('complete');
      }
    }

    // Desktop selected count 스타일
    var $desktopSelectedCount = $('#desktopSelectedCount');
    if ($desktopSelectedCount.length > 0) {
      if (selected === total) {
        $desktopSelectedCount.addClass('complete');
      } else {
        $desktopSelectedCount.removeClass('complete');
      }
    }

    // Mobile CTA 버튼 상태
    var $mobileBtn = $('#mobileCtaButton');
    var $mobileCheckIcon = $('#mobileCtaCheckIcon');
    if ($mobileBtn.length > 0) {
      if (selected === total) {
        $mobileBtn.prop('disabled', false);
        if ($mobileCheckIcon.length > 0) $mobileCheckIcon.show();
      } else {
        $mobileBtn.prop('disabled', true);
        if ($mobileCheckIcon.length > 0) $mobileCheckIcon.hide();
      }
    }

    // Desktop CTA 버튼 상태
    var $desktopBtn = $('#desktopCtaButton');
    var $desktopCheckIcon = $('#desktopCtaCheckIcon');
    if ($desktopBtn.length > 0) {
      if (selected === total) {
        $desktopBtn.prop('disabled', false);
        if ($desktopCheckIcon.length > 0) $desktopCheckIcon.show();
      } else {
        $desktopBtn.prop('disabled', true);
        if ($desktopCheckIcon.length > 0) $desktopCheckIcon.hide();
      }
    }
  }

  function updateQuantityButtons() {
    // Mobile quantity selector
    $('#mobileQuantitySelector .qty-btn').removeClass('active');
    $('#mobileQuantitySelector .qty-btn[data-qty="' + state.totalQuantity + '"]').addClass('active');

    // Desktop quantity selector
    $('#desktopQuantitySelector .qty-btn').removeClass('active');
    $('#desktopQuantitySelector .qty-btn[data-qty="' + state.totalQuantity + '"]').addClass('active');
  }

  function updatePricing() {
    var optPricing = getOptionPricing(state.totalQuantity);
    var premiumTotal = calculateTotalPremium();
    var basePrice = optPricing.optionPrice;
    var totalPrice = basePrice + premiumTotal;

    state.selectedOptionSno = optPricing.optionSno;

    $('#discountPrice, #totalPrice').text(formatNumber(totalPrice) + '원');
    $('#perMealPrice').text(formatNumber(Math.round(totalPrice / state.totalQuantity)) + '원');

    // form 필드 업데이트
    $('#goodsPriceSum').val(basePrice);
    $('#addGoodsPriceSum').val(premiumTotal);
    $('#setTotalPrice').val(totalPrice);
  }

  // ============ Lightweight card state update (no DOM rebuild) ============
  function updateCardStates() {
    $('#menuSections .menu-card').each(function() {
      var $card = $(this);
      var id = $card.attr('data-id');
      var qty = state.selections[id] || 0;
      var isSelected = qty > 0;

      $card.toggleClass('selected', isSelected);

      var $badge = $card.find('.card-qty-badge');
      if (qty > 0) {
        if ($badge.length) {
          var prev = parseInt($badge.find('.qty-num').text() || $badge.text(), 10);
          if (prev !== qty) {
            $badge.html('<span class="qty-num">' + qty + '</span>');
          }
        } else {
          $card.find('.card-image-wrapper').append('<span class="card-qty-badge"><span class="qty-num">' + qty + '</span></span>');
        }
      } else {
        $badge.remove();
      }
    });
  }

  // ============ EVENT HANDLERS ============
  function adjustQuantity(itemId, delta) {
    var menu = getMenuById(itemId);
    if (!menu) return;

    // 품절 체크
    if (delta > 0 && isMenuSoldOut(menu)) return;

    var current = state.selections[itemId] || 0;
    var selectedTotal = getSelectedTotal();
    var remaining = state.totalQuantity - selectedTotal + current;
    var newVal = Math.max(0, Math.min(remaining, current + delta));

    // limitCnt 제한 로직 (gd_goods_view.js 패턴 동일)
    if (delta > 0 && newVal > current) {
      var scaledLimit = getScaledLimitCnt(menu);
      if (scaledLimit > 0 && newVal > scaledLimit) {
        if (state.premiumAddGoods) {
          // 프리미엄 추가 금액이 있으면 confirm
          // 최초로 limit을 넘는 시점에만 confirm (이미 초과 상태에서 추가 시에는 스킵)
          if (current < scaledLimit) {
            var unitPremium = (state.premiumAddGoods.goodsPrice || 0) * (menu.premiumMultiplier || 1);
            if (!confirm('이 메뉴는 ' + scaledLimit + '개 이상 선택 시 개 당 ' + formatNumber(unitPremium) + '원의 추가 금액이 발생합니다. 추가할까요?')) {
              // 거부 → limit까지만 적용
              newVal = Math.min(scaledLimit, remaining);
              if (newVal === current) return;
            }
          }
          // confirm 승인 또는 이미 초과 상태 → newVal 유지 (프리미엄 과금)
        } else {
          // 프리미엄 추가 상품 없으면 limit에서 차단
          alert('이 메뉴는 ' + scaledLimit + '개 까지만 고를 수 있습니다.');
          newVal = Math.min(scaledLimit, remaining);
          if (newVal === current) return;
        }
      }
    }

    if (newVal === 0) {
      delete state.selections[itemId];
    } else {
      state.selections[itemId] = newVal;
    }

    updateCardStates();
    renderSelectedPreview();
    updateCounts();
    updatePricing();
  }

  function handleQuantityChange(newTotal) {
    var oldTotal = state.totalQuantity;
    state.totalQuantity = newTotal;
    state.selectedOptionSno = getOptionPricing(newTotal).optionSno;

    if (oldTotal !== newTotal) {
      var ratio = newTotal / oldTotal;
      var newSelections = {};
      var totalAllocated = 0;

      for (var id in state.selections) {
        var newQty = Math.round(state.selections[id] * ratio);
        if (newQty > 0) {
          newSelections[id] = newQty;
          totalAllocated += newQty;
        }
      }

      while (totalAllocated > newTotal) {
        for (var id in newSelections) {
          if (newSelections[id] > 1) {
            newSelections[id]--;
            totalAllocated--;
            break;
          }
        }
      }

      state.selections = newSelections;
    }

    updateQuantityButtons();
    updatePricing();
    renderMenuSections();
    renderSelectedPreview();
    updateCounts();
  }

  // ============ DETAIL MODAL (V0 스타일) ============
  function showDetail(itemId) {
    var menu = getMenuById(itemId);
    if (!menu) return;

    state.selectedMenuItem = menu;
    var qty = state.selections[menu.id] || 0;

    // 기본 정보
    $('#detailImage').attr('src', menu.image || '/data/goods/default_goods.png');
    $('#detailName').text(menu.name);
    $('#detailNameEn').text(menu.nameEn || '');
    var desc = (menu.description || '').replace(/\\"/g, '"').replace(/\\'/g, "'");
    $('#detailDescription').html(desc);

    // Weight & Omega3
    $('#detailWeight').text(menu.weight || 0);
    if (menu.omega3 && menu.omega3 > 0) {
      $('#detailOmega3').text(menu.omega3);
      $('#detailOmega3Wrap').show();
    } else {
      $('#detailOmega3Wrap').hide();
    }

    // Health Indicators (V0 스타일 O/X 표시)
    updateHealthIndicator('indicatorLowSugar', menu.sugar != null && menu.sugar <= 4);
    updateHealthIndicator('indicatorOmega3', menu.omega3 && menu.omega3 >= 1000);
    updateHealthIndicator('indicatorProtein', menu.protein && menu.protein >= 20);
    updateHealthIndicator('indicatorCholesterol', menu.cholesterol != null && menu.cholesterol <= 50);

    // Dark Nutrition Panel (V0 스타일)
    var calories = menu.calories || 0;
    var sodium = menu.sodium || 0;
    var carbs = menu.carbs || 0;
    var sugar = menu.sugar || 0;
    var fat = menu.fat || 0;
    var protein = menu.protein || 0;

    $('#panelWeight').text(menu.weight || 0);
    $('#panelCalories').text(calories);
    $('#panelSodium').text(sodium);
    $('#panelSodiumPercent').text(Math.round(sodium / 20) + '%');
    $('#panelCarbs').text(carbs);
    $('#panelCarbsPercent').text(Math.round(carbs / 3.24) + '%');
    $('#panelSugar').text(sugar);
    $('#panelSugarPercent').text(Math.round(sugar) + '%');
    $('#panelFat').text(fat);
    $('#panelFatPercent').text(Math.round(fat / 0.54) + '%');
    $('#panelTransFat').text(0);
    $('#panelSaturatedFat').text(Math.round(fat * 0.3));
    $('#panelSaturatedFatPercent').text(Math.round((fat * 0.3) / 0.15) + '%');
    var cholesterol = menu.cholesterol || 45;
    $('#panelCholesterol').text(cholesterol);
    $('#panelCholesterolPercent').text(Math.round(cholesterol / 3) + '%');
    $('#panelProtein').text(protein);
    $('#panelProteinPercent').text(Math.round(protein / 0.55) + '%');

    // 수량 표시
    updateDetailQtyDisplay(qty);

    $('#menuDetailModal').show();
  }

  function updateHealthIndicator(elementId, isTrue) {
    var $el = $('#' + elementId);
    if (isTrue) {
      $el.text('O').removeClass('no').addClass('yes');
    } else {
      $el.text('X').removeClass('yes').addClass('no');
    }
  }

  function updateDetailQtyDisplay(qty) {
    var $qtyValue = $('#detailQty');
    var $confirmText = $('#detailConfirmText');
    var $qtyMinus = $('#detailQtyMinus');

    $qtyValue.text(qty);

    if (qty > 0) {
      $qtyValue.addClass('active');
      $confirmText.text(qty + '개 선택됨');
      $qtyMinus.prop('disabled', false);
    } else {
      $qtyValue.removeClass('active');
      $confirmText.text('선택 안 함');
      $qtyMinus.prop('disabled', true);
    }
  }

  function closeDetail() {
    $('#menuDetailModal').hide();
    state.selectedMenuItem = null;
  }

  function adjustDetailQty(delta) {
    if (!state.selectedMenuItem) return;
    adjustQuantity(state.selectedMenuItem.id, delta);
    var newQty = state.selections[state.selectedMenuItem.id] || 0;
    updateDetailQtyDisplay(newQty);
  }

  // ============ ADVANCED FILTERS ============
  function showAdvancedFilters() {
    state.showAdvanced = true;
    $('#advancedFiltersOverlay').show();
  }

  function hideAdvancedFilters() {
    state.showAdvanced = false;
    $('#advancedFiltersOverlay').hide();
  }

  function updateFilterValue(type, value) {
    if (type === 'protein') {
      state.advancedFilters.minProtein = parseInt(value);
      $('#proteinValue').text(value);
    } else if (type === 'sodium') {
      state.advancedFilters.maxSodium = parseInt(value);
      $('#sodiumValue').text(value);
    } else if (type === 'calories') {
      state.advancedFilters.maxCalories = parseInt(value);
      $('#caloriesValue').text(value);
    }
  }

  function applyFilters() {
    renderMenuSections();
    hideAdvancedFilters();
  }

  function resetFilters() {
    state.advancedFilters = { minProtein: 0, maxSodium: 700, maxCalories: 700 };
    $('#filterProtein').val(0);
    $('#filterSodium').val(700);
    $('#filterCalories').val(700);
    $('#proteinValue').text('0');
    $('#sodiumValue').text('700');
    $('#caloriesValue').text('700');
    renderMenuSections();
  }

  // (renderSummary 제거됨 — 별도 페이지 custom-diet-summary.js 로 이전)

  // ============ CART VALIDATION ============
  function validateSelections() {
    var total = getSelectedTotal();
    if (total !== state.totalQuantity) {
      alert(state.totalQuantity + '식을 모두 선택해주세요. (현재 ' + total + '식 선택됨)');
      return false;
    }
    return true;
  }

  // ============ PUBLIC API ============
  return {
    init: function(config) {
      console.log('[CustomDietBuilder] init config:', config);

      state.goodsNo = config.goodsNo || '';
      state.menuData = Array.isArray(config.menuData) ? config.menuData : [];
      state.options = Array.isArray(config.options) ? config.options : [];
      state.conditions = config.conditions || [];
      state.userData = config.userData || null;
      state.recommendedLines = config.recommendedLines || null;
      state.premiumAddGoods = config.premiumAddGoods || null;
      state.goodsPrice = parseInt(config.goodsPrice, 10) || 0;

      console.log('[CustomDietBuilder] premiumAddGoods:', state.premiumAddGoods);
      console.log('[CustomDietBuilder] menuData count:', state.menuData.length);
      // 프리미엄 판별 디버깅 (limitCnt > 0 = 프리미엄 메뉴)
      console.log('[CustomDietBuilder] ALL menus (limitCnt/multiplier):', state.menuData.map(function(m) { return m.name + ' L:' + (m.limitCnt||0) + ' M:' + (m.premiumMultiplier||1); }));
      var premiumMenus = state.menuData.filter(function(m) { return isPremiumMenu(m); });
      console.log('[CustomDietBuilder] premium menus (' + premiumMenus.length + '):', premiumMenus.map(function(m) { return m.name + '(limit:' + m.limitCnt + ', x' + m.premiumMultiplier + ')'; }));

      // 첫 번째 옵션 기반으로 초기 수량 설정
      var tiers = parseOptionTiers();
      if (tiers.length > 0) {
        state.totalQuantity = tiers[0].qty;
      }
      renderQuantityButtons();
      state.selectedOptionSno = getOptionPricing(state.totalQuantity).optionSno;

      renderSuggestionCard();
      renderMenuSections();
      renderSelectedPreview();
      updateCounts();
      updatePricing();
    },

    // Quantity management
    adjustQuantity: adjustQuantity,
    handleQuantityChange: handleQuantityChange,

    // Category & Filters
    setCategory: function(cat) {
      state.categoryFilter = cat;
      // Update both mobile and desktop filter chips
      $('#mobileCategoryFilters .filter-chip, #desktopCategoryFilters .filter-chip').removeClass('active');
      $('#mobileCategoryFilters .filter-chip[data-category="' + cat + '"], #desktopCategoryFilters .filter-chip[data-category="' + cat + '"]').addClass('active');
      renderMenuSections();
    },

    showAdvancedFilters: showAdvancedFilters,
    hideAdvancedFilters: hideAdvancedFilters,
    updateFilterValue: updateFilterValue,
    applyFilters: applyFilters,
    resetFilters: resetFilters,

    // Suggestion card toggle
    toggleSuggestion: function() {
      var $mobile = $('#mobileSuggestionCard');
      var $desktop = $('#desktopSuggestionCard');

      if ($mobile.length > 0) {
        $mobile.toggleClass('expanded');
      }
      if ($desktop.length > 0) {
        $desktop.toggleClass('expanded');
      }
    },

    // Modal
    showDetail: showDetail,
    closeDetail: closeDetail,
    adjustDetailQty: adjustDetailQty,

    // Screen navigation
    showSummary: function() {
      if (!validateSelections()) return;
      // sessionStorage에 selections 상태 저장
      var summaryData = {
        selections: state.selections,
        totalQuantity: state.totalQuantity,
        selectedOptionSno: state.selectedOptionSno,
        conditions: state.conditions,
        userData: state.userData,
        recommendedLines: state.recommendedLines
      };
      sessionStorage.setItem('customDietSummary', JSON.stringify(summaryData));
      // 요약 페이지로 이동
      var url = '/goods/goods_view.php?goodsNo=' + state.goodsNo + '&view=summary';
      var params = new URLSearchParams(window.location.search);
      if (params.get('responseSno')) url += '&responseSno=' + params.get('responseSno');
      location.href = url;
    },

    goBack: function() {
      // 설문 리포트 화면으로 돌아가기 (sessionStorage에 리포트 데이터가 있는 경우)
      var saved = null;
      try { saved = JSON.parse(sessionStorage.getItem('dqReportState') || 'null'); } catch (e) {}
      if (saved && saved.goodsNo == state.goodsNo) {
        location.href = '../guide/diet_quiz.html?goodsNo=' + state.goodsNo + '&showReport=1';
        return;
      }
      history.back();
    },

    // Debug
    getState: function() { return state; }
  };
})();
