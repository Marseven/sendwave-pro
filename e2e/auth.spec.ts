import { test, expect } from '@playwright/test';
import { TEST_USER } from './fixtures';

test.describe('Authentication', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/login');
  });

  test('should display login page', async ({ page }) => {
    await expect(page.getByRole('heading', { name: /connexion/i })).toBeVisible();
    await expect(page.getByLabel('Email')).toBeVisible();
    await expect(page.getByLabel('Mot de passe')).toBeVisible();
    await expect(page.getByRole('button', { name: /connexion/i })).toBeVisible();
  });

  test('should show error with invalid credentials', async ({ page }) => {
    await page.getByLabel('Email').fill('invalid@example.com');
    await page.getByLabel('Mot de passe').fill('wrongpassword');
    await page.getByRole('button', { name: /connexion/i }).click();

    // Wait for error message
    await expect(page.getByText(/identifiants incorrects|invalid credentials/i)).toBeVisible({ timeout: 5000 });
  });

  test('should require email field', async ({ page }) => {
    await page.getByLabel('Mot de passe').fill('password123');
    await page.getByRole('button', { name: /connexion/i }).click();

    // Check for validation error
    const emailInput = page.getByLabel('Email');
    await expect(emailInput).toHaveAttribute('required', '');
  });

  test('should require password field', async ({ page }) => {
    await page.getByLabel('Email').fill('test@example.com');
    await page.getByRole('button', { name: /connexion/i }).click();

    // Check for validation error
    const passwordInput = page.getByLabel('Mot de passe');
    await expect(passwordInput).toHaveAttribute('required', '');
  });

  test('should login successfully with valid credentials', async ({ page }) => {
    await page.getByLabel('Email').fill(TEST_USER.email);
    await page.getByLabel('Mot de passe').fill(TEST_USER.password);
    await page.getByRole('button', { name: /connexion/i }).click();

    // Should redirect to dashboard
    await page.waitForURL('**/dashboard', { timeout: 10000 });
    await expect(page).toHaveURL(/.*dashboard/);
  });

  test('should redirect to login when accessing protected route', async ({ page }) => {
    // Try to access dashboard without being logged in
    await page.goto('/dashboard');

    // Should be redirected to login
    await expect(page).toHaveURL(/.*login/);
  });

  test('should logout successfully', async ({ page }) => {
    // First login
    await page.getByLabel('Email').fill(TEST_USER.email);
    await page.getByLabel('Mot de passe').fill(TEST_USER.password);
    await page.getByRole('button', { name: /connexion/i }).click();
    await page.waitForURL('**/dashboard', { timeout: 10000 });

    // Click logout button (usually in a dropdown or sidebar)
    await page.getByRole('button', { name: /déconnexion|logout/i }).click();

    // Should be redirected to login
    await expect(page).toHaveURL(/.*login/);
  });
});
