-- 메디솔라 나만의 식단 플랜: es_addGoods 테이블에 V0 UI용 추가 필드
-- 실행 방법: PHP MySQL 웹 인터페이스에서 직접 실행
-- 테이블 네이밍 규칙: tableAddGoods → es_addGoods
-- 선행 조건: migrations/002_alter_es_addGoods_nutrition.sql 실행 완료

ALTER TABLE es_addGoods
    -- V0 UI에 필요한 추가 필드
    -- 주의: 기존 tags 칼럼은 골라담기 필터용이므로 건드리지 않음
    ADD COLUMN name_en VARCHAR(200) DEFAULT NULL COMMENT '영문명',
    ADD COLUMN category VARCHAR(50) DEFAULT NULL COMMENT '카테고리(seafood/meat/rice/noodle)',
    ADD COLUMN product_weight INT DEFAULT NULL COMMENT '중량(g)',
    ADD COLUMN nutrition_tags VARCHAR(255) DEFAULT NULL COMMENT '영양 태그(콤마 구분): 당뇨케어,고단백,저나트륨,오메가3',
    ADD COLUMN disease_type VARCHAR(50) DEFAULT NULL COMMENT '질환케어유형(당뇨케어/신장케어/암케어)',
    ADD COLUMN is_new TINYINT(1) DEFAULT 0 COMMENT '신상품 여부',
    ADD COLUMN recommend_reasons TEXT DEFAULT NULL COMMENT 'JSON: {"diabetes":"혈당 관리에 도움","kidney":"나트륨 함량 낮음"}',

    -- 인덱스 (필터링 성능 최적화)
    ADD INDEX idx_category (category),
    ADD INDEX idx_disease_type (disease_type),
    ADD INDEX idx_is_new (is_new);

-- 참고: 이미 칼럼이 존재하면 에러 발생
-- 에러 시 개별 칼럼씩 추가:
-- ALTER TABLE es_addGoods ADD COLUMN name_en VARCHAR(200) DEFAULT NULL COMMENT '영문명';
-- ALTER TABLE es_addGoods ADD COLUMN category VARCHAR(50) DEFAULT NULL COMMENT '카테고리(seafood/meat/rice/noodle)';
-- ...
