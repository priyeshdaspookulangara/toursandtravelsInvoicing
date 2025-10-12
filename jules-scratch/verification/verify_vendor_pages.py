from playwright.sync_api import sync_playwright, expect
import os

def main():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        # Cannot use base_url because the server is not running.
        # We will navigate using file paths.
        base_path = "file://" + os.path.abspath(".")

        page = browser.new_page()

        try:
            # --- Verify list_vendors.php ---
            print("Verifying list_vendors.php...")
            page.goto(f"{base_path}/list_vendors.php")
            expect(page.get_by_role("heading", name="Manage Vendors")).to_be_visible()
            screenshot_path_list = "jules-scratch/verification/list_vendors.png"
            page.screenshot(path=screenshot_path_list)
            print(f"Screenshot saved to {screenshot_path_list}")

            # --- Verify add_vendor.php ---
            print("Verifying add_vendor.php...")
            page.goto(f"{base_path}/add_vendor.php")
            expect(page.get_by_role("heading", name="Add New Vendor")).to_be_visible()
            screenshot_path_add = "jules-scratch/verification/add_vendor.png"
            page.screenshot(path=screenshot_path_add)
            print(f"Screenshot saved to {screenshot_path_add}")

            # --- Verify list_vendor_bills.php ---
            print("Verifying list_vendor_bills.php...")
            page.goto(f"{base_path}/list_vendor_bills.php")
            expect(page.get_by_role("heading", name="Vendor Bills")).to_be_visible()
            screenshot_path_list_bills = "jules-scratch/verification/list_vendor_bills.png"
            page.screenshot(path=screenshot_path_list_bills)
            print(f"Screenshot saved to {screenshot_path_list_bills}")

            # --- Verify add_vendor_bill.php ---
            print("Verifying add_vendor_bill.php...")
            page.goto(f"{base_path}/add_vendor_bill.php")
            expect(page.get_by_role("heading", name="Add Vendor Bill")).to_be_visible()
            screenshot_path_add_bill = "jules-scratch/verification/add_vendor_bill.png"
            page.screenshot(path=screenshot_path_add_bill)
            print(f"Screenshot saved to {screenshot_path_add_bill}")

        finally:
            browser.close()

if __name__ == "__main__":
    main()