# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a **PHP-based e-commerce development module** for the Medisola Godo Shopping Mall platform (GodomMall 5). It extends the core Bundle components with custom functionality for the Medisola brand.

- **Framework**: PHP with CakePHP-style architecture
- **Architecture Documentation**: http://doc.godomall5.godomall.com/Getting_Started/Architecture
- **Deployment**: Direct SFTP deployment to `gdadmin.medisola2.godomall.com`

## Development Workflow

**No Build Process Required**
- Files are deployed directly via SFTP on save (configured in `.vscode/sftp.json`)
- No composer dependencies or package managers
- No compilation or build steps needed

**Development Environment**
- Uses VSCode with SFTP extension for live deployment
- Files auto-upload to remote server on save
- Bundle directory is read-only (extends core GodomMall functionality)

## Architecture

### Directory Structure

```
Bundle/           # Core GodomMall components (READ-ONLY)
├── Component/    # Core business logic components
├── Controller/   # Base controllers and admin functionality
└── Widget/       # Base widget implementations

Component/        # Custom Medisola extensions
├── GiftOrder/    # Gift ordering system
├── Order/        # Extended order management
├── Wm/          # Medisola-specific modules (EarlyDelivery, FirstDelivery, etc.)  
├── Traits/      # Reusable traits (Common, SendSms, GoodsInfo, etc.)
└── ...          # Other component extensions

Controller/       # MVC Controllers
├── Admin/       # Administrative interface controllers
├── Front/       # Customer-facing controllers  
└── Mobile/      # Mobile-specific controllers

Widget/          # Custom UI widgets
├── Front/       # Desktop widgets
└── Mobile/      # Mobile widgets
```

### Key Architecture Principles

**Inheritance Pattern**: Custom components extend Bundle classes
```php
class Order extends \Bundle\Component\Order\Order
```

**Namespace Structure**: Components organized by functionality
```php
namespace Component\GiftOrder;
namespace Controller\Admin\Goods;
```

**Trait Usage**: Common functionality shared via traits
```php
use Component\Traits\SendSms;
use Component\Traits\GoodsInfo;
```

## Core Modules

**E-commerce Core**
- `Order/` - Order processing and management
- `Cart/` - Shopping cart functionality  
- `Goods/` - Product catalog and management
- `Member/` - User accounts and authentication

**Medisola-Specific Features**
- `GiftOrder/` - Gift card and gift ordering system
- `Wm/EarlyDelivery` - Express delivery services
- `Wm/FirstDelivery` - First-time delivery logic
- `OurMenu/` - Custom menu management

**Integration Services**
- `Sms/` - SMS notifications and marketing
- `Mail/` - Email systems
- `Marketing/` - Third-party integrations (Kakao, Naver, Apple Login)

## Database Conventions

- Tables use Korean comments for business logic
- Custom tables prefixed with `wm_` (e.g., `wm_giftSet`)
- Extends standard GodomMall database schema

## Key Development Notes

- Bundle directory is protected and should not be modified
- All customizations go in Component/, Controller/, or Widget/ directories
- Use existing traits for common functionality (SMS, goods info, etc.)
- Follow namespace conventions when creating new components
- Korean language support throughout the codebase