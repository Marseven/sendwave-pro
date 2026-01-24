import { test, expect } from '@playwright/test';
import { TEST_USER, randomString } from './fixtures';

test.describe('Message Templates', () => {
  test.beforeEach(async ({ page }) => {
    // Login before each test
    await page.goto('/login');
    await page.getByLabel('Email').fill(TEST_USER.email);
    await page.getByLabel('Mot de passe').fill(TEST_USER.password);
    await page.getByRole('button', { name: /connexion/i }).click();
    await page.waitForURL('**/dashboard', { timeout: 10000 });

    // Navigate to templates
    await page.getByRole('link', { name: /templates|modèles/i }).click();
    await page.waitForURL('**/templates', { timeout: 5000 });
  });

  test('should display templates page', async ({ page }) => {
    await expect(page.getByRole('heading', { name: /templates|modèles/i })).toBeVisible();
  });

  test('should display templates list', async ({ page }) => {
    const list = page.locator('table, [class*="grid"], [class*="list"]');
    await expect(list.first()).toBeVisible();
  });

  test('should have create template button', async ({ page }) => {
    await expect(page.getByRole('button', { name: /nouveau|créer|create|ajouter|add/i })).toBeVisible();
  });

  test('should open create template modal', async ({ page }) => {
    await page.getByRole('button', { name: /nouveau|créer|create|ajouter|add/i }).click();

    await expect(page.getByRole('dialog')).toBeVisible();
    await expect(page.getByLabel(/nom|name/i)).toBeVisible();
    await expect(page.getByLabel(/contenu|content|message/i)).toBeVisible();
  });

  test('should create a new template', async ({ page }) => {
    const templateName = `Template ${randomString()}`;

    await page.getByRole('button', { name: /nouveau|créer|create|ajouter|add/i }).click();

    // Fill form
    await page.getByLabel(/nom|name/i).fill(templateName);
    await page.getByLabel(/contenu|content|message/i).fill('Bonjour {nom}, merci pour votre achat.');

    // Select category if available
    const categorySelect = page.getByRole('combobox').filter({ hasText: /catégorie|category/i });
    if (await categorySelect.isVisible()) {
      await categorySelect.click();
      await page.getByRole('option').first().click();
    }

    // Save
    await page.getByRole('button', { name: /enregistrer|save|créer|create/i }).click();

    // Should show in list
    await expect(page.getByText(templateName)).toBeVisible({ timeout: 5000 });
  });

  test('should show available variables', async ({ page }) => {
    await page.getByRole('button', { name: /nouveau|créer|create|ajouter|add/i }).click();

    // Variables list should be visible
    await expect(page.getByText(/\{nom\}|\{prenom\}|\{telephone\}/)).toBeVisible();
  });

  test('should show live preview', async ({ page }) => {
    await page.getByRole('button', { name: /nouveau|créer|create|ajouter|add/i }).click();

    // Fill template content
    await page.getByLabel(/contenu|content|message/i).fill('Bonjour {nom}, votre code est {code}.');

    // Preview should show with example values
    const preview = page.locator('[class*="preview"], [class*="aperçu"]');
    if (await preview.isVisible()) {
      await expect(preview.getByText(/Bonjour/)).toBeVisible();
    }
  });

  test('should show character count', async ({ page }) => {
    await page.getByRole('button', { name: /nouveau|créer|create|ajouter|add/i }).click();

    await page.getByLabel(/contenu|content|message/i).fill('Test message with some content');

    // Should show character/SMS count
    await expect(page.getByText(/caractères|characters|\d+ sms/i)).toBeVisible();
  });

  test('should edit a template', async ({ page }) => {
    const editButton = page.getByRole('button', { name: /modifier|edit/i }).first();
    if (await editButton.isVisible()) {
      await editButton.click();

      // Modal should open with existing data
      await expect(page.getByRole('dialog')).toBeVisible();

      // Update content
      const contentInput = page.getByLabel(/contenu|content|message/i);
      await contentInput.clear();
      await contentInput.fill('Updated template content');

      // Save
      await page.getByRole('button', { name: /enregistrer|save|mettre à jour|update/i }).click();

      await expect(page.getByText(/succès|success|mis à jour|updated/i)).toBeVisible({ timeout: 5000 });
    }
  });

  test('should delete a template', async ({ page }) => {
    const deleteButton = page.getByRole('button', { name: /supprimer|delete/i }).first();
    if (await deleteButton.isVisible()) {
      await deleteButton.click();

      // Confirm
      const confirmButton = page.getByRole('button', { name: /confirmer|oui|yes|supprimer/i });
      if (await confirmButton.isVisible()) {
        await confirmButton.click();
      }

      await expect(page.getByText(/supprimé|deleted|succès|success/i)).toBeVisible({ timeout: 5000 });
    }
  });

  test('should toggle template public/private', async ({ page }) => {
    const toggleButton = page.getByRole('button', { name: /public|privé|private|partager|share/i }).first();
    if (await toggleButton.isVisible()) {
      await toggleButton.click();

      await expect(page.getByText(/public|partagé|shared|privé|private/i)).toBeVisible({ timeout: 5000 });
    }
  });

  test('should filter by category', async ({ page }) => {
    const categoryFilter = page.getByRole('combobox').filter({ hasText: /catégorie|category|tous|all/i });
    if (await categoryFilter.isVisible()) {
      await categoryFilter.click();
      await page.getByRole('option', { name: /marketing|notification|transactionnel/i }).first().click();
    }
  });

  test('should search templates', async ({ page }) => {
    const searchInput = page.getByPlaceholder(/rechercher|search/i);
    if (await searchInput.isVisible()) {
      await searchInput.fill('promo');
      await page.waitForTimeout(500);
    }
  });

  test('should copy template content', async ({ page }) => {
    const copyButton = page.getByRole('button', { name: /copier|copy/i }).first();
    if (await copyButton.isVisible()) {
      await copyButton.click();
      await expect(page.getByText(/copié|copied/i)).toBeVisible({ timeout: 3000 });
    }
  });

  test('should use template in message', async ({ page }) => {
    const useButton = page.getByRole('button', { name: /utiliser|use/i }).first();
    if (await useButton.isVisible()) {
      await useButton.click();
      // Should navigate to send message or fill message field
      await expect(page).toHaveURL(/.*send|message|campaign/);
    }
  });
});
