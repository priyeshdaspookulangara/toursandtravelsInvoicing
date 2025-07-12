-- SQL Schema for the Web-Based Invoicing Application

-- Table to store main invoice details
CREATE TABLE invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(50) NOT NULL UNIQUE,
    client_name VARCHAR(255) NOT NULL,
    invoice_date DATE NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table to store individual items on each invoice
CREATE TABLE invoice_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    item_description TEXT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL,
    line_total DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
);

-- Optional: Add indexes for frequently queried columns
-- CREATE INDEX idx_invoice_date ON invoices(invoice_date);
-- CREATE INDEX idx_client_name ON invoices(client_name);
-- CREATE INDEX idx_invoice_id ON invoice_items(invoice_id);

-- Note:
-- ON DELETE CASCADE for invoice_items.invoice_id means that if an invoice is deleted,
-- all its associated items will also be automatically deleted.
-- Adjust this behavior if a different referential integrity action is desired (e.g., SET NULL or RESTRICT).
-- The `created_at` column in `invoices` is for tracking when the invoice record was made.
-- `invoice_number` is marked UNIQUE to prevent duplicate invoice numbers.
-- Data types DECIMAL(10, 2) are suitable for currency values, allowing for up to 10 digits in total, with 2 digits after the decimal point.
-- `item_description` is TEXT to allow for longer descriptions if needed.
-- `quantity` is INT. Adjust if fractional quantities are possible.
