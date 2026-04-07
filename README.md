# Medisola Godo Shopping Mall - 개발 가이드

메디솔라 고도몰 통합 프로젝트 개발자 온보딩 가이드입니다.

## 목차

- [프로젝트 개요](#프로젝트-개요)
- [프로젝트 구조](#프로젝트-구조)
- [개발 환경 설정](#개발-환경-설정)
- [개발 워크플로우](#개발-워크플로우)
- [고도몰 커스터마이제이션 가이드](#고도몰-커스터마이제이션-가이드)
- [배포 방법](#배포-방법)
- [주요 개발 규칙](#주요-개발-규칙)
- [문제 해결](#문제-해결)
- [참고 자료](#참고-자료)

---

## 프로젝트 개요

이 프로젝트는 메디솔라(Medisola) 건강식품 쇼핑몰을 위한 **고도몰 통합 프로젝트**입니다.

### 기술 스택

- **백엔드**: PHP (CakePHP 스타일 아키텍처)
- **플랫폼**: Godo Shopping Mall 5
- **프론트엔드**: HTML, CSS, JavaScript (jQuery 기반)
- **데이터베이스**: MySQL (한국어 지원)
- **배포**: SFTP를 통한 직접 배포
- **버전 관리**: Git

## 프로젝트 구조

이 프로젝트는 **4개의 독립적인 저장소**가 하나의 모노레포로 통합된 구조입니다.

```
godo-mall/
├── godo-dev-module/        # 개발 환경 백엔드 모듈
├── godo-module/             # 운영 환경 백엔드 모듈
├── godo-skin/               # 프론트엔드 스킨 (데스크톱/모바일)
├── admin-skin/              # 관리자 인터페이스
├── .vscode/
│   └── sftp.json           # SFTP 배포 설정
├── CLAUDE.md               # 프로젝트 전체 개요
├── GODOMALL_DEVELOPMENT_GUIDELINES.md  # 개발 가이드라인
└── README.md               # 이 문서
```

### 각 저장소의 역할

#### 1. godo-dev-module (개발 모듈)

```
godo-dev-module/
├── Bundle/                  # 고도몰 코어 프레임워크 (읽기 전용)
├── Component/               # 커스텀 비즈니스 로직
│   ├── GiftOrder/          # 선물하기 시스템
│   ├── Order/              # 주문 확장 기능
│   ├── Member/             # 회원 관리 확장
│   ├── Wm/                 # 메디솔라 전용 모듈
│   │   ├── EarlyDelivery/  # 새벽배송
│   │   └── FirstDelivery/  # 첫구매배송
│   └── Traits/             # 공통 Trait (SendSms, GoodsInfo 등)
├── Controller/              # MVC 컨트롤러
│   ├── Admin/              # 관리자 컨트롤러
│   ├── Front/              # 프론트엔드 컨트롤러
│   └── Mobile/             # 모바일 컨트롤러
└── Widget/                  # UI 위젯
```

**특징:**
- 완전한 Bundle 프레임워크 포함
- 저장 시 자동으로 개발 서버에 배포 (`uploadOnSave: true`)
- 실험과 테스트에 안전한 환경

#### 2. godo-module (운영 모듈)

```
godo-module/
├── Component/               # 커스텀 확장 기능만 포함
├── Controller/              # 운영 컨트롤러
└── Widget/                  # 운영 위젯
```

**특징:**
- Bundle은 플랫폼에서 상속받음 (저장소에 없음)
- 수동 배포 (`uploadOnSave: false`)
- 개발 완료 후 배포하는 최종 타겟

#### 3. godo-skin (프론트엔드 스킨)

```
godo-skin/
├── front/                   # 데스크톱 스킨
│   ├── medisola_dev/       # 개발용 테마 (작업 여기서 시작)
│   └── drorganic_24_renewal/  # 운영용 테마 (확정 후 배포)
├── mobile/                  # 모바일 스킨
│   ├── medisola_dev/       # 개발용 모바일 테마
│   └── dorganic_24_renewal/  # 운영용 모바일 테마
└── backup/                  # 테마 백업
```

**특징:**
- 저장 시 자동 배포 (`uploadOnSave: true`)
- HTML 템플릿, CSS, JavaScript 포함
- 고도몰 템플릿 문법 사용 (`{ }` 태그)

#### 4. admin-skin (관리자 스킨)

```
admin-skin/
└── medisola_admin_skin_original/  # 라이브 관리자 인터페이스
```

**특징:**
- 라이브 환경 (주의 필요!)
- 저장 시 즉시 운영 서버에 반영
- 관리자 페이지 커스터마이징

---

### SFTP 설정 이해하기

`.vscode/sftp.json` 파일에 4개의 프로필이 설정되어 있습니다:

```json
[
  {
    "name": "Dev Module",
    "context": "./godo-dev-module",
    "uploadOnSave": true,           // 저장 시 자동 배포
    "remotePath": "~/data/module"
  },
  {
    "name": "Module",
    "context": "./godo-module",
    "uploadOnSave": false,          // 수동 배포
    "remotePath": "~/module"
  },
  {
    "name": "Skin",
    "context": "./godo-skin",
    "uploadOnSave": true,
    "remotePath": "~/data/skin"
  },
  {
    "name": "Admin Skin",
    "context": "./admin-skin",
    "uploadOnSave": true,           // 라이브 환경!
    "remotePath": "~/admin"
  }
]
```

### Git Hooks 설정

프로젝트는 자동으로 Git 훅을 설정합니다:

```bash
# Git hooks 설정 (이미 자동 실행됨)
chmod +x setup-hooks.sh
./setup-hooks.sh
```

**설정된 훅:**
- `post-checkout`: 브랜치 변경 시 SFTP 설정 초기화

---

## 개발 워크플로우

### 기본 개발 프로세스

고도몰 개발은 **개발 환경 → 테스트 → 운영 배포** 순서를 따릅니다.

```
┌─────────────────┐      ┌──────────────┐      ┌─────────────────┐
│ 개발 환경        │      │ 테스트       │      │ 운영 배포        │
├─────────────────┤      ├──────────────┤      ├─────────────────┤
│ godo-dev-module │ ───> │ 개발 사이트   │ ───> │ godo-module     │
│ medisola_dev    │      │ 확인         │      │ drorganic_24    │
│ (스킨)          │      │              │      │ (운영 스킨)      │
└─────────────────┘      └──────────────┘      └─────────────────┘
     ↓ 저장 시                                        ↓ 수동 배포
  자동 배포                                        확정 후 배포
```

### 단계별 워크플로우

#### 1단계: 프론트엔드 개발 (개발 테마)

```bash
# 데스크톱 개발
godo-skin/front/medisola_dev/

# 모바일 개발
godo-skin/mobile/medisola_dev/
```

**중요:** 데스크톱과 모바일 **둘 다** 구현해야 합니다!

#### 2단계: 백엔드 개발 (개발 모듈)

```bash
# 백엔드 로직 개발
godo-dev-module/Component/
godo-dev-module/Controller/
```

#### 3단계: 운영 배포 (모든 기능 확정 후)

```bash
# 프론트엔드 배포
godo-skin/front/medisola_dev/ → drorganic_24_renewal/
godo-skin/mobile/medisola_dev/ → dorganic_24_renewal/

# 백엔드 배포
godo-dev-module/ → godo-module/
```

### 일반적인 작업 시나리오

#### 시나리오 1: 새로운 페이지 추가

```bash
# 1. 프론트엔드 템플릿 작성 (개발 테마)
godo-skin/front/medisola_dev/member/new_page.html
godo-skin/mobile/medisola_dev/member/new_page.html

# 2. 컨트롤러 작성
godo-dev-module/Controller/Front/Member/NewPageController.php
godo-dev-module/Controller/Mobile/Member/NewPageController.php

# 3. 테스트 후 운영 배포
# 프론트엔드 → drorganic_24_renewal/dorganic_24_renewal
# 백엔드 → godo-module
```

#### 시나리오 2: 기존 기능 수정

```bash
# 1. 개발 테마에서 수정
godo-skin/front/medisola_dev/goods/goods_view.html

# 2. 모바일도 동일하게 수정
godo-skin/mobile/medisola_dev/goods/goods_view.html

# 3. 관련 백엔드 로직 수정 (필요시)
godo-dev-module/Component/Goods/Goods.php

# 4. 테스트 후 운영 배포
```

#### 시나리오 3: 관리자 기능 추가

```bash
# 1. 관리자 컨트롤러 작성
godo-dev-module/Controller/Admin/Goods/CustomController.php

# 2. 관리자 스킨 수정 (주의: 라이브 환경!)
admin-skin/medisola_admin_skin_original/custom/page.php

# 3. 테스트 후 운영 배포
godo-dev-module/ → godo-module/
```

---

## 고도몰 커스터마이제이션 가이드

### Bundle-First 개발 방법론

고도몰 개발의 핵심 원칙: **항상 기존 Bundle 기능을 찾아서 확장하기**

#### 1. Bundle 탐색 프로세스

```bash
# 1. 유사한 컨트롤러 찾기
find godo-dev-module/Bundle/Controller -name "*Member*Controller.php"

# 2. 관련 컴포넌트 찾기
find godo-dev-module/Bundle/Component -name "*Member*"

# 3. 기존 템플릿 찾기
find godo-skin -name "*join*.html"
```

#### 2. Bundle 분석 체크리스트

기능 구현 전 반드시 확인:
- [ ] Bundle 컨트롤러 메서드 시그니처와 예상 파라미터
- [ ] 템플릿 필드명과 데이터 구조
- [ ] 세션 관리 패턴
- [ ] 유효성 검사 및 처리 흐름

#### 3. 확장 패턴

**컨트롤러 확장 (프론트엔드)**

```php
// godo-dev-module/Controller/Front/Member/CustomController.php
namespace Controller\Front\Member;

class CustomController extends \Controller\Front\Controller
{
    public function index()
    {
        // Bundle 패턴 따르기
        $siteLink = new \Component\SiteLink\SiteLink();
        $this->setData('actionUrl', $siteLink->link('../member/member_ps.php', 'ssl'));

        // 템플릿에서 사용할 데이터 설정
        $this->setData('birthYearOptions', $birthYearOptions);
    }
}
```

**모바일 컨트롤러 패턴**

```php
// godo-dev-module/Controller/Mobile/Member/CustomController.php
namespace Controller\Mobile\Member;

class CustomController extends \Bundle\Controller\Mobile\Controller
{
    public function index()
    {
        // 프론트엔드 컨트롤러에 위임
        $front = \App::load('\\Controller\\Front\\Member\\CustomController');
        $front->index();
        $this->setData($front->getData());
    }
}
```

**컴포넌트 확장**

```php
// godo-dev-module/Component/Member/Member.php
namespace Component\Member;

class Member extends \Bundle\Component\Member\Member
{
    public function customJoin($params)
    {
        // 부모 메서드 먼저 호출
        $result = parent::join($params);

        // 커스텀 로직 추가
        if (isset($params['wanban'])) {
            // 완판 회원 추가 처리
        }

        return $result;
    }
}
```

### 템플릿 개발

#### 고도몰 템플릿 문법

```html
{*** 파일 설명 | file_path ***}

<!-- 변수 출력 -->
<h1>{mallName}</h1>
<p>{=gSess.memNm}님 환영합니다</p>

<!-- 반복문 -->
{@goodsList}
<div class="goods-item">
    <h3>{goodsNm}</h3>
    <p>{goodsPrice}원</p>
</div>
{/@}

<!-- 조건문 -->
{?isLogin}
    <a href="/mypage">마이페이지</a>
{:}
    <a href="/member/login">로그인</a>
{/?}

<!-- 파일 인클루드 -->
{#header}
{#footer}
```

#### 폼 필드명 규칙

Bundle과 호환되는 필드명 사용이 필수입니다:

```html
<!-- 올바른 예시 -->
<input name="memId" />          <!-- 회원 ID -->
<input name="memPw" />          <!-- 비밀번호 -->
<input name="memPwRe" />        <!-- 비밀번호 확인 -->
<input name="memNm" />          <!-- 이름 -->
<input name="cellPhone" />      <!-- 휴대폰 (단일 필드) -->
<input name="sexFl" value="m"/> <!-- 성별 (sexFl, not sex) -->
<select name="birthYear">       <!-- 생년 -->
<select name="birthMonth">      <!-- 생월 -->
<select name="birthDay">        <!-- 생일 -->

<!-- 잘못된 예시 -->
<input name="password" />       <!-- memPw 사용해야 함 -->
<input name="passwordConfirm" /><!-- memPwRe 사용해야 함 -->
<input name="sex" />            <!-- sexFl 사용해야 함 -->
<input name="phone[]" />        <!-- cellPhone 단일 필드 사용 -->
```

#### 데스크톱/모바일 동시 구현

**필수 규칙:** 모든 기능은 데스크톱과 모바일 동시 구현

```
구현 체크리스트:
□ /front/medisola_dev/feature.html (데스크톱 템플릿)
□ /mobile/medisola_dev/feature.html (모바일 템플릿)
□ /front/medisola_dev/js/feature.js (데스크톱 JS)
□ /mobile/medisola_dev/js/feature.js (모바일 JS)
□ 동일한 폼 필드명 사용
□ 동일한 처리 흐름
□ 플랫폼별 적절한 스타일링
```

### 공통 함정 피하기

#### 함정 1: 필드명 불일치

```php
// 잘못된 예시
<input name="password_confirm" />

// 올바른 예시
<input name="memPwRe" />
```

#### 함정 2: 모바일 컨트롤러 상속

```php
// 잘못된 예시
class MobileController extends \Controller\Front\SomeController

// 올바른 예시
class MobileController extends \Bundle\Controller\Mobile\Controller
{
    public function index()
    {
        $front = \App::load('\\Controller\\Front\\SomeController');
        $front->index();
        $this->setData($front->getData());
    }
}
```

#### 함정 3: 템플릿 데이터 불일치

```php
// 잘못된 예시
$this->setData('birthYears', $data);  // 템플릿은 birthYearOptions 기대

// 올바른 예시
$this->setData('birthYearOptions', $data);
```

### 개발 체크리스트

기능 배포 전 확인 사항:

**Bundle 호환성**
- [ ] 유사 Bundle 기능 찾아서 분석함
- [ ] 폼 필드명이 Bundle 컴포넌트 기대값과 일치
- [ ] 컨트롤러가 Bundle 상속/위임 패턴 따름
- [ ] 템플릿 데이터 구조가 컨트롤러 출력과 일치
- [ ] 처리 흐름이 Bundle 컴포넌트와 통합됨

**아키텍처 준수**
- [ ] 프론트 컨트롤러가 `\Controller\Front\Controller` 상속
- [ ] 모바일 컨트롤러가 `\Bundle\Controller\Mobile\Controller` 상속
- [ ] 모바일 컨트롤러가 프론트 컨트롤러에 위임
- [ ] 컴포넌트 확장이 부모 Bundle 메서드 호출
- [ ] 세션 관리가 Bundle 패턴 따름

**듀얼 플랫폼 구현**
- [ ] 데스크톱 템플릿 구현
- [ ] 모바일 템플릿 구현
- [ ] 양쪽 템플릿이 동일한 필드명 사용
- [ ] 양쪽 템플릿이 동일한 컨트롤러 통해 처리
- [ ] 각 플랫폼에 적절한 스타일링

**테스트**
- [ ] 데스크톱에서 폼 제출 성공
- [ ] 모바일에서 폼 제출 성공
- [ ] 데이터가 Bundle Member 데이터베이스에 정확히 저장
- [ ] 세션 관리 정상 동작
- [ ] 기존 Bundle 기능과의 통합 검증

---

## 배포 방법

### SFTP를 통한 서버 배포

이 프로젝트는 **SFTP를 통해 서버에 직접 배포**되며, **Git을 통해 변경 이력을 관리**합니다.

```
로컬 개발 → SFTP 배포 → 원격 서버
    ↓
  Git Commit → GitHub (변경 이력 관리)
```

### 자동 배포 (개발 환경)

다음 디렉토리는 **저장 시 자동으로 서버에 업로드**됩니다:

```
✓ godo-dev-module/   → 개발 서버 ~/data/module
✓ godo-skin/         → 개발 서버 ~/data/skin
✓ admin-skin/        → 운영 서버 ~/admin (주의!)
```

**작업 방법:**
1. VSCode에서 파일 수정
2. `Cmd+S` (저장)
3. SFTP 확장이 자동으로 서버에 업로드
4. 개발 사이트에서 즉시 확인 가능

### 수동 배포 (운영 환경)

`godo-module/`는 **수동 배포**가 필요합니다:

**VSCode 명령 팔레트 사용:**

```bash
Cmd+Shift+P
> SFTP: Upload File          # 현재 파일만 업로드
> SFTP: Upload Folder        # 폴더 전체 업로드
> SFTP: Sync Local -> Remote # 변경된 파일만 동기화
```

**권장 배포 프로세스:**

```bash
# 1. 개발 모듈에서 기능 완성
# godo-dev-module/ 수정 → 저장 → 자동 배포 → 테스트

# 2. 운영 모듈로 복사
cp -r godo-dev-module/Component/Feature godo-module/Component/

# 3. 수동 업로드
# VSCode: SFTP: Upload Folder 실행

# 4. Git 커밋
git add godo-module/
git commit -m "feat: Add new feature to production module"
git push
```

### Git 워크플로우

**브랜치 전략:**

```bash
main        # 운영 브랜치 (운영 서버 코드와 동기화)
develop     # 개발 브랜치 (개발 서버 코드와 동기화)
feature/*   # 기능 개발 브랜치
```

**일반적인 Git 작업:**

```bash
# 1. 기능 브랜치 생성
git checkout develop
git checkout -b feature/new-feature

# 2. 개발 및 커밋
# ... 코드 수정 ...
git add .
git commit -m "feat: Implement new feature"

# 3. 개발 브랜치에 머지
git checkout develop
git merge feature/new-feature

# 4. 테스트 후 운영 배포
git checkout main
git merge develop

# 5. 운영 모듈 수동 배포
# VSCode: SFTP: Sync Local -> Remote (Module)
```

**커밋 메시지 규칙:**

```
feat: 새로운 기능 추가
fix: 버그 수정
docs: 문서 수정
style: 코드 포맷팅, 세미콜론 누락 등
refactor: 코드 리팩토링
test: 테스트 추가
chore: 빌드 작업, 패키지 매니저 설정 등
```

### 배포 전 체크리스트

**개발 환경 → 운영 환경 배포 시:**

- [ ] 개발 사이트에서 충분히 테스트 완료
- [ ] 데스크톱/모바일 양쪽 모두 동작 확인
- [ ] 관련 문서 업데이트 (필요시)
- [ ] Git에 커밋 완료
- [ ] 프론트엔드: `medisola_dev` → `drorganic_24_renewal`, `dorganic_24_renewal` 파일 복사
- [ ] 백엔드: `godo-dev-module` → `godo-module` 파일 복사
- [ ] SFTP 수동 업로드 실행
- [ ] 운영 사이트에서 최종 확인

---

## 주요 개발 규칙

### 1. 데스크톱/모바일 듀얼 구현 필수

**모든 기능은 데스크톱과 모바일 둘 다 구현해야 합니다.**

```
개발:
✓ /godo-skin/front/medisola_dev/feature.html
✓ /godo-skin/mobile/medisola_dev/feature.html

운영 배포:
✓ /godo-skin/front/drorganic_24_renewal/feature.html
✓ /godo-skin/mobile/dorganic_24_renewal/feature.html
```

### 2. Bundle-First 개발

**새 기능을 만들기 전에 항상 Bundle에서 유사 기능을 찾으세요.**

```bash
# Bundle 탐색 예시
find godo-dev-module/Bundle -name "*Order*"
grep -r "function checkout" godo-dev-module/Bundle
```

### 3. 개발 → 운영 순서 엄수

**절대 운영 폴더를 개발 중에 수정하지 마세요!**

```
올바른 순서:
1. medisola_dev 테마에서 개발
2. godo-dev-module에서 백엔드 개발
3. 테스트 완료 후
4. drorganic_24_renewal/dorganic_24_renewal 배포
5. godo-module 배포
```

### 4. admin-skin 주의

**admin-skin은 라이브 환경입니다!**

- 저장 즉시 운영 서버에 반영됨
- 수정 전 반드시 백업
- 신중하게 테스트 후 수정

### 5. Bundle 디렉토리 수정 금지

**Bundle/은 읽기 전용입니다.**

```
✗ Bundle/Component/Order/Order.php 수정  (절대 금지)
✓ Component/Order/Order.php 확장        (올바른 방법)
```

### 6. 네임스페이스 규칙

```php
// 컨트롤러
namespace Controller\Front\Member;
namespace Controller\Mobile\Member;
namespace Controller\Admin\Goods;

// 컴포넌트
namespace Component\GiftOrder;
namespace Component\Wm\EarlyDelivery;

// 위젯
namespace Widget\Front\FirstDelivery;
```

### 7. 공통 Trait 활용

```php
use Component\Traits\SendSms;       // SMS 전송
use Component\Traits\GoodsInfo;     // 상품 정보
use Component\Traits\Common;        // 공통 기능
use Component\Traits\Security;      // 보안 기능
```

---


### 외부 문서

- [고도몰 공식 문서](http://doc.godomall5.godomall.com/)
- [고도몰 아키텍처](http://doc.godomall5.godomall.com/Getting_Started/Architecture)
- [고도몰 API 레퍼런스](http://doc.godomall5.godomall.com/API_Reference)

### 개발 도구

- [VSCode SFTP 확장](https://marketplace.visualstudio.com/items?itemName=liximomo.sftp)
- [PHP Intelephense](https://marketplace.visualstudio.com/items?itemName=bmewburn.vscode-intelephense-client) - PHP 자동완성
- [GitLens](https://marketplace.visualstudio.com/items?itemName=eamodio.gitlens) - Git 히스토리 시각화

### 자주 사용하는 명령어

**SFTP 명령:**
```
Cmd+Shift+P → SFTP: Upload File
Cmd+Shift+P → SFTP: Download File
```

**Git 명령:**
```bash
git status                    # 변경 상태 확인
git add .                     # 모든 변경사항 스테이징
git commit -m "message"       # 커밋
git pull origin develop       # 최신 코드 받기
git push origin develop       # 원격 저장소에 푸시
```

**Happy Coding!** 🚀

메디솔라 고도몰 프로젝트에 오신 것을 환영합니다. 궁금한 점이 있으면 언제든지 팀원들에게 문의하세요.
