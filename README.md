# X-Panda Florist eCommerce Platform

A production-style florist eCommerce platform built for a real flower shop workflow, including storefront shopping, customer accounts, reminders, email automation, email campaigns, media management, and a mobile-friendly admin dashboard.

**Live Demo:** https://nashvillesamedayflowerdelivery.com  
**Project Type:** Custom PHP MVC eCommerce application  
**Primary Business Use Case:** Local florist / gift shop / same-day delivery business  
**Developer:** Pouya Setoudeh Nejad

---

## Overview

X-Panda Florist eCommerce Platform is a custom-built online store designed around the real operational needs of a florist business.

Unlike a simple product catalog, this project includes both customer-facing shopping flows and store-owner tools for managing products, orders, reminders, email campaigns, homepage content, media, navigation, and delivery planning.

The platform is especially suitable for businesses that sell products tied to occasions, delivery dates, customer reminders, add-ons, and repeat seasonal purchases.

---

## Key Highlights

- Custom PHP MVC architecture
- Responsive storefront
- Mobile-friendly admin panel
- Product catalog with categories, occasions, variants, and add-ons
- Customer account system
- Account-required checkout rule
- Reminder system with purchase and no-purchase modes
- Automated transactional emails
- Admin-managed email settings
- Bulk email campaign tool
- Order management console
- Delivery-date-focused order planning
- Homepage hero image management
- Media library
- Admin-managed navigation and mega menu
- iPhone 14 Pro Max admin usability improvements
- Security-conscious GitHub preparation with sensitive files excluded

---

## Main Features

### Storefront

- Modern responsive homepage
- Dynamic occasion-based product sections
- Product listing pages
- Product detail pages
- Product variants
- Product add-ons
- Cart flow
- Checkout flow
- Same-day ordering support structure
- Mobile hamburger navigation
- Desktop mega menu navigation
- Search/discovery-oriented browsing

### Product System

Products can be connected to multiple business dimensions:

- Categories
- Occasions
- Add-ons
- Related products
- Variants
- Media assets

The admin product editor includes multi-select usability tools:

- Search options
- Select All
- Select Visible
- Clear All
- Selected count
- Mobile-friendly option management

---

## Customer Features

Customers can:

- Create an account
- Sign in
- Manage account information
- Add products to cart
- Continue checkout after login/register
- View order-related information
- Create reminders
- Manage reminder-related flows

The checkout flow allows browsing and cart usage as a guest, but requires an account before final order placement. This keeps customer records, delivery information, reminders, and order history connected.

---

## Reminder System

The reminder system supports two major workflows:

### Reminder With Purchase

A customer creates a reminder and completes a related purchase.

The system can then send reminder-related emails confirming that the order is already linked or scheduled.

### Reminder Without Purchase

A customer creates a reminder without completing a purchase immediately.

The system can later send an action-needed email reminding the customer that no purchase has been completed yet and encouraging them to return and complete the order.

Reminder-related email flows include:

- Reminder confirmation
- Upcoming reminder
- Action-needed reminder
- Purchased reminder messaging
- No-purchase reminder messaging

---

## Email System

The project includes a branded email notification system with HTML email templates and plain-text fallbacks.

Supported email flows include:

- Account welcome email
- Password reset email
- Order confirmation email
- Store new order notification
- Reminder confirmation email
- Reminder upcoming email
- Reminder action-needed email
- Email campaign messages

Email templates use admin-managed store identity values where appropriate, such as:

- Store name
- Sender display name
- Reply-to email
- Store contact email
- Phone number
- Store address
- Website URL
- Footer text
- Support message
- Social links

---

## Email Campaigns

The admin panel includes an email campaign tool for sending branded emails to selected customers.

Campaign features include:

- Customer search
- Audience filtering
- Recipient checkboxes
- Select All
- Select Visible
- Clear All
- Selected recipient count
- Selected Recipients panel
- Remove from this campaign
- Re-add recipient support
- Subject input
- Message body input
- CTA label and URL
- QA/test send support
- Campaign send logging
- Suppression/marketing opt-in awareness where supported

Recipient removal from a campaign does not delete the customer record. It only removes that recipient from the current campaign selection.

---

## Admin Panel Features

The admin dashboard is designed for store-owner operations.

Main admin areas include:

- Dashboard
- Orders
- Products
- Product create/edit
- Categories
- Occasions
- Add-ons
- Promo codes
- Customers
- Delivery zones
- Navigation / Mega Menu
- Homepage editor
- Hero image manager
- Media library
- Public pages
- Theme settings
- Banners
- Footer / SEO
- Site settings
- Runtime settings
- Email settings
- Email campaigns

The admin panel has been improved for mobile use, especially for iPhone 14 Pro Max-sized screens.

---

## Order Management

The Orders area is designed as an operational console rather than a basic order list.

It supports:

- Order summary metrics
- Order list view
- Delivery date visibility
- Order date visibility
- Delivery type indicators
- Status indicators
- Search and filters
- Calendar-style delivery planning
- Quick preview
- Full order view

The system separates order creation date from delivery date so the store owner can plan daily and upcoming delivery workload.

---

## Homepage and Media Management

The platform includes admin-managed homepage content tools, including:

- Hero image manager
- Upload and make active flow
- Current hero preview
- Safe handling of old/non-active assets
- Media library
- Homepage content controls

The homepage hero image can be managed from the admin panel without manually editing code.

---

## Navigation and Mega Menu

The storefront navigation supports:

- Desktop mega menu behavior
- Mobile hamburger behavior
- Admin-managed navigation items
- Nested menu structures
- Responsive menu behavior

The navigation system is designed to support future category and occasion expansion.

---

## Responsive Design

The storefront and admin panel are designed to work across major screen sizes.

Responsive priorities include:

- Mobile-friendly storefront
- Desktop mega menu
- Mobile hamburger navigation
- Responsive product cards
- Responsive product detail pages
- Responsive cart and checkout
- Mobile-friendly admin shell
- Touch-friendly admin controls
- Table-to-card behavior in admin pages
- iPhone 14 Pro Max admin usability improvements

The responsive approach prioritizes preserving layout where possible, scaling intelligently, and collapsing only when necessary.

---

## Tech Stack

Core stack:

- PHP
- Custom MVC structure
- MySQL-compatible database
- HTML
- CSS
- JavaScript
- Tailwind/PostCSS tooling where applicable

Project structure includes:

- `app/` — application services and controllers
- `routes/` — route definitions
- `views/` — page templates and layouts
- `config/` — environment-driven configuration
- `database/migrations/` — database migration files
- `public/` — public assets and entry points
- `storage/` — runtime storage placeholders
- `tests/` — test and QA helpers
- `deployment/` — deployment examples and safe configuration references

---

## Project Structure

```text
.
├── app/
│   ├── Controllers/
│   └── Services/
├── config/
├── database/
│   └── migrations/
├── public/
│   └── assets/
├── routes/
├── storage/
├── tests/
├── views/
│   ├── components/
│   ├── layouts/
│   └── pages/
├── .env.example
├── .gitignore
├── index.php
├── README.md
└── package.json
