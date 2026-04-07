# GodomMall Development Guidelines

This document provides comprehensive guidelines for developing features in GodomMall while following established Bundle patterns and architectural principles.

## 🎯 **Bundle-First Development Methodology**

### **Core Principle**
**Always find and extend existing Bundle features rather than creating from scratch.**

When adding any new feature:
1. **FIRST**: Search `/Bundle` for similar existing functionality
2. **ANALYZE**: Study Bundle controller methods and expected data structures  
3. **COMPARE**: Match Bundle template field names with your requirements
4. **EXTEND**: Copy and extend Bundle patterns while maintaining structure
5. **VALIDATE**: Ensure compatibility with existing Bundle processing flows

---

## 📋 **Feature Development Workflow**

### **Step 1: Bundle Discovery Process**
```bash
# Search for similar controllers
find /Bundle/Controller -name "*FeatureName*Controller.php"

# Find related components
find /Bundle/Component -name "*FeatureName*"

# Locate existing templates
find /skin -name "*feature_name*.html"
```

**Example: Adding member feature**
```bash
# Found existing patterns
/Bundle/Controller/Front/Member/JoinController.php
/Bundle/Controller/Front/Member/JoinAgreementController.php
/Bundle/Component/Member/Member.php
/skin/front/*/member/join.html
```

### **Step 2: Bundle Analysis Framework**
**Before implementing, analyze:**
- Bundle controller method signatures and expected parameters
- Template field names and data structures
- Session management patterns
- Validation and processing flows

**Example Analysis:**
```php
// Bundle Member::join() expects these exact field names:
'memId', 'memPw', 'memPwRe', 'memNm', 'cellPhone', 'sexFl', 
'birthYear', 'birthMonth', 'birthDay'

// NOT custom names like:
'memPwConfirm', 'sex', 'cellPhone[]'
```

### **Step 3: Extension Patterns**

#### **Controller Extension (Custom Features)**
```php
// ✅ CORRECT: Extend Bundle patterns
class WanbanJoinController extends \Controller\Front\Controller
{
    public function index()
    {
        // Follow Bundle controller data preparation patterns
        $siteLink = new \Component\SiteLink\SiteLink();
        $this->setData('joinActionUrl', $siteLink->link('../member/member_ps.php', 'ssl'));
        $this->setData('token', Token::generate('token'));
        
        // Use Bundle-compatible data structures
        $this->setData('birthYearOptions', $birthYearOptions);
        $this->setData('birthMonthOptions', $birthMonthOptions);
        $this->setData('birthDayOptions', $birthDayOptions);
    }
}
```

#### **Mobile Controller Pattern**
```php
// ✅ CORRECT: Bundle mobile delegation pattern
class WanbanJoinMethodController extends \Bundle\Controller\Mobile\Controller
{
    public function index()
    {
        /** @var \Controller\Front\Member\WanbanJoinMethodController $front */
        $front = \App::load('\\Controller\\Front\\Member\\WanbanJoinMethodController');
        $front->index();
        $this->setData($front->getData());
    }
}

// ❌ WRONG: Extending Front controllers
class WanbanJoinMethodController extends \Controller\Front\Member\WanbanJoinMethodController
```

---

## 🎨 **Template Development Standards**

### **Form Field Naming Compatibility**
**CRITICAL**: Always use Bundle-compatible field names

#### **Member Registration Fields**
```html
<!-- ✅ CORRECT: Bundle-compatible names -->
<input name="memId" />          <!-- Member ID -->
<input name="memPw" />          <!-- Password -->
<input name="memPwRe" />        <!-- Password Confirmation -->
<input name="memNm" />          <!-- Member Name -->
<input name="cellPhone" />      <!-- Cell Phone (single field) -->
<input name="sexFl" value="m"/> <!-- Gender (sexFl, not sex) -->
<select name="birthYear">       <!-- Birth Year -->
<select name="birthMonth">      <!-- Birth Month -->
<select name="birthDay">        <!-- Birth Day -->

<!-- ❌ WRONG: Custom names incompatible with Bundle -->
<input name="memPwConfirm" />   <!-- Should be memPwRe -->
<input name="sex" />            <!-- Should be sexFl -->
<input name="cellPhone[]" />    <!-- Should be single cellPhone field -->
```

#### **Template Data Binding**
```html
<!-- ✅ Use Bundle controller data patterns -->
<form action="{joinActionUrl}">  <!-- From controller setData -->
<input value="{token}" />        <!-- Security token -->

<!--{@ birthYearOptions}-->      <!-- Loop Bundle data -->
<option value="{.}">{.}년</option>
<!--{/}-->
```

### **Dual Platform Implementation**
**MANDATORY**: Always implement both desktop and mobile versions

```
/skin/front/theme_name/member/feature.html     <!-- Desktop -->
/skin/mobile/theme_name/member/feature.html    <!-- Mobile -->
```

**Consistency Requirements:**
- Same form field names
- Same validation logic  
- Same processing flow
- Platform-appropriate styling

---

## 🏗️ **Architecture Patterns**

### **MVC Structure**
```
Bundle/                          # Core GodomMall (READ-ONLY)
├── Component/                   # Business logic
├── Controller/                  # Base controllers
└── Widget/                      # Base widgets

Component/                       # Custom extensions
├── Member/                      # Extended member functionality
└── [Feature]/                   # Custom components

Controller/                      # Custom controllers
├── Front/                       # Desktop controllers
└── Mobile/                      # Mobile controllers (delegate to Front)

skin/                           # Templates
├── front/[theme]/              # Desktop templates
└── mobile/[theme]/             # Mobile templates
```

