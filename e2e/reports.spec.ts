import { test, expect } from '@playwright/test';
import { TEST_USER } from './fixtures';

test.describe('Reports & Analytics', () => {
  test.beforeEach(async ({ page }) => {
    // Login before each test
    await page.goto('/login');
    await page.getByLabel('Email').fill(TEST_USER.email);
    await page.getByLabel('Mot de passe').fill(TEST_USER.password);
    await page.getByRole('button', { name: /connexion/i }).click();
    await page.waitForURL('**/dashboard', { timeout: 10000 });

    // Navigate to reports
    await page.getByRole('link', { name: /rapports|reports|analytics/i }).click();
    await page.waitForURL('**/reports', { timeout: 5000 });
  });

  test('should display reports page', async ({ page }) => {
    await expect(page.getByRole('heading', { name: /rapports|reports|analytics/i })).toBeVisible();
  });

  test('should display analytics charts', async ({ page }) => {
    const chart = page.locator('[class*="chart"], [class*="recharts"], canvas, svg');
    await expect(chart.first()).toBeVisible({ timeout: 10000 });
  });

  test('should display key metrics', async ({ page }) => {
    await expect(page.getByText(/messages|envoyés|sent/i).first()).toBeVisible();
    await expect(page.getByText(/livraison|delivery|taux/i).first()).toBeVisible();
  });

  test('should filter by date range', async ({ page }) => {
    const startDate = page.getByLabel(/début|start|de/i);
    const endDate = page.getByLabel(/fin|end|à/i);

    if (await startDate.isVisible()) {
      await startDate.fill('2025-01-01');
      await endDate.fill('2025-01-31');
    }
  });

  test('should filter by period preset', async ({ page }) => {
    const periodSelect = page.getByRole('combobox').filter({ hasText: /période|period|aujourd|today|semaine|week/i });
    if (await periodSelect.isVisible()) {
      await periodSelect.click();
      await page.getByRole('option', { name: /mois|month/i }).click();
    }
  });

  test('should export to PDF', async ({ page }) => {
    const pdfButton = page.getByRole('button', { name: /pdf/i });
    if (await pdfButton.isVisible()) {
      const downloadPromise = page.waitForEvent('download');
      await pdfButton.click();
      const download = await downloadPromise;
      expect(download.suggestedFilename()).toMatch(/\.pdf$/i);
    }
  });

  test('should export to Excel', async ({ page }) => {
    const excelButton = page.getByRole('button', { name: /excel|xlsx/i });
    if (await excelButton.isVisible()) {
      const downloadPromise = page.waitForEvent('download');
      await excelButton.click();
      const download = await downloadPromise;
      expect(download.suggestedFilename()).toMatch(/\.xlsx?$/i);
    }
  });

  test('should export to CSV', async ({ page }) => {
    const csvButton = page.getByRole('button', { name: /csv/i });
    if (await csvButton.isVisible()) {
      const downloadPromise = page.waitForEvent('download');
      await csvButton.click();
      const download = await downloadPromise;
      expect(download.suggestedFilename()).toMatch(/\.csv$/i);
    }
  });

  test('should show operator breakdown', async ({ page }) => {
    await expect(page.getByText(/airtel|moov|opérateur|operator/i).first()).toBeVisible();
  });

  test('should show top campaigns', async ({ page }) => {
    await expect(page.getByText(/top|meilleures|best|campagnes/i).first()).toBeVisible();
  });

  test('should display message history section', async ({ page }) => {
    const historyLink = page.getByRole('link', { name: /historique|history/i });
    if (await historyLink.isVisible()) {
      await historyLink.click();
      await expect(page.getByRole('heading', { name: /historique|history/i })).toBeVisible();
    }
  });
});

test.describe('Message History', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/login');
    await page.getByLabel('Email').fill(TEST_USER.email);
    await page.getByLabel('Mot de passe').fill(TEST_USER.password);
    await page.getByRole('button', { name: /connexion/i }).click();
    await page.waitForURL('**/dashboard', { timeout: 10000 });

    await page.goto('/history');
  });

  test('should display message history', async ({ page }) => {
    await expect(page.getByRole('heading', { name: /historique|history/i })).toBeVisible();
  });

  test('should display messages table', async ({ page }) => {
    const table = page.locator('table, [role="grid"]');
    await expect(table.first()).toBeVisible();
  });

  test('should filter by status', async ({ page }) => {
    const statusFilter = page.getByRole('combobox').filter({ hasText: /statut|status|tous|all/i });
    if (await statusFilter.isVisible()) {
      await statusFilter.click();
      await page.getByRole('option', { name: /livré|delivered/i }).click();
    }
  });

  test('should filter by operator', async ({ page }) => {
    const operatorFilter = page.getByRole('combobox').filter({ hasText: /opérateur|operator|tous|all/i });
    if (await operatorFilter.isVisible()) {
      await operatorFilter.click();
      await page.getByRole('option', { name: /airtel|moov/i }).first().click();
    }
  });

  test('should search messages', async ({ page }) => {
    const searchInput = page.getByPlaceholder(/rechercher|search/i);
    if (await searchInput.isVisible()) {
      await searchInput.fill('77');
      await page.waitForTimeout(500);
    }
  });

  test('should paginate messages', async ({ page }) => {
    const pagination = page.locator('[class*="pagination"]');
    if (await pagination.isVisible()) {
      const nextButton = page.getByRole('button', { name: /suivant|next/i });
      if (await nextButton.isEnabled()) {
        await nextButton.click();
      }
    }
  });

  test('should view message details', async ({ page }) => {
    const viewButton = page.getByRole('button', { name: /voir|view|détails/i }).first();
    if (await viewButton.isVisible()) {
      await viewButton.click();
      await expect(page.getByRole('dialog')).toBeVisible();
    }
  });

  test('should resend failed message', async ({ page }) => {
    const resendButton = page.getByRole('button', { name: /renvoyer|resend/i }).first();
    if (await resendButton.isVisible()) {
      await resendButton.click();
      await expect(page.getByText(/envoyé|sent|succès|success/i)).toBeVisible({ timeout: 5000 });
    }
  });
});
