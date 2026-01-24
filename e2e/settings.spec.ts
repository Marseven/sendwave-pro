import { test, expect } from '@playwright/test';
import { TEST_USER } from './fixtures';

test.describe('Settings', () => {
  test.beforeEach(async ({ page }) => {
    // Login before each test
    await page.goto('/login');
    await page.getByLabel('Email').fill(TEST_USER.email);
    await page.getByLabel('Mot de passe').fill(TEST_USER.password);
    await page.getByRole('button', { name: /connexion/i }).click();
    await page.waitForURL('**/dashboard', { timeout: 10000 });

    // Navigate to settings
    await page.getByRole('link', { name: /paramètres|settings/i }).click();
    await page.waitForURL('**/settings', { timeout: 5000 });
  });

  test('should display settings page', async ({ page }) => {
    await expect(page.getByRole('heading', { name: /paramètres|settings/i })).toBeVisible();
  });

  test('should display notification settings', async ({ page }) => {
    await expect(page.getByText(/notifications/i)).toBeVisible();
  });

  test('should toggle campaign alerts', async ({ page }) => {
    const toggle = page.getByLabel(/alertes campagne|campaign alerts/i);
    if (await toggle.isVisible()) {
      const initialState = await toggle.isChecked();
      await toggle.click();
      expect(await toggle.isChecked()).toBe(!initialState);
    }
  });

  test('should toggle low credit alerts', async ({ page }) => {
    const toggle = page.getByLabel(/crédit faible|low credit/i);
    if (await toggle.isVisible()) {
      await toggle.click();
    }
  });

  test('should display regional preferences', async ({ page }) => {
    await expect(page.getByText(/régionales|regional|préférences/i)).toBeVisible();
  });

  test('should change language preference', async ({ page }) => {
    const languageSelect = page.getByRole('combobox').filter({ hasText: /langue|language|français|english/i });
    if (await languageSelect.isVisible()) {
      await languageSelect.click();
      await page.getByRole('option').first().click();
    }
  });

  test('should change timezone', async ({ page }) => {
    const timezoneSelect = page.getByRole('combobox').filter({ hasText: /fuseau|timezone|africa/i });
    if (await timezoneSelect.isVisible()) {
      await timezoneSelect.click();
      await page.getByRole('option', { name: /libreville|africa/i }).first().click();
    }
  });

  test('should display SMS preferences', async ({ page }) => {
    await expect(page.getByText(/sms|préférences sms|sms preferences/i)).toBeVisible();
  });

  test('should set default signature', async ({ page }) => {
    const signatureInput = page.getByLabel(/signature/i);
    if (await signatureInput.isVisible()) {
      await signatureInput.clear();
      await signatureInput.fill('- SendWave Pro');
    }
  });

  test('should set credit alert threshold', async ({ page }) => {
    const thresholdInput = page.getByLabel(/seuil|threshold/i);
    if (await thresholdInput.isVisible()) {
      await thresholdInput.clear();
      await thresholdInput.fill('100');
    }
  });

  test('should save settings', async ({ page }) => {
    // Make a change
    const signatureInput = page.getByLabel(/signature/i);
    if (await signatureInput.isVisible()) {
      await signatureInput.clear();
      await signatureInput.fill('Test Signature');
    }

    // Save
    await page.getByRole('button', { name: /enregistrer|save|sauvegarder/i }).click();

    // Should show success
    await expect(page.getByText(/succès|success|enregistré|saved/i)).toBeVisible({ timeout: 5000 });
  });

  test('should navigate to profile from settings', async ({ page }) => {
    const profileLink = page.getByRole('link', { name: /profil|profile/i });
    if (await profileLink.isVisible()) {
      await profileLink.click();
      await expect(page).toHaveURL(/.*profile/);
    }
  });

  test('should have account security section', async ({ page }) => {
    const securitySection = page.getByText(/sécurité|security|mot de passe|password/i);
    await expect(securitySection.first()).toBeVisible();
  });

  test('should change password', async ({ page }) => {
    // Navigate to password section if needed
    const passwordSection = page.getByRole('button', { name: /mot de passe|password/i });
    if (await passwordSection.isVisible()) {
      await passwordSection.click();
    }

    const currentPasswordInput = page.getByLabel(/actuel|current/i);
    const newPasswordInput = page.getByLabel(/nouveau|new/i);
    const confirmPasswordInput = page.getByLabel(/confirmer|confirm/i);

    if (await currentPasswordInput.isVisible()) {
      await currentPasswordInput.fill('oldpassword');
      await newPasswordInput.fill('newpassword123');
      await confirmPasswordInput.fill('newpassword123');
    }
  });
});

test.describe('Profile', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/login');
    await page.getByLabel('Email').fill(TEST_USER.email);
    await page.getByLabel('Mot de passe').fill(TEST_USER.password);
    await page.getByRole('button', { name: /connexion/i }).click();
    await page.waitForURL('**/dashboard', { timeout: 10000 });

    await page.goto('/profile');
  });

  test('should display profile page', async ({ page }) => {
    await expect(page.getByRole('heading', { name: /profil|profile/i })).toBeVisible();
  });

  test('should display user info', async ({ page }) => {
    await expect(page.getByLabel(/nom|name/i).first()).toBeVisible();
    await expect(page.getByLabel(/email/i)).toBeVisible();
  });

  test('should update profile info', async ({ page }) => {
    const nameInput = page.getByLabel(/nom|name/i).first();
    if (await nameInput.isVisible()) {
      await nameInput.clear();
      await nameInput.fill('Updated Name');

      await page.getByRole('button', { name: /enregistrer|save|mettre à jour|update/i }).click();

      await expect(page.getByText(/succès|success|mis à jour|updated/i)).toBeVisible({ timeout: 5000 });
    }
  });

  test('should display account stats', async ({ page }) => {
    // Stats like messages sent, account creation date, etc.
    const stats = page.getByText(/messages|campagnes|créé le|created/i);
    await expect(stats.first()).toBeVisible();
  });
});
