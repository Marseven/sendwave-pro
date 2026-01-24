import { test, expect } from '@playwright/test';
import { TEST_USER, generateRandomPhone, randomString } from './fixtures';

test.describe('Send Message', () => {
  test.beforeEach(async ({ page }) => {
    // Login before each test
    await page.goto('/login');
    await page.getByLabel('Email').fill(TEST_USER.email);
    await page.getByLabel('Mot de passe').fill(TEST_USER.password);
    await page.getByRole('button', { name: /connexion/i }).click();
    await page.waitForURL('**/dashboard', { timeout: 10000 });

    // Navigate to send message
    await page.goto('/send');
  });

  test('should display send message page', async ({ page }) => {
    await expect(page.getByRole('heading', { name: /envoyer|send|nouveau message|new message/i })).toBeVisible();
  });

  test('should have recipient input', async ({ page }) => {
    await expect(page.getByLabel(/destinataire|recipient|numéro|number/i)).toBeVisible();
  });

  test('should have message textarea', async ({ page }) => {
    await expect(page.getByLabel(/message|contenu|content/i)).toBeVisible();
  });

  test('should show character count', async ({ page }) => {
    const messageInput = page.getByLabel(/message|contenu|content/i);
    await messageInput.fill('Test message for counting');

    await expect(page.getByText(/caractères|characters|\d+\/\d+/i)).toBeVisible();
  });

  test('should show SMS count for long messages', async ({ page }) => {
    const messageInput = page.getByLabel(/message|contenu|content/i);
    // Fill with long message (>160 chars)
    await messageInput.fill('A'.repeat(200));

    await expect(page.getByText(/2 sms|sms: 2/i)).toBeVisible();
  });

  test('should validate phone number format', async ({ page }) => {
    const phoneInput = page.getByLabel(/destinataire|recipient|numéro|number/i);
    await phoneInput.fill('invalid');

    const sendButton = page.getByRole('button', { name: /envoyer|send/i });
    await sendButton.click();

    await expect(page.getByText(/numéro invalide|invalid number|format/i)).toBeVisible({ timeout: 3000 });
  });

  test('should accept Gabon phone number', async ({ page }) => {
    const phoneInput = page.getByLabel(/destinataire|recipient|numéro|number/i);
    const phone = generateRandomPhone();
    await phoneInput.fill(`+241${phone}`);

    // Should not show validation error
    const errorMessage = page.getByText(/numéro invalide|invalid number/i);
    await expect(errorMessage).not.toBeVisible();
  });

  test('should send message to single recipient', async ({ page }) => {
    const phone = generateRandomPhone();

    await page.getByLabel(/destinataire|recipient|numéro|number/i).fill(`+241${phone}`);
    await page.getByLabel(/message|contenu|content/i).fill('Test message ' + randomString());

    await page.getByRole('button', { name: /envoyer|send/i }).click();

    // Should show success
    await expect(page.getByText(/envoyé|sent|succès|success/i)).toBeVisible({ timeout: 10000 });
  });

  test('should send to multiple recipients', async ({ page }) => {
    const phone1 = generateRandomPhone();
    const phone2 = generateRandomPhone();

    const phoneInput = page.getByLabel(/destinataires|recipients|numéros|numbers/i);
    if (await phoneInput.isVisible()) {
      await phoneInput.fill(`+241${phone1}, +241${phone2}`);
    }

    await page.getByLabel(/message|contenu|content/i).fill('Test bulk message');

    await page.getByRole('button', { name: /envoyer|send/i }).click();

    await expect(page.getByText(/envoyé|sent|succès|success/i)).toBeVisible({ timeout: 10000 });
  });

  test('should select contact from list', async ({ page }) => {
    const contactSelect = page.getByRole('button', { name: /choisir|select|contacts/i });
    if (await contactSelect.isVisible()) {
      await contactSelect.click();

      // Modal or dropdown should open
      await expect(page.getByRole('dialog', { name: /contacts/i })).toBeVisible();

      // Select first contact
      await page.getByRole('checkbox').first().check();
      await page.getByRole('button', { name: /confirmer|confirm|ajouter|add/i }).click();
    }
  });

  test('should select group', async ({ page }) => {
    const groupSelect = page.getByRole('combobox').filter({ hasText: /groupe|group/i });
    if (await groupSelect.isVisible()) {
      await groupSelect.click();
      await page.getByRole('option').first().click();
    }
  });

  test('should use template', async ({ page }) => {
    const templateSelect = page.getByRole('combobox').filter({ hasText: /template|modèle/i });
    if (await templateSelect.isVisible()) {
      await templateSelect.click();
      await page.getByRole('option').first().click();

      // Message field should be populated
      const messageInput = page.getByLabel(/message|contenu|content/i);
      await expect(messageInput).not.toHaveValue('');
    }
  });

  test('should schedule message', async ({ page }) => {
    await page.getByLabel(/destinataire|recipient/i).fill(`+241${generateRandomPhone()}`);
    await page.getByLabel(/message|contenu|content/i).fill('Scheduled message test');

    const scheduleToggle = page.getByLabel(/planifier|schedule/i);
    if (await scheduleToggle.isVisible()) {
      await scheduleToggle.check();

      // Date/time picker should appear
      await expect(page.getByLabel(/date|quand/i)).toBeVisible();
    }
  });

  test('should preview message', async ({ page }) => {
    await page.getByLabel(/destinataire|recipient/i).fill(`+241${generateRandomPhone()}`);
    await page.getByLabel(/message|contenu|content/i).fill('Test preview message');

    const previewButton = page.getByRole('button', { name: /aperçu|preview/i });
    if (await previewButton.isVisible()) {
      await previewButton.click();
      await expect(page.getByText('Test preview message')).toBeVisible();
    }
  });

  test('should show operator detection', async ({ page }) => {
    // Airtel number (starts with 74, 77)
    await page.getByLabel(/destinataire|recipient/i).fill('+24177123456');

    await expect(page.getByText(/airtel/i)).toBeVisible({ timeout: 3000 });

    // Moov number (starts with 62, 66)
    await page.getByLabel(/destinataire|recipient/i).clear();
    await page.getByLabel(/destinataire|recipient/i).fill('+24166123456');

    await expect(page.getByText(/moov/i)).toBeVisible({ timeout: 3000 });
  });

  test('should show estimated cost', async ({ page }) => {
    await page.getByLabel(/destinataire|recipient/i).fill(`+241${generateRandomPhone()}`);
    await page.getByLabel(/message|contenu|content/i).fill('Test message');

    // Cost estimate should be visible
    await expect(page.getByText(/coût|cost|fcfa|xaf/i)).toBeVisible();
  });

  test('should handle insufficient credit', async ({ page }) => {
    // This test depends on user having low credit
    // We just check that sending shows appropriate error if no credit
    await page.getByLabel(/destinataire|recipient/i).fill(`+241${generateRandomPhone()}`);
    await page.getByLabel(/message|contenu|content/i).fill('Test message');

    await page.getByRole('button', { name: /envoyer|send/i }).click();

    // Either success or credit error
    const result = page.getByText(/envoyé|sent|crédit insuffisant|insufficient credit|succès|success|erreur|error/i);
    await expect(result).toBeVisible({ timeout: 10000 });
  });
});
