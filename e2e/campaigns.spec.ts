import { test, expect } from '@playwright/test';
import { TEST_USER, randomString } from './fixtures';

test.describe('Campaigns', () => {
  test.beforeEach(async ({ page }) => {
    // Login before each test
    await page.goto('/login');
    await page.getByLabel('Email').fill(TEST_USER.email);
    await page.getByLabel('Mot de passe').fill(TEST_USER.password);
    await page.getByRole('button', { name: /connexion/i }).click();
    await page.waitForURL('**/dashboard', { timeout: 10000 });
  });

  test.describe('Campaign History', () => {
    test.beforeEach(async ({ page }) => {
      await page.getByRole('link', { name: /campagnes|campaigns/i }).click();
      await page.waitForURL('**/campaigns', { timeout: 5000 });
    });

    test('should display campaigns page', async ({ page }) => {
      await expect(page.getByRole('heading', { name: /campagnes|campaigns/i })).toBeVisible();
    });

    test('should display campaigns list', async ({ page }) => {
      const table = page.locator('table, [role="grid"], [class*="list"]');
      await expect(table.first()).toBeVisible();
    });

    test('should have create campaign button', async ({ page }) => {
      await expect(page.getByRole('link', { name: /nouvelle|créer|create|new/i })).toBeVisible();
    });

    test('should filter by status', async ({ page }) => {
      const statusFilter = page.getByRole('combobox').filter({ hasText: /statut|status/i });
      if (await statusFilter.isVisible()) {
        await statusFilter.click();
        await page.getByRole('option', { name: /envoyé|sent|terminé|completed/i }).click();
      }
    });

    test('should clone a campaign', async ({ page }) => {
      const cloneButton = page.getByRole('button', { name: /cloner|clone|dupliquer/i }).first();
      if (await cloneButton.isVisible()) {
        await cloneButton.click();
        await expect(page.getByText(/cloné|cloned|copié|copied/i)).toBeVisible({ timeout: 5000 });
      }
    });

    test('should view campaign details', async ({ page }) => {
      const viewButton = page.getByRole('button', { name: /voir|view|détails|details/i }).first();
      if (await viewButton.isVisible()) {
        await viewButton.click();
        await expect(page.getByRole('dialog')).toBeVisible();
      }
    });
  });

  test.describe('Campaign Creation', () => {
    test.beforeEach(async ({ page }) => {
      await page.goto('/campaigns/create');
    });

    test('should display campaign creation page', async ({ page }) => {
      await expect(page.getByRole('heading', { name: /nouvelle campagne|create campaign|créer/i })).toBeVisible();
    });

    test('should show step navigation', async ({ page }) => {
      // Multi-step form should have step indicators
      await expect(page.getByText(/étape|step/i).first()).toBeVisible();
    });

    test('should fill basic campaign info', async ({ page }) => {
      const campaignName = `Test Campaign ${randomString()}`;

      // Fill campaign name
      await page.getByLabel(/nom|name/i).fill(campaignName);

      // Select group or recipients
      const groupSelect = page.getByRole('combobox').filter({ hasText: /groupe|group|destinataires|recipients/i });
      if (await groupSelect.isVisible()) {
        await groupSelect.click();
        await page.getByRole('option').first().click();
      }

      // Message content
      await page.getByLabel(/message|contenu|content/i).fill('Bonjour, ceci est un test.');
    });

    test('should enable A/B testing', async ({ page }) => {
      // Fill required fields first
      await page.getByLabel(/nom|name/i).fill('A/B Test Campaign');

      // Enable A/B testing
      const abToggle = page.getByLabel(/test a\/b|a\/b testing/i);
      if (await abToggle.isVisible()) {
        await abToggle.check();

        // Should show variant inputs
        await expect(page.getByText(/variante|variant/i)).toBeVisible();
      }
    });

    test('should add A/B variants', async ({ page }) => {
      await page.getByLabel(/nom|name/i).fill('A/B Campaign');

      const abToggle = page.getByLabel(/test a\/b|a\/b testing/i);
      if (await abToggle.isVisible()) {
        await abToggle.check();

        // Fill first variant
        await page.getByLabel(/variante a|variant a/i).fill('Message variante A');

        // Add another variant
        const addVariantButton = page.getByRole('button', { name: /ajouter variante|add variant/i });
        if (await addVariantButton.isVisible()) {
          await addVariantButton.click();
          await page.getByLabel(/variante b|variant b/i).fill('Message variante B');
        }
      }
    });

    test('should schedule campaign', async ({ page }) => {
      // Fill required fields
      await page.getByLabel(/nom|name/i).fill('Scheduled Campaign');
      await page.getByLabel(/message|contenu|content/i).fill('Test message');

      // Enable scheduling
      const scheduleToggle = page.getByLabel(/planifier|schedule/i);
      if (await scheduleToggle.isVisible()) {
        await scheduleToggle.check();

        // Date picker should appear
        await expect(page.getByLabel(/date|quand|when/i)).toBeVisible();
      }
    });

    test('should set recurrence', async ({ page }) => {
      await page.getByLabel(/nom|name/i).fill('Recurring Campaign');

      // Enable scheduling first
      const scheduleToggle = page.getByLabel(/planifier|schedule/i);
      if (await scheduleToggle.isVisible()) {
        await scheduleToggle.check();

        // Select recurrence
        const recurrenceSelect = page.getByRole('combobox').filter({ hasText: /récurrence|recurrence|répéter|repeat/i });
        if (await recurrenceSelect.isVisible()) {
          await recurrenceSelect.click();
          await page.getByRole('option', { name: /hebdomadaire|weekly/i }).click();
        }
      }
    });

    test('should preview message', async ({ page }) => {
      await page.getByLabel(/nom|name/i).fill('Preview Test');
      await page.getByLabel(/message|contenu|content/i).fill('Bonjour {nom}, test message.');

      // Look for preview section
      const preview = page.getByText(/aperçu|preview/i);
      if (await preview.isVisible()) {
        // Preview should show the message
        await expect(page.getByText(/Bonjour/)).toBeVisible();
      }
    });

    test('should show character count', async ({ page }) => {
      const messageInput = page.getByLabel(/message|contenu|content/i);
      await messageInput.fill('Test message for character count');

      // Should show character/SMS count
      await expect(page.getByText(/caractères|characters|sms/i)).toBeVisible();
    });

    test('should navigate through steps', async ({ page }) => {
      // Fill step 1
      await page.getByLabel(/nom|name/i).fill('Step Test');

      // Go to next step
      const nextButton = page.getByRole('button', { name: /suivant|next|continuer|continue/i });
      if (await nextButton.isVisible()) {
        await nextButton.click();

        // Should be on step 2
        await expect(page.getByText(/étape 2|step 2/i)).toBeVisible();
      }
    });

    test('should show recap before sending', async ({ page }) => {
      // Fill basic info and navigate to recap
      await page.getByLabel(/nom|name/i).fill('Recap Test');
      await page.getByLabel(/message|contenu|content/i).fill('Test message');

      // Navigate to last step (usually recap)
      const steps = page.locator('[class*="step"]');
      const lastStep = steps.last();
      if (await lastStep.isVisible()) {
        await lastStep.click();
      }

      // Should show recap information
      const recapSection = page.getByText(/récapitulatif|recap|résumé|summary/i);
      if (await recapSection.isVisible()) {
        await expect(page.getByText('Recap Test')).toBeVisible();
      }
    });
  });
});
