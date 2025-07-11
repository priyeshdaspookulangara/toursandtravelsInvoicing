-- Database: invoice_system

-- Table structure for table `users`
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table structure for table `clients`
CREATE TABLE `clients` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `address` TEXT,
  `email` VARCHAR(100),
  `phone` VARCHAR(20),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table structure for table `packages` (for services/products)
CREATE TABLE `packages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `price` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table structure for table `invoices`
CREATE TABLE `invoices` (
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

-- Table structure for table `invoice_items`
CREATE TABLE `invoice_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `invoice_id` INT NOT NULL,
  `package_id` INT DEFAULT NULL, -- Can be NULL if it's a custom item not from predefined packages
  `item_description` VARCHAR(255) NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `unit_price` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `total_price` DECIMAL(10, 2) GENERATED ALWAYS AS (quantity * unit_price) STORED,
  FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`package_id`) REFERENCES `packages`(`id`) ON DELETE SET NULL -- Allows package deletion without deleting invoice items
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
CREATE INDEX idx_invoice_items_package_id ON invoice_items(package_id);
