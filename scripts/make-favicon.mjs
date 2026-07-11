// Gera o favicon genérico (escudo + check) em SVG + PNGs. Rodar: node scripts/make-favicon.mjs
import sharp from 'sharp';
import { writeFileSync } from 'node:fs';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const pub = join(__dirname, '..', 'public');

// Ícone genérico e sóbrio: escudo (segurança/norma) com check, na cor da marca.
const svg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" role="img" aria-label="NR1 na Prática">
  <rect width="64" height="64" rx="14" fill="#0f2540"/>
  <path d="M32 12 L50 19 V33 C50 44 42 51 32 55 C22 51 14 44 14 33 V19 Z" fill="#2563a8"/>
  <path d="M23.5 32.5 l6 6 l12 -14" fill="none" stroke="#ffffff" stroke-width="4.2" stroke-linecap="round" stroke-linejoin="round"/>
</svg>`;

writeFileSync(join(pub, 'favicon.svg'), svg);
await sharp(Buffer.from(svg)).resize(180, 180).png().toFile(join(pub, 'apple-touch-icon.png'));
await sharp(Buffer.from(svg)).resize(32, 32).png().toFile(join(pub, 'favicon-32x32.png'));
await sharp(Buffer.from(svg)).resize(16, 16).png().toFile(join(pub, 'favicon-16x16.png'));
console.log('✓ favicon gerado (svg + apple-touch-icon + 32/16 png)');
