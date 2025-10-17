from playwright.sync_api import sync_playwright, expect
import os

def main():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        base_path = "file://" + os.path.abspath(".")
        page = browser.new_page()

        try:
            print("Verifying create_invoice.php...")
            page.goto(f"{base_path}/create_invoice.php")
            expect(page.get_by_role("heading", name="Create New Invoice")).to_be_visible()
            screenshot_path = "jules-scratch/verification/new_ui_create_invoice.png"
            page.screenshot(path=screenshot_path)
            print(f"Screenshot saved to {screenshot_path}")

        finally:
            browser.close()

if __name__ == "__main__":
    main()