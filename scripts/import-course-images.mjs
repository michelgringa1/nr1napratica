// Otimiza as imagens da página de vendas (pasta ../imagens) para WebP com nomes
// semânticos em public/images/modulos/. Rodar: node scripts/import-course-images.mjs
import sharp from 'sharp';
import { readdirSync, mkdirSync } from 'node:fs';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const srcDir = join(__dirname, '..', 'imagens');
const outDir = join(__dirname, '..', 'public', 'images', 'modulos');
mkdirSync(outDir, { recursive: true });

const files = readdirSync(srcDir);
const findByIndex = (n) => {
  const re = new RegExp(`^imgi_${n}_`);
  return files.filter((f) => re.test(f) && !f.includes('(1)'))[0];
};

// [índice de origem, nome de saída, largura máx, qualidade]
const jobs = [
  // 12 módulos (cards verticais)
  [4, 'modulo-01-produtividade-sustentavel', 640, 74],
  [5, 'modulo-02-aspectos-juridicos', 640, 74],
  [6, 'modulo-03-comunicacao-assertiva', 640, 74],
  [7, 'modulo-04-gro-na-pratica', 640, 74],
  [8, 'modulo-05-custos-evitados', 640, 74],
  [9, 'modulo-06-riscos-psicossociais', 640, 74],
  [10, 'modulo-07-saude-mental-na-pratica', 640, 74],
  [11, 'modulo-08-epis-da-saude-mental', 640, 74],
  [12, 'modulo-09-pensamento-sistemico', 640, 74],
  [13, 'modulo-10-endividamento', 640, 74],
  [14, 'modulo-11-cultura-organizacional', 640, 74],
  [15, 'modulo-12-seguranca-psicologica', 640, 74],
  // selo e retrato
  [16, 'selo-reconhecido-mec', 280, 82],
  [26, 'izabella-camargo', 680, 80],
];

let ok = 0;
for (const [idx, name, width, quality] of jobs) {
  const src = findByIndex(idx);
  if (!src) {
    console.warn(`⚠️  origem imgi_${idx}_ não encontrada — pulando ${name}`);
    continue;
  }
  await sharp(join(srcDir, src))
    .resize({ width, withoutEnlargement: true })
    .webp({ quality })
    .toFile(join(outDir, `${name}.webp`));
  ok++;
}
console.log(`✓ ${ok}/${jobs.length} imagens otimizadas em public/images/modulos/`);
