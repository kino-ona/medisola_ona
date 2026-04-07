/**
 * DietQuiz - 나만의 식단 플랜 온보딩 퀴즈
 * 7 Screens: Entry → Gender → BirthYear → Height → Weight → Condition → AI Analysis
 * V0 pixel-perfect match
 */
var DietQuiz = (function () {
    'use strict';

    /* ============================================================
       Constants
       ============================================================ */
    var STEP_ORDER = ['entry', 'gender', 'birthYear', 'height', 'weight', 'condition', 'analysis', 'report'];
    var CURRENT_YEAR = new Date().getFullYear();

    var BIRTH_YEAR_MIN = CURRENT_YEAR - 98;
    var BIRTH_YEAR_MAX = CURRENT_YEAR - 18;
    var BIRTH_ITEM_HEIGHT = 64;

    var HEIGHT_MIN = 120;
    var HEIGHT_MAX = 220;
    var WEIGHT_MIN = 30;
    var WEIGHT_MAX = 150;
    var HW_ITEM_HEIGHT = 56;

    var SCROLL_DEBOUNCE = 100;
    var ANALYSIS_STEP_DURATION = 800;
    var CACHE_KEY = 'dq_quiz_state';

    /* --- Health Goals (단일 선택) --- */
    var HEALTH_GOALS = [
        { id: 'weight-loss', label: '체중 감량', subtitle: '건강한 다이어트', color: '#22C55E', icon: 'trendingDown' },
        { id: 'general', label: '균형잡힌 식단', subtitle: '전반적 건강 관리', color: '#10B981', icon: 'smile' },
        { id: 'weight-gain', label: '체중 증량', subtitle: '건강한 체중 증가', color: '#F97316', icon: 'trendingUp' },
        { id: 'muscle-gain', label: '근육 증량', subtitle: '고단백 식단', color: '#EF4444', icon: 'dumbbell' },
        { id: 'pregnancy', label: '임신·수유부 관리', subtitle: '엄마와 아기를 위한 영양', color: '#EC4899', icon: 'baby' },
        { id: 'growth', label: '성장 관리', subtitle: '성장기 맞춤 영양', color: '#8B5CF6', icon: 'sprout' },
        { id: 'aging', label: '노화 관리', subtitle: '건강한 노년을 위한 영양', color: '#6366F1', icon: 'shield' }
    ];

    /* --- 질환 관리 (복수 선택 가능) --- */
    var DISEASE_CONDITIONS = [
        { id: 'diabetes', label: '당뇨', subtitle: '혈당 관리가 필요한 분', color: '#3B82F6', icon: 'droplet' },
        { id: 'gestational-diabetes', label: '임신성 당뇨', subtitle: '임신 중 혈당 관리', color: '#EC4899', icon: 'baby' },
        { id: 'kidney', label: '신장 질환', subtitle: '신장 기능 보호가 필요한 분', color: '#8B5CF6', icon: 'kidney', hasSubOptions: true },
        { id: 'cancer', label: '암 투병', subtitle: '영양 보충이 필요한 분', color: '#F472B6', icon: 'ribbon' },
        { id: 'cholesterol', label: '고콜레스테롤', subtitle: '콜레스테롤 관리가 필요한 분', color: '#F59E0B', icon: 'heart' }
    ];

    var KIDNEY_SUB_OPTIONS = [
        { id: 'kidney-pre-dialysis', label: '투석 전 단계', subtitle: '신장 기능 보존 식단' },
        { id: 'kidney-dialysis', label: '투석 중', subtitle: '투석 환자 맞춤 식단' }
    ];

    var CONDITION_LABELS = {
        'diabetes': '당뇨', 'gestational-diabetes': '임신성 당뇨',
        'kidney': '신장 질환', 'kidney-pre-dialysis': '신장질환(투석 전)', 'kidney-dialysis': '신장질환(투석 중)',
        'cancer': '암', 'cholesterol': '고지혈증',
        'weight-loss': '체중 감량', 'general': '균형잡힌 식단', 'weight-gain': '체중 증량',
        'muscle-gain': '근육 증량', 'pregnancy': '임신·수유부 관리',
        'growth': '성장 관리', 'aging': '노화 관리'
    };

    var ANALYSIS_STEPS = [
        { id: 1, text: '영양 요구량 계산 중', icon: 'chart' },
        { id: 2, text: '식단 라인 매칭 중', icon: 'brain' },
        { id: 3, text: '맞춤 메뉴 구성 중', icon: 'utensils' },
        { id: 4, text: '영양 분석 결과 작성 중', icon: 'sparkles' }
    ];

    /* --- Report Rendering Constants (visualization only — content comes from backend) --- */
    var BMI_ZONES = [
        { label: '저체중', max: 18.5, color: '#3B82F6', pct: 18.5 },
        { label: '정상', max: 23, color: '#10B981', pct: 13.5 },
        { label: '과체중', max: 25, color: '#FBBF24', pct: 8 },
        { label: '비만', max: 40, color: '#EF4444', pct: 60 }
    ];

    /* --- Profile-based goal/disease restrictions --- */
    function calculateBmi() {
        return state.weight / Math.pow(state.height / 100, 2);
    }

    function getUserAge() {
        return CURRENT_YEAR - state.birthYear;
    }

    function getDisabledGoals() {
        var bmi = calculateBmi();
        var age = getUserAge();
        var disabled = {};
        // BMI 기반
        if (bmi < 18.5) {
            disabled['weight-loss'] = '저체중 상태에서는 체중 감량이 권장되지 않습니다';
        }
        if (bmi >= 25) {
            disabled['weight-gain'] = '비만 상태에서는 체중 증량이 권장되지 않습니다';
        }
        // 성별 기반
        if (state.gender === 'male') {
            disabled['pregnancy'] = '여성 전용 목표입니다';
        }
        // 연령 기반
        if (age >= 25) {
            disabled['growth'] = '성장기(만 24세 이하) 대상 목표입니다';
        }
        if (age < 40) {
            disabled['aging'] = '만 40세 이상 대상 목표입니다';
        }
        return disabled;
    }

    function getDisabledDiseases() {
        var disabled = {};
        if (state.gender === 'male') {
            disabled['gestational-diabetes'] = '여성 전용 질환입니다';
        }
        return disabled;
    }

    /* ============================================================
       State
       ============================================================ */
    var state = {
        step: 'entry', goodsNo: 0, isMobile: false,
        gender: '', birthYear: CURRENT_YEAR - 35, height: 170, weight: 65,
        selectedGoal: '', conditions: [], showDiseaseSection: false, showKidneyOptions: false,
        isSaving: false, reportData: null, isReportReady: false, _animationDone: false
    };

    var $container = null;
    var scrollTimeouts = {};

    /* ============================================================
       LocalStorage Cache
       ============================================================ */
    function saveToCache() {
        try {
            localStorage.setItem(CACHE_KEY, JSON.stringify({
                goodsNo: state.goodsNo,
                gender: state.gender,
                birthYear: state.birthYear,
                height: state.height,
                weight: state.weight,
                selectedGoal: state.selectedGoal,
                conditions: state.conditions,
                ts: Date.now()
            }));
        } catch (e) { /* localStorage 사용 불가 시 무시 */ }
    }

    function loadFromCache(goodsNo) {
        try {
            var raw = localStorage.getItem(CACHE_KEY);
            if (!raw) return false;
            var cached = JSON.parse(raw);
            // 같은 상품이 아니면 무시
            if (cached.goodsNo !== goodsNo) return false;
            // 7일 이상 된 캐시는 무시
            if (Date.now() - (cached.ts || 0) > 7 * 24 * 60 * 60 * 1000) {
                localStorage.removeItem(CACHE_KEY);
                return false;
            }
            // 데이터 복원
            if (cached.gender) state.gender = cached.gender;
            if (cached.birthYear) state.birthYear = cached.birthYear;
            if (cached.height) state.height = cached.height;
            if (cached.weight) state.weight = cached.weight;
            if (cached.selectedGoal) {
                state.selectedGoal = cached.selectedGoal;
                // 프로필 제약으로 무효화된 목표 초기화
                var bmi = cached.weight / Math.pow((cached.height || 170) / 100, 2);
                var age = CURRENT_YEAR - (cached.birthYear || CURRENT_YEAR - 35);
                var goal = cached.selectedGoal;
                if ((bmi < 18.5 && goal === 'weight-loss') ||
                    (bmi >= 25 && goal === 'weight-gain') ||
                    (cached.gender === 'male' && goal === 'pregnancy') ||
                    (age >= 25 && goal === 'growth') ||
                    (age < 40 && goal === 'aging')) {
                    state.selectedGoal = '';
                }
            }
            if (Array.isArray(cached.conditions)) {
                // 프로필 제약으로 무효화된 질환 필터링
                state.conditions = cached.conditions.filter(function(c) {
                    return !(cached.gender === 'male' && c === 'gestational-diabetes');
                });
                state.showDiseaseSection = state.conditions.length > 0;
                state.showKidneyOptions = cached.conditions.some(function(c) {
                    return c === 'kidney-pre-dialysis' || c === 'kidney-dialysis';
                });
            }
            // 항상 entry부터 시작 (step은 저장하지 않음)
            state.step = 'entry';
            return true;
        } catch (e) { return false; }
    }

    function clearCache() {
        try { localStorage.removeItem(CACHE_KEY); } catch (e) {}
    }

    /* ============================================================
       SVG Icons
       ============================================================ */
    var ICONS = {
        arrowLeft: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>',
        arrowRight: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>',
        check: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>',
        sparkles: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"/><path d="M5 3v4"/><path d="M19 17v4"/><path d="M3 5h4"/><path d="M17 19h4"/></svg>',
        brain: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5a3 3 0 1 0-5.997.125 4 4 0 0 0-2.526 5.77 4 4 0 0 0 .556 6.588A4 4 0 1 0 12 18Z"/><path d="M12 5a3 3 0 1 1 5.997.125 4 4 0 0 1 2.526 5.77 4 4 0 0 1-.556 6.588A4 4 0 1 1 12 18Z"/><path d="M15 13a4.5 4.5 0 0 1-3-4 4.5 4.5 0 0 1-3 4"/><path d="M17.599 6.5a3 3 0 0 0 .399-1.375"/><path d="M6.003 5.125A3 3 0 0 0 6.401 6.5"/><path d="M3.477 10.896a4 4 0 0 1 .585-.396"/><path d="M19.938 10.5a4 4 0 0 1 .585.396"/><path d="M6 18a4 4 0 0 1-1.967-.516"/><path d="M19.967 17.484A4 4 0 0 1 18 18"/></svg>',
        lineChart: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg>',
        shield: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/></svg>',
        male: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="10" cy="14" r="5"/><path d="M19 5l-5.4 5.4"/><path d="M15 5h4v4"/></svg>',
        female: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="5"/><path d="M12 13v8"/><path d="M9 18h6"/></svg>',
        calendar: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/></svg>',
        ruler: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.3 15.3a2.4 2.4 0 0 1 0 3.4l-2.6 2.6a2.4 2.4 0 0 1-3.4 0L2.7 8.7a2.41 2.41 0 0 1 0-3.4l2.6-2.6a2.41 2.41 0 0 1 3.4 0Z"/><path d="m14.5 12.5 2-2"/><path d="m11.5 9.5 2-2"/><path d="m8.5 6.5 2-2"/><path d="m17.5 15.5 2-2"/></svg>',
        scale: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m16 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"/><path d="m2 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"/><path d="M7 21h10"/><path d="M12 3v18"/><path d="M3 7h2c2 0 5-1 7-2 2 1 5 2 7 2h2"/></svg>',
        droplet: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22a7 7 0 0 0 7-7c0-2-1-3.9-3-5.5s-3.5-4-4-6.5c-.5 2.5-2 4.9-4 6.5C6 11.1 5 13 5 15a7 7 0 0 0 7 7z"/></svg>',
        baby: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12h.01"/><path d="M15 12h.01"/><path d="M10 16c.5.3 1.2.5 2 .5s1.5-.2 2-.5"/><path d="M19 6.3a9 9 0 0 1 1.8 3.9 2 2 0 0 1 0 3.6 9 9 0 0 1-17.6 0 2 2 0 0 1 0-3.6A9 9 0 0 1 12 3c2 0 3.5 1.1 3.5 2.5s-.9 2.5-2 2.5c-.8 0-1.5-.4-1.5-1"/></svg>',
        kidney: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 2c-1.5 0-3 1-3.5 3.5C4 8 5 10 5 12c0 2-1 4-1 6 0 2.5 2 4 4.5 4 2 0 3.5-1.5 3.5-3.5 0-2-2-3-2-5s1-3.5 1-5.5C11 4 9.5 2 8 2z"/><path d="M16 2c1.5 0 3 1 3.5 3.5C20 8 19 10 19 12c0 2 1 4 1 6 0 2.5-2 4-4.5 4-2 0-3.5-1.5-3.5-3.5 0-2 2-3 2-5s-1-3.5-1-5.5C13 4 14.5 2 16 2z"/></svg>',
        ribbon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"/><path d="M9 17v5l3-2 3 2v-5"/></svg>',
        heart: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>',
        trendingDown: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 17 13.5 8.5 8.5 13.5 2 7"/><polyline points="16 17 22 17 22 11"/></svg>',
        dumbbell: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.4 14.4 9.6 9.6"/><path d="M18.657 21.485a2 2 0 1 1-2.829-2.828l-1.767-1.768a2 2 0 1 1-2.829-2.829l6.364-6.364a2 2 0 1 1 2.829 2.829l1.767 1.767a2 2 0 1 1 2.829 2.829Z"/><path d="m21.5 21.5-1.4-1.4"/><path d="M3.9 3.9 2.5 2.5"/><path d="M6.404 12.768a2 2 0 1 1-2.829-2.829l1.768-1.767a2 2 0 1 1-2.828-2.829l2.828-2.828a2 2 0 1 1 2.829 2.828l1.767-1.768a2 2 0 1 1 2.829 2.829Z"/></svg>',
        smile: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" x2="9.01" y1="9" y2="9"/><line x1="15" x2="15.01" y1="9" y2="9"/></svg>',
        utensils: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"/><path d="M7 2v20"/><path d="M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3Zm0 0v7"/></svg>',
        chart: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M17 12v4"/><path d="M12 8v8"/><path d="M7 14v2"/></svg>',
        chevronDown: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>',
        trendingUp: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg>',
        activity: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-2.48a2 2 0 0 0-1.93 1.46l-2.35 8.36a.25.25 0 0 1-.48 0L9.24 2.18a.25.25 0 0 0-.48 0l-2.35 8.36A2 2 0 0 1 4.49 12H2"/></svg>',
        flame: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/></svg>',
        fish: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6.5 12c.94-3.46 4.94-6 8.5-6 3.56 0 6.06 2.54 7 6-.94 3.46-3.44 6-7 6-3.56 0-7.56-2.54-8.5-6Z"/><path d="M18 12v.5"/><path d="M16 17.93a9.77 9.77 0 0 1-3.5.07"/><path d="M2 12S4 6 8.5 6"/><path d="M2 12s2 6 6.5 6"/><path d="M10 12v-.5"/></svg>',
        alertTriangle: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>',
        sprout: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 20h10"/><path d="M10 20c5.5-2.5.8-6.4 3-10"/><path d="M9.5 9.4c1.1.8 1.8 2.2 2.3 3.7-2 .4-3.5.4-4.8-.3-1.2-.6-2.3-1.9-3-4.2 2.8-.5 4.4 0 5.5.8z"/><path d="M14.1 6a7 7 0 0 0-1.1 4c1.9-.1 3.3-.6 4.3-1.4 1-1 1.6-2.3 1.7-4.6-2.7.1-4 1-4.9 2z"/></svg>'
    };

    function getIcon(name) { return ICONS[name] || ''; }

    function getConditionIcon(iconName) {
        var map = { 'droplet':'droplet','baby':'baby','kidney':'kidney','ribbon':'ribbon','heart':'heart','trending-down':'trendingDown','dumbbell':'dumbbell','smile':'smile','sprout':'sprout','trendingDown':'trendingDown','trendingUp':'trendingUp','shield':'shield' };
        return getIcon(map[iconName] || iconName);
    }

    /* ============================================================
       Navigation
       ============================================================ */
    function getStepIndex(step) { return STEP_ORDER.indexOf(step); }
    function canGoBack() { return getStepIndex(state.step) > 0; }

    function canProceed() {
        switch (state.step) {
            case 'entry': return true;
            case 'gender': return !!state.gender;
            case 'birthYear': return state.birthYear >= BIRTH_YEAR_MIN && state.birthYear <= BIRTH_YEAR_MAX;
            case 'height': return state.height >= HEIGHT_MIN && state.height <= HEIGHT_MAX;
            case 'weight': return state.weight >= WEIGHT_MIN && state.weight <= WEIGHT_MAX;
            case 'condition': return !!state.selectedGoal && (state.selectedGoal !== 'disease-management' || state.conditions.length > 0);
            case 'report': return true;
            case 'analysis': return false;
            default: return false;
        }
    }

    function setStep(step) { state.step = step; saveToCache(); render(); }

    function goNext() {
        var idx = getStepIndex(state.step);
        if (idx < STEP_ORDER.length - 1) setStep(STEP_ORDER[idx + 1]);
    }

    function goBack() {
        // report에서 뒤로가면 condition으로 (analysis 스킵)
        if (state.step === 'report') { setStep('condition'); return; }
        var idx = getStepIndex(state.step);
        if (idx > 0) setStep(STEP_ORDER[idx - 1]);
    }

    /* ============================================================
       Render Router
       ============================================================ */
    function render() {
        if (!$container) return;
        saveToCache();

        // condition 단계: 스크롤 위치 보존
        var savedScroll = null;
        if (state.step === 'condition') {
            var body = $container.querySelector('.dq-condition-body');
            if (body) savedScroll = { el: '.dq-condition-body', top: body.scrollTop };
            // 카드 내부 스크롤이 없으면 window 스크롤 보존 (모바일 폴백)
            if (!savedScroll || savedScroll.top === 0) {
                savedScroll = { el: null, top: window.scrollY || document.documentElement.scrollTop };
            }
        }

        var html = '';
        switch (state.step) {
            case 'entry':      html = renderEntry(); break;
            case 'gender':     html = renderGender(); break;
            case 'birthYear':  html = renderBirthYear(); break;
            case 'height':     html = renderHeight(); break;
            case 'weight':     html = renderWeight(); break;
            case 'condition':  html = renderCondition(); break;
            case 'analysis':   html = renderAnalysis(); break;
            case 'report':     html = renderReport(); break;
        }
        $container.innerHTML = html;
        afterRender();

        // condition 단계: 스크롤 위치 복원
        if (savedScroll && state.step === 'condition') {
            if (savedScroll.el) {
                var el = $container.querySelector(savedScroll.el);
                if (el) el.scrollTop = savedScroll.top;
            } else {
                window.scrollTo(0, savedScroll.top);
            }
        }
    }

    function afterRender() {
        switch (state.step) {
            case 'birthYear': initScrollPicker('birthYear'); break;
            case 'height':    initScrollPicker('height'); break;
            case 'weight':    initScrollPicker('weight'); break;
            case 'analysis':  startAnalysisAnimation(); break;
        }
    }

    /* ============================================================
       Shared Components
       ============================================================ */
    function renderHeader() {
        var idx = getStepIndex(state.step);
        if (state.step === 'entry' || state.step === 'analysis' || state.step === 'report') return '';
        var dots = '';
        for (var i = 1; i <= 5; i++) {
            var cls = 'dq-progress-dot';
            if (i < idx) cls += ' completed';
            else if (i === idx) cls += ' active';
            dots += '<div class="' + cls + '"></div>';
        }
        return '<div class="dq-header">' +
            '<button class="dq-back-btn" onclick="DietQuiz.goBack()" aria-label="뒤로">' + getIcon('arrowLeft') + '</button>' +
            '<div class="dq-progress">' + dots + '</div>' +
            '<div class="dq-header-spacer"></div>' +
        '</div>';
    }

    function renderFooter(label, disabled) {
        if (state.step === 'entry' || state.step === 'analysis' || state.step === 'report') return '';
        return '<div class="dq-footer"><button class="dq-next-btn" onclick="DietQuiz.goNext()"' + (disabled ? ' disabled' : '') + '>' + label + '</button></div>';
    }

    /* ============================================================
       Screen 1: Entry  (V0 pixel-perfect)
       ============================================================ */
    function renderEntry() {
        return '<div class="dq-screen active"><div class="dq-entry">' +
            /* ---- Hero (left desktop / top mobile) ---- */
            '<div class="dq-entry-hero">' +
                '<div class="dq-hero-bg"><div class="dq-hero-circle"></div><div class="dq-hero-circle"></div><div class="dq-hero-circle"></div></div>' +
                '<div class="dq-entry-hero-content">' +
                    '<div class="dq-entry-brand"><strong>MEDI</strong><span>SOLA</span></div>' +
                    '<h2 class="dq-entry-headline">나를 알고 시작하는<br>나만의 식단 플랜</h2>' +
                    '<p class="dq-entry-desc">건강 상태, 목표, 취향을 함께 고려해<br>나만의 식단 플랜을 시작하세요</p>' +
                    '<div class="dq-entry-stats dq-desktop-only">' +
                        '<div class="dq-entry-stat"><div class="dq-entry-stat-value">1만7천+</div><div class="dq-entry-stat-label">누적 임상 수</div></div>' +
                        '<div class="dq-entry-stat"><div class="dq-entry-stat-value">24회</div><div class="dq-entry-stat-label">SCI급 논문 기재</div></div>' +
                        '<div class="dq-entry-stat"><div class="dq-entry-stat-value">80+</div><div class="dq-entry-stat-label">운영 식단 수</div></div>' +
                    '</div>' +
                '</div>' +
            '</div>' +
            /* ---- Content (right desktop / bottom mobile) ---- */
            '<div class="dq-entry-content">' +
                /* Mobile brand header */
                '<div class="dq-mobile-brand dq-mobile-only"><span class="dq-entry-brand-sm"><strong>MEDI</strong><span>SOLA</span></span></div>' +
                '<div class="dq-entry-card">' +
                    '<div class="dq-entry-ai-badge">' + getIcon('sparkles') + ' AI 기반 맞춤 영양 분석</div>' +
                    '<h1 class="dq-entry-title">내 몸에 맞게<br>어떻게 먹어야 할지<br><span style="color:var(--dq-primary)">고민하고 계신가요?</span></h1>' +
                    '<p class="dq-entry-body">메디쏠라는<br>당신의 건강 상태, 목표, 취향까지 고려해<br>지금의 나에게 맞는 식단 플랜을 시작하세요.</p>' +
                    /* Trust Indicators */
                    '<div class="dq-entry-features">' +
                        renderEntryFeature('brain', 'AI 영양 분석 알고리즘', '50만 건의 건강 데이터 기반 추천') +
                        renderEntryFeature('lineChart', '정밀 영양소 밸런싱', '단백질, 나트륨, 칼로리 최적화') +
                        renderEntryFeature('shield', '임상 영양사 검증', '질환별 특화 메뉴 구성') +
                    '</div>' +
                    /* Mobile-only stats */
                    '<div class="dq-entry-stats-mobile dq-mobile-only">' +
                        '<div class="dq-entry-stat-m"><div class="dq-entry-stat-value-m">1만7천+</div><div class="dq-entry-stat-label-m">누적 임상 수</div></div>' +
                        '<div class="dq-entry-stat-m"><div class="dq-entry-stat-value-m">24회</div><div class="dq-entry-stat-label-m">SCI급 논문 기재</div></div>' +
                        '<div class="dq-entry-stat-m"><div class="dq-entry-stat-value-m">80+</div><div class="dq-entry-stat-label-m">식단 운영 수</div></div>' +
                    '</div>' +
                    '<button class="dq-entry-cta" onclick="DietQuiz.goNext()">나만의 식단 플랜 만들기 ' + getIcon('arrowRight') + '</button>' +
                    '<p class="dq-entry-cta-sub">약 1분 소요</p>' +
                '</div>' +
            '</div>' +
        '</div></div>';
    }

    function renderEntryFeature(icon, title, subtitle) {
        return '<div class="dq-entry-feature">' +
            '<div class="dq-entry-feature-icon">' + getIcon(icon) + '</div>' +
            '<div class="dq-entry-feature-text"><p class="dq-feature-title">' + title + '</p><p class="dq-feature-sub">' + subtitle + '</p></div>' +
        '</div>';
    }

    /* ============================================================
       Screen 2: Gender  (V0 match)
       ============================================================ */
    function renderGender() {
        var mSel = state.gender === 'male' ? ' selected' : '';
        var fSel = state.gender === 'female' ? ' selected' : '';
        var mLabelCls = state.gender === 'male' ? ' style="color:var(--dq-primary)"' : '';
        var fLabelCls = state.gender === 'female' ? ' style="color:var(--dq-primary)"' : '';

        return '<div class="dq-screen active dq-quiz-screen">' +
            '<div class="dq-quiz-card">' +
            renderHeader() +
            '<div class="dq-screen-body">' +
                '<h1 class="dq-screen-title">성별을 선택해주세요</h1>' +
                '<p class="dq-screen-subtitle">맞춤 영양 분석을 위해 필요해요</p>' +
                '<div class="dq-gender-grid">' +
                    '<button class="dq-gender-btn' + mSel + '" onclick="DietQuiz.selectGender(\'male\')">' +
                        '<div class="dq-gender-icon male">' + getIcon('male') + '</div>' +
                        '<div class="dq-gender-label"' + mLabelCls + '>남성</div>' +
                    '</button>' +
                    '<button class="dq-gender-btn' + fSel + '" onclick="DietQuiz.selectGender(\'female\')">' +
                        '<div class="dq-gender-icon female">' + getIcon('female') + '</div>' +
                        '<div class="dq-gender-label"' + fLabelCls + '>여성</div>' +
                    '</button>' +
                '</div>' +
            '</div>' +
            '</div>' +
        '</div>';
    }

    function selectGender(g) {
        state.gender = g;
        render();
        setTimeout(function () { goNext(); }, 400);
    }

    /* ============================================================
       Screen 3: BirthYear  (V0 match — large icon, h-16 items, age below)
       ============================================================ */
    function renderBirthYear() {
        var age = CURRENT_YEAR - state.birthYear;
        var items = '';
        for (var y = BIRTH_YEAR_MAX; y >= BIRTH_YEAR_MIN; y--) {
            var cls = y === state.birthYear ? ' active' : '';
            items += '<button class="dq-picker-item dq-birth-item' + cls + '" data-value="' + y + '">' + y + '년</button>';
        }

        return '<div class="dq-screen active dq-quiz-screen">' +
            '<div class="dq-quiz-card">' +
            renderHeader() +
            '<div class="dq-screen-body">' +
                '<div class="dq-picker-icon-header">' +
                    '<div class="dq-picker-icon dq-picker-icon-lg">' + getIcon('calendar') + '</div>' +
                '</div>' +
                '<h1 class="dq-screen-title dq-title-lg">출생연도를 알려주세요</h1>' +
                '<p class="dq-screen-subtitle">연령에 맞는 영양 분석에 활용해요</p>' +
                '<div class="dq-picker-container dq-birth-picker" id="dqPickerContainer">' +
                    '<div class="dq-picker-gradient-top"></div>' +
                    '<div class="dq-picker-scroll" id="dqPickerScroll" data-type="birthYear">' + items + '</div>' +
                    '<div class="dq-picker-highlight dq-birth-highlight"></div>' +
                    '<div class="dq-picker-gradient-bottom"></div>' +
                '</div>' +
                '<div class="dq-age-display">만 <span class="dq-age-value" id="dqAgeValue">' + age + '</span>세</div>' +
            '</div>' +
            renderFooter('다음', !canProceed()) +
            '</div>' +
        '</div>';
    }

    /* ============================================================
       Screen 4: Height  (V0 match — no icon, text-3xl input, h-14 items)
       ============================================================ */
    function renderHeight() {
        var items = '';
        for (var h = HEIGHT_MIN; h <= HEIGHT_MAX; h++) {
            var cls = h === state.height ? ' active' : '';
            items += '<div class="dq-picker-item dq-hw-item' + cls + '" data-value="' + h + '">' + h + '</div>';
        }

        return '<div class="dq-screen active dq-quiz-screen">' +
            '<div class="dq-quiz-card">' +
            renderHeader() +
            '<div class="dq-screen-body">' +
                '<h1 class="dq-screen-title dq-title-lg">키를 입력해주세요</h1>' +
                '<p class="dq-screen-subtitle">직접 입력하거나 스크롤로 선택하세요</p>' +
                '<div class="dq-direct-input">' +
                    '<input type="number" id="dqDirectInput" value="' + state.height + '" min="' + HEIGHT_MIN + '" max="' + HEIGHT_MAX + '" onchange="DietQuiz.onDirectInput(\'height\',this.value)" onkeyup="DietQuiz.onDirectInput(\'height\',this.value)" />' +
                    '<span class="dq-unit-label">cm</span>' +
                '</div>' +
                '<div class="dq-picker-container dq-hw-picker" id="dqPickerContainer">' +
                    '<div class="dq-picker-gradient-top"></div>' +
                    '<div class="dq-picker-scroll" id="dqPickerScroll" data-type="height">' + items + '</div>' +
                    '<div class="dq-picker-highlight dq-hw-highlight"></div>' +
                    '<div class="dq-picker-gradient-bottom"></div>' +
                '</div>' +
            '</div>' +
            renderFooter('다음', !canProceed()) +
            '</div>' +
        '</div>';
    }

    /* ============================================================
       Screen 5: Weight  (V0 match)
       ============================================================ */
    function renderWeight() {
        var items = '';
        for (var w = WEIGHT_MIN; w <= WEIGHT_MAX; w++) {
            var cls = w === state.weight ? ' active' : '';
            items += '<div class="dq-picker-item dq-hw-item' + cls + '" data-value="' + w + '">' + w + '</div>';
        }

        return '<div class="dq-screen active dq-quiz-screen">' +
            '<div class="dq-quiz-card">' +
            renderHeader() +
            '<div class="dq-screen-body">' +
                '<h1 class="dq-screen-title dq-title-lg">체중을 입력해주세요</h1>' +
                '<p class="dq-screen-subtitle">직접 입력하거나 스크롤로 선택하세요</p>' +
                '<div class="dq-direct-input">' +
                    '<input type="number" id="dqDirectInput" value="' + state.weight + '" min="' + WEIGHT_MIN + '" max="' + WEIGHT_MAX + '" onchange="DietQuiz.onDirectInput(\'weight\',this.value)" onkeyup="DietQuiz.onDirectInput(\'weight\',this.value)" />' +
                    '<span class="dq-unit-label">kg</span>' +
                '</div>' +
                '<div class="dq-picker-container dq-hw-picker" id="dqPickerContainer">' +
                    '<div class="dq-picker-gradient-top"></div>' +
                    '<div class="dq-picker-scroll" id="dqPickerScroll" data-type="weight">' + items + '</div>' +
                    '<div class="dq-picker-highlight dq-hw-highlight"></div>' +
                    '<div class="dq-picker-gradient-bottom"></div>' +
                '</div>' +
            '</div>' +
            renderFooter('다음', !canProceed()) +
            '</div>' +
        '</div>';
    }

    /* ============================================================
       Scroll Picker Logic (supports different item heights)
       ============================================================ */
    function getPickerConfig(type) {
        if (type === 'birthYear') return { value: state.birthYear, min: BIRTH_YEAR_MIN, max: BIRTH_YEAR_MAX, itemH: BIRTH_ITEM_HEIGHT, reversed: true };
        if (type === 'height')    return { value: state.height, min: HEIGHT_MIN, max: HEIGHT_MAX, itemH: HW_ITEM_HEIGHT, reversed: false };
        return { value: state.weight, min: WEIGHT_MIN, max: WEIGHT_MAX, itemH: HW_ITEM_HEIGHT, reversed: false };
    }

    function initScrollPicker(type) {
        var scrollEl = document.getElementById('dqPickerScroll');
        if (!scrollEl) return;
        var cfg = getPickerConfig(type);

        var index = cfg.reversed ? (cfg.max - cfg.value) : (cfg.value - cfg.min);
        var containerHeight = scrollEl.parentElement.clientHeight;
        var paddingTop = (containerHeight - cfg.itemH) / 2;
        scrollEl.style.paddingTop = paddingTop + 'px';
        scrollEl.style.paddingBottom = paddingTop + 'px';
        scrollEl.scrollTop = index * cfg.itemH;

        scrollEl.addEventListener('scroll', function () {
            if (scrollTimeouts[type]) clearTimeout(scrollTimeouts[type]);
            scrollTimeouts[type] = setTimeout(function () {
                var idx = Math.round(scrollEl.scrollTop / cfg.itemH);
                var newVal = cfg.reversed
                    ? Math.max(cfg.min, Math.min(cfg.max, cfg.max - idx))
                    : Math.max(cfg.min, Math.min(cfg.max, cfg.min + idx));
                updatePickerValue(type, newVal, false);
            }, SCROLL_DEBOUNCE);
        });
    }

    function updatePickerValue(type, value, scrollTo) {
        var cfg = getPickerConfig(type);
        if (type === 'birthYear') { if (value === state.birthYear && !scrollTo) return; state.birthYear = value; }
        else if (type === 'height') { if (value === state.height && !scrollTo) return; state.height = value; }
        else { if (value === state.weight && !scrollTo) return; state.weight = value; }

        // Update active class + adjacent styling
        var scrollEl = document.getElementById('dqPickerScroll');
        if (scrollEl) {
            scrollEl.querySelectorAll('.dq-picker-item').forEach(function (item) {
                var v = parseInt(item.getAttribute('data-value'), 10);
                var dist = Math.abs(v - value);
                item.classList.toggle('active', v === value);
                item.classList.toggle('adjacent', dist === 1);
                item.classList.toggle('far', dist > 1);
            });
        }

        if (type === 'birthYear') {
            var ageEl = document.getElementById('dqAgeValue');
            if (ageEl) ageEl.textContent = CURRENT_YEAR - value;
        }
        if (type === 'height' || type === 'weight') {
            var inputEl = document.getElementById('dqDirectInput');
            if (inputEl && document.activeElement !== inputEl) inputEl.value = value;
        }
        if (scrollTo && scrollEl) {
            var index = cfg.reversed ? (cfg.max - value) : (value - cfg.min);
            scrollEl.scrollTo({ top: index * cfg.itemH, behavior: 'smooth' });
        }
        var btn = document.querySelector('.dq-next-btn');
        if (btn) btn.disabled = !canProceed();
        saveToCache();
    }

    function onDirectInput(type, rawValue) {
        var val = parseInt(rawValue, 10);
        if (isNaN(val)) return;
        var min = type === 'height' ? HEIGHT_MIN : WEIGHT_MIN;
        var max = type === 'height' ? HEIGHT_MAX : WEIGHT_MAX;
        val = Math.max(min, Math.min(max, val));
        updatePickerValue(type, val, true);
    }

    /* ============================================================
       Screen 6: Condition  (V0 match — management first, then disease)
       ============================================================ */
    function renderCondition() {
        var goalItems = renderGoalItems();
        var diseaseToggle = renderDiseaseToggle();
        var diseaseItems = state.showDiseaseSection ? renderDiseaseItems() : '';

        return '<div class="dq-screen active dq-quiz-screen">' +
            '<div class="dq-quiz-card">' +
            renderHeader() +
            '<div class="dq-screen-body dq-condition-body">' +
                '<h1 class="dq-screen-title">건강 목표를 선택해주세요</h1>' +
                '<p class="dq-screen-subtitle">목표에 맞는 맞춤 식단을 추천해드려요</p>' +
                '<div class="dq-condition-sections">' +
                    '<div class="dq-condition-section"><div class="dq-condition-grid">' + goalItems + '</div></div>' +
                    diseaseToggle +
                    diseaseItems +
                '</div>' +
            '</div>' +
            renderFooter('플랜을 위한 나의 상태 확인', !canProceed()) +
            '</div>' +
        '</div>';
    }

    function renderGoalItems() {
        var html = '';
        var disabled = getDisabledGoals();
        HEALTH_GOALS.forEach(function (goal) {
            var isDisabled = !!disabled[goal.id];
            var isSelected = state.selectedGoal === goal.id;
            var cls = 'dq-condition-item' + (isSelected ? ' selected' : '') + (isDisabled ? ' disabled' : '');
            var labelStyle = isSelected ? ' style="color:var(--dq-primary)"' : '';
            var trailing = '';

            if (isDisabled) {
                trailing = '<span class="dq-goal-disabled-hint">' + disabled[goal.id] + '</span>';
            } else if (isSelected) {
                trailing = '<div class="dq-condition-check">' + getIcon('check') + '</div>';
            }

            html += '<button class="' + cls + '"' + (isDisabled ? ' disabled' : '') + ' onclick="DietQuiz.toggleCondition(\'' + goal.id + '\')">' +
                '<div class="dq-condition-icon" style="background-color:' + goal.color + '15;color:' + goal.color + '">' + getConditionIcon(goal.icon) + '</div>' +
                '<div class="dq-condition-info"><span class="dq-condition-label"' + labelStyle + '>' + goal.label + '</span><span class="dq-condition-subtitle">' + goal.subtitle + '</span></div>' +
                trailing +
            '</button>';
        });
        return html;
    }

    function renderDiseaseToggle() {
        var isSelected = state.selectedGoal === 'disease-management';
        var selCls = isSelected ? ' selected' : '';
        var rot = isSelected ? ' style="transform:rotate(180deg)"' : '';

        return '<div class="dq-condition-section dq-disease-toggle-section">' +
            '<button class="dq-condition-item dq-disease-toggle' + selCls + '" onclick="DietQuiz.toggleCondition(\'disease-management\')">' +
                '<div class="dq-condition-icon" style="background-color:#64748B15;color:#64748B">' + getIcon('shield') + '</div>' +
                '<div class="dq-condition-info"><span class="dq-condition-label"' + (isSelected ? ' style="color:var(--dq-primary)"' : '') + '>질환 관리</span><span class="dq-condition-subtitle">질환자 한정 목표</span></div>' +
                '<span class="dq-condition-chevron"' + rot + '>' + getIcon('chevronDown') + '</span>' +
            '</button>' +
        '</div>';
    }

    function renderDiseaseItems() {
        var disabledDiseases = getDisabledDiseases();
        var html = '<div class="dq-condition-section dq-disease-section"><p class="dq-condition-section-title">질환을 선택해주세요 <span class="dq-section-hint">(복수 선택 가능)</span></p><div class="dq-condition-grid">';
        DISEASE_CONDITIONS.forEach(function (cond) {
            var isDisabled = !!disabledDiseases[cond.id];
            var isSelected = false;
            if (cond.id === 'kidney') {
                isSelected = state.conditions.indexOf('kidney-pre-dialysis') !== -1 || state.conditions.indexOf('kidney-dialysis') !== -1;
            } else {
                isSelected = state.conditions.indexOf(cond.id) !== -1;
            }
            var cls = 'dq-condition-item' + (isSelected ? ' selected' : '') + (isDisabled ? ' disabled' : '');
            var labelStyle = isSelected ? ' style="color:var(--dq-primary)"' : '';

            var trailing = '';
            if (isDisabled) {
                trailing = '<span class="dq-goal-disabled-hint">' + disabledDiseases[cond.id] + '</span>';
            } else if (cond.hasSubOptions) {
                var rot = state.showKidneyOptions ? ' style="transform:rotate(180deg)"' : '';
                trailing = '<span class="dq-condition-chevron"' + rot + '>' + getIcon('chevronDown') + '</span>';
            } else if (isSelected) {
                trailing = '<div class="dq-condition-check">' + getIcon('check') + '</div>';
            }

            html += '<button class="' + cls + '"' + (isDisabled ? ' disabled' : '') + ' onclick="DietQuiz.toggleCondition(\'' + cond.id + '\')">' +
                '<div class="dq-condition-icon" style="background-color:' + cond.color + '15;color:' + cond.color + '">' + getConditionIcon(cond.icon) + '</div>' +
                '<div class="dq-condition-info"><span class="dq-condition-label"' + labelStyle + '>' + cond.label + '</span><span class="dq-condition-subtitle">' + cond.subtitle + '</span></div>' +
                trailing +
            '</button>';

            if (cond.hasSubOptions && state.showKidneyOptions) {
                html += '<div class="dq-kidney-sub">';
                KIDNEY_SUB_OPTIONS.forEach(function (sub) {
                    var subSel = state.conditions.indexOf(sub.id) !== -1;
                    html += '<button class="dq-kidney-sub-item' + (subSel ? ' selected' : '') + '" onclick="DietQuiz.toggleCondition(\'' + sub.id + '\')">' +
                        '<div class="dq-kidney-sub-icon" style="background-color:#8B5CF615;color:#8B5CF6"><div class="dq-kidney-sub-dot-inner"></div></div>' +
                        '<div class="dq-condition-info"><span class="dq-condition-label"' + (subSel ? ' style="color:var(--dq-primary)"' : '') + '>' + sub.label + '</span><span class="dq-condition-subtitle">' + sub.subtitle + '</span></div>' +
                        (subSel ? '<div class="dq-condition-check dq-check-sm">' + getIcon('check') + '</div>' : '') +
                    '</button>';
                });
                html += '</div>';
            }
        });
        html += '</div></div>';
        return html;
    }

    function toggleCondition(id) {
        // 질환 관리 (건강 목표 중 하나로 단일 선택)
        if (id === 'disease-management') {
            if (state.selectedGoal === 'disease-management') {
                state.selectedGoal = '';
                state.showDiseaseSection = false;
                state.conditions = [];
                state.showKidneyOptions = false;
            } else {
                state.selectedGoal = 'disease-management';
                state.showDiseaseSection = true;
            }
            render(); return;
        }

        // 건강 목표 (단일 선택 — 질환 관리와 상호 배타)
        var isGoal = HEALTH_GOALS.some(function(g) { return g.id === id; });
        if (isGoal) {
            // BMI 기반 제약: 비활성 목표 선택 차단
            var disabled = getDisabledGoals();
            if (disabled[id]) return;

            if (state.selectedGoal === id) {
                state.selectedGoal = '';
            } else {
                state.selectedGoal = id;
                state.showDiseaseSection = false;
                state.conditions = [];
                state.showKidneyOptions = false;
            }
            render(); return;
        }

        // 질환 선택 (복수 선택)
        // 프로필 기반 제약: 비활성 질환 선택 차단
        var disabledDiseases = getDisabledDiseases();
        if (disabledDiseases[id]) return;

        var conditions = state.conditions.slice();

        // 신장 질환 토글 (하위옵션 펼치기)
        if (id === 'kidney') {
            state.showKidneyOptions = !state.showKidneyOptions;
            if (!state.showKidneyOptions) {
                conditions = conditions.filter(function (c) { return c !== 'kidney-pre-dialysis' && c !== 'kidney-dialysis'; });
            } else if (!conditions.some(function(c) { return c.indexOf('kidney') === 0; })) {
                conditions.push('kidney-pre-dialysis');
            }
            state.conditions = conditions; render(); return;
        }

        // 신장 하위옵션 (라디오 — 투석전/투석중 중 하나)
        if (id === 'kidney-pre-dialysis' || id === 'kidney-dialysis') {
            conditions = conditions.filter(function (c) { return c !== 'kidney-pre-dialysis' && c !== 'kidney-dialysis'; });
            conditions.push(id);
            state.conditions = conditions; render(); return;
        }

        // 일반 질환 토글 (복수 선택)
        var idx = conditions.indexOf(id);
        if (idx !== -1) conditions.splice(idx, 1); else conditions.push(id);
        state.conditions = conditions; render();
    }

    /** 백엔드 전송용 조건 배열 생성 (goal + diseases 병합) */
    function getAllConditionsForBackend() {
        var result = [];
        if (state.selectedGoal && state.selectedGoal !== 'disease-management') {
            result.push(state.selectedGoal);
        }
        state.conditions.forEach(function(c) { result.push(c); });
        if (result.length === 0) result.push('general');
        return result;
    }

    /* ============================================================
       Screen 7: AI Analysis  (V0 match — two-column desktop, section headers)
       ============================================================ */
    function renderAnalysis() {
        var bmi = state.weight / Math.pow(state.height / 100, 2);
        var bmiR = bmi.toFixed(1);
        var bmiStatus, bmiClass;
        if (bmi < 18.5) { bmiStatus = '저체중'; bmiClass = 'dq-bmi-underweight'; }
        else if (bmi < 23) { bmiStatus = '정상'; bmiClass = 'dq-bmi-normal'; }
        else if (bmi < 25) { bmiStatus = '과체중'; bmiClass = 'dq-bmi-overweight'; }
        else { bmiStatus = '비만'; bmiClass = 'dq-bmi-obese'; }
        var age = CURRENT_YEAR - state.birthYear;

        var dataCells =
            '<div class="dq-data-cell"><p class="dq-data-label">나이</p><p class="dq-data-value">' + age + '세</p></div>' +
            '<div class="dq-data-cell"><p class="dq-data-label">신장</p><p class="dq-data-value">' + state.height + 'cm</p></div>' +
            '<div class="dq-data-cell"><p class="dq-data-label">체중</p><p class="dq-data-value">' + state.weight + 'kg</p></div>' +
            '<div class="dq-data-cell dq-data-cell-bmi"><p class="dq-data-label">BMI</p><p class="dq-data-value" style="color:var(--dq-primary)">' + bmiR + '</p></div>';

        var badges = '';
        var allBadgeLabels = [];
        if (state.selectedGoal && state.selectedGoal !== 'disease-management') {
            allBadgeLabels.push(CONDITION_LABELS[state.selectedGoal] || state.selectedGoal);
        }
        state.conditions.forEach(function (c) {
            allBadgeLabels.push(CONDITION_LABELS[c] || c);
        });
        if (allBadgeLabels.length > 0) {
            badges = '<div class="dq-analysis-conditions"><p class="dq-analysis-section-label">건강 목표</p><div class="dq-conditions-badges">';
            allBadgeLabels.forEach(function (label) {
                badges += '<span class="dq-condition-badge">' + label + '</span>';
            });
            badges += '</div></div>';
        }

        var steps = '';
        ANALYSIS_STEPS.forEach(function (s) {
            steps += '<div class="dq-step-item pending" id="dqStep' + s.id + '">' +
                '<div class="dq-step-icon">' + getIcon(s.icon) + '</div>' +
                '<span class="dq-step-text">' + s.text + '</span>' +
                '<div class="dq-loading-dots" style="display:none"><span class="dq-loading-dot"></span><span class="dq-loading-dot"></span><span class="dq-loading-dot"></span></div>' +
            '</div>';
        });

        return '<div class="dq-screen active"><div class="dq-analysis"><div class="dq-analysis-card">' +
            /* Central animation */
            '<div class="dq-analysis-rings"><div class="dq-ring-outer"></div><div class="dq-ring-inner"></div><div class="dq-ring-center"><div class="dq-ring-brain">' + getIcon('brain') + '</div></div></div>' +
            '<h1 class="dq-analysis-title">AI 영양 분석 중</h1>' +
            '<p class="dq-analysis-subtitle">입력하신 정보를 바탕으로 최적의 식단을 구성합니다</p>' +
            /* Two-column area */
            '<div class="dq-analysis-columns">' +
                '<div class="dq-analysis-data">' +
                    '<p class="dq-analysis-section-label">입력 정보</p>' +
                    '<div class="dq-data-summary">' + dataCells + '</div>' +
                    badges +
                '</div>' +
                '<div class="dq-analysis-steps"><div class="dq-steps-list">' + steps + '</div></div>' +
            '</div>' +
        '</div></div></div>';
    }

    function fetchReportData() {
        $.ajax({
            url: 'diet_quiz_report_ps.php', type: 'POST',
            data: { gender: state.gender, birthYear: state.birthYear, height: state.height, weight: state.weight, conditions: JSON.stringify(getAllConditionsForBackend()) },
            dataType: 'json',
            success: function (res) {
                state.reportData = (res.success && res.report) ? res.report : null;
                state.isReportReady = true;
                if (state._animationDone) setStep('report');
            },
            error: function () {
                state.reportData = null;
                state.isReportReady = true;
                if (state._animationDone) setStep('report');
            }
        });
    }

    function startAnalysisAnimation() {
        state.isReportReady = false;
        state.reportData = null;
        state._animationDone = false;
        fetchReportData();

        var cur = 0, total = ANALYSIS_STEPS.length;
        function advance() {
            cur++;
            if (cur > total) {
                state._animationDone = true;
                if (state.isReportReady) setStep('report');
                return;
            }
            if (cur > 1) {
                var prev = document.getElementById('dqStep' + (cur - 1));
                if (prev) {
                    prev.className = 'dq-step-item completed';
                    var d = prev.querySelector('.dq-loading-dots'); if (d) d.style.display = 'none';
                    var ic = prev.querySelector('.dq-step-icon'); if (ic) ic.innerHTML = getIcon('check');
                }
            }
            if (cur <= total) {
                var el = document.getElementById('dqStep' + cur);
                if (el) {
                    el.className = 'dq-step-item current';
                    var d = el.querySelector('.dq-loading-dots'); if (d) d.style.display = 'flex';
                }
            }
            setTimeout(advance, ANALYSIS_STEP_DURATION);
        }
        setTimeout(advance, 300);
    }

    /* ============================================================
       Screen 8: Nutrition Report
       ============================================================ */
    function renderReport() {
        var r = state.reportData;

        // AJAX 실패 또는 데이터 없을 경우 fallback
        if (!r) {
            return '<div class="dq-screen active"><div class="dqr-report">' +
                '<div class="dqr-header"><div class="dqr-header-titles">' +
                    '<h2 class="dqr-header-title">영양 분석 결과</h2>' +
                    '<p class="dqr-header-sub">데이터를 불러오는 중 오류가 발생했습니다.</p>' +
                '</div></div>' +
                '<div class="dqr-cta-wrap"><button class="dqr-cta-btn" onclick="DietQuiz.startPlan()">식단 플랜으로 이동 ' + getIcon('arrowRight') + '</button></div>' +
            '</div></div>';
        }

        var bmi = r.bmi;
        var bmiPct = Math.max(0, Math.min(100, ((bmi.value - 15) / 25) * 100));

        // Coaching hero
        var coachingHtml = '';
        r.coachingMessages.forEach(function (m) {
            coachingHtml += '<p class="dqr-coaching-text">' + m + '</p>';
        });

        // BMI bar zones
        var bmiZones = '';
        BMI_ZONES.forEach(function (z) {
            bmiZones += '<div class="dqr-bmi-zone" style="flex:' + z.pct + ';background-color:' + z.color + '"><span class="dqr-bmi-zone-label">' + z.label + '</span></div>';
        });

        // Daily nutrients (from backend)
        var dn = r.dailyNutrients || {};
        var macroRows = '';
        (dn.macros || []).forEach(function (m) {
            macroRows += '<div class="dqr-macro-row">' +
                '<span class="dqr-macro-dot" style="background-color:' + m.color + '"></span>' +
                '<span class="dqr-macro-label">' + m.label + '</span>' +
                '<span class="dqr-macro-value">' + m.g + 'g</span>' +
                '<span class="dqr-macro-pct">' + m.pct + '%</span>' +
            '</div>';
        });
        var microRows = '';
        (dn.micros || []).forEach(function (mi) {
            microRows += '<div class="dqr-micro-row">' +
                '<div class="dqr-micro-icon" style="background-color:' + mi.color + '15;color:' + mi.color + '">' + getIcon(mi.icon) + '</div>' +
                '<div class="dqr-micro-info">' +
                    '<span class="dqr-micro-label">' + mi.label + '</span>' +
                    '<span class="dqr-micro-desc">' + mi.desc + '</span>' +
                '</div>' +
                '<span class="dqr-micro-value" style="color:' + mi.color + '">' + mi.value + '</span>' +
            '</div>';
        });

        // Strategy cards (from backend)
        var strategies = '';
        r.strategies.forEach(function (s) {
            strategies += '<div class="dqr-strategy-card">' +
                '<div class="dqr-strategy-icon" style="background-color:' + s.bgColor + ';color:' + s.color + '">' + getIcon(s.icon) + '</div>' +
                '<div class="dqr-strategy-info"><span class="dqr-strategy-label">' + s.label + '</span></div>' +
                '<span class="dqr-strategy-badge" style="background-color:' + s.bgColor + ';color:' + s.color + '">' + s.target + '</span>' +
            '</div>';
        });

        // Recommended lines (from backend)
        var lineCards = '';
        r.recommendedLines.forEach(function (line, idx) {
            var benefits = '';
            line.benefits.forEach(function (b) {
                benefits += '<span class="dqr-benefit-tag" style="background-color:' + line.color + '15;color:' + line.color + '">' + b + '</span>';
            });
            lineCards += '<div class="dqr-line-card">' +
                '<div class="dqr-line-header">' +
                    '<span class="dqr-line-number" style="background-color:' + line.color + '">' + (idx + 1) + '</span>' +
                    '<span class="dqr-line-label">' + line.label + '</span>' +
                '</div>' +
                '<p class="dqr-line-reason">' + line.reason + '</p>' +
                '<div class="dqr-line-benefits">' + benefits + '</div>' +
            '</div>';
        });

        // Condition badges (from backend)
        var condBadges = '';
        r.conditionLabels.forEach(function (label) {
            condBadges += '<span class="dqr-condition-tag">' + label + '</span>';
        });

        return '<div class="dq-screen active"><div class="dqr-report">' +
            /* ---- Header ---- */
            '<div class="dqr-header">' +
                '<button class="dqr-back-btn" onclick="DietQuiz.goBack()" aria-label="뒤로">' + getIcon('arrowLeft') + '</button>' +
                '<div class="dqr-header-titles">' +
                    '<h2 class="dqr-header-title">메디쏠라 영양 분석 결과</h2>' +
                    '<p class="dqr-header-sub">맞춤 케어 분석 결과</p>' +
                '</div>' +
            '</div>' +

            /* ---- Coaching Hero ---- */
            '<div class="dqr-hero">' +
                '<div class="dqr-hero-icon dqr-hero-icon-solid">' + getIcon('activity') + '</div>' +
                coachingHtml +
            '</div>' +

            /* ---- Two Column Layout ---- */
            '<div class="dqr-content">' +
                '<div class="dqr-columns">' +
                    /* -- Left Column -- */
                    '<div class="dqr-col-left">' +
                        /* BMI Analysis */
                        '<div class="dqr-card">' +
                            '<p class="dqr-section-label">체질량 분석</p>' +
                            '<div class="dqr-bmi-display">' +
                                '<span class="dqr-bmi-value">' + bmi.value + '</span>' +
                                '<span class="dqr-bmi-unit">kg/m²</span>' +
                                '<span class="dqr-bmi-badge dqr-bmi-' + bmi.statusKey + '">' + bmi.status + '</span>' +
                            '</div>' +
                            '<div class="dqr-bmi-bar-wrap">' +
                                '<div class="dqr-bmi-bar">' + bmiZones + '</div>' +
                                '<div class="dqr-bmi-marker" style="left:' + bmiPct + '%"></div>' +
                            '</div>' +
                        '</div>' +
                        /* Daily Nutrients */
                        '<div class="dqr-card">' +
                            '<p class="dqr-section-label">하루 필요 영양소</p>' +
                            '<div class="dqr-dn-highlights">' +
                                '<div class="dqr-dn-box">' +
                                    '<span class="dqr-dn-big">' + Number(Math.max(1200, Math.min(3000, Math.round((dn.calories || 0) / 100) * 100))).toLocaleString() + ' <small class="dqr-dn-inline-unit">kcal</small></span>' +
                                    '<span class="dqr-dn-unit">1일 필요 칼로리</span>' +
                                '</div>' +
                                '<div class="dqr-dn-box">' +
                                    '<span class="dqr-dn-big">' + (dn.proteinG || 0) + ' <small class="dqr-dn-inline-unit">g</small></span>' +
                                    '<span class="dqr-dn-unit">필요 단백질</span>' +
                                '</div>' +
                            '</div>' +
                            '<p class="dqr-dn-desc">3대 영양소 필요 배분 비율</p>' +
                            '<div class="dqr-macro-bar">' +
                                ((dn.macros || []).map(function (m) {
                                    return '<div class="dqr-macro-seg" style="flex:' + m.pct + ';background-color:' + m.color + '"><span>' + m.pct + '%</span></div>';
                                }).join('')) +
                            '</div>' +
                            '<div class="dqr-macro-rows">' + macroRows + '</div>' +
                            '<div class="dqr-micro-divider"></div>' +
                            '<div class="dqr-micro-rows">' + microRows + '</div>' +
                        '</div>' +
                        /* Brand Message */
                        '<div class="dqr-brand">' +
                            '<div class="dqr-brand-icon">' + getIcon('heart') + '</div>' +
                            '<p class="dqr-brand-msg">' + r.brandMessage.text + '</p>' +
                            '<p class="dqr-brand-tagline">' + r.brandMessage.tagline + '</p>' +
                        '</div>' +
                    '</div>' +

                    /* -- Right Column -- */
                    '<div class="dqr-col-right">' +
                        /* Nutrition Strategy */
                        '<div class="dqr-card">' +
                            '<p class="dqr-section-label">핵심 영양 전략</p>' +
                            '<div class="dqr-strategies">' + strategies + '</div>' +
                        '</div>' +
                        /* Recommended Lines */
                        '<div class="dqr-card">' +
                            '<p class="dqr-section-label">메디쏠라 케어 솔루션</p>' +
                            '<div class="dqr-lines">' + lineCards + '</div>' +
                            (condBadges ? '<div class="dqr-conditions-row"><span class="dqr-conditions-label">선택한 건강 목표:</span>' + condBadges + '</div>' : '') +
                        '</div>' +
                    '</div>' +
                '</div>' +
            '</div>' +

            /* ---- Fixed CTA ---- */
            '<div class="dqr-cta-wrap">' +
                '<button class="dqr-cta-btn" onclick="DietQuiz.startPlan()">' +
                    r.cta.label + ' ' + getIcon('arrowRight') +
                '</button>' +
                '<p class="dqr-cta-sub">' + r.cta.subtext + '</p>' +
            '</div>' +
        '</div></div>';
    }

    function startPlan() {
        saveAndRedirect();
    }

    /* ============================================================
       Save & Redirect
       ============================================================ */
    function saveAndRedirect() {
        if (state.isSaving) return;
        state.isSaving = true;
        $.ajax({
            url: 'diet_quiz_ps.php', type: 'POST',
            data: {
                gender: state.gender, birthYear: state.birthYear,
                height: state.height, weight: state.weight,
                conditions: JSON.stringify(getAllConditionsForBackend()),
                recommendedLines: JSON.stringify(state.reportData && state.reportData.recommendedLines ? state.reportData.recommendedLines : [])
            },
            dataType: 'json',
            success: function (res) {
                if (res.success && res.responseSno) {
                    // 리포트 상태 저장 (뒤로가기 시 리포트 화면 복원용)
                    try {
                        sessionStorage.setItem('dqReportState', JSON.stringify({
                            goodsNo: state.goodsNo,
                            gender: state.gender, birthYear: state.birthYear,
                            height: state.height, weight: state.weight,
                            conditions: state.conditions,
                            reportData: state.reportData,
                            responseSno: res.responseSno
                        }));
                    } catch (e) {}
                    window.location.href = '../goods/goods_view.php?goodsNo=' + state.goodsNo + '&responseSno=' + res.responseSno;
                } else { alert(res.error || '저장에 실패했습니다.'); state.isSaving = false; }
            },
            error: function (xhr) {
                var msg = '네트워크 오류가 발생했습니다.';
                if (xhr.status) msg += ' (HTTP ' + xhr.status + ')';
                try { var r = JSON.parse(xhr.responseText); if (r.error) msg = r.error; } catch (e) {}
                alert(msg);
                state.isSaving = false;
            }
        });
    }

    /* ============================================================
       Init
       ============================================================ */
    function init(opts) {
        state.goodsNo = parseInt(opts.goodsNo, 10) || 0;

        // Fallback: extract goodsNo from URL if template variable didn't render
        if (!state.goodsNo) {
            var urlParams = new URLSearchParams(window.location.search);
            var urlGoodsNo = parseInt(urlParams.get('goodsNo'), 10);
            if (urlGoodsNo > 0) state.goodsNo = urlGoodsNo;
        }

        state.isMobile = !!opts.isMobile;
        $container = document.getElementById('dqScreenContainer');
        if (!$container) return;

        // 식단 플랜에서 뒤로가기로 돌아온 경우: 리포트 화면 바로 표시
        var urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('showReport') === '1') {
            try {
                var saved = JSON.parse(sessionStorage.getItem('dqReportState') || 'null');
                if (saved && saved.goodsNo == state.goodsNo && saved.reportData) {
                    state.gender = saved.gender;
                    state.birthYear = saved.birthYear;
                    state.height = saved.height;
                    state.weight = saved.weight;
                    state.conditions = saved.conditions || {};
                    state.reportData = saved.reportData;
                    state.step = 'report';
                    render();
                    return;
                }
            } catch (e) {}
        }

        // 이전 입력값 복원 (같은 goodsNo + 7일 이내)
        loadFromCache(state.goodsNo);

        render();
    }

    return { init: init, goNext: goNext, goBack: goBack, selectGender: selectGender, toggleCondition: toggleCondition, onDirectInput: onDirectInput, startPlan: startPlan };
})();
