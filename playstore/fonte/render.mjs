// Gera as artes da Play Store a partir das capturas reais (raw/) — ícone, feature graphic e 6 screenshots.
import { chromium } from '/home/rodrigo/.claude/skills/magento-verify/scripts/node_modules/playwright/index.mjs';
import { readFileSync } from 'node:fs';
const S = process.argv[2];
const img = n => 'data:image/png;base64,' + readFileSync(`${S}/raw/${n}.png`).toString('base64');
const FONTS = `<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600;700&family=Nunito:wght@600;700;800&display=block" rel="stylesheet">`;
const PAW = `<svg viewBox="0 0 24 24" fill="#fff"><ellipse cx="6" cy="9.5" rx="2.2" ry="2.8"/><ellipse cx="10" cy="5.5" rx="2.2" ry="2.9"/><ellipse cx="14.5" cy="5.5" rx="2.2" ry="2.9"/><ellipse cx="18.3" cy="9.7" rx="2.2" ry="2.8"/><path d="M12.2 11.2c-2.9 0-6.4 3.7-6.4 6.5 0 1.8 1.4 2.8 3 2.8 1.3 0 2.2-.7 3.4-.7s2.1.7 3.4.7c1.6 0 3-1 3-2.8 0-2.8-3.5-6.5-6.4-6.5z"/></svg>`;
// patinhas decorativas espalhadas no fundo
const pawsBg = (n, seed, op) => { let r = seed, h = ''; const rnd = () => (r = (r * 9301 + 49297) % 233280) / 233280;
  for (let i = 0; i < n; i++) h += `<div style="position:absolute;left:${rnd()*100}%;top:${rnd()*100}%;width:${40+rnd()*70}px;opacity:${op};transform:rotate(${rnd()*80-40}deg)">${PAW}</div>`; return h; };
const BASE = `*{margin:0;box-sizing:border-box}body{font-family:Nunito,sans-serif;-webkit-font-smoothing:antialiased}`;
const phone = (shot, w) => `<div style="width:${w}px;padding:${w*0.028}px;border-radius:${w*0.13}px;background:#1c1917;box-shadow:0 40px 80px -20px rgba(80,20,0,.55),0 0 0 3px rgba(255,255,255,.08) inset">
  <div style="border-radius:${w*0.105}px;overflow:hidden;line-height:0;position:relative"><img src="${shot}" style="width:100%"><div style="position:absolute;top:${w*0.022}px;left:50%;transform:translateX(-50%);width:${w*0.035}px;height:${w*0.035}px;border-radius:50%;background:#0c0a09"></div></div></div>`;

const SHOTS = [
  ['feed', '#fb923c', '#ea580c', 'A rede social<br>do seu pet', 'Fotos, rastros e patinhas de quem você ama'],
  ['diario', '#f472b6', '#db2777', 'Um diário com<br>cada dia dele', 'Calendário de momentos, humor e marcos'],
  ['story', '#a78bfa', '#7c3aed', 'Rastros que<br>somem em 24h', 'Stories com stickers, reações e recados'],
  ['feed-post', '#38bdf8', '#0284c7', 'Aqui é só pet,<br>garantido!', 'Nossa IA confere se tem um bichinho na foto'],
  ['saude', '#34d399', '#059669', 'Vacinas e consultas<br>em dia', 'Carteirinha de saúde e lembretes automáticos'],
  ['explorar', '#facc15', '#ea580c', 'Descubra novos<br>amigos', 'Siga pets, explore raças e momentos'],
];
const shotHtml = ([n, c1, c2, title, sub], i) => `<html><head>${FONTS}<style>${BASE}</style></head><body>
<div style="width:1080px;height:1920px;position:relative;overflow:hidden;background:linear-gradient(160deg,${c1},${c2})">
  ${pawsBg(14, i * 7 + 3, 0.10)}
  <div style="position:relative;text-align:center;padding:110px 70px 0;color:#fff">
    <h1 style="font-family:Fredoka;font-weight:700;font-size:92px;line-height:1.02;letter-spacing:-1px;text-shadow:0 4px 18px rgba(0,0,0,.12)">${title}</h1>
    <p style="margin-top:26px;font-size:40px;font-weight:700;opacity:.95">${sub}</p>
  </div>
  <div style="position:absolute;left:50%;top:560px;transform:translateX(-50%)">${phone(img(n), 760)}</div>
</div></body></html>`;

const feature = `<html><head>${FONTS}<style>${BASE}</style></head><body>
<div style="width:1024px;height:500px;position:relative;overflow:hidden;background:linear-gradient(135deg,#fdba74 0%,#f97316 45%,#ea580c 100%)">
  ${pawsBg(12, 11, 0.12)}
  <div style="position:absolute;left:64px;top:0;bottom:0;display:flex;flex-direction:column;justify-content:center;color:#fff;width:520px">
    <div style="display:flex;align-items:center;gap:20px">
      <div style="width:96px;height:96px;border-radius:28px;background:#fff;display:grid;place-items:center;transform:rotate(-6deg);box-shadow:0 12px 30px -8px rgba(120,40,0,.5)"><div style="width:60px">${PAW.replace('#fff', '#ea580c')}</div></div>
      <span style="font-family:Fredoka;font-weight:700;font-size:84px;letter-spacing:-1px">PetDay</span>
    </div>
    <p style="margin-top:22px;font-family:Fredoka;font-weight:600;font-size:40px;line-height:1.1">A rede social e o diário<br>do seu pet</p>
  </div>
  <div style="position:absolute;right:170px;top:70px;transform:rotate(8deg)">${phone(img('diario'), 230)}</div>
  <div style="position:absolute;right:36px;top:40px;transform:rotate(-4deg)">${phone(img('feed'), 250)}</div>
</div></body></html>`;

// Ícone: quadrado cheio (a Play Store aplica a máscara arredondada)
const icon = `<html><head><style>${BASE}</style></head><body>
<div style="width:512px;height:512px;background:linear-gradient(135deg,#fb923c,#ea580c);display:grid;place-items:center">
  <div style="width:300px;transform:rotate(-6deg);filter:drop-shadow(0 10px 18px rgba(120,40,0,.35))">${PAW}</div></div></body></html>`;

const browser = await chromium.launch();
async function render(html, w, h, file, type = 'png') {
  const p = await browser.newPage({ viewport: { width: w, height: h } });
  await p.setContent(html, { waitUntil: 'networkidle' });
  await p.evaluate(() => document.fonts.ready);
  await p.screenshot({ path: file, type, ...(type === 'jpeg' ? { quality: 95 } : {}), omitBackground: false });
  const fonts = await p.evaluate(() => [...document.fonts].filter(f => f.status === 'loaded').map(f => f.family + ' ' + f.weight));
  await p.close(); console.log(file.split('/').pop(), '| fontes:', [...new Set(fonts)].join(', ') || 'nenhuma');
}
await render(icon, 512, 512, `${S}/out/icone-512.png`);
await render(feature, 1024, 500, `${S}/out/feature-graphic-1024x500.png`);
for (const [i, s] of SHOTS.entries()) await render(shotHtml(s, i), 1080, 1920, `${S}/out/screenshot-${i + 1}-${s[0]}.png`);
await browser.close();
