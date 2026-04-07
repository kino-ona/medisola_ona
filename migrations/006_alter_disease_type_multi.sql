-- disease_type 다중 값 지원: VARCHAR(50) → VARCHAR(255)
-- 콤마 구분으로 복수 질환 저장 가능 (예: "당뇨케어,신장케어")
-- 실행 방법: PHP MySQL 웹 인터페이스에서 직접 실행

ALTER TABLE es_addGoods
    MODIFY COLUMN disease_type VARCHAR(255) DEFAULT NULL COMMENT '질환케어유형(콤마 구분): 당뇨케어,신장케어,암케어,고지혈증케어';
