/**
 * NUTRITION_LINES 공유 설정
 * 식단 플랜 builder, summary 에서 공통으로 사용하는 영양 라인 정의
 */
window.NUTRITION_LINES = [
  {
    id: 'high-protein',
    label: '고단백 라인',
    line: '고단백',
    color: '#EF4444',
    filter: function(i) { return i.lines && i.lines.indexOf('고단백') !== -1; },
    sort: function(a, b) { return b.protein - a.protein; },
    focus: { name: '단백질', key: 'protein', unit: 'g', max: 55 }
  },
  {
    id: 'low-sodium',
    label: '저나트륨 라인',
    line: '저나트륨',
    color: '#3B82F6',
    filter: function(i) { return i.lines && i.lines.indexOf('저나트륨') !== -1; },
    sort: function(a, b) { return a.sodium - b.sodium; },
    focus: { name: '나트륨', key: 'sodium', unit: 'mg', max: 2000 }
  },
  {
    id: 'omega3',
    label: 'Omega-3 함유',
    line: '오메가3',
    color: 'rgb(14, 165, 233)',
    filter: function(i) { return i.lines && i.lines.indexOf('오메가3') !== -1; },
    sort: function(a, b) { return b.omega3 - a.omega3; },
    focus: { name: '오메가3', key: 'omega3', unit: 'mg', max: 1600 }
  },
  {
    id: 'cal-550',
    label: '550kcal 라인',
    line: '550kcal 라인',
    color: '#F97316',
    filter: function(i) { return i.lines && i.lines.indexOf('550kcal 라인') !== -1; },
    sort: function(a, b) { return a.calories - b.calories; },
    focus: { name: '칼로리', key: 'calories', unit: 'kcal', max: 2000 }
  },
  {
    id: 'low-sugar',
    label: '저당 라인',
    line: '저당',
    color: '#F59E0B',
    filter: function(i) { return i.lines && i.lines.indexOf('저당') !== -1; },
    sort: function(a, b) { return a.sugar - b.sugar; },
    focus: { name: '당', key: 'sugar', unit: 'g', max: 50 }
  },
  {
    id: 'low-saturated-fat',
    label: '저포화지방 라인',
    line: '저포화지방',
    color: '#EC4899',
    filter: function(i) { return i.lines && i.lines.indexOf('저포화지방') !== -1; },
    sort: function(a, b) { return a.saturatedFat - b.saturatedFat; },
    focus: { name: '포화지방', key: 'saturatedFat', unit: 'g', max: 15 }
  },
  {
    id: 'low-cholesterol',
    label: '저콜레스테롤 라인',
    line: '저콜레스테롤',
    color: '#14B8A6',
    filter: function(i) { return i.lines && i.lines.indexOf('저콜레스테롤') !== -1; },
    sort: function(a, b) { return a.cholesterol - b.cholesterol; },
    focus: { name: '콜레스테롤', key: 'cholesterol', unit: 'mg', max: 300 }
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

// ── 질환식 설정 ──
// 질환 조건 선택 시 라인 대신 질환식 단위로 메뉴를 그룹핑
// 백엔드 recommendedDiseaseDiets[].id 로 매칭
window.DISEASE_DIETS = [
  {
    id: 'diabetes-care',
    diseaseType: '당뇨케어',
    label: '당뇨식',
    color: '#3B82F6',
    filter: function(i) { return i.diseaseType && i.diseaseType.indexOf('당뇨케어') !== -1; },
    sort: function(a, b) { return (a.sugar || 0) - (b.sugar || 0); },
    focus: { name: '당류', key: 'sugar', unit: 'g', max: 50 }
  },
  {
    id: 'gest-diabetes-care',
    diseaseType: '당뇨케어',
    label: '임신성당뇨식',
    color: '#EC4899',
    filter: function(i) { return i.diseaseType && i.diseaseType.indexOf('당뇨케어') !== -1; },
    sort: function(a, b) { return (a.sugar || 0) - (b.sugar || 0); },
    focus: { name: '당류', key: 'sugar', unit: 'g', max: 50 }
  },
  {
    id: 'kidney-pre-care',
    diseaseType: '신장튼튼',
    label: '신장튼튼식',
    color: '#8B5CF6',
    filter: function(i) { return i.diseaseType && i.diseaseType.indexOf('신장튼튼') !== -1; },
    sort: function(a, b) { return (a.sodium || 0) - (b.sodium || 0); },
    focus: { name: '나트륨', key: 'sodium', unit: 'mg', max: 1500 }
  },
  {
    id: 'kidney-dial-care',
    diseaseType: '신장케어',
    label: '신장케어식',
    color: '#7C3AED',
    filter: function(i) { return i.diseaseType && i.diseaseType.indexOf('신장케어') !== -1; },
    sort: function(a, b) { return (b.protein || 0) - (a.protein || 0); },
    focus: { name: '단백질', key: 'protein', unit: 'g', max: null }
  },
  {
    id: 'cancer-care',
    diseaseType: '암케어',
    label: '암케어식',
    color: '#F472B6',
    filter: function(i) { return i.diseaseType && i.diseaseType.indexOf('암케어') !== -1; },
    sort: function(a, b) { return (b.protein || 0) - (a.protein || 0); },
    focus: { name: '단백질', key: 'protein', unit: 'g', max: null }
  },
  {
    id: 'breast-cancer-care',
    diseaseType: '핑크리본',
    label: '핑크리본',
    color: '#EC4899',
    filter: function(i) { return i.diseaseType && i.diseaseType.indexOf('핑크리본') !== -1; },
    sort: function(a, b) { return (b.protein || 0) - (a.protein || 0); },
    focus: { name: '단백질', key: 'protein', unit: 'g', max: null }
  }
];
