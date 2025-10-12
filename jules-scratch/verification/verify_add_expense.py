import subprocess
from playwright.sync_api import sync_playwright, expect
import time

def run_php_script(script_path):
    """Executes a PHP script using the command line and handles errors."""
    try:
        print(f"Running PHP script: {script_path}...")
        result = subprocess.run(['php', script_path], check=True, capture_output=True, text=True, timeout=30)
        print(result.stdout)
    except (subprocess.CalledProcessError, subprocess.TimeoutExpired) as e:
        print(f"Error running {script_path}:\nSTDOUT: {e.stdout}\nSTDERR: {e.stderr}")
        raise

def main():
    # --- Step 1: Setup and Seed Database ---
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
            expect(page.get_by_role("heading", name="Dashboard")).to_be_visible()
            print("Login successful.")

            # --- Step 3: Verify Add Expense Page ---
            print("Verifying add expense page...")
            page.goto("add_expense.php")

            # Assert that the category dropdown is populated
            expect(page.get_by_label("Expense Category:")).to_contain_text("Office Supplies")
            print("Expense categories are present.")

            # --- Step 4: Take Screenshot ---
            screenshot_path = "jules-scratch/verification/add_expense_verification.png"
            page.screenshot(path=screenshot_path)
            print(f"Screenshot saved to {screenshot_path}")

        finally:
            context.close()
            browser.close()

if __name__ == "__main__":
    main()