-- Database: invoice_system

-- Table structure for table `users`
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table structure for table `clients`
CREATE TABLE IF NOT EXISTS `clients` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `address` TEXT,
  `email` VARCHAR(100),
  `phone` VARCHAR(20),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table structure for table `services` (formerly packages)
CREATE TABLE IF NOT EXISTS `services` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `price` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table structure for table `invoices`
CREATE TABLE IF NOT EXISTS `invoices` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `invoice_number` VARCHAR(50) NOT NULL UNIQUE,
  `client_id` INT NOT NULL,
  `invoice_date` DATE NOT NULL,
  `due_date` DATE NOT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'Draft', -- e.g., Draft, Sent, Paid, Overdue
  `sub_total` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `tax_percentage` DECIMAL(5, 2) DEFAULT 0.00, -- Store percentage, calculate amount on the fly or store
  `tax_amount` DECIMAL(10, 2) DEFAULT 0.00,
  `discount_amount` DECIMAL(10, 2) DEFAULT 0.00, -- Can be a fixed amount or calculated from a percentage
  `grand_total` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `amount_paid` DECIMAL(10, 2) DEFAULT 0.00,
  `balance_due` DECIMAL(10, 2) GENERATED ALWAYS AS (grand_total - amount_paid) STORED,
  `payment_terms` TEXT,
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE RESTRICT -- Or ON DELETE CASCADE if appropriate
);

-- Table structure for table `ticket_bookings`
CREATE TABLE IF NOT EXISTS `ticket_bookings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `invoice_no` VARCHAR(50) NOT NULL UNIQUE,
  `issue_date` DATE NOT NULL,
  `pnr` VARCHAR(50) NOT NULL,
  `ticket_no` VARCHAR(50) NOT NULL,
  `client_id` INT,
  `client_name` VARCHAR(100) NOT NULL,
  `client_email` VARCHAR(100),
  `client_address` TEXT,
  `currency` VARCHAR(10) NOT NULL,
  `base_fare` DECIMAL(10, 2) NOT NULL,
  `taxes` DECIMAL(10, 2) NOT NULL,
  `agency_fee` DECIMAL(10, 2) NOT NULL,
  `total_amount` DECIMAL(10, 2) NOT NULL,
  `payment_status` VARCHAR(20) NOT NULL,
  `payment_method` VARCHAR(50),
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE SET NULL
);

-- Table structure for table `ticket_passengers`
CREATE TABLE IF NOT EXISTS `ticket_passengers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `booking_id` INT NOT NULL,
  `passenger_name` VARCHAR(100) NOT NULL,
  `airline` VARCHAR(100),
  `flight_no` VARCHAR(20),
  `route` VARCHAR(100),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`booking_id`) REFERENCES `ticket_bookings`(`id`) ON DELETE CASCADE
);

-- Table structure for table `invoice_items`
CREATE TABLE IF NOT EXISTS `invoice_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `invoice_id` INT NOT NULL,
  `service_id` INT DEFAULT NULL, -- Can be NULL if it's a custom item not from predefined services
  `item_description` VARCHAR(255) NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `unit_price` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `unit_type` VARCHAR(20) DEFAULT NULL,
  `total_price` DECIMAL(10, 2) GENERATED ALWAYS AS (quantity * unit_price) STORED,
  FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`service_id`) REFERENCES `services`(`id`) ON DELETE SET NULL -- Allows service deletion without deleting invoice items
);

-- Chart of Accounts: The master list of all accounts.
CREATE TABLE IF NOT EXISTS `chart_of_accounts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `account_name` VARCHAR(255) NOT NULL UNIQUE,
    `account_type` ENUM('Asset', 'Liability', 'Equity', 'Revenue', 'Expense') NOT NULL,
    `description` TEXT,
    `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- General Ledger: Records all financial transactions (journal entries).
CREATE TABLE IF NOT EXISTS `general_ledger` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `entry_date` DATE NOT NULL,
    `account_id` INT NOT NULL,
    `debit` DECIMAL(10, 2) DEFAULT 0.00,
    `credit` DECIMAL(10, 2) DEFAULT 0.00,
    `description` VARCHAR(255) NOT NULL,
    `reference_type` VARCHAR(50), -- e.g., 'invoice', 'expense', 'payment'
    `reference_id` INT, -- e.g., invoice_id, expense_id
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`account_id`) REFERENCES `chart_of_accounts`(`id`),
    CONSTRAINT chk_debit_credit CHECK (debit > 0.00 OR credit > 0.00)
);

-- Expenses: To track all business expenses.
CREATE TABLE IF NOT EXISTS `expenses` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `expense_date` DATE NOT NULL,
    `vendor` VARCHAR(255),
    `category_id` INT NOT NULL, -- Foreign key to an expense account in chart_of_accounts
    `amount` DECIMAL(10, 2) NOT NULL,
    `description` TEXT,
    `receipt_url` VARCHAR(255), -- Optional path to a scanned receipt
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `chart_of_accounts`(`id`)
);


-- Default Company Details (Not in DB, will be in config.php, but noted here for completeness)
-- Company Name: "Your Tours & Travels Company"
-- Address: "123 Travel Lane, Adventure City, Country"
-- Contact Info: "Phone: +1234567890, Email: info@yourtravels.com, Website: www.yourtravels.com"
-- Logo Path: "assets/images/logo.png" (Example path)

-- Indexes for performance
CREATE INDEX idx_invoices_client_id ON invoices(client_id);
CREATE INDEX idx_invoices_status ON invoices(status);
CREATE INDEX idx_invoice_items_invoice_id ON invoice_items(invoice_id);
CREATE INDEX idx_invoice_items_service_id ON invoice_items(service_id);
CREATE INDEX idx_gl_account_id ON general_ledger(account_id);
CREATE INDEX idx_gl_reference ON general_ledger(reference_type, reference_id);
CREATE INDEX idx_expenses_category_id ON expenses(category_id);
CREATE INDEX idx_ticket_bookings_client_id ON ticket_bookings(client_id);
CREATE INDEX idx_ticket_passengers_booking_id ON ticket_passengers(booking_id);