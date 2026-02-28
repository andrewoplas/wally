import { chromium } from 'playwright';

const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: 1440, height: 900 } });
await page.goto('http://localhost:3001/', { waitUntil: 'networkidle' });

await page.screenshot({ path: './screenshots/full.png', fullPage: true });

const sections = [
  { name: 'hero', clip: { x: 0, y: 0, width: 1440, height: 900 } },
  { name: 'problem', selector: 'section:nth-of-type(2)' },
  { name: 'video-demo', selector: 'section:nth-of-type(3)' },
  { name: 'how-it-works', selector: '#features' },
  { name: 'who-its-for', selector: 'section:nth-of-type(5)' },
  { name: 'trust-safety', selector: 'section:nth-of-type(6)' },
  { name: 'pricing', selector: '#pricing' },
  { name: 'final-cta', selector: 'section:nth-of-type(8)' },
  { name: 'footer', selector: 'footer' },
];

for (const s of sections) {
  try {
    if (s.selector) {
      const el = page.locator(s.selector).first();
      await el.screenshot({ path: `./screenshots/${s.name}.png` });
    } else {
      await page.screenshot({ path: `./screenshots/${s.name}.png`, clip: s.clip });
    }
    console.log(`Captured: ${s.name}`);
  } catch (e) {
    console.error(`Failed: ${s.name} - ${e.message}`);
  }
}

await browser.close();
console.log('Done!');
