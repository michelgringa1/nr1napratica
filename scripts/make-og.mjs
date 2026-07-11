// Gera public/og-default.png (1200x630) a partir de um SVG branded.
// Rodar: node scripts/make-og.mjs
import sharp from 'sharp';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';

const __dirname = dirname(fileURLToPath(import.meta.url));
const out = join(__dirname, '..', 'public', 'og-default.png');

const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="630" viewBox="0 0 1200 630">
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="#0f2540"/>
      <stop offset="1" stop-color="#1b4b8a"/>
    </linearGradient>
  </defs>
  <rect width="1200" height="630" fill="url(#bg)"/>
  <rect x="80" y="470" width="70" height="70" rx="14" fill="#1b4b8a" stroke="#3b82c4" stroke-width="3"/>
  <text x="115" y="516" font-family="Arial, sans-serif" font-size="26" font-weight="700" fill="#ffffff" text-anchor="middle">NR1</text>
  <text x="80" y="150" font-family="Arial, sans-serif" font-size="30" font-weight="700" letter-spacing="4" fill="#3b82c4">PORTAL INDEPENDENTE</text>
  <text x="80" y="300" font-family="Arial, sans-serif" font-size="88" font-weight="800" fill="#ffffff">NR-1 em 2026</text>
  <text x="80" y="390" font-family="Arial, sans-serif" font-size="44" font-weight="600" fill="#d7e5f5">Riscos psicossociais, prazos e carreira</text>
  <text x="175" y="516" font-family="Arial, sans-serif" font-size="34" font-weight="700" fill="#ffffff">nr1napratica.online</text>
</svg>`;

await sharp(Buffer.from(svg)).png().toFile(out);
console.log('OG gerado em', out);
