-- 메디솔라 나만의 식단 플랜: es_goods 테이블에 isCustomDiet 플래그 추가
-- 실행 방법: PHP MySQL 웹 인터페이스에서 직접 실행
-- 테이블 네이밍 규칙: tableGoods → es_goods

ALTER TABLE es_goods
    ADD COLUMN isCustomDiet TINYINT(1) DEFAULT 0 COMMENT '나만의 식단 플랜 상품 여부 (1=커스텀 다이어트 UI 사용)';

-- 인덱스 추가 (선택적)
ALTER TABLE es_goods ADD INDEX idx_isCustomDiet (isCustomDiet);

-- 사용법:
-- 나만의 식단 플랜 상품으로 지정할 goodsNo에 대해:
-- UPDATE es_goods SET isCustomDiet = 1 WHERE goodsNo = {상품번호};

-- 참고: 이미 칼럼이 존재하면 에러 발생
-- 에러 시: SHOW COLUMNS FROM es_goods LIKE 'isCustomDiet'; 로 확인
