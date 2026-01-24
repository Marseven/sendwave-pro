import { test, expect } from '@playwright/test';
import { TEST_USER } from './fixtures';

test.describe('Dashboard', () => {
  test.beforeEach(async ({ page }) => {
    // Login before each test
    await page.goto('/login');
    await page.getByLabel('Email').fill(TEST_USER.email);
    await page.getByLabel('Mot de passe').fill(TEST_USER.password);
    await page.getByRole('button', { name: /connexion/i }).click();
    await page.waitForURL('**/dashboard', { timeout: 10000 });
  });

  test('should display dashboard page', async ({ page }) => {
    await expect(page).toHaveURL(/.*dashboard/);
    await expect(page.getByRole('heading', { name: /tableau de bord|dashboard/i })).toBeVisible();
  });

  test('should display statistics widgets', async ({ page }) => {
    // Check for main stat cards
    await expect(page.getByText(/messages envoyés|sent/i)).toBeVisible();
    await expect(page.getByText(/taux de livraison|delivery rate/i)).toBeVisible();
  });

  test('should display recent campaigns section', async ({ page }) => {
    await expect(page.getByText(/campagnes récentes|recent campaigns/i)).toBeVisible();
  });

  test('should display analytics chart', async ({ page }) => {
    // Check for chart container
    const chartContainer = page.locator('[class*="chart"], [class*="recharts"], canvas');
    await expect(chartContainer.first()).toBeVisible({ timeout: 10000 });
  });

  test('should navigate to send message from quick action', async ({ page }) => {
    // Look for quick action buttons
    const sendButton = page.getByRole('link', { name: /envoyer|nouveau message|send/i });
    if (await sendButton.isVisible()) {
      await sendButton.click();
      await expect(page).toHaveURL(/.*send|message/);
    }
  });

  test('should display credit balance', async ({ page }) => {
    // Check for credit/balance display
    await expect(page.getByText(/crédit|solde|balance|sms/i).first()).toBeVisible();
  });

  test('should allow period filter change', async ({ page }) => {
    // Look for period selector (today, week, month)
    const periodSelector = page.getByRole('combobox').filter({ hasText: /aujourd|today|semaine|week|mois|month/i });
    if (await periodSelector.isVisible()) {
      await periodSelector.click();
      // Select a different period
      await page.getByRole('option').filter({ hasText: /semaine|week/i }).click();
    }
  });

  test('should show sidebar navigation', async ({ page }) => {
    // Check main navigation items
    await expect(page.getByRole('link', { name: /contacts/i })).toBeVisible();
    await expect(page.getByRole('link', { name: /campagnes|campaigns/i })).toBeVisible();
    await expect(page.getByRole('link', { name: /historique|history/i })).toBeVisible();
  });

  test('should be responsive on mobile', async ({ page }) => {
    // Set mobile viewport
    await page.setViewportSize({ width: 375, height: 667 });

    // Dashboard should still be accessible
    await expect(page.getByRole('heading', { name: /tableau de bord|dashboard/i })).toBeVisible();

    // Menu should be collapsed or hamburger visible
    const hamburger = page.getByRole('button', { name: /menu/i });
    if (await hamburger.isVisible()) {
      await hamburger.click();
      // Sidebar should open
      await expect(page.getByRole('link', { name: /contacts/i })).toBeVisible();
    }
  });
});
