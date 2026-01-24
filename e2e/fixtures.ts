import { test as base, expect } from '@playwright/test';

/**
 * Test fixtures for SendWave Pro E2E tests
 */

// Test user credentials
export const TEST_USER = {
  email: 'test@sendwave.pro',
  password: 'password123',
  name: 'Test User',
};

// Extend the base test with authentication
export const test = base.extend<{ authenticatedPage: typeof base }>({
  authenticatedPage: async ({ page }, use) => {
    // Go to login page
    await page.goto('/login');

    // Fill in credentials
    await page.getByLabel('Email').fill(TEST_USER.email);
    await page.getByLabel('Mot de passe').fill(TEST_USER.password);

    // Submit login form
    await page.getByRole('button', { name: /connexion/i }).click();

    // Wait for redirect to dashboard
    await page.waitForURL('**/dashboard', { timeout: 10000 });

    // Verify we're logged in
    await expect(page).toHaveURL(/.*dashboard/);

    await use(base);
  },
});

export { expect };

// Helper to format phone numbers for Gabon
export function formatGabonPhone(number: string): string {
  return number.startsWith('+241') ? number : `+241${number}`;
}

// Helper to generate random phone number
export function generateRandomPhone(): string {
  const prefixes = ['77', '74', '66', '62']; // Airtel and Moov prefixes
  const prefix = prefixes[Math.floor(Math.random() * prefixes.length)];
  const suffix = Math.floor(Math.random() * 10000000).toString().padStart(7, '0');
  return `${prefix}${suffix}`;
}

// Helper to generate random string
export function randomString(length: number = 8): string {
  return Math.random().toString(36).substring(2, length + 2);
}