### **Controller Inheritance Patterns**

#### **Front Controllers**
```php
// Extend base Front controller
class CustomController extends \Controller\Front\Controller
{
    public function index()
    {
        // Custom logic while following Bundle patterns
        $this->setData('key', $value);
    }
}
```

#### **Mobile Controllers**
```php
// Always delegate to Front controller
class MobileController extends \Bundle\Controller\Mobile\Controller  
{
    public function index()
    {
        $front = \App::load('\\Controller\\Front\\Path\\FrontController');
        $front->index();
        $this->setData($front->getData());
    }
}
```

#### **Component Extensions**
```php
// Extend Bundle components
class CustomMember extends \Bundle\Component\Member\Member
{
    public function customMethod($params)
    {
        // Call parent Bundle method first
        $result = parent::join($params);
        
        // Add custom logic
        if (isset($params['custom_flag'])) {
            // Custom processing
        }
        
        return $result;
    }
}
```

---

## ⚠️ **Common Pitfalls and Solutions**

### **1. Form Field Name Mismatches**
**Problem**: Custom field names don't match Bundle expectations
```html
<!-- ❌ WRONG -->
<input name="password_confirm" />
<input name="gender" />

<!-- ✅ CORRECT -->
<input name="memPwRe" />
<input name="sexFl" />
```

**Solution**: Always check Bundle Member component field requirements

### **2. Mobile Controller Inheritance**
**Problem**: Mobile controllers extending Front controllers
```php
// ❌ WRONG
class MobileController extends \Controller\Front\SomeController

// ✅ CORRECT  
class MobileController extends \Bundle\Controller\Mobile\Controller
```

**Solution**: Use Bundle delegation pattern

### **3. Template Data Mismatches**
**Problem**: Controller doesn't provide data that template expects
```php
// Template expects: {birthYearOptions}
// Controller provides: $birthYears (wrong)

// ✅ CORRECT
$this->setData('birthYearOptions', $data);
```

**Solution**: Match template variable names exactly

### **4. Session Management**
**Problem**: Custom session keys instead of Bundle patterns
```php
// ❌ WRONG: Custom session management
Session::set('custom_signup_flow', $data);

// ✅ CORRECT: Follow Bundle patterns
Session::set('wanban', 'true');  // Follow existing patterns
```

---

## ✅ **Validation Checklist**

Before deploying any GodomMall feature:

### **Bundle Compatibility**
- [ ] Found and analyzed similar Bundle feature
- [ ] Form field names match Bundle component expectations
- [ ] Controller follows Bundle inheritance/delegation patterns
- [ ] Template data structure matches controller output
- [ ] Processing flow integrates with Bundle components

### **Architecture Compliance**
- [ ] Front controller extends `\Controller\Front\Controller`
- [ ] Mobile controller extends `\Bundle\Controller\Mobile\Controller` 
- [ ] Mobile controller delegates to Front controller
- [ ] Component extensions properly call parent Bundle methods
- [ ] Session management follows Bundle patterns

### **Dual Platform Implementation**
- [ ] Desktop template implemented
- [ ] Mobile template implemented  
- [ ] Both templates use identical field names
- [ ] Both templates process through same controllers
- [ ] Styling appropriate for each platform

### **Processing Flow**
- [ ] Form submits to correct Bundle processing endpoint
- [ ] Data flows correctly through MemberPsController → Bundle Member
- [ ] Validation works with Bundle validation systems
- [ ] Error handling follows Bundle patterns
- [ ] Success/failure redirects work correctly

### **Testing Verification**
- [ ] Form submission successful on desktop
- [ ] Form submission successful on mobile
- [ ] Data appears correctly in Bundle Member database
- [ ] Session management works as expected
- [ ] Integration with existing Bundle features verified

---

## 🎯 **Best Practices Summary**

### **DO**
✅ Find existing Bundle features before implementing  
✅ Use Bundle-compatible field names  
✅ Follow Bundle controller patterns  
✅ Implement both desktop and mobile versions  
✅ Test integration with Bundle processing flows  
✅ Maintain GodomMall architectural conventions  

### **DON'T**  
❌ Create custom field names incompatible with Bundle  
❌ Inherit mobile controllers from Front controllers  
❌ Reinvent Bundle functionality from scratch  
❌ Skip dual platform implementation  
❌ Ignore existing Bundle processing patterns  
❌ Create custom session management systems  

---

## 📚 **Reference Examples**

### **Successful Implementation: Wanban Join Feature**
The Wanban join feature demonstrates proper Bundle-First development:

1. **Found Bundle Reference**: `Bundle/Controller/Front/Member/JoinController.php`
2. **Analyzed Field Requirements**: `memId`, `memPw`, `memPwRe`, `sexFl`, `cellPhone`
3. **Extended Bundle Patterns**: Controller delegation, template data structure
4. **Maintained Compatibility**: Form processes through existing `MemberPsController`
5. **Dual Implementation**: Both desktop and mobile versions
6. **Bundle Integration**: Uses existing `Bundle\Component\Member\Member::join()`

This approach ensured seamless integration with GodomMall's existing infrastructure while adding custom Wanban functionality.

---

## 🔄 **Continuous Improvement**

This document should be updated whenever:
- New Bundle patterns are discovered
- Common development pitfalls are identified  
- GodomMall architecture evolves
- New best practices are established

**Remember**: GodomMall's strength lies in its established Bundle patterns. Always extend, never reinvent.