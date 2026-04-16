-- Users
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE,
    password TEXT,
    full_name TEXT,
    email TEXT,
    role TEXT DEFAULT 'cashier',
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Categories
CREATE TABLE categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT,
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Products
CREATE TABLE products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    barcode TEXT UNIQUE,
    name TEXT,
    category_id INTEGER,
    size TEXT,
    color TEXT,
    brand TEXT,
    cost_price DECIMAL(10,2),
    selling_price DECIMAL(10,2),
    stock INTEGER DEFAULT 0,
    low_stock_threshold INTEGER DEFAULT 5,
    image TEXT,
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- Customers
CREATE TABLE customers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT,
    phone TEXT,
    email TEXT,
    address TEXT,
    total_spent DECIMAL(10,2) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Sales
CREATE TABLE sales (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    bill_number TEXT UNIQUE,
    customer_id INTEGER,
    subtotal DECIMAL(10,2),
    discount_type TEXT,
    discount_value DECIMAL(10,2) DEFAULT 0,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    tax_amount DECIMAL(10,2) DEFAULT 0,
    grand_total DECIMAL(10,2),
    paid_amount DECIMAL(10,2),
    change_amount DECIMAL(10,2) DEFAULT 0,
    payment_method TEXT,
    created_by INTEGER,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Sale Items
CREATE TABLE sale_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    sale_id INTEGER,
    product_id INTEGER,
    quantity INTEGER,
    unit_price DECIMAL(10,2),
    total_price DECIMAL(10,2),
    FOREIGN KEY (sale_id) REFERENCES sales(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Returns
CREATE TABLE returns (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    return_number TEXT UNIQUE,
    sale_id INTEGER,
    customer_id INTEGER,
    total_return_amount DECIMAL(10,2),
    refund_amount DECIMAL(10,2),
    refund_method TEXT,
    reason TEXT,
    created_by INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sale_id) REFERENCES sales(id),
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Return Items
CREATE TABLE return_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    return_id INTEGER,
    sale_item_id INTEGER,
    product_id INTEGER,
    quantity INTEGER,
    refund_price DECIMAL(10,2),
    reason TEXT,
    FOREIGN KEY (return_id) REFERENCES returns(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Expenses
CREATE TABLE expenses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    category TEXT,
    description TEXT,
    amount DECIMAL(10,2),
    expense_date DATE,
    created_by INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Shop Settings
CREATE TABLE shop_settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    shop_name TEXT,
    shop_address TEXT,
    shop_phone TEXT,
    shop_email TEXT,
    gst_number TEXT,
    tax_rate DECIMAL(5,2) DEFAULT 0,
    currency_symbol TEXT DEFAULT '₹',
    receipt_footer TEXT,
    logo_path TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Insert default data
INSERT INTO users (username, password, full_name, role) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador', 'admin');

INSERT INTO shop_settings (shop_name, shop_address, shop_phone, receipt_footer, currency_symbol) 
VALUES ('RootSale', 'Calle Falsa 123, Madrid', '+34 912 345 678', '¡Gracias por su compra!', '₹');

INSERT INTO categories (name) VALUES ('Camisas'), ('Pantalones'), ('Vestidos'), ('Chaquetas'), ('Accesorios');

-- User Profile Settings
CREATE TABLE IF NOT EXISTS user_profiles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER UNIQUE,
    email TEXT,
    phone TEXT,
    profile_image TEXT,
    last_password_change DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Invoice Settings
CREATE TABLE IF NOT EXISTS invoice_settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    receipt_type TEXT DEFAULT 'thermal', -- 'thermal' or 'a4'
    a4_logo_path TEXT,
    a4_footer_text TEXT,
    a4_terms_conditions TEXT,
    tax_type TEXT DEFAULT 'iva', -- 'iva'
    tax_name TEXT DEFAULT 'IVA', -- 'IVA', 'GST', 'VAT', 'TAX'
    iva_rate DECIMAL(5,2) DEFAULT 21.00,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Return Receipts
CREATE TABLE IF NOT EXISTS return_receipts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    return_id INTEGER UNIQUE,
    shop_copy_printed INTEGER DEFAULT 0,
    customer_copy_printed INTEGER DEFAULT 0,
    printed_by INTEGER,
    printed_at DATETIME,
    FOREIGN KEY (return_id) REFERENCES returns(id)
);

-- Insert default invoice settings
INSERT INTO invoice_settings (receipt_type, a4_footer_text, a4_terms_conditions) 
VALUES ('thermal', 'Thank you for shopping!', 'Goods once sold cannot be returned unless defective.');
