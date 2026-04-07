/**
 * 나만의 식단 플랜 - Summary Page Module
 * CustomDietBuilder에서 요약 페이지에 필요한 기능만 추출
 * sessionStorage('customDietSummary')에서 상태를 복원하여 렌더링
 */
var CustomDietSummary = (function() {
  'use strict';

  // ============ CONFIG ============
  var NUTRITION_LINES = [
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
      id: 'cal-440',
      label: '440kcal 라인',
      line: '440kcal 라인',
      color: '#22C55E',
      filter: function(i) { return i.lines && i.lines.indexOf('440kcal 라인') !== -1; },
      sort: function(a, b) { return a.calories - b.calories; },
      focus: { name: '칼로리', key: 'calories', unit: 'kcal', max: 500 }
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

  var PLAN_NAMES = {
    'diabetes': { en: 'Glycemic Balance Plan', ko: '혈당 균형 케어 플랜' },
    'gestational-diabetes': { en: 'Maternal Glycemic Plan', ko: '임산부 혈당 케어 플랜' },
    'kidney': { en: 'Renal Care Plan', ko: '신장 케어 플랜' },
    'kidney-pre-dialysis': { en: 'Pre-Dialysis Care Plan', ko: '투석 전 케어 플랜' },
    'kidney-dialysis': { en: 'Dialysis Nutrition Plan', ko: '투석 영양 케어 플랜' },
    'cancer': { en: 'Oncology Nutrition Plan', ko: '항암 영양 케어 플랜' },
    'cholesterol': { en: 'Lipid Balance Plan', ko: '지질 균형 케어 플랜' },
    'weight-loss': { en: 'Metabolic Care Plan', ko: '대사 케어 플랜' },
    'muscle-gain': { en: 'Protein Balance Plan', ko: '근력 영양 케어 플랜' },
    'general': { en: 'Balanced Nutrition Plan', ko: '균형 영양 케어 플랜' }
  };

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

  // ============ STATE ============
  var state = {
    goodsNo: '',
    totalQuantity: 10,
    selections: {},
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

  function escapeHtml(str) {
    return String(str).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  }

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

  function isMenuSoldOut(menu) {
    if (menu.soldOutFl === 'y') return true;
    if (menu.stockUseFl === 'y' && menu.stockCnt <= 0) return true;
    return false;
  }

  function getMealsPerWeek() {
    var tier = getOptionPricing(state.totalQuantity);
    var weeksNum = tier.weeksText ? parseInt(tier.weeksText) : 1;
    return Math.floor(state.totalQuantity / weeksNum) || 10;
  }

  function getScaledLimitCnt(menu) {
    var base = menu.limitCnt || 0;
    if (base <= 0) return 0;
    var amplifier = Math.floor(state.totalQuantity / getMealsPerWeek()) || 1;
    return base * amplifier;
  }

  function isPremiumMenu(menu) {
    return (menu.limitCnt || 0) > 0;
  }

  function getPremiumUnits() {
    if (!state.premiumAddGoods) return 0;
    var units = 0;
    for (var id in state.selections) {
      var menu = getMenuById(id);
      if (!menu) continue;
      var limit = getScaledLimitCnt(menu);
      if (limit <= 0) continue;
      var count = state.selections[id];
      var overflow = Math.max(0, count - limit);
      if (overflow > 0) {
        units += overflow * (menu.premiumMultiplier || 1);
      }
    }
    return units;
  }

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

  // Harris-Benedict BMR × PAL — DietFinder.php와 동일한 공식
  // 설문 userData 기반, 100단위 반올림
  function calculateDailyCalories() {
    var u = state.userData;
    if (!u || !u.height || !u.weight) return 2000; // 폴백
    var age = u.birthYear ? (new Date().getFullYear() - u.birthYear) : 35;
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
    var cal = Math.round(bmr * pal);
    // 100단위 반올림, 1200~3000 제한
    return Math.max(1200, Math.min(3000, Math.round(cal / 100) * 100));
  }

  function getLineColor(lineLabel) {
    for (var i = 0; i < NUTRITION_LINES.length; i++) {
      if (NUTRITION_LINES[i].line === lineLabel || NUTRITION_LINES[i].label === lineLabel) {
        return NUTRITION_LINES[i].color;
      }
    }
    return '#64748b';
  }

  // ============ SUMMARY HELPERS ============
  function getSummaryData() {
    var selectedMenus = [];
    var totalCal = 0, totalProtein = 0, totalCarbs = 0, totalFat = 0, totalSodium = 0, totalOmega3 = 0, totalCount = 0;
    for (var id in state.selections) {
      var menu = getMenuById(id);
      var qty = state.selections[id];
      if (!menu || qty <= 0) continue;
      selectedMenus.push({ menu: menu, qty: qty });
      totalCal += (menu.calories || 0) * qty;
      totalProtein += (menu.protein || 0) * qty;
      totalCarbs += (menu.carbs || 0) * qty;
      totalFat += (menu.fat || 0) * qty;
      totalSodium += (menu.sodium || 0) * qty;
      totalOmega3 += (menu.omega3 || 0) * qty;
      totalCount += qty;
    }
    if (totalCount === 0) return null;
    var avgCal = Math.round(totalCal / totalCount);
    var avgProtein = Math.round(totalProtein / totalCount);
    var avgCarbs = Math.round(totalCarbs / totalCount);
    var avgFat = Math.round(totalFat / totalCount);
    var avgSodium = Math.round(totalSodium / totalCount);
    var avgOmega3 = Math.round(totalOmega3 / totalCount);
    var proteinCal = avgProtein * 4, carbsCal = avgCarbs * 4, fatCal = avgFat * 9;
    var totalMacroCal = proteinCal + carbsCal + fatCal;
    var carbsPct = totalMacroCal > 0 ? Math.round((carbsCal / totalMacroCal) * 100) : 0;
    var proteinPct = totalMacroCal > 0 ? Math.round((proteinCal / totalMacroCal) * 100) : 0;
    var fatPct = totalMacroCal > 0 ? Math.round((fatCal / totalMacroCal) * 100) : 0;
    var conditions = getConditionsArray();
    var condKeys = conditions.map(function(c) { return CONDITION_KEY_MAP[c] || c; });
    var primaryCond = condKeys.length > 0 ? condKeys[0] : 'general';
    var plan = PLAN_NAMES[primaryCond] || PLAN_NAMES['general'];
    var weeksText = getOptionPricing(state.totalQuantity).weeksText || '';
    var chars = [];
    if (avgProtein >= 25) chars.push('고단백');
    if (avgSodium <= 400) chars.push('저염');
    if (avgCal <= 450) chars.push('저칼로리');
    if (avgOmega3 >= 500) chars.push('오메가3 강화');
    var dailyCal = calculateDailyCalories();
    var coveragePerMeal = Math.round((avgCal / dailyCal) * 100);
    var coverage2Meals = Math.min(Math.round((avgCal * 2 / dailyCal) * 100), 100);
    var remainCal = Math.max(0, dailyCal - avgCal * 2);
    return {
      selectedMenus: selectedMenus, totalCount: totalCount,
      avgCal: avgCal, avgProtein: avgProtein, avgCarbs: avgCarbs, avgFat: avgFat,
      avgSodium: avgSodium, avgOmega3: avgOmega3,
      carbsPct: carbsPct, proteinPct: proteinPct, fatPct: fatPct,
      condKeys: condKeys, primaryCond: primaryCond, plan: plan,
      weeksText: weeksText, chars: chars,
      dailyCal: dailyCal, coveragePerMeal: coveragePerMeal,
      coverage2Meals: coverage2Meals, remainCal: remainCal
    };
  }

  function buildSvgDonut(carbsPct, proteinPct, fatPct, centerVal) {
    var r = 48, cx = 65, cy = 65, C = 2 * Math.PI * r, gap = 4;
    var segs = [
      { pct: carbsPct, color: '#3B82F6' },
      { pct: proteinPct, color: '#EF4444' },
      { pct: fatPct, color: '#F59E0B' }
    ];
    var s = '<svg width="130" height="130" viewBox="0 0 130 130">';
    s += '<circle cx="'+cx+'" cy="'+cy+'" r="'+r+'" fill="none" stroke="#e2e8f0" stroke-width="16"/>';
    var offset = 0;
    for (var i = 0; i < segs.length; i++) {
      var len = (segs[i].pct / 100) * C - gap;
      if (len <= 0) { offset += (segs[i].pct / 100) * C; continue; }
      s += '<circle cx="'+cx+'" cy="'+cy+'" r="'+r+'" fill="none" stroke="'+segs[i].color+'" stroke-width="16" ';
      s += 'stroke-dasharray="'+len+' '+(C - len)+'" stroke-dashoffset="'+(-offset)+'" ';
      s += 'transform="rotate(-90 '+cx+' '+cy+')" />';
      offset += (segs[i].pct / 100) * C;
    }
    s += '<text x="'+cx+'" y="'+(cy-4)+'" text-anchor="middle" font-size="24" font-weight="bold" fill="#1a1a2e">'+centerVal+'</text>';
    s += '<text x="'+cx+'" y="'+(cy+14)+'" text-anchor="middle" font-size="11" fill="#64748b">kcal</text>';
    s += '</svg>';
    return s;
  }

  function getSummaryExpectedEffects(condKeys) {
    var effects = [];
    if (condKeys.indexOf('diabetes') >= 0 || condKeys.indexOf('gestational-diabetes') >= 0)
      effects.push({ title: '혈당 관리 지원', desc: '저GI 식단 구성으로 식후 혈당 변동폭을 줄이고 안정적인 혈당 관리를 돕습니다.' });
    if (condKeys.indexOf('weight-loss') >= 0)
      effects.push({ title: '건강한 체중 관리', desc: '적정 칼로리 내 고단백 식단으로 근손실 없는 체중 관리를 지원합니다.' });
    if (condKeys.indexOf('kidney') >= 0 || condKeys.indexOf('kidney-pre-dialysis') >= 0 || condKeys.indexOf('kidney-dialysis') >= 0)
      effects.push({ title: '신장 부담 경감', desc: '나트륨과 단백질 조절을 통해 신장 기능에 무리가 가지 않는 영양 구성을 제공합니다.' });
    if (condKeys.indexOf('cholesterol') >= 0)
      effects.push({ title: '지질 균형 지원', desc: '불포화 지방산 중심의 식단으로 혈중 콜레스테롤 수치 관리에 도움을 줍니다.' });
    if (condKeys.indexOf('muscle-gain') >= 0)
      effects.push({ title: '근육량 증가 지원', desc: '1식당 고단백 구성으로 근합성에 필요한 아미노산을 안정적으로 공급합니다.' });
    if (condKeys.indexOf('cancer') >= 0)
      effects.push({ title: '항암 영양 지원', desc: '고단백·고칼로리 설계로 치료 중 체력 유지와 회복을 돕습니다.' });
    effects.push({ title: '식습관 안정화', desc: '영양사가 설계한 균형 잡힌 식단으로 불규칙한 식사 패턴을 개선합니다.' });
    effects.push({ title: '영양 균형 개선', desc: '매끼 필수 영양소를 골고루 섭취하여 전반적인 영양 상태를 향상시킵니다.' });
    return effects.slice(0, 4);
  }

  // ============ SUMMARY RENDER ============
  function renderSummary() {
    var d = getSummaryData();
    if (!d) return;

    var optPricing = getOptionPricing(state.totalQuantity);
    var premiumTotal = calculateTotalPremium();
    var basePrice = optPricing.optionPrice;
    var totalPrice = basePrice + premiumTotal;
    var perMeal = state.totalQuantity > 0 ? Math.round(totalPrice / state.totalQuantity) : 0;
    var html = '';

    // ══════ 2-Column Grid Wrapper ══════
    html += '<div class="sp-two-col">';

    // ── LEFT COLUMN ──
    html += '<div class="sp-col-left">';

    // ── Plan Name Card ──
    html += '<section class="sp-plan-card">';
    html += '<div class="sp-plan-deco"><div class="deco-circle deco-circle-1"></div><div class="deco-circle deco-circle-2"></div></div>';
    html += '<div class="sp-plan-inner">';
    html += '<div class="sp-plan-label"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/></svg> Medisola Care Plan</div>';
    html += '<p class="sp-plan-en">' + d.plan.en + '</p>';
    html += '<h2 class="sp-plan-ko">' + d.plan.ko + '</h2>';
    html += '<div class="sp-plan-badges">';
    html += '<span class="sp-badge">' + state.totalQuantity + '식 / ' + d.weeksText + '</span>';
    html += '<span class="sp-badge">' + d.selectedMenus.length + '종 메뉴</span>';
    for (var ci = 0; ci < d.chars.length; ci++) {
      html += '<span class="sp-badge sp-badge-dim">' + d.chars[ci] + '</span>';
    }
    html += '</div>';
    html += '<div class="sp-plan-desc">하루 필요 칼로리 <strong>' + d.dailyCal + 'kcal</strong> 중 메디쏠라 식사 2식(점심/저녁)이 <strong>' + d.coverage2Meals + '%</strong>를 충족하며, 아침 식사와 간식으로 영양 균형을 맞추는 구조입니다.</div>';
    html += '</div></section>';

    // ── Macro Chart + Stats ──
    html += '<section class="sp-section sp-macro-section">';
    html += '<h3 class="sp-section-label">1식 평균 영양 구성</h3>';
    html += '<div class="sp-macro-row">';
    html += '<div class="sp-donut-wrap">' + buildSvgDonut(d.carbsPct, d.proteinPct, d.fatPct, d.avgCal) + '</div>';
    html += '<div class="sp-macro-list">';
    var macros = [
      { name: '탄수화물', g: d.avgCarbs, pct: d.carbsPct, color: '#3B82F6' },
      { name: '단백질', g: d.avgProtein, pct: d.proteinPct, color: '#EF4444' },
      { name: '지방', g: d.avgFat, pct: d.fatPct, color: '#F59E0B' }
    ];
    for (var mi = 0; mi < macros.length; mi++) {
      var m = macros[mi];
      html += '<div class="sp-macro-item"><div class="sp-macro-dot" style="background:'+m.color+'"></div>';
      html += '<div class="sp-macro-detail"><div class="sp-macro-top"><span class="sp-macro-g">'+m.g+'g</span> <span class="sp-macro-name">'+m.name+'</span></div>';
      html += '<div class="sp-macro-bar-wrap"><div class="sp-macro-bar" style="width:'+Math.min(m.pct,100)+'%;background:'+m.color+'"></div></div>';
      html += '</div><span class="sp-macro-pct">'+m.pct+'%</span></div>';
    }
    html += '</div></div>';
    html += '<div class="sp-macro-footer">1일 필요' + d.dailyCal + 'kcal 대비 <strong>' + d.coverage2Meals + '%</strong> 충족 (2식 기준)</div>';
    // Quick Stats
    html += '<div class="sp-quick-stats">';
    html += '<div class="sp-stat"><div class="sp-stat-icon sp-stat-red"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"/></svg></div><span class="sp-stat-val">' + d.avgProtein + 'g</span><span class="sp-stat-lbl">단백질</span></div>';
    html += '<div class="sp-stat"><div class="sp-stat-icon sp-stat-blue"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 16.3c2.2 0 4-1.83 4-4.05 0-1.16-.57-2.26-1.71-3.19S7.29 6.75 7 5.3c-.29 1.45-1.14 2.84-2.29 3.76S3 11.1 3 12.25c0 2.22 1.8 4.05 4 4.05z"/></svg></div><span class="sp-stat-val">' + d.avgSodium + 'mg</span><span class="sp-stat-lbl">나트륨</span></div>';
    html += '<div class="sp-stat"><div class="sp-stat-icon sp-stat-cyan"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6.5 12c.94-3.46 4.94-6 8.5-6 3.56 0 6.06 2.54 7 6-.94 3.47-3.44 6-7 6s-7.56-2.53-8.5-6Z"/></svg></div><span class="sp-stat-val">' + d.avgOmega3 + 'mg</span><span class="sp-stat-lbl">Omega-3</span></div>';
    html += '</div></section>';

    // ── Pricing (left column) ──
    html += '<section class="sp-section sp-pricing-section">';
    html += '<div class="sp-pricing-header"><span class="sp-pricing-title">결제 금액</span>';
    html += '<span class="sp-pricing-sub">' + state.totalQuantity + '식 / ' + d.weeksText + ' 기준</span></div>';
    html += '<div class="sp-pricing-row"><span>기본 플랜 가격</span><span>' + formatNumber(basePrice) + '원</span></div>';
    if (premiumTotal > 0) {
      html += '<div class="sp-pricing-row sp-pricing-premium"><span>프리미엄 메뉴 추가</span><span>+' + formatNumber(premiumTotal) + '원</span></div>';
    }
    html += '<div class="sp-pricing-total"><span>총 결제 금액</span><span>' + formatNumber(totalPrice) + '원</span></div>';
    html += '<p class="sp-pricing-per">1식당 ' + formatNumber(perMeal) + '원</p>';
    html += '</section>';

    // ── CTA Buttons (inline, V0 style) ──
    html += '<div class="sp-cta-inline">';
    html += '<button class="sp-cta-primary" onclick="CustomDietSummary.buyNow()"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg> 이 가격으로 식단 시작하기</button>';
    html += '<button class="sp-cta-secondary" onclick="CustomDietSummary.addToCart()"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg> 이 식단 장바구니에 담아두기</button>';
    html += '</div>';

    html += '</div>'; // end sp-col-left

    // ── RIGHT COLUMN ──
    html += '<div class="sp-col-right">';

    // ── Daily Meal Execution Guide ──
    var breakfastCal = Math.round(d.remainCal * 0.6);
    var snackCal = Math.round(d.remainCal * 0.4);
    html += '<section class="sp-section sp-guide-section">';
    html += '<div class="sp-guide-header"><div class="sp-guide-icon"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/></svg></div>';
    html += '<div><h3 class="sp-guide-title">하루 식사 실행 가이드</h3><p class="sp-guide-sub">하루 ' + d.dailyCal + 'kcal 기준</p></div></div>';
    html += '<div class="sp-timeline">';
    // Breakfast
    html += '<div class="sp-tl-item"><div class="sp-tl-left"><div class="sp-tl-dot sp-dot-amber"></div><div class="sp-tl-line"></div></div>';
    html += '<div class="sp-tl-content"><div class="sp-tl-head"><span class="sp-tl-title">아침 - 가벼운 자가 식사</span><span class="sp-tl-kcal sp-kcal-amber">~'+breakfastCal+'kcal</span></div>';
    html += '<p class="sp-tl-desc">하루를 여는 가벼운 식사로 아래 중 2~3가지를 조합해 보세요.</p>';
    html += '<div class="sp-food-list">';
    html += '<div class="sp-food sp-food-amber"><span>&#x1F35A;</span> 현미밥 반공기 <em>130kcal</em></div>';
    html += '<div class="sp-food sp-food-amber"><span>&#x1F95A;</span> 달걀 2개 <em>140kcal, 단백질 12g</em></div>';
    html += '<div class="sp-food sp-food-amber"><span>&#x1F957;</span> 채소 샐러드 <em>50kcal</em></div>';
    html += '</div></div></div>';
    // Lunch
    html += '<div class="sp-tl-item"><div class="sp-tl-left"><div class="sp-tl-dot sp-dot-primary sp-dot-ring"></div><div class="sp-tl-line"></div></div>';
    html += '<div class="sp-tl-content"><div class="sp-tl-head"><span class="sp-tl-title sp-tl-primary">점심 - 메디쏠라 식사</span><span class="sp-tl-badge">CORE</span></div>';
    html += '<p class="sp-tl-desc">영양 설계된 핵심 식사입니다. 1식 '+d.avgCal+'kcal로 하루 필요 열량의 '+d.coveragePerMeal+'%를 충족합니다.</p>';
    html += '<div class="sp-tl-nutrition"><span class="sp-tl-nut-val">'+d.avgCal+'kcal / 단백질 '+d.avgProtein+'g</span><span class="sp-tl-nut-pct">1일의 <strong>'+d.coveragePerMeal+'%</strong></span></div>';
    html += '</div></div>';
    // Dinner
    html += '<div class="sp-tl-item"><div class="sp-tl-left"><div class="sp-tl-dot sp-dot-primary sp-dot-ring"></div><div class="sp-tl-line"></div></div>';
    html += '<div class="sp-tl-content"><div class="sp-tl-head"><span class="sp-tl-title sp-tl-primary">저녁 - 메디쏠라 식사</span><span class="sp-tl-badge">CORE</span></div>';
    html += '<p class="sp-tl-desc">점심과 다른 메뉴를 배치하여 균형 잡힌 영양 관리를 이어가세요.</p>';
    html += '<div class="sp-tl-nutrition"><span class="sp-tl-nut-val">'+d.avgCal+'kcal / 단백질 '+d.avgProtein+'g</span><span class="sp-tl-nut-pct">1일의 <strong>'+d.coveragePerMeal+'%</strong></span></div>';
    html += '</div></div>';
    // Snack
    html += '<div class="sp-tl-item"><div class="sp-tl-left"><div class="sp-tl-dot sp-dot-green"></div></div>';
    html += '<div class="sp-tl-content"><div class="sp-tl-head"><span class="sp-tl-title">간식</span><span class="sp-tl-kcal sp-kcal-green">~'+snackCal+'kcal</span></div>';
    html += '<p class="sp-tl-desc">부족한 영양소를 보충하는 가벼운 간식을 추천드립니다.</p>';
    html += '<div class="sp-food-list">';
    html += '<div class="sp-food sp-food-green"><span>&#x1F330;</span> 견과류 한 줌 <em>150kcal</em></div>';
    html += '<div class="sp-food sp-food-green"><span>&#x1F34E;</span> 과일 1회분 <em>80kcal</em></div>';
    if (d.condKeys.indexOf('diabetes') >= 0 || d.condKeys.indexOf('gestational-diabetes') >= 0) {
      html += '<div class="sp-food sp-food-green"><span>&#x1F964;</span> 무가당 두유 <em>80kcal, 저GI</em></div>';
    } else {
      html += '<div class="sp-food sp-food-green"><span>&#x1F95A;</span> 삶은 달걀 1개 <em>70kcal, 단백질 6g</em></div>';
    }
    html += '</div></div></div>';
    html += '</div>'; // end timeline
    // Daily calorie bar
    html += '<div class="sp-daily-bar-section">';
    html += '<p class="sp-daily-bar-label">1일 칼로리 배분</p>';
    html += '<div class="sp-daily-bar">';
    var bfPct = Math.round((breakfastCal / d.dailyCal) * 100);
    html += '<div class="sp-bar-seg sp-bar-amber" style="width:'+bfPct+'%">아침</div>';
    html += '<div class="sp-bar-seg sp-bar-primary" style="width:'+d.coveragePerMeal+'%">점심</div>';
    html += '<div class="sp-bar-seg sp-bar-primary-dim" style="width:'+d.coveragePerMeal+'%">저녁</div>';
    html += '<div class="sp-bar-seg sp-bar-green" style="flex:1">간식</div>';
    html += '</div>';
    html += '<div class="sp-daily-bar-footer"><span>메디쏠라 2식 = <strong>'+d.coverage2Meals+'%</strong></span><span>총 '+d.dailyCal+'kcal</span></div>';
    html += '</div></section>';

    // ── Expected Effects ──
    var effects = getSummaryExpectedEffects(d.condKeys);
    html += '<section class="sp-section sp-effects-section">';
    html += '<div class="sp-effects-header"><div class="sp-effects-icon"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"/></svg></div>';
    html += '<div><h3 class="sp-effects-title">' + d.weeksText + ' 플랜 예상 효과</h3><p class="sp-effects-sub">꾸준히 실행하셨을 때 기대할 수 있는 변화</p></div></div>';
    html += '<div class="sp-effects-list">';
    for (var ei = 0; ei < effects.length; ei++) {
      html += '<div class="sp-effect-item"><div class="sp-effect-title">' + effects[ei].title + '</div>';
      html += '<p class="sp-effect-desc">' + effects[ei].desc + '</p></div>';
    }
    html += '</div>';
    html += '<p class="sp-effects-disclaimer">본 플랜은 질병의 치료나 개선을 목적으로 하지 않으며, 건강한 식습관 형성과 영양 관리를 지원합니다.</p>';
    html += '</section>';

    // ── Menu List ──
    html += '<section class="sp-section sp-menu-list-section">';
    html += '<div class="sp-ml-header"><h3>메뉴 구성 (' + d.selectedMenus.length + '종)</h3>';
    html += '<button class="sp-ml-edit" onclick="history.back()">수정 <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg></button></div>';
    html += '<div class="sp-ml-list" id="summaryMenuListV2">';
    var showCount = Math.min(d.selectedMenus.length, 4);
    for (var si = 0; si < d.selectedMenus.length; si++) {
      var sm = d.selectedMenus[si];
      var hiddenClass = si >= showCount ? ' sp-ml-hidden' : '';
      html += '<div class="sp-ml-item' + hiddenClass + '">';
      html += '<img src="' + (sm.menu.image || '/data/goods/default_goods.png') + '" class="sp-ml-img" alt="">';
      html += '<div class="sp-ml-info"><div class="sp-ml-name">' + sm.menu.name + '</div>';
      html += '<div class="sp-ml-nut">' + (sm.menu.calories||0) + 'kcal / 단백질 ' + (sm.menu.protein||0) + 'g';
      if (isPremiumMenu(sm.menu) && state.premiumAddGoods) {
        var smSurcharge = (state.premiumAddGoods.goodsPrice || 0) * (sm.menu.premiumMultiplier || 1);
        html += ' <span class="card-premium-badge"><svg class="crown-icon" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M5 16L3 5l5.5 5L12 4l3.5 6L21 5l-2 11H5z"/><path d="M5 19h14v2H5z"/></svg> +' + formatNumber(smSurcharge) + '원</span>';
      }
      html += '</div></div>';
      html += '<div class="sp-ml-qty">' + sm.qty + '</div></div>';
    }
    html += '</div>';
    if (d.selectedMenus.length > 4) {
      html += '<button class="sp-ml-toggle" onclick="document.querySelectorAll(\'.sp-ml-hidden\').forEach(function(e){e.classList.remove(\'sp-ml-hidden\')});this.style.display=\'none\';">전체 ' + d.selectedMenus.length + '종 메뉴 보기 <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg></button>';
    }
    html += '</section>';

    // ── Purchase Info Accordion (DB 데이터) ──
    var pi = state.purchaseInfo || {};
    html += '<section class="sp-section sp-purchase-section">';
    html += '<h3 class="sp-purchase-title">구매 안내</h3>';
    // Accordion items — DB에서 가져온 데이터
    html += '<div class="sp-accordion">';
    if (pi.goodsMustInfo) {
      html += '<div class="sp-acc-item"><button class="sp-acc-btn" onclick="this.parentElement.classList.toggle(\'open\')"><span>상품필수 정보</span><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg></button>';
      html += '<div class="sp-acc-body">' + pi.goodsMustInfo + '</div></div>';
    }
    if (pi.delivery) {
      html += '<div class="sp-acc-item"><button class="sp-acc-btn" onclick="this.parentElement.classList.toggle(\'open\')"><span>배송 안내</span><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg></button>';
      html += '<div class="sp-acc-body">' + pi.delivery + '</div></div>';
    }
    if (pi.exchange) {
      html += '<div class="sp-acc-item"><button class="sp-acc-btn" onclick="this.parentElement.classList.toggle(\'open\')"><span>교환 및 반품</span><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg></button>';
      html += '<div class="sp-acc-body">' + pi.exchange + '</div></div>';
    }
    if (pi.refund) {
      html += '<div class="sp-acc-item"><button class="sp-acc-btn" onclick="this.parentElement.classList.toggle(\'open\')"><span>환불 안내</span><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg></button>';
      html += '<div class="sp-acc-body">' + pi.refund + '</div></div>';
    }
    html += '</div>';
    // Customer service (서비스 문의)
    if (pi.as) {
      html += '<div class="sp-cs-bar">' + pi.as + '</div>';
    }
    html += '</section>';

    html += '</div>'; // end sp-col-right
    html += '</div>'; // end sp-two-col

    $('#summaryContent').html(html);
    $('#goodsPriceSum').val(basePrice);
    $('#addGoodsPriceSum').val(premiumTotal);
    $('#setTotalPrice').val(totalPrice);
  }

  // ============ CART SUBMISSION ============
  function validateSelections() {
    var total = getSelectedTotal();
    if (total !== state.totalQuantity) {
      alert(state.totalQuantity + '식을 모두 선택해주세요. (현재 ' + total + '식 선택됨)');
      return false;
    }
    return true;
  }

  function buildFormInputs() {
    var $comp = $('#componentGoodsInputs');
    var $addG = $('#addGoodsInputs');
    $comp.empty();
    $addG.empty();

    for (var id in state.selections) {
      var count = state.selections[id];
      if (count <= 0) continue;

      var menu = getMenuById(id);
      if (!menu) continue;

      var limit = getScaledLimitCnt(menu);
      var overflow = (limit > 0) ? Math.max(0, count - limit) : 0;
      var itemAddedPrice = overflow * (menu.premiumMultiplier || 1) * (state.premiumAddGoods ? state.premiumAddGoods.goodsPrice : 0);

      console.log('[buildFormInputs]', menu.name, '| count:', count, '| limitCnt:', menu.limitCnt, '| scaledLimit:', limit, '| overflow:', overflow, '| multiplier:', menu.premiumMultiplier, '| itemAddedPrice:', itemAddedPrice);

      $comp.append(
        '<input type="hidden" name="componentGoodsNo[0][]" value="' + menu.addGoodsNo + '">' +
        '<input type="hidden" name="componentGoodsCnt[0][]" value="' + count + '">' +
        '<input type="hidden" name="componentGoodsAddedPrice[0][]" value="' + itemAddedPrice + '">' +
        '<input type="hidden" name="componentGoodsName[0][]" value="' + escapeHtml(menu.name) + '">'
      );
    }

    var premiumUnits = getPremiumUnits();
    var totalPremium = calculateTotalPremium();
    if (state.premiumAddGoods && premiumUnits > 0) {
      $addG.append(
        '<input type="hidden" name="addGoodsNo[0][]" value="' + state.premiumAddGoods.addGoodsNo + '">' +
        '<input type="hidden" name="addGoodsCnt[0][]" value="' + premiumUnits + '">' +
        '<input type="hidden" name="add_goods_total_price[0][]" value="' + totalPremium + '">'
      );
    }

    var optPricing = getOptionPricing(state.totalQuantity);
    $('#optionSno').val(optPricing.optionSno);
    $('#goodsPriceSum').val(optPricing.optionPrice);
    $('#addGoodsPriceSum').val(totalPremium);
    $('#setTotalPrice').val(optPricing.optionPrice + totalPremium);
  }

  function validateSoldOut() {
    for (var id in state.selections) {
      var menu = getMenuById(id);
      if (menu && isMenuSoldOut(menu)) {
        alert(menu.name + '은(는) 품절되었습니다.');
        delete state.selections[id];
        return false;
      }
    }
    return true;
  }

  function addToCart() {
    if (!validateSelections()) return;
    if (!validateSoldOut()) return;
    buildFormInputs();

    $('input[name="cartMode"]').val('');

    $.ajax({
      method: 'POST',
      url: '../order/cart_ps.php',
      data: $('#frmView').serialize(),
      dataType: 'json',
      success: function(data) {
        if (data && data.error) {
          alert(data.message || '장바구니 담기에 실패했습니다.');
          return;
        }
        sessionStorage.removeItem('customDietSummary');
        location.href = '../order/cart.php';
      },
      error: function() {
        sessionStorage.removeItem('customDietSummary');
        location.href = '../order/cart.php';
      }
    });
  }

  function buyNow() {
    if (!validateSelections()) return;
    if (!validateSoldOut()) return;
    buildFormInputs();

    sessionStorage.removeItem('customDietSummary');
    $('input[name="cartMode"]').val('d');
    $('#frmView').attr('action', '../order/cart_ps.php');
    $('#frmView').submit();
  }

  // ============ STATE RESTORE ============
  function restoreState() {
    var raw = sessionStorage.getItem('customDietSummary');
    if (!raw) {
      console.warn('[CustomDietSummary] No session data found');
      return false;
    }
    try {
      var saved = JSON.parse(raw);
      if (saved.selections) state.selections = saved.selections;
      if (saved.totalQuantity) state.totalQuantity = saved.totalQuantity;
      if (saved.selectedOptionSno) state.selectedOptionSno = saved.selectedOptionSno;
      if (saved.conditions) state.conditions = saved.conditions;
      if (saved.userData) state.userData = saved.userData;
      if (saved.recommendedLines) state.recommendedLines = saved.recommendedLines;
      console.log('[CustomDietSummary] State restored:', state);
      return true;
    } catch (e) {
      console.error('[CustomDietSummary] Failed to parse session data:', e);
      return false;
    }
  }

  // ============ PUBLIC API ============
  return {
    init: function(config) {
      console.log('[CustomDietSummary] init config:', config);

      // Set server data from config
      state.goodsNo = config.goodsNo || '';
      state.menuData = Array.isArray(config.menuData) ? config.menuData : [];
      state.options = Array.isArray(config.options) ? config.options : [];
      state.premiumAddGoods = config.premiumAddGoods || null;
      state.goodsPrice = parseInt(config.goodsPrice, 10) || 0;
      state.purchaseInfo = config.purchaseInfo || {};

      // Restore user selections from sessionStorage
      if (!restoreState()) {
        console.warn('[CustomDietSummary] No saved state. Redirecting back.');
        history.back();
        return;
      }

      // Render summary
      renderSummary();
    },

    addToCart: addToCart,
    buyNow: buyNow,
    goBack: function() {
      history.back();
    },

    // Debug
    getState: function() { return state; }
  };
})();
