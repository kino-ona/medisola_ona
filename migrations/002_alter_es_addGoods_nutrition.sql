-- 메디솔라 나만의 식단 플랜: es_addGoods 테이블에 영양 정보 컬럼 추가
-- 실행 방법: PHP MySQL 웹 인터페이스에서 직접 실행
-- 테이블 네이밍 규칙: tableAddGoods → es_addGoods

ALTER TABLE es_addGoods
    -- 핵심 영양소 (필터링 대상)
    ADD COLUMN nutrition_calories INT DEFAULT NULL COMMENT '칼로리(kcal)',
    ADD COLUMN nutrition_protein DECIMAL(5,1) DEFAULT NULL COMMENT '단백질(g)',
    ADD COLUMN nutrition_carbs DECIMAL(5,1) DEFAULT NULL COMMENT '탄수화물(g)',
    ADD COLUMN nutrition_sugar DECIMAL(5,1) DEFAULT NULL COMMENT '당(g)',
    ADD COLUMN nutrition_fat DECIMAL(5,1) DEFAULT NULL COMMENT '지방(g)',
    ADD COLUMN nutrition_saturated_fat DECIMAL(5,1) DEFAULT NULL COMMENT '포화지방(g)',
    ADD COLUMN nutrition_trans_fat DECIMAL(5,1) DEFAULT NULL COMMENT '트랜스지방(g)',
    ADD COLUMN nutrition_omega3 INT DEFAULT NULL COMMENT '오메가-3(mg)',
    ADD COLUMN nutrition_cholesterol INT DEFAULT NULL COMMENT '콜레스테롤(mg)',

    -- 추가 영양소 (참고용)
    ADD COLUMN nutrition_sodium INT DEFAULT NULL COMMENT '나트륨(mg)',
    ADD COLUMN nutrition_fiber DECIMAL(5,1) DEFAULT NULL COMMENT '식이섬유(g)',

    -- 4가지 메타데이터
    ADD COLUMN main_ingredients TEXT DEFAULT NULL COMMENT 'JSON: ["연어","브로콜리"]',
    ADD COLUMN allergens TEXT DEFAULT NULL COMMENT 'JSON: ["해산물","유제품"]',
    ADD COLUMN food_style VARCHAR(50) DEFAULT NULL COMMENT '음식스타일(한식/양식/일식/중식/에스닉)',
    ADD COLUMN meal_type VARCHAR(50) DEFAULT NULL COMMENT '메뉴타입(밥류/덮밥/파스타/스테이크/베이글)',

    -- 복합 인덱스 (영양소 필터링 성능 최적화)
    ADD INDEX idx_nutrition_filter (nutrition_calories, nutrition_protein, nutrition_sugar, nutrition_cholesterol);
