/**
 * 아침 식사 & 간식 권장 메뉴 설정
 *
 * 조회 키: (성별, 칼로리밴드, 질환그룹)
 * 칼로리밴드: 1200-1400, 1500-1700, 1800-2000, 2100-2300, 2400-2600, 2700-3000
 * 질환그룹: general, diabetes, kidney, cancer
 *
 * 룩업 순서:
 *   1) overrides[gender][exactCal][disease] — 100kcal 단위 정확 매칭
 *   2) bands[gender][band][disease] — 칼로리 밴드 매칭
 *   3) bands[gender][band]['general'] — 질환 폴백
 *   4) defaults[mealType] — 최종 폴백 (현재 하드코딩과 동일)
 *
 * 채워 넣기 예시:
 *   bands.male['1800-2000'].diabetes.breakfast = {
 *     title: '아침 - 혈당 안정 식사',
 *     desc: '저GI 식단으로 혈당 급등을 방지합니다.',
 *     items: [
 *       { emoji: '\u{1F35A}', name: '잡곡밥 반공기', kcal: 120, note: '저GI' },
 *       { emoji: '\u{1F95A}', name: '달걀찜', kcal: 100, note: '단백질 10g' },
 *       { emoji: '\u{1F96C}', name: '시금치 나물', kcal: 30, note: '식이섬유' }
 *     ]
 *   };
 */
window.MEAL_RECOMMENDATIONS = {

  // ── 칼로리 밴드 정의 ──
  bands_def: [
    { id: '1200-1400', min: 1200, max: 1400 },
    { id: '1500-1700', min: 1500, max: 1700 },
    { id: '1800-2000', min: 1800, max: 2000 },
    { id: '2100-2300', min: 2100, max: 2300 },
    { id: '2400-2600', min: 2400, max: 2600 },
    { id: '2700-3000', min: 2700, max: 3000 }
  ],

  // ── 질환 조건 → 그룹 매핑 ──
  // 매핑에 없는 조건(weight-loss, general, muscle-gain 등)은 'general' 처리
  disease_group_map: {
    'diabetes': 'diabetes',
    'gestational-diabetes': 'diabetes',
    'kidney-pre-dialysis': 'kidney',
    'kidney-dialysis': 'kidney',
    'cancer': 'cancer',
    'breast-cancer': 'cancer'
  },

  // ── 최종 폴백 (현재 하드코딩과 동일) ──
  defaults: {
    breakfast: {
      title: '아침 - 가벼운 자가 식사',
      desc: '하루를 여는 가벼운 식사로 아래 중 2~3가지를 조합해 보세요.',
      items: [
        { emoji: '\u{1F35A}', name: '현미밥 반공기', kcal: 130, note: '' },
        { emoji: '\u{1F95A}', name: '달걀 2개', kcal: 140, note: '단백질 12g' },
        { emoji: '\u{1F957}', name: '채소 샐러드', kcal: 50, note: '' }
      ]
    },
    snack: {
      title: '간식',
      desc: '부족한 영양소를 보충하는 가벼운 간식을 추천드립니다.',
      items: [
        { emoji: '\u{1F330}', name: '견과류 한 줌', kcal: 150, note: '' },
        { emoji: '\u{1F34E}', name: '과일 1회분', kcal: 80, note: '' },
        { emoji: '\u{1F95A}', name: '삶은 달걀 1개', kcal: 70, note: '단백질 6g' }
      ]
    }
  },

  // ── 칼로리 밴드별 권장 식단 ──
  // null = 아직 미입력 → defaults 폴백 사용
  // 채워 넣을 때: { title: '...', desc: '...', items: [{ emoji, name, kcal, note }] }
  bands: {
    male: {
      '1200-1400': {
        general:  { breakfast: null, snack: null },
        diabetes: { breakfast: null, snack: null },
        kidney:   { breakfast: null, snack: null },
        cancer:   { breakfast: null, snack: null }
      },
      '1500-1700': {
        general:  { breakfast: null, snack: null },
        diabetes: { breakfast: null, snack: null },
        kidney:   { breakfast: null, snack: null },
        cancer:   { breakfast: null, snack: null }
      },
      '1800-2000': {
        general:  { breakfast: null, snack: null },
        diabetes: { breakfast: null, snack: null },
        kidney:   { breakfast: null, snack: null },
        cancer:   { breakfast: null, snack: null }
      },
      '2100-2300': {
        general:  { breakfast: null, snack: null },
        diabetes: { breakfast: null, snack: null },
        kidney:   { breakfast: null, snack: null },
        cancer:   { breakfast: null, snack: null }
      },
      '2400-2600': {
        general:  { breakfast: null, snack: null },
        diabetes: { breakfast: null, snack: null },
        kidney:   { breakfast: null, snack: null },
        cancer:   { breakfast: null, snack: null }
      },
      '2700-3000': {
        general:  { breakfast: null, snack: null },
        diabetes: { breakfast: null, snack: null },
        kidney:   { breakfast: null, snack: null },
        cancer:   { breakfast: null, snack: null }
      }
    },
    female: {
      '1200-1400': {
        general:  { breakfast: null, snack: null },
        diabetes: { breakfast: null, snack: null },
        kidney:   { breakfast: null, snack: null },
        cancer:   { breakfast: null, snack: null }
      },
      '1500-1700': {
        general:  { breakfast: null, snack: null },
        diabetes: { breakfast: null, snack: null },
        kidney:   { breakfast: null, snack: null },
        cancer:   { breakfast: null, snack: null }
      },
      '1800-2000': {
        general:  { breakfast: null, snack: null },
        diabetes: { breakfast: null, snack: null },
        kidney:   { breakfast: null, snack: null },
        cancer:   { breakfast: null, snack: null }
      },
      '2100-2300': {
        general:  { breakfast: null, snack: null },
        diabetes: { breakfast: null, snack: null },
        kidney:   { breakfast: null, snack: null },
        cancer:   { breakfast: null, snack: null }
      },
      '2400-2600': {
        general:  { breakfast: null, snack: null },
        diabetes: { breakfast: null, snack: null },
        kidney:   { breakfast: null, snack: null },
        cancer:   { breakfast: null, snack: null }
      },
      '2700-3000': {
        general:  { breakfast: null, snack: null },
        diabetes: { breakfast: null, snack: null },
        kidney:   { breakfast: null, snack: null },
        cancer:   { breakfast: null, snack: null }
      }
    }
  },

  // ── 100kcal 단위 오버라이드 (선택사항) ──
  // 특정 칼로리에서 밴드 기본값과 다른 메뉴가 필요할 때 사용
  // 예: overrides.male[2000] = { general: { breakfast: {...}, snack: {...} } }
  overrides: {
    male: {},
    female: {}
  }
};
