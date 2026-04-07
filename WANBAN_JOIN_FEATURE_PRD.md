# Wanban Join Feature - Product Requirements Document (PRD)

**Project:** Wanban Restaurant Customer Conversion to Medisola Online Members  
**Date:** 2025-07-04  
**Status:** Development Ready

## 1. Business Context & Goals

### Product Overview

-   **Wanban (완반)**: Healthy food restaurant (offline)
-   **Medisola**: HMR (Home Meal Replacement) e-commerce site (online)
-   **Relationship**: Same owner, similar healthy food products

### Business Goals

-   **Primary Goal**: Convert offline Wanban restaurant customers → online Medisola members
-   **Strategy**: Create omnichannel customer experience
-   **Value**: Increase customer lifetime value through cross-channel engagement
-   **Data**: Build unified customer database with proper attribution

### Customer Acquisition Touchpoints

-   **QR codes** at restaurant tables
-   **Staff recommendations** during service

## 2. Current Implementation Status

### ✅ Already Implemented

-   `wanban_join_method.html` (desktop & mobile) - Landing page with modern gradient design
-   `WanbanJoinMethodController.php` (Front & Mobile) - Controller logic
-   Kakao login integration for wanban users
-   Backend logic in `Member.php` (group 20 assignment, joinedVia tracking)
-   Amplitude analytics tracking throughout the flow

### 🔨 Needs to be Built

-   Custom wanban agreement page
-   Custom wanban join form (simplified fields)
-   Custom wanban welcome/success page
-   Associated backend controllers

## 3. Complete User Journey

### Current Flow (Incomplete)

1. **QR Code Scan** → `wanban_join_method.html` ✅
2. **Click "회원가입"** → Redirects to standard `join_agreement.php` ❌
3. **Standard flow** → Not optimized for wanban customers ❌

### Target Flow (Complete)

1. **QR Code Scan** → `wanban_join_method.html` ✅
2. **Click "회원가입"** → `wanban_join_agreement.html` 🔨
3. **Accept agreements** → `wanban_join.html` 🔨
4. **Complete signup** → Account created with group 20 assignment 🔨
5. **Success redirect** → `wanban_welcome.html` 🔨

## 4. Technical Specifications

### 4.1 Custom Wanban Join Form Fields

**Required Fields:**

-   ID (username)
-   Password
-   Name
-   Phone

**Additional Fields:**

-   Gender
-   Birthday

**Technical Requirements:**

-   Form assigns users to **group 20** (wanban group)
-   Sets `joinedVia = 'wanban'` for tracking
-   Uses existing Medisola validation rules
-   Simplified compared to standard join form (no business info, complex address, etc.)

### 4.2 Member Group Assignment

-   **Group Number**: 20 (existing in current implementation)
-   **Purpose**: Enable future benefit management via Godo Mall's member group system
-   **Tracking**: `joinedVia = 'wanban'` for attribution analysis

### 4.3 Agreement Page Requirements

-   **Content**: Use standard Medisola agreements (privacy, terms, optional consents)
-   **Branding**: Update step indicators to show "완반 회원가입"
-   **Flow**: Direct to custom wanban join form instead of standard form

### 4.4 Welcome Page Requirements

-   **Messaging**: Welcome message specific to wanban customers
-   **Benefits**: Information about group benefits (managed by Godo Mall)
-   **Call-to-Action**: Links to start shopping or special offers
-   **Tracking**: Analytics for successful wanban registration

## 5. Implementation Plan

### Phase 1: Frontend Development (medisola_dev themes)

#### 5.1 Custom Wanban Agreement Page

**Files to create:**

-   `/godo-skin/front/medisola_dev/member/wanban_join_agreement.html`
-   `/godo-skin/mobile/medisola_dev/member/wanban_join_agreement.html`

**Implementation approach:**

-   Copy structure from existing `join_agreement.html`
-   Modify form action to point to `wanban_join.php`
-   Update step indicator for wanban branding
-   Keep all standard agreement content
-   Update analytics events with "wanban\_" prefix

#### 5.2 Custom Wanban Join Form

**Files to create:**

-   `/godo-skin/front/medisola_dev/member/wanban_join.html`
-   `/godo-skin/mobile/medisola_dev/member/wanban_join.html`

**Implementation approach:**

-   Use existing join form structure but simplified
-   Include only required and additional fields listed above
-   Remove business info, complex address fields
-   Maintain validation and security features
-   Add wanban-specific styling and branding

#### 5.3 Custom Wanban Welcome Page

**Files to create:**

-   `/godo-skin/front/medisola_dev/member/wanban_welcome.html`
-   `/godo-skin/mobile/medisola_dev/member/wanban_welcome.html`

**Content requirements:**

-   Welcome message for wanban customers
-   Information about group benefits
-   Call-to-action to start shopping
-   Links to special wanban customer features

### Phase 2: Backend Development (godo-dev-module)

#### 5.4 Wanban Agreement Controller

**Files to create:**

-   `/godo-dev-module/Controller/Front/Member/WanbanJoinAgreementController.php`
-   `/godo-dev-module/Controller/Mobile/Member/WanbanJoinAgreementController.php`

**Functionality:**

-   Load standard agreement data
-   Set wanban context for tracking
-   Handle form submission to wanban join form

#### 5.5 Enhanced Wanban Join Controller

**Files to enhance:**

-   `/godo-dev-module/Controller/Front/Member/WanbanJoinController.php`
-   `/godo-dev-module/Controller/Mobile/Member/WanbanJoinController.php`

**Functionality:**

