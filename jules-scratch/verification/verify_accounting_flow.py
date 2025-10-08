import subprocess
import re
from playwright.sync_api import sync_playwright, expect

def run_php_script(script_path):
    """Executes a PHP script using the command line and returns its output."""
    try:
        # Use a timeout to prevent hanging
        result = subprocess.run(['php', script_path], capture_output=True, text=True, check=True, timeout=30)
        return result.stdout
    except subprocess.CalledProcessError as e:
        print(f"Error running {script_path}:\nSTDOUT: {e.stdout}\nSTDERR: {e.stderr}")
        raise
    except subprocess.TimeoutExpired as e:
        print(f"Timeout running {script_path}:\nSTDOUT: {e.stdout}\nSTDERR: {e.stderr}")
        raise

def main():
    # --- Step 1: Setup Database ---
    print("Running setup scripts...")
    run_php_script('setup.php')
    run_php_script('setup_accounts.php')
    print("Setup complete.")

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        context = browser.new_context(base_url="http://localhost/")
        page = context.new_page()

        try:
            # --- Step 2: Login ---
            print("Logging in...")
            page.goto("login.php")
            page.get_by_label("Username:").fill("admin")
            page.get_by_label("Password:").fill("password123")
            page.get_by_role("button", name="Login").click()
            expect(page.get_by_role("heading", name="Dashboard - Invoices")).to_be_visible()
            print("Login successful.")

            # --- Step 3: Create Client via UI ---
            print("Creating client via UI...")
            page.goto("add_client.php")
            page.get_by_label("Client Name:").fill("Global Travel Co")
            page.get_by_label("Email:").fill("contact@globaltravel.com")
            page.get_by_label("Address:").fill("100 Main Street")
            page.get_by_label("Phone:").fill("555-0100")
            page.get_by_role("button", name="Add Client").click()
            expect(page.get_by_role("heading", name="Manage Clients")).to_be_visible()
            expect(page.get_by_text("Client 'Global Travel Co' added successfully!")).to_be_visible()
            print("Client created successfully.")

            # --- Step 4: Create Service via UI ---
            print("Creating service via UI...")
            page.goto("add_service.php")
            page.get_by_label("Service Name:").fill("Visa Processing")
            page.get_by_label("Price").fill("550.00")
            page.get_by_label("Description:").fill("Standard visa application assistance.")
            page.get_by_role("button", name="Add Service").click()
            expect(page.get_by_role("heading", name="Manage Services")).to_be_visible()
            expect(page.get_by_text("Service 'Visa Processing' added successfully!")).to_be_visible()
            print("Service created successfully.")

            # --- Step 5: Create Invoice ---
            print("Creating invoice...")
            page.goto("create_invoice.php")
            page.get_by_label("Client:").select_option(label="Global Travel Co")
            page.locator(".service-select").first.select_option(label="Visa Processing")
            page.locator(".item-quantity").first.fill("2")
            page.get_by_label("Tax (%)").fill("5.00")

            # Clear unused rows to prevent validation errors
            for i in range(1, 5):
                page.locator(f"#item_quantity_{i}").fill("")

            expect(page.locator("#sub_total_display")).to_have_value("1100.00")
            expect(page.locator("#tax_amount_display")).to_have_value("55.00")
            expect(page.locator("#grand_total_display")).to_have_value("1155.00")

            page.get_by_role("button", name="Create Invoice").click()

            # Assert that the success message is visible on the view invoice page
            expect(page.get_by_text(re.compile("Invoice INV-.* created successfully!"))).to_be_visible()
            print("Invoice created successfully.")

            # --- Step 6: Record Payment ---
            print("Recording payment...")
            expect(page.get_by_role("heading", name="Record a Payment")).to_be_visible()
            page.get_by_label("Payment Amount:").fill("655.00")
            page.get_by_role("button", name="Record Payment").click()
            expect(page.get_by_text("Payment of 655.00 recorded successfully!")).to_be_visible()
            # Use .last to select the second of the two matching td.text-right elements
            expect(page.locator("tr.total:has-text('Balance Due') td.text-right").last).to_contain_text("500.00")
            print("Payment recorded.")

            # --- Step 7: Record Expense ---
            print("Recording expense...")
            page.goto("add_expense.php")
            page.get_by_label("Expense Category:").select_option(label="Office Supplies")
            page.get_by_label("Amount:").fill("125.50")
            page.get_by_label("Description:").fill("Stationery and printer paper")
            page.get_by_role("button", name="Record Expense").click()
            expect(page.get_by_role("heading", name="Manage Expenses")).to_be_visible()
            expect(page.get_by_text("Expense recorded successfully!")).to_be_visible()
            print("Expense recorded.")

            # --- Step 8: Generate P&L Report and take screenshot ---
            print("Generating P&L Report...")
            page.goto("report_profit_loss.php")
            # Set a wide date range to ensure test data is included
            page.get_by_label("Start Date:").fill("2025-01-01")
            page.get_by_label("End Date:").fill("2025-12-31")
            page.get_by_role("button", name="Generate Report").click()
            # Target the h2 specifically to avoid strict mode violation
            expect(page.locator("h2:has-text('Profit & Loss Statement')")).to_be_visible()

            expect(page.locator("tr.total-row:has-text('Total Revenue') td.text-right")).to_have_text("1,100.00")
            expect(page.locator("tr.total-row:has-text('Total Expenses') td.text-right")).to_have_text("125.50")
            expect(page.locator("tr.total-row:has-text('Net Profit') td.text-right strong")).to_have_text("974.50")

            screenshot_path = "jules-scratch/verification/verification.png"
            page.screenshot(path=screenshot_path)
            print(f"Screenshot saved to {screenshot_path}")

        finally:
            context.close()
            browser.close()

if __name__ == "__main__":
    main()