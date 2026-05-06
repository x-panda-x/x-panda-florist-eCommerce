# x-panda-florist-eCommerce

Custom production-style florist eCommerce platform for **LILY AND ROSE FLORIST** with a full storefront, advanced admin panel, transactional reminders, and campaign email tooling.

## Live Demo
- https://nashvillesamedayflowerdelivery.com

## Project Overview
This project is a custom PHP MVC flower shop system focused on real-world operations: product/catalog management, order intake and lifecycle handling, media/theme/content editing, customer accounts, reminders, and branded email communication.

## Key Features
- Custom storefront with catalog, product detail, cart, checkout, and account flows
- Operational admin panel with product/order/customer/media/content management
- Reminder lifecycle with purchase-linked and action-needed scenarios
- Branded transactional email system with HTML + text fallback
- Admin bulk email campaigns with recipient filtering and safe targeting

## Admin Features
- Dashboard, orders console, products, categories, occasions, add-ons, promo codes
- Customers, delivery zones, navigation, homepage/hero, banners, footer/SEO, theme
- Site settings, email settings, email campaigns
- Mobile-first admin UX improvements (including iPhone-oriented usability)

## Customer Features
- Registration/login/account profile
- Password reset
- Address and reminder management
- Order and checkout flows

## Email & Reminder System
- Flows: welcome, password reset, order confirmation, store order notification, reminder confirmation/upcoming/action-needed
- Reminder processing job endpoint with lifecycle transitions
- Campaign send path for opted-in recipients

## Email Campaigns
- Admin recipient filtering (subscribed/all/orders/reminders)
- Search + select controls (all/visible/clear)
- Selected-recipient management panel
- QA override/test-only send support
- Send history logging

## Order Management
- Admin order listing, details, status updates, and tracking updates
- Customer/store notifications integrated into order lifecycle

## Responsive Design
- Storefront and admin responsive behavior for mobile/tablet/desktop
- Admin includes touch-friendly controls and small-screen layout adaptations

## Tech Stack
- PHP 8+ (custom MVC)
- MySQL
- Vanilla JS/CSS (+ Tailwind/PostCSS toolchain present)

## Local Setup
1. Clone repository.
2. Copy `.env.example` to `.env` and set environment values.
3. Create database and import schema/migrations as needed.
4. Configure web server document root to project root with `index.php` front controller.
5. Start app and verify `/admin/login` and storefront routes.

## Environment Variables
See `.env.example` for:
- app: `APP_NAME`, `APP_URL`
- database: `DB_*`
- email/store identity: `STORE_*`, `PUBLIC_BASE_URL`, `EMAIL_DELIVERY_MODE`, `SMTP_*`

## Security Notes
- No secrets are committed.
- Local credentials, deployment-local files, runtime logs, and temporary deployment artifacts are ignored.
- Use private infrastructure secrets only via environment/local config.

## Use Cases
- Local florist same-day delivery operations
- Catalog-heavy retail with editorial homepage control
- Event/occasion reminder-driven repeat purchases
- Direct customer campaign communication

## About Developer
Built and maintained by **Pouya Setoudeh Nejad**.
