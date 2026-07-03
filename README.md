# Duka Bora – Online Market Inventory Management System

A complete, professional, and optimized academic-grade **Inventory Management System (IMS)** built for **Duka Bora**, a fictional retail storefront. The system is designed using PHP 8+ and MySQL (via mysqli), styled with highly aesthetic, responsive, vanilla CSS3, and driven sequentially with interactive vanilla JavaScript.

---

## 🛠 Features Implemented

*   **Dashboards & KPI Cards**: Active counts of total products, categories, suppliers, sales today, and total all-time revenue showing smooth CSS number counter animation.
*   **Complete CRUD Management**:
    *   **Categories**: Insert, edit, and delete category entities with error checks when products are nested inside them.
    *   **Suppliers**: Complete vendor list with contact attributes, registration checks, and location tracking.
    *   **Products**: Full stock metadata details including category mappings, unique pricing values, stock quantities, and relative status badges.
*   **Dynamic Sales Terminal**: Real-time total calculation based on prices and quantities utilizing JavaScript event triggers, alongside transaction-safe server verification which blocks overselling and decrements database quantity values atomically.
*   **PHP Cookies & Recently Viewed Listing**: Set via asynchronous AJAX posting whenever a product row is reviewed, enabling immediate client-side and server-side tracking banners.
*   **Restocking & Analytic Reports**: Dashboard summarizing items falling below critical limits (quantities < 5), printing functionality, and lists detailing top 3 best-selling products.

---

## 📂 Project Structure

```text
Dukabora/
├── config/
│   └── database.php       # Singleton pattern DB connection & error wrapper
├── css/
│   └── style.css          # Core visual theme layout, animations & buttons
├── js/
│   └── script.js          # Form controls, calculations, and local cookies
├── includes/
│   ├── header.php         # Document definitions & page title setups
│   ├── footer.php         # End of layout page tags & footer details
│   ├── nav.php            # Active-state responsive navigation bar
│   └── functions.php      # Reusable helpers & centralized queries
├── add_product.php        # Adding products form + server validation
├── categories.php         # Categories lists & in-place update handlers
├── database.sql           # Schema structures, foreign keys & test records
├── delete_product.php     # Deletes a product with verification checks
├── edit_product.php       # Modifies active product variables
├── index.php              # Global dashboard home view
├── products.php           # Catalog table with filters and tags
├── record_sale.php        # Sales module & inventory ledger controls
├── report.php             # Analytical KPIs & low stock warning boards
├── sales_history.php      # Detailed sales ledger with join queries
├── set_cookie.php         # Backend cookie updater for recently viewed item
└── README.md              # Installation and run documentation
```

---

## ⚙ Requirements

Ensure you have a local environment supporting:
*   **PHP** 8.0 or newer
*   **MySQL** / **MariaDB** 5.7+
*   **Apache** server (typically via XAMPP)

---

## 🚀 Installation & Running on XAMPP

1.  **Clone or Copy Project**: Copy the folder `Dukabora` into your local XAMPP web server directory:
    ```bash
    C:\xampp\htdocs\Dukabora
    ```
2.  **Start Services**: Launch the XAMPP Control Panel and start the **Apache** and **MySQL** modules.
3.  **Create / Import Database**:
    *   Open your browser and navigate to: `http://localhost/phpmyadmin`
    *   Click on **New** in the sidebar to create a new database.
    *   Set the database name exactly to `dukabora_db` (using collation `utf8mb4_unicode_ci` or standard utf8mb4) and click **Create**.
    *   Select the newly created database, go to the **Import** tab at the top.
    *   Click **Choose File**, select the `database.sql` file from inside the `Dukabora` folder directory, and click **Import** (or **Go**).
4.  **Run Application**:
    *   In your web browser, open the URL: `http://localhost/Dukabora`
    *   The home page dashboard is loaded with seeded category, supplier, product, and sales data.

---

## 📝 Coding Standards & Security Highlights

*   **SQL Injection Protection**: Strictly utilizes MySQLi prepared statements (`prepare` & `bind_param`) for all dynamically bound database operations.
*   **Cross-Site Scripting (XSS) Prevention**: Utilizes custom output escaping helpers (`e()` and `sanitize()`) to output HTML safe text.
*   **Graceful Exception Catching**: Database connectivity errors and foreign key violations (e.g., trying to deletes categories containing active products) are captured and output as clean, non-leaking alert messages to the user.
*   **Responsive layouts**: CSS Grid and Flexbox layouts dynamically adjust from large screens down to mobile screens.
