-- 메디솔라 나만의 식단 플랜: 포화지방/트랜스지방 컬럼 추가
-- 실행 방법: PHP MySQL 웹 인터페이스에서 직접 실행
-- 선행 조건: migrations/002_alter_es_addGoods_nutrition.sql 실행 완료
-- 용도: 기존에 002 Migration을 실행한 경우, 추가로 실행

ALTER TABLE es_addGoods
    ADD COLUMN nutrition_saturated_fat DECIMAL(5,1) DEFAULT NULL COMMENT '포화지방(g)' AFTER nutrition_fat,
    ADD COLUMN nutrition_trans_fat DECIMAL(5,1) DEFAULT NULL COMMENT '트랜스지방(g)' AFTER nutrition_saturated_fat;

-- 참고: 이미 컬럼이 존재하면 에러 발생
-- 에러 시 무시하고 다음 단계 진행
