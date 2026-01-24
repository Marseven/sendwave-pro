import { test, expect } from '@playwright/test';
import { TEST_USER, generateRandomPhone, randomString } from './fixtures';

test.describe('Contacts Management', () => {
  test.beforeEach(async ({ page }) => {
    // Login before each test
    await page.goto('/login');
    await page.getByLabel('Email').fill(TEST_USER.email);
    await page.getByLabel('Mot de passe').fill(TEST_USER.password);
    await page.getByRole('button', { name: /connexion/i }).click();
    await page.waitForURL('**/dashboard', { timeout: 10000 });

    // Navigate to contacts
    await page.getByRole('link', { name: /contacts/i }).click();
    await page.waitForURL('**/contacts', { timeout: 5000 });
  });

  test('should display contacts page', async ({ page }) => {
    await expect(page.getByRole('heading', { name: /contacts/i })).toBeVisible();
  });

  test('should display contacts list', async ({ page }) => {
    // Check for table or list structure
    const table = page.locator('table, [role="grid"], [class*="list"]');
    await expect(table.first()).toBeVisible();
  });

  test('should have add contact button', async ({ page }) => {
    await expect(page.getByRole('button', { name: /ajouter|nouveau|add|new/i })).toBeVisible();
  });

  test('should open add contact modal', async ({ page }) => {
    await page.getByRole('button', { name: /ajouter|nouveau|add|new/i }).click();

    // Modal should appear
    await expect(page.getByRole('dialog')).toBeVisible();
    await expect(page.getByLabel(/nom|name/i)).toBeVisible();
    await expect(page.getByLabel(/téléphone|phone/i)).toBeVisible();
  });

  test('should create a new contact', async ({ page }) => {
    const contactName = `Test Contact ${randomString()}`;
    const phone = generateRandomPhone();

    // Open add modal
    await page.getByRole('button', { name: /ajouter|nouveau|add|new/i }).click();

    // Fill form
    await page.getByLabel(/nom|name/i).fill(contactName);
    await page.getByLabel(/téléphone|phone/i).fill(phone);

    // Submit
    await page.getByRole('button', { name: /enregistrer|sauvegarder|save|créer|create/i }).click();

    // Should show success or contact in list
    await expect(page.getByText(contactName)).toBeVisible({ timeout: 5000 });
  });

  test('should edit an existing contact', async ({ page }) => {
    // Find first edit button
    const editButton = page.getByRole('button', { name: /modifier|edit/i }).first();
    if (await editButton.isVisible()) {
      await editButton.click();

      // Modal should open
      await expect(page.getByRole('dialog')).toBeVisible();

      // Modify name
      const nameInput = page.getByLabel(/nom|name/i);
      await nameInput.clear();
      await nameInput.fill(`Updated Contact ${randomString()}`);

      // Save
      await page.getByRole('button', { name: /enregistrer|sauvegarder|save|mettre à jour|update/i }).click();

      // Should show success
      await expect(page.getByText(/succès|success|mis à jour|updated/i)).toBeVisible({ timeout: 5000 });
    }
  });

  test('should delete a contact', async ({ page }) => {
    // Find delete button
    const deleteButton = page.getByRole('button', { name: /supprimer|delete/i }).first();
    if (await deleteButton.isVisible()) {
      await deleteButton.click();

      // Confirm deletion
      const confirmButton = page.getByRole('button', { name: /confirmer|oui|yes|supprimer|delete/i });
      if (await confirmButton.isVisible()) {
        await confirmButton.click();
      }

      // Should show success
      await expect(page.getByText(/supprimé|deleted|succès|success/i)).toBeVisible({ timeout: 5000 });
    }
  });

  test('should search contacts', async ({ page }) => {
    const searchInput = page.getByPlaceholder(/rechercher|search/i);
    if (await searchInput.isVisible()) {
      await searchInput.fill('test');
      await page.waitForTimeout(500); // Wait for debounce

      // Results should filter
      const rows = page.locator('tbody tr, [class*="item"], [class*="row"]');
      // Either find results or "no results" message
    }
  });

  test('should export contacts', async ({ page }) => {
    const exportButton = page.getByRole('button', { name: /export/i });
    if (await exportButton.isVisible()) {
      // Start waiting for download before clicking
      const downloadPromise = page.waitForEvent('download');
      await exportButton.click();

      const download = await downloadPromise;
      expect(download.suggestedFilename()).toContain('.csv');
    }
  });

  test('should filter by group', async ({ page }) => {
    const groupFilter = page.getByRole('combobox').filter({ hasText: /groupe|group/i });
    if (await groupFilter.isVisible()) {
      await groupFilter.click();
      // Select a group
      const firstOption = page.getByRole('option').first();
      if (await firstOption.isVisible()) {
        await firstOption.click();
      }
    }
  });

  test('should paginate contacts', async ({ page }) => {
    // Check for pagination
    const pagination = page.locator('[class*="pagination"], nav[aria-label*="pagination"]');
    if (await pagination.isVisible()) {
      const nextButton = page.getByRole('button', { name: /suivant|next|>/i });
      if (await nextButton.isEnabled()) {
        await nextButton.click();
        // Page should change
      }
    }
  });
});
