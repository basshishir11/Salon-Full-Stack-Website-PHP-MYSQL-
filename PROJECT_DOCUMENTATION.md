# Technical Documentation: Sharma Salon & Spa

This document provides a deep dive into the technical architecture and logical flows of the Sharma Salon & Spa management system. Use this for future maintenance, debugging, and feature extensions.

---

## 🏗️ Core Architecture

The project follows a **Modular PHP Architecture**:
- **Logic**: Contained in `ajax/` endpoints for asynchronous operations.
- **Config**: Centralized database connection in `config/database.php`.
- **UI**: Pages are split between root `pages/` (Customer) and `pages/admin/` (Administrative).
- **Includes**: Header, footer, auth, and CSRF helpers are in `includes/`.

---

## 🔄 The Token Lifecycle (Core Flow)

Understanding this flow is critical for debugging queue issues:

1. **Generation** (`ajax/book_token_logic.php`):
   - Validates service selection.
   - Calculates estimated wait time.
   - Generates a unique token number (e.g., `B-01` for Boys, `G-01` for Girls) using `token_counters`.
   - Records customer details and increments `visit_count` ONLY upon completion.

2. **Real-time Tracking** (`pages/track_token.php` & `ajax/sse_manage_tokens.php`):
   - Customers track their position.
   - Admin uses **SSE (Server-Sent Events)** for live updates without page refreshes.

3. **Service Processing** (`ajax/update_token.php`):
   - **Waiting → In Service**: Marks the beginning of the service.
   - **In Service → Completed**: Performs critical post-service logic:
     - **Revenue**: Inserts a record into the `revenue` table.
     - **Visits**: Increments the customer's `visit_count`.
     - **Loyalty Milestone**: Checks if the new visit count matches `loyalty_milestones`.
     - **Reward Generation**: If a milestone is reached, a new `Pending` reward is created.

---

## 🎁 Loyalty & Personalized Rewards System

This is the most complex logical component:

- **Logic Location**: `ajax/update_token.php` (Generation) and `pages/admin/manage_rewards.php` (Management).
- **Personalization**: Admins can see "Premium Spender" vs "Regular Spender" labels.
- **The Grant Flow**:
  1. Customer hits milestone (e.g., 5 visits).
  2. System creates a `Pending` reward with a generic description.
  3. Customer sees "Calculating your special reward..." in their portal.
  4. Admin selects a specific `service_id` for that customer on the Rewards page.
  5. `ajax/assign_reward_service.php` updates the reward description and links the service.
  6. Admin clicks "Grant & Claim" which calls `ajax/claim_reward.php`.

---

## 📊 Revenue & Analytics System

Charts in the admin dashboard use **Chart.js**:
- **Data Source**: `ajax/get_revenue_stats.php`.
- **Computation**: Aggregates `final_amount` from the `revenue` table grouped by date/week/month.
- **Top Services**: Calculated by parsing the `services_summary` in the `tokens` table or directly from service associations (if implemented).

---

## 🗄️ Database Schema Breakdown

### Key Tables:
- **`tokens`**: Central registry of all appointments. Contains `services_summary` (text) and `status`.
- **`customers`**: Stores `phone` (primary key for customer portal) and `visit_count`.
- **`rewards`**: Tracks both `Loyalty` and `Referral` rewards. `assigned_service_id` stores the specific prize.
- **`revenue`**: Permanent financial record linked to `token_id`.

### Important Relationships:
- `tokens.customer_id` -> `customers.id`
- `revenue.token_id` -> `tokens.id`
- `rewards.assigned_service_id` -> `services.id`

---

## 📢 Promotions & Offers Module

Allows dynamic marketing with specific targeting:
- **Displays**: Homepage (Hero sliders) and Booking page (Contextual banners).
- **Targeting**: Can be linked to a specific `service_id`.
- **Logic**: Automatically handles visibility based on `start_date` and `end_date`.

## ✍️ Blog & SEO Module

Full content management system for SEO and news:
- **Features**: Slug auto-generation, Featured images, and Meta tags.
- **Pages**: `/blog.php` (Listing) and `/blog_post.php?slug=...` (Reading).
- **SEO**: Dynamic `sitemap.php` automatically finds and lists all published posts.

---

## 🛡️ Security Measures

1. **CSRF Protection**: Every POST request must include a `csrf_token`. Controlled via `includes/csrf.php`.
2. **Admin Auth**: Guarded by `requireAdmin()` check (Session-based).
3. **Database**: Phelon-style PDO Prepared Statements prevent SQL Injection.
4. **XSS**: Every dynamic output uses `htmlspecialchars()`.

---

## 🛠️ Maintenance & Common Fixes

### 1. Database Connection Error
If you see "could not find driver", check if `extension=pdo_mysql` is enabled in your `php.ini`.

### 2. Rewards Not Appearing
A reward ONLY triggers when a token is marked as **"Completed"**. If a customer has 5 visits but no reward, check if all 5 tokens are actually in `Completed` status.

### 3. Clear/Reset Token Counters
Daily counters reset via the `setup.php` or can be manually cleared in the `token_counters` table to start from 1 again.

### 4. Admin Login Reset
Use `reset_admin.php` if you forget your password. It will re-seed the default admin: `saloon@11gmail.com` / `salon@2026`.

---

## 🚀 Deployment Checklist
- [ ] Set `config/database.php` credentials.
- [ ] Run `setup.php` to initialize schema.
- [ ] Set up a Cron job for `cron/process_notifications.php` (Every 1 min).
- [ ] Ensure `assets/uploads/` directory is writable (CHMOD 755).

---
*Generated on 2026-01-03 for Sharma Salon & Spa Project Continuity.*