-   Handle simplified wanban join form submission
-   Validate required fields
-   Assign users to group 20
-   Set `joinedVia = 'wanban'`
-   Redirect to wanban welcome page

#### 5.6 Wanban Welcome Controller

**Files to create:**

-   `/godo-dev-module/Controller/Front/Member/WanbanWelcomeController.php`
-   `/godo-dev-module/Controller/Mobile/Member/WanbanWelcomeController.php`

**Functionality:**

-   Display welcome message
-   Load group benefits information
-   Track successful wanban registration

### Phase 3: Flow Updates

#### 5.7 Update wanban_join_method.html

**Current redirect:**

```javascript
location.href = "../member/join_agreement.php?memberFl=personal";
```

**New redirect:**

```javascript
location.href = "../member/wanban_join_agreement.php";
```

### Phase 4: Testing & Validation

#### 5.8 Complete User Journey Testing

1. QR code scan → `wanban_join_method.html`
2. Click "회원가입" → `wanban_join_agreement.html`
3. Accept agreements → `wanban_join.html`
4. Fill form → Account created with group 20
5. Redirect to → `wanban_welcome.html`

#### 5.9 Data Validation Checklist

-   [ ] Users get assigned to group 20
-   [ ] `joinedVia = 'wanban'` is set correctly
-   [ ] Analytics tracking works throughout flow
-   [ ] Form validation functions properly
-   [ ] Security measures are maintained
-   [ ] Both desktop and mobile versions work

### Phase 5: Production Distribution (After confirmation)

#### 5.10 Distribution Workflow

Following the established development workflow:

**Frontend Distribution:**

1. Copy confirmed changes from `front/medisola_dev/` → `front/drorganic_24_renewal/`
2. Copy confirmed changes from `mobile/medisola_dev/` → `mobile/dorganic_24_renewal/`

**Backend Distribution:** 3. Copy confirmed changes from `godo-dev-module/` → `godo-module/`

## 6. Acceptance Criteria

### 6.1 Functional Requirements

-   [ ] Users can complete the wanban signup flow from QR code to welcome page
-   [ ] All wanban users are assigned to group 20
-   [ ] `joinedVia = 'wanban'` tracking is implemented
-   [ ] Form validation works for all required fields
-   [ ] Both desktop and mobile versions function identically

### 6.2 Technical Requirements

-   [ ] Follows existing Medisola design patterns
-   [ ] Uses standard Godo Mall validation and security
-   [ ] Implements proper analytics tracking
-   [ ] Maintains dual implementation rule (desktop + mobile)
-   [ ] Integrates with existing member group benefit system

### 6.3 Analytics Requirements

-   [ ] Track wanban signup funnel: method view → agreement → form → success
-   [ ] Measure conversion rates at each step
-   [ ] Monitor group 20 member behavior vs. standard members
-   [ ] Attribution tracking for restaurant source

## 7. Development Guidelines

### 7.1 Follow Established Workflow

-   **Development**: Work only in `medisola_dev` themes and `godo-dev-module`
-   **Testing**: Verify functionality on development site
-   **Production**: Distribute to production folders only after confirmation

### 7.2 Code Standards

-   **Namespace**: Follow existing patterns (`Component\Member`, `Controller\Front\Member`)
-   **Validation**: Use existing Medisola validation rules
-   **Security**: Maintain existing security measures
-   **Localization**: Support Korean language throughout

### 7.3 Design Consistency

-   **Styling**: No need to follow Medisola design patterns
-   **User Experience**: Follow established flow patterns
-   **Branding**: Incorporate wanban-specific elements while maintaining Wanban consistency

### 7.4 Wanban Branding Assets

**Wanban Logo URL:**
`https://img.kr.gcp-karroter.net/businessPlatform/bizPlatform/profile/center_biz_1673210/1751352914557/e8692bcfca369a4ef756b7643e143ab91ae282113f6902eb745b90de1e98e0c1.jpeg?q=82&s=640x640&t=crop`

**Wanban Restaurant Photo URL:**
`https://img.kr.gcp-karroter.net/local_business_ugc/local_business_ugc/undefined/94201707/1749454217714/VF8zZmx5dG52RWFMZ1g3TTlBbURL.jpeg`

**Usage Guidelines:**
- **Logo**: Primary branding element on all pages, responsive sizing, proper alt text
- **Restaurant Photo**: Background element or hero image, creates emotional connection to offline experience
- **Visual Consistency**: Clean, professional aesthetic matching restaurant quality
- **Accessibility**: Proper fallback handling and alt text for all images

## 8. Key Benefits

### 8.1 Business Benefits

-   **Customer Acquisition**: Convert offline customers to online members
-   **Data Integration**: Unified customer database across channels
-   **Lifetime Value**: Increase customer engagement through multiple touchpoints
-   **Attribution**: Track restaurant-to-online conversion effectiveness

### 8.2 Technical Benefits

-   **Leverages Existing Infrastructure**: Uses standard Godo Mall systems
-   **Maintainable**: Follows established code patterns
-   **Scalable**: Easy to extend with additional wanban features
-   **Trackable**: Comprehensive analytics for optimization

### 8.3 User Experience Benefits

-   **Simplified Signup**: Streamlined form with only essential fields
-   **Fast Conversion**: Optimized flow from restaurant to online member
-   **Clear Value**: Immediate understanding of benefits and next steps
-   **Mobile Optimized**: Seamless experience on restaurant customer's primary device

---

**Next Steps:** Begin implementation with Phase 1 (Frontend Development) starting with the wanban agreement page.
