import { chromium } from '/home/rodrigo/.claude/skills/magento-verify/scripts/node_modules/playwright/index.mjs';
const B = 'http://127.0.0.1:8311', OUT = process.argv[2];
const browser = await chromium.launch();
const ctx = await browser.newContext({ viewport: { width: 360, height: 800 }, deviceScaleFactor: 3, isMobile: true, hasTouch: true,
  userAgent: 'Mozilla/5.0 (Linux; Android 15; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130 Mobile Safari/537.36 PetDayApp/1.1.0', locale: 'pt-BR' });
const page = await ctx.newPage(); const errors = [];
page.on('pageerror', e => errors.push(e.message));
await page.goto(B + '/entrar'); await page.fill('input[name=email]', 'tutor@petday.test'); await page.fill('input[name=password]', 'password');
await Promise.all([page.waitForURL(u => !u.pathname.startsWith('/entrar')), page.press('input[name=password]', 'Enter')]);
async function shot(name, path, scrollSel) {
  await page.goto(B + path, { waitUntil: 'networkidle' });
  if (scrollSel) await page.evaluate(s => { const el = typeof s === 'number' ? null : document.querySelector(s); window.scrollTo(0, el ? el.getBoundingClientRect().top + scrollY - 70 : s); }, scrollSel);
  await page.waitForTimeout(700); await page.screenshot({ path: `${OUT}/${name}.png` }); console.log(name);
}
await shot('consultas', '/agendamentos');
await shot('diario', '/pet/thor', 560);
await shot('posts', '/pet/thor?aba=posts', 560);
await shot('saude', '/pet/thor?aba=saude', 560);
await shot('feed-post', '/', 470);
// story de algum pet que tenha
await page.goto(B + '/', { waitUntil: 'networkidle' });
const story = await page.$$eval('a[href*="/stories/"]', as => as.map(a => a.getAttribute('href')));
console.log('stories:', story.slice(0, 5));
if (story[0]) { await page.goto(new URL(story[0], B).href, { waitUntil: 'networkidle' }); await page.waitForTimeout(900); await page.screenshot({ path: `${OUT}/story.png` }); console.log('story'); }
console.log('erros JS:', errors.length ? errors : 'nenhum'); await browser.close();
