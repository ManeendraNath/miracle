# 🗄️ Miracle Web Technologies - Database Schema & Architecture Dictionary
**Last Structural Audit Sync:** October 01, 2026 | **Database Engine Matrix:** MariaDB / InnoDB & MyISAM Hybrid

---

## 🏛️ 1. Framework Identity, Security & System Kernels
These tables form the operational core of the Yii2 Advanced Template framework. They handle application orchestration, multi-tier user profiles, security constraints, and path rule configurations.

### 👤 `admin`
*   **Engine Type:** `InnoDB`
*   **Operational Purpose:** Stores the internal corporate administration accounts (Superadmin, Administrator, Manager).
*   **Crucial Columns:**
    *   `role`: Handles privilege tiers string parameters natively (`Superadmin`, `Admin`, `Manager`).
    *   `password_hash`: Cryptographically secure 60-character Blowfish/Bcrypt string matching framework security expectations.
*   **Engineering Rules:** Restrict write capabilities to this table strictly through backend controllers using custom role verification callbacks.

### 👤 `user`
*   **Engine Type:** `InnoDB`
*   **Operational Purpose:** Holds public customer profiles. It has been successfully cleaned of legacy bot-spam records and contains your 22 genuine historical clients.
*   **Relationships:** Has a one-to-many relationship with `user_visit_log`, `domains`, and `hosting`.
*   **Engineering Rules:** All passwords migrated via SQL are stored as SHA-256 signatures; users must utilize the frontend "Forgot Password" workflow on their initial session initialization to automatically upgrade their keys to production-grade Bcrypt arrays.

### 🛡️ RBAC Authorization Matrix (`auth_assignment`, `auth_item`, `auth_item_child`, `auth_item_group`, `auth_rule`)
*   **Engine Type:** `InnoDB`
*   **Operational Purpose:** Implements Yii2's native Role-Based Access Control (RBAC). It maps exact controller actions (e.g., `/user-management/*`, `/cache/flush-all`) directly to defined permission hierarchies.
*   **Relational Links:** `auth_assignment.user_id` acts as a strict foreign key pointing directly to `user.id`.

### 🧭 `migration`
*   **Engine Type:** `InnoDB`
*   **Operational Purpose:** The version tracking log used by Yii's core command-line utility. It ensures schema definitions remain identical when pushing updates between local Docker containers and production cPanel reseller nodes.

### ⚙️ `site_setting`
*   **Engine Type:** `InnoDB`
*   **Operational Purpose:** The global data dictionary configuration row (Locked to row `id = 1`). It feeds your 17 brand layout vectors (split phone configurations, corporate helpdesk emails, physical addresses, and social network links) cleanly into the frontend layout templates.

---

## 🌐 2. Web Hosting, Domain Management & Inventory
These business ledger tables store infrastructure asset items, account parameters, and next renewal milestones.

### 🔗 `domains`
*   **Engine Type:** `InnoDB`
*   **Operational Purpose:** Tracks client domain registrations, renewal costs, and registrars (e.g., GoDaddy).
*   **Crucial Columns:**
    *   `NextRenewalDate`: Evaluates expirations. Used by the alerts engine to calculate upcoming client billing cycles.
    *   `UserID`: Foreign key mapping the asset link back onto the target owner in the `user` table.

### 🐋 `hosting`
*   **Engine Type:** `InnoDB`
*   **Operational Purpose:** Tracks cPanel hosting allocations, server IP nodes, and secure user credentials mapped to active reseller packages.
*   **Constraints:** Enforces an absolute `UNIQUE` key index constraint loop on the `Domain` field to prevent duplicate allocation collisions.

### 🛠️ `maintenance`
*   **Engine Type:** `InnoDB`
*   **Operational Purpose:** Records active corporate retainer parameters, website upkeep schedules, and explicit pricing agreements.

### ⏰ `renewals`
*   **Engine Type:** `MyISAM` *(Legacy Optimization Log)*
*   **Operational Purpose:** The central engine processing renewal entries for all three business lines:
    *   `ProductType`: Enum style indicator mapping definitions (`1` = Domain, `2` = Hosting, `3` = Maintenance).

---

## 💳 3. Billing Architecture & Payment Gateways
These dynamic relational tables track the financial transaction pipeline, tax matrices, and line-item processing.

### 🧾 `invoice`
*   **Engine Type:** `InnoDB`
*   **Operational Purpose:** The parent record repository for the automated accounting system.
*   **Crucial Tax Matrix Fields:**
    *   `cgst_percent`, `sgst_percent`, `igst_percent`: Columns storing custom Indian tax matrix percentage criteria.
    *   `discount_amount`: Numeric cell tracking promo subtractions prior to grand totals computation.
*   **Relational Link:** Has a one-to-many relationship with `invoice_item`.

### 📋 `invoice_item`
*   **Engine Type:** `InnoDB`
*   **Operational Purpose:** Child record log storing lines and items for parent invoices.
*   **Constraints:** Controlled via `fk-invoice_item-invoice_id` with `ON DELETE CASCADE`. If an invoice record is deleted from the CRM, its individual item rows drop automatically to keep data clean.

### 💳 Transaction Arrays (`transaction`, `transaction_details`, `payment`)
*   **Engine Type:** `InnoDB` / `MyISAM` Mix
*   **Operational Purpose:** Collects, verifies, and logs incoming digital financial gateway handshakes (such as active `razorpay` payment loops). It stores unique transaction IDs (`TxnID`) for auditing and verification.

---

## 🎟️ 4. Promotional Modules, Marketing & Communications
These tables handle secondary operational features. They are currently active but can be safely left unmapped until specialized frontend dashboards are deployed.

### 🏷️ Coupons (`coupons`, `coupon_email_track`, `coupon_redeem_track`)
*   **Engine Type:** `InnoDB`
*   **Operational Purpose:** Stores promotional campaign metrics and tracking codes (e.g., code `MIT50` tracking a 50% discount).

### 🎨 `templates`
*   **Engine Type:** `InnoDB`
*   **Operational Purpose:** Houses pricing tiers, screenshots, and source code link descriptors for pre-made website themes that can be sold to customers.

### 🤝 `affiliates`
*   **Engine Type:** `MyISAM`
*   **Operational Purpose:** Tracks referral commissions and balance metrics for third-party partners.

### 📧 `subscribers` & `messages`
*   **Engine Type:** `MyISAM` / `InnoDB`
*   **Operational Purpose:** Handles newsletter mailing indexes, internal corporate support tickets, and general customer feedback text.

