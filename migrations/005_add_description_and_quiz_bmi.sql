-- 메디솔라 나만의 식단 플랜: Phase A 잔여 - description 칼럼 추가 + 퀴즈 BMI 필드
-- 실행 방법: PHP MySQL 웹 인터페이스에서 직접 실행
-- 선행 조건: migrations/003_alter_es_addGoods_custom_diet.sql 실행 완료

-- ===================================================================
-- 1. es_addGoods: description 칼럼 추가 (메뉴 한줄 소개)
--    goodsDescription은 기본 상품설명(HTML 포함)이므로, 식단 플랜 전용 한줄 소개 필드
-- ===================================================================
ALTER TABLE es_addGoods
    ADD COLUMN description TEXT DEFAULT NULL COMMENT '식단 플랜용 메뉴 설명 (한줄 소개)';

-- 참고: recommend_reasons 칼럼은 이미 003 마이그레이션에서 추가됨

-- ===================================================================
-- 2. ms_diet_quiz_responses: BMI 계산용 필드 추가
--    기존 age(연령대 텍스트)에 더해 실제 수치 데이터 필요
-- ===================================================================
ALTER TABLE ms_diet_quiz_responses
    ADD COLUMN birthYear INT DEFAULT NULL COMMENT '출생년도 (예: 1990)',
    ADD COLUMN height DECIMAL(5,1) DEFAULT NULL COMMENT '키(cm) (예: 175.0)',
    ADD COLUMN weight DECIMAL(5,1) DEFAULT NULL COMMENT '체중(kg) (예: 70.5)';
