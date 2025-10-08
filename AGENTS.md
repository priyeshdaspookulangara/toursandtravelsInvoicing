## Project Goal

To transform the existing simple invoice generator into a more comprehensive business management tool for a travel and services company based in Abu Dhabi.

### Core Requirements:

1.  **Service Management:**
    *   The existing "Packages" feature should be enhanced or clarified to manage a variety of services (e.g., Typing, Visa, Ticketing, Tour Packages, Car Rentals).
    *   The user interface should reflect this change, moving from "Packages" to a more general "Services" or "Products/Services".

2.  **Accounting System (Phase 1):**
    *   **General Ledger:** Implement a double-entry accounting system. A `general_ledger` table must be created to track all financial transactions (debits and credits).
    *   **Chart of Accounts:** Create a `chart_of_accounts` table to define different types of accounts (e.g., Assets, Liabilities, Equity, Revenue, Expenses). This will be pre-populated with standard accounts relevant to the business.
    *   **VAT Integration:**
        *   Ensure VAT is correctly calculated on all invoices at the standard UAE rate (5%).
        *   Transactions involving VAT must be recorded in the general ledger, crediting a "VAT Payable" account.
    *   **Transaction Automation:** When an invoice is created, paid, or modified, corresponding entries must be automatically posted to the general ledger. For example:
        *   *Creating an invoice:* Debit "Accounts Receivable", Credit "Sales Revenue", Credit "VAT Payable".
        *   *Receiving payment:* Debit "Cash/Bank", Credit "Accounts Receivable".

3.  **Expense Tracking:**
    *   Implement a feature to record business expenses.
    *   This will require a new `expenses` table and UI forms to add and manage expenses.
    *   Expense entries must also create corresponding transactions in the general ledger (e.g., Debit "Expense Account", Credit "Cash/Bank").

4.  **Reporting (Phase 1):**
    *   Develop a basic "Profit & Loss Statement" report based on the data in the general ledger.
    *   Develop a "VAT Report" showing total VAT collected and payable for a specific period.

### Technical Guidelines:

*   **Database:** All schema changes must be reflected in `schema.sql`. New setup data (like the chart of accounts) should be handled gracefully.
*   **Coding Style:** Maintain the existing coding style (plain PHP, procedural).
*   **Security:** Continue to use the existing sanitization functions for all user inputs.
*   **User Interface:** All new features must be integrated into the existing UI, with clear navigation links added to the header/sidebar.
*   **Configuration:** Make VAT rate configurable in `config.php`.

### Development Plan:

The project will be implemented in phases.

1.  **Foundation:**
    *   Update the database schema (`schema.sql`) with `chart_of_accounts`, `general_ledger`, and `expenses` tables.
    *   Update `config.php` with the VAT rate.
    *   Create a script to populate the `chart_of_accounts` with default accounts.
    *   Modify the UI to refer to "Services" instead of "Packages".

2.  **Invoice & Payment Integration:**
    *   Create helper functions to post transactions to the general ledger.
    *   Modify the `create_invoice.php` and `edit_invoice.php` scripts to post accounting entries when an invoice is saved.
    *   Modify the invoice payment section to post payment entries to the ledger.

3.  **Expense Management:**
    *   Create the UI (`add_expense.php`, `list_expenses.php`) for managing expenses.
    *   Integrate expense creation with the general ledger.

4.  **Reporting:**
    *   Create the UI and backend logic for the Profit & Loss and VAT reports.

5.  **Review and Refine:**
    *   Thoroughly test all new features.
    *   Ensure all financial data is consistent between invoices, expenses, and the ledger.
    *   Complete pre-commit checks.
    *   Submit the final changes.