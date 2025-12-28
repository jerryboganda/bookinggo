import { chromium } from 'playwright';

(async () => {
    const browser = await chromium.launch({ headless: false });
    const context = await browser.newContext();
    const page = await context.newPage();
    
    // Enable console logging
    page.on('console', msg => console.log('BROWSER:', msg.text()));
    page.on('pageerror', error => console.log('PAGE ERROR:', error.message));
    
    try {
        console.log('Step 1: Navigate to appointment form');
        await page.goto('https://bookinggo.test/amad-diagnostic-centre-gujranwala');
        await page.waitForTimeout(2000);
        
        console.log('Step 2: Select category');
        await page.selectOption('#categorySelect', { index: 1 });
        await page.waitForTimeout(1000);
        
        console.log('Step 3: Select service');
        await page.waitForSelector('#serviceSelect option:not([value=""])', { timeout: 5000 });
        await page.selectOption('#serviceSelect', { index: 1 });
        await page.waitForTimeout(1000);
        
        console.log('Step 4: Select location');
        await page.selectOption('select[name="location"]', { index: 1 });
        await page.waitForTimeout(500);
        
        console.log('Step 5: Select staff');
        await page.selectOption('select[name="staff"]', { index: 1 });
        await page.waitForTimeout(500);
        
        console.log('Step 6: Click Continue Step 1');
        await page.click('#fl11ContinueStep1');
        await page.waitForTimeout(1000);
        
        console.log('Step 7: Select date');
        await page.fill('#datepicker', '2025-12-30');
        await page.waitForTimeout(2000);
        
        console.log('Step 8: Select time slot');
        const timeSlot = await page.locator('input[name="duration"]').first();
        if (await timeSlot.isVisible()) {
            await timeSlot.click();
            await page.waitForTimeout(500);
        } else {
            console.log('No time slots available');
        }
        
        console.log('Step 9: Click Continue Step 2');
        await page.click('#fl11ContinueStep2');
        await page.waitForTimeout(1000);
        
        console.log('Step 10: Fill guest details');
        await page.fill('#guest_name', 'Test Patient Playwright');
        await page.fill('#guest_email', 'testplaywright@example.com');
        await page.fill('#guest_contact', '+923001234567');
        await page.waitForTimeout(500);
        
        console.log('Step 11: Click Continue Step 4');
        await page.click('#fl11ContinueStep4');
        await page.waitForTimeout(1000);
        
        console.log('Step 12: Submit form');
        
        // Listen for network response
        const responsePromise = page.waitForResponse(response => 
            response.url().includes('appointment-book') && response.status() !== 0
        );
        
        await page.click('#fl11SubmitBtn');
        
        // Wait for response
        const response = await responsePromise;
        const responseData = await response.json();
        console.log('Response Status:', response.status());
        console.log('Response Data:', JSON.stringify(responseData, null, 2));
        
        await page.waitForTimeout(3000);
        
        // Check if confirmation page is shown
        const isConfirmationVisible = await page.locator('#fl11Step6').isVisible();
        console.log('Confirmation page visible:', isConfirmationVisible);
        
        if (responseData.status === 'success') {
            console.log('SUCCESS: Appointment created!');
            console.log('Appointment ID:', responseData.appointment_id);
            
            // Now check contacts
            console.log('Step 13: Navigate to contacts page');
            await page.goto('https://bookinggo.test/contacts');
            await page.waitForTimeout(2000);
            
            // Search for the email
            const contactExists = await page.locator('text=testplaywright@example.com').count() > 0;
            console.log('Contact found in list:', contactExists);
            
            if (contactExists) {
                console.log('SUCCESS: Contact was created and appears in the list!');
            } else {
                console.log('FAILED: Contact was NOT created in the list!');
            }
        } else {
            console.log('FAILED: Appointment creation failed');
            console.log('Error:', responseData.message || responseData.error);
        }
        
    } catch (error) {
        console.log('ERROR:', error.message);
        console.log('Stack:', error.stack);
    }
    
    await browser.close();
})();
