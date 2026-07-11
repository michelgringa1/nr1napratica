// Gera infográficos PNG em public/images/. Rodar: node scripts/make-images.mjs
import sharp from 'sharp';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';
import { mkdirSync } from 'node:fs';

const __dirname = dirname(fileURLToPath(import.meta.url));
const outDir = join(__dirname, '..', 'public', 'images');
mkdirSync(outDir, { recursive: true });

// ---- Linha do tempo NR-1 / riscos psicossociais ----
const marcos = [
  ['Ago/2024', 'Portaria 1.419/2024', 'Riscos psicossociais na NR-1'],
  ['Mai/2025', 'Portaria 765/2025', 'Adiamento + fase educativa'],
  ['26/05/2026', 'Obrigação em vigor', 'Fim da fase educativa'],
  ['Jun/2026', 'STF suspende multas', 'Por 90 dias; regra continua'],
];

const W = 1200;
const H = 520;
const pad = 155;
const step = (W - pad * 2) / (marcos.length - 1);

const nodes = marcos
  .map((m, i) => {
    const x = pad + step * i;
    const y = 250;
    const up = i % 2 === 0;
    const boxY = up ? y - 180 : y + 40;
    const [data, titulo, desc] = m;
    return `
      <line x1="${x}" y1="${y}" x2="${x}" y2="${up ? boxY + 140 : boxY}" stroke="#c9d6e8" stroke-width="2"/>
      <circle cx="${x}" cy="${y}" r="12" fill="#1b4b8a" stroke="#fff" stroke-width="3"/>
      <rect x="${x - 135}" y="${boxY}" width="270" height="140" rx="12" fill="#ffffff" stroke="#d7dce4" stroke-width="1.5"/>
      <text x="${x}" y="${boxY + 40}" font-family="Arial, sans-serif" font-size="24" font-weight="800" fill="#1b4b8a" text-anchor="middle">${data}</text>
      <text x="${x}" y="${boxY + 76}" font-family="Arial, sans-serif" font-size="19" font-weight="700" fill="#0f2540" text-anchor="middle">${titulo}</text>
      <text x="${x}" y="${boxY + 108}" font-family="Arial, sans-serif" font-size="15" fill="#3d4658" text-anchor="middle">${desc}</text>`;
  })
  .join('');

const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="${W}" height="${H}" viewBox="0 0 ${W} ${H}">
  <rect width="${W}" height="${H}" fill="#f3f6fa"/>
  <text x="${W / 2}" y="46" font-family="Arial, sans-serif" font-size="28" font-weight="800" fill="#0f2540" text-anchor="middle">NR-1 e riscos psicossociais — linha do tempo (2024–2026)</text>
  <line x1="${pad}" y1="250" x2="${W - pad}" y2="250" stroke="#1b4b8a" stroke-width="4"/>
  ${nodes}
  <text x="${W / 2}" y="${H - 18}" font-family="Arial, sans-serif" font-size="15" fill="#6b7280" text-anchor="middle">nr1napratica.online — obrigação de gerir os riscos continua; apenas as multas estão temporariamente suspensas</text>
</svg>`;

await sharp(Buffer.from(svg))
  .png()
  .toFile(join(outDir, 'linha-do-tempo-nr1-riscos-psicossociais.png'));
console.log('Infográfico gerado.');
