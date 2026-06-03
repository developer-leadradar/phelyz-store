# 💎 PHELYZ DIAMOND STORE

A premium, fully-featured e-commerce jewelry store built with PHP and MySQL.

## ✨ FEATURES

### Customer Features
- 🛍️ Advanced product filtering (10+ filter types)
- 💎 8 jewelry categories with subcategories
- 🔍 Live search with autocomplete
- 🛒 Shopping cart with AJAX updates
- ❤️ Wishlist functionality
- 👤 Complete customer dashboard
- 📦 Order tracking and history
- 📍 Multiple shipping addresses
- ⭐ Product reviews and ratings
- 📱 Fully responsive design

### Admin Features
- 📊 Comprehensive dashboard with statistics
- 💎 Product management (CRUD operations)
- 📦 Order management with status updates
- 👥 Customer management
- 📁 Category management
- 📈 Sales reports and analytics
- 🖼️ Image upload functionality
- 🔍 Advanced product filtering in admin

### Technical Features
- 🔒 Secure authentication (bcrypt password hashing)
- 🛡️ SQL injection prevention (PDO prepared statements)
- 🚀 AJAX cart operations
- 💳 Payment ready (COD, Bank Transfer, PayPal, Stripe)
- 📧 Email notifications
- 🎨 Modern UI with Gold & Black theme
- 📱 Mobile-first responsive design

## 📋 REQUIREMENTS

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- mod_rewrite enabled (for Apache)

## 🚀 INSTALLATION

### Step 1: Download Files
Extract all files to your web server directory:
```
/var/www/html/phelyz-store/  (Linux)
C:/xampp/htdocs/phelyz-store/  (Windows)
```

### Step 2: Configure Database
Edit `config.php` and update your database credentials:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'phelyz_store');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### Step 3: Run Installation
1. Open your browser
2. Navigate to: `http://localhost/phelyz-store/install.php`
3. Wait for installation to complete
4. **IMPORTANT:** Delete `install.php` and `database.sql` after installation

### Step 4: Access Your Store

**Customer Area:**
- URL: `http://localhost/phelyz-store/`
- Demo Login: `customer@example.com` / `customer123`

**Admin Panel:**
- URL: `http://localhost/phelyz-store/admin/`
- Demo Login: `admin@phelyz.com` / `admin123`

## 📁 FILE STRUCTURE

```
phelyz-store/
├── config.php                  # Configuration file
├── install.php                 # One-click installer
├── database.sql                # Database schema
├── index.php                   # Homepage
├── shop.php                    # Product catalog
├── product.php                 # Product details
├── cart.php                    # Shopping cart
├── checkout.php                # Checkout process
├── login.php                   # Customer login
├── register.php                # Customer registration
├── logout.php                  # Logout handler
├── search.php                  # Search results
├── customer-dashboard.php      # Customer dashboard
├── customer-orders.php         # Order history
├── customer-profile.php        # Profile management
├── customer-wishlist.php       # Wishlist
├── customer-addresses.php      # Address management
├── order-details.php           # Order details view
│
├── includes/
│   ├── db.php                  # Database class
│   ├── functions.php           # Helper functions
│   ├── header.php              # Site header
│   ├── footer.php              # Site footer
│   └── cart-functions.php      # Cart logic
│
├── admin/
│   ├── index.php               # Admin dashboard
│   ├── login.php               # Admin login
│   ├── logout.php              # Admin logout
│   ├── products.php            # Product list
│   ├── add-product.php         # Add product
│   ├── edit-product.php        # Edit product
│   ├── delete-product.php      # Delete product
│   ├── orders.php              # Order management
│   ├── order-details.php       # Order details
│   ├── customers.php           # Customer list
│   ├── categories.php          # Category management
│   ├── reports.php             # Sales reports
│   ├── settings.php            # Store settings
│   └── includes/
│       ├── header.php          # Admin header
│       └── footer.php          # Admin footer
│
├── assets/
│   ├── css/
│   │   ├── style.css           # Main stylesheet
│   │   ├── admin.css           # Admin styles
│   │   └── responsive.css      # Responsive styles
│   └── js/
│       ├── main.js             # Main JavaScript
│       ├── cart.js             # Cart functions
│       └── admin.js            # Admin scripts
│
├── api/
│   ├── add-to-cart.php         # Add to cart API
│   ├── update-cart.php         # Update cart API
│   ├── add-to-wishlist.php     # Wishlist API
│   ├── filter-products.php     # Filter API
│   └── search-autocomplete.php # Search API
│
├── uploads/                    # Image uploads folder
│   ├── products/
│   ├── categories/
│   └── profiles/
│
├── .htaccess                   # URL rewriting
└── README.md                   # This file
```

## 🗄️ DATABASE STRUCTURE

**10 Tables:**
1. `users` - Customer and admin accounts
2. `categories` - Product categories
3. `products` - All products with details
4. `orders` - Customer orders
5. `order_items` - Items in each order
6. `cart` - Shopping carts
7. `cart_items` - Items in carts
8. `wishlist` - Saved products
9. `addresses` - Customer addresses
10. `reviews` - Product reviews

## 📦 SAMPLE DATA

The installation includes:
- **8 Categories:** Rings, Necklaces, Bracelets, Earrings, Pendants, Watches, Bridal Sets, Men's Jewelry
- **15 Sample Products:** Fully detailed with prices, images, and specifications
- **2 Demo Users:** 1 Admin, 1 Customer
- **1 Sample Order:** For testing

## 🎨 CUSTOMIZATION

### Change Colors
Edit `assets/css/style.css`:
```css
:root {
    --primary-black: #000000;
    --primary-gold: #FFD700;
    /* Change these values */
}
```

### Update Site Name
Edit `config.php`:
```php
define('SITE_NAME', 'Your Store Name');
define('SITE_EMAIL', 'your@email.com');
```

### Add More Products
1. Login to admin panel
2. Go to "Add Product"
3. Fill in all details
4. Upload product image
5. Save

## 🔐 SECURITY

- Passwords hashed with bcrypt
- PDO prepared statements (SQL injection prevention)
- XSS protection
- CSRF tokens
- Session security
- Input validation
- File upload validation

## 📧 EMAIL CONFIGURATION

Edit `includes/functions.php` to configure email settings:
```php
function sendEmail($to, $subject, $message) {
    // Configure your SMTP settings here
}
```

## 🐛 TROUBLESHOOTING

**Database connection error:**
- Check config.php credentials
- Ensure MySQL service is running
- Verify database exists

**Images not loading:**
- Check file permissions on uploads/ folder
- Ensure folder exists: uploads/products/
- Verify image paths in database

**404 errors:**
- Enable mod_rewrite in Apache
- Check .htaccess file exists
- Verify document root settings

## 📝 LICENSE

This project is for educational purposes.
Modify and use as needed for your projects.

## 👨‍💻 DEVELOPER

Created for educational purposes
PHP + MySQL + Modern Web Technologies

## 🌟 VERSION

Version: 1.0.0
Release Date: January 2026

## 📞 SUPPORT

For issues or questions:
1. Check this README file
2. Review code comments
3. Check error logs

---

**Enjoy your Phelyz Diamond Store! 💎**