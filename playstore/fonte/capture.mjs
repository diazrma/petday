// Captura telas reais do PetDay (dados de demonstração) em viewport de celular 1080x2400.
import { chromium } from '/home/rodrigo/.claude/skills/magento-verify/scripts/node_modules/playwright/index.mjs';
const B = 'http://127.0.0.1:8311', OUT = process.argv[2];
const browser = await chromium.launch();
const ctx = await browser.newContext({
  viewport: { width: 360, height: 800 }, deviceScaleFactor: 3, isMobile: true, hasTouch: true,
  userAgent: 'Mozilla/5.0 (Linux; Android 15; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130 Mobile Safari/537.36 PetDayApp/1.1.0',
  locale: 'pt-BR', colorScheme: 'light',
});
const page = await ctx.newPage();
const errors = [];
page.on('pageerror', e => errors.push(e.message));
page.on('console', m => m.type() === 'error' && errors.push(m.text()));
await page.goto(B + '/entrar');
await page.fill('input[name=email]', 'tutor@petday.test');
await page.fill('input[name=password]', 'password');
await Promise.all([page.waitForURL(u => !u.pathname.startsWith('/entrar')), page.press('input[name=password]', 'Enter')]);
const shots = { feed: '/', perfil: '/pet/thor', posts: '/pet/thor?aba=posts', saude: '/pet/thor?aba=saude', explorar: '/explorar', consultas: '/agendamentos', notificacoes: '/notificacoes' };
for (const [name, path] of Object.entries(shots)) {
  await page.goto(B + path, { waitUntil: 'networkidle' });
  await page.waitForTimeout(600);
  await page.screenshot({ path: `${OUT}/${name}.png` });
  console.log(name, page.url());
}
console.log('erros JS:', errors.length ? errors : 'nenhum');
await browser.close();
