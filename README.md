# MLB POS System

A web-based **Bakery Management & POS System** built with **PHP 8 (PDO)**, **MySQL/MariaDB**, **Bootstrap 5**, and **Chart.js**.

---

## 🚀 Quick Setup Instructions

1. Start **Apache** and **MySQL** in your XAMPP Control Panel.
2. Open your web browser and navigate to:
   `http://localhost/bakery%20manegement/` or `http://localhost/bakery%20manegement/install.php`
3. Click **"Run Database Installer"** (The database `bakery_db` and sample data will be automatically set up).

---

## 🔑 Default Login Credentials

All sample accounts use the password: `password123`

| Role | Username | Password | Access Rights |
| :--- | :--- | :--- | :--- |
| **Admin** | `admin` | `password123` | Full Access (Dashboard, POS, Products, Orders, Inventory, Recipes, Customers, Reports) |
| **Cashier** | `cashier` | `password123` | Counter POS Billing, Invoices, Customer Booking |
| **Baker** | `baker` | `password123` | Kitchen Production Logging, Recipe BOMs, Ingredient Restock |

---

## ✨ Features Included

- **Executive Dashboard**: Daily sales metrics, low stock raw material alerts, quick POS shortcuts, and 7-day sales trend graph.
- **POS Billing Counter**: Category filter pills, fast product search, live cart calculator, discount input, change calculator, and thermal receipt print modal.
- **Custom Cake & Pre-Orders**: Delivery date/time scheduling, custom cake design specifications & wording, advance payment tracking, and status workflow (*Pending -> In Production -> Ready -> Delivered*).
- **Raw Material Inventory**: Ingredient stock levels, reorder alert threshold badges, stock in/out forms, and movement audit logs.
- **Production & Recipe Management (BOM)**: Define raw ingredient quantities required per product; automatic stock deduction upon batch production logging.
- **Product & Category Catalog**: Full CRUD for bakery items, pricing, SKU codes, and category management.
- **Customer Directory**: Customer profiles, order history, contact details, and total spend tracking.
- **Sales Reports & Analytics**: Date-range financial reporting, gross vs net sales, payment method breakdown (Cash, Card, UPI), and top-selling bakery products.
