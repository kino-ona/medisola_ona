-- 메디솔라 나만의 식단 플랜: 퀴즈 응답 테이블 생성 (V0 기준)
-- 실행 방법: PHP MySQL 웹 인터페이스에서 직접 실행
-- DietFinder.php의 getConditionsFromResponse()와 호환

CREATE TABLE IF NOT EXISTS ms_diet_quiz_responses (
    sno INT(11) AUTO_INCREMENT PRIMARY KEY COMMENT '일련번호 (responseSno)',
    memNo INT(11) DEFAULT 0 COMMENT '회원번호 (0=비회원)',
    sessionKey VARCHAR(100) NOT NULL COMMENT '세션키',

    -- 기본 사용자 정보 (DietFinder.php userData와 매칭)
    age VARCHAR(20) DEFAULT NULL COMMENT '연령대 (20대/30대/40대/50대/60대이상)',
    gender VARCHAR(10) DEFAULT NULL COMMENT '성별 (male/female)',
    goal VARCHAR(50) DEFAULT NULL COMMENT '건강 목표 (체중관리/근육증가/혈당관리/심혈관건강)',

    -- 건강 조건 (JSON 배열)
    conditions TEXT DEFAULT NULL COMMENT 'JSON: ["당뇨", "고혈압", "신장질환"]',

    -- 추천 영양 라인 (JSON 배열) - DietFinder calculateLines와 매칭
    recommended_lines TEXT DEFAULT NULL COMMENT 'JSON: ["고단백", "저나트륨", "오메가3", "550kcal이하"]',

    -- 추가 선호도 정보
    excluded_ingredients TEXT DEFAULT NULL COMMENT 'JSON: 제외할 재료 ["오징어", "새우"]',
    preferred_categories TEXT DEFAULT NULL COMMENT 'JSON: 선호 카테고리 ["seafood", "meat"]',

    -- 결과 캐시
    matched_goods_no INT(11) DEFAULT NULL COMMENT '매칭된 상품 goodsNo',
    matched_menu_ids TEXT DEFAULT NULL COMMENT 'JSON: 추천 메뉴 addGoodsNo 배열',

    -- 메타데이터
    regDt DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '생성일',
    modDt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일',

    INDEX idx_memNo (memNo),
    INDEX idx_sessionKey (sessionKey),
    INDEX idx_regDt (regDt)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='나만의 식단 플랜 퀴즈 응답 (V0)';

-- 기존 테이블이 있으면 삭제 후 재생성 (개발 환경만)
-- DROP TABLE IF EXISTS ms_diet_quiz_responses;
