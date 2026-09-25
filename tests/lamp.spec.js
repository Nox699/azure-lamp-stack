const { test, expect } = require('@playwright/test');

test('homepage loads', async ({ page }) => {
  await page.goto('/');
  await expect(page).toHaveTitle('Azure LAMP');
  await expect(page.getByRole('heading', { name: 'LAMP-stack virker!' })).toBeVisible();
});

test('message can be saved and is still visible after reload', async ({ page }) => {
  const marker = `playwright-${Date.now()}`;

  await page.goto('/');
  await page.getByLabel('Navn').fill('Playwright');
  await page.getByLabel('Besked').fill(marker);
  await page.getByRole('button', { name: 'Send' }).click();

  await expect(page.getByText(marker)).toBeVisible();
  await page.reload();
  await expect(page.getByText(marker)).toBeVisible();
});

test('health endpoint confirms database connectivity', async ({ request }) => {
  const response = await request.get('/health.php');
  expect(response.status()).toBe(200);

  const payload = await response.json();
  expect(payload.status).toBe('ok');
  expect(payload.database).toBe('ok');
});
