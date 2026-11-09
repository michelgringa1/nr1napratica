#!/usr/bin/env node
// Trava do rastreio /go/ para o workflow do projeto (roda no GitHub, sem depender do Gestor).
// Copie este arquivo para scripts/trava-go.mjs do projeto, ao lado do go-map.json.
//
//   node scripts/trava-go.mjs <pasta publicada> --map scripts/go-map.json [--permitir-diretos url1,url2] [--afiliado-por-query] [--s-opcional]
//
// Flags opcionais (desligadas por padrao; sem elas o comportamento e o de sempre. Vieram de projetos e foram promovidas ao kit em 01/11/2026):
//   --afiliado-por-query  so e link de afiliado o que leva a query de parceiro do destino do mapa (last_source_level_*, aff, cpg...). Use quando o
//                         site tem link OFICIAL para o mesmo origem+caminho do destino, sem a query (Lance Ton: livementor.com/mission/ sem a query
//                         e link comum; a pagina nova do executor nao pode travar o deploy por falso positivo). Destino do mapa SEM query: continua barrando.
//   --s-opcional          aceita botao /go/ SEM ?s= (Myron: 33 botoes sem posicao). Se o ?s= existir, tem de ser valido.
//
// Sai com 1 (nada vai ao ar) se:
//   - api/go.php nao tiver cada produto do go-map.json exatamente como o gerar-go.mjs escreve (mapa mudou e o PHP nao foi gerado de novo);
//   - api/go-stats.php nao existir ou nao tiver o hash da chave;
//   - o .htaccess nao tiver a regra ^go/ ou o robots.txt nao tiver "Disallow: /go/";
//   - houver /go/<slug>/ fora do mapa, botao /go/ sem rel "sponsored" e "nofollow", ou parametro s invalido;
//   - sobrar link DIRETO de afiliado (href igual a origem+caminho de algum destino do mapa). Home e termos do produto
//     (outro caminho) nao contam; para liberar um endereco exato use --permitir-diretos;
//   - o log de cliques (go-log.csv, pasta privado/) estiver dentro da pasta publicada (o log mora AO LADO de public_html).

import { readdirSync, readFileSync, statSync, existsSync } from 'node:fs';
import { join, relative, resolve } from 'node:path';

const args = process.argv.slice(2);
const valor = (f) => { const i = args.indexOf(f); return i >= 0 ? args[i + 1] : null; };
const pasta = args.find((a) => !a.startsWith('--') && statSync(a, { throwIfNoEntry: false })?.isDirectory());
const arqMapa = valor('--map');
if (!pasta || !arqMapa) { console.error('uso: node scripts/trava-go.mjs <pasta publicada> --map scripts/go-map.json [--permitir-diretos url1,url2] [--afiliado-por-query] [--s-opcional]'); process.exit(2); }

const erros = [];
const erro = (m) => erros.push(m);
const raiz = resolve(pasta);
const mapa = JSON.parse(readFileSync(arqMapa, 'utf8')).produtos || {};
const slugs = Object.keys(mapa);
if (!slugs.length) erro('go-map.json sem produtos.');

const php = (v) => `'${String(v).replace(/\\/g, '\\\\').replace(/'/g, "\\'")}'`;
const base = (u) => { try { const x = new URL(u); return `${x.protocol}//${x.host.toLowerCase().replace(/^www\./, '')}${x.pathname.replace(/\/+$/, '')}`; } catch { return u; } };
const bases = new Map(slugs.map((s) => [base(mapa[s].url), s]));
const permitidos = new Set((valor('--permitir-diretos') || '').split(',').filter(Boolean).map((u) => base(u.trim())));
const sOpcional = args.includes('--s-opcional');
const porQuery = args.includes('--afiliado-por-query');
const chavesQuery = (u) => { try { return [...new URL(u).searchParams.keys()]; } catch { return []; } };
const temQueryDeParceiro = (href, slug) => { const ks = chavesQuery(mapa[slug].url); if (!ks.length) return true; try { const q = new URL(href).searchParams; return ks.some((k) => q.has(k)); } catch { return true; } };

// 1) api/go.php bate com o mapa
const goPhp = join(raiz, 'api', 'go.php');
if (!existsSync(goPhp)) erro('api/go.php ausente (rode Gestor/go/gerar-go.mjs).');
else {
  const txt = readFileSync(goPhp, 'utf8');
  for (const s of slugs) {
    const p = mapa[s];
    const linha = `${php(s)} => [${php(p.url)}, ${php(p.subParam || '')}, ${php(p.subPrefix || '')}],`;
    if (!txt.includes(linha)) erro(`api/go.php nao bate com o go-map.json no produto "${s}" (gere de novo com gerar-go.mjs).`);
  }
  const noPhp = [...txt.matchAll(/^\s*'([a-z0-9-]+)' => \['/gm)].map((m) => m[1]);
  for (const s of noPhp) if (!mapa[s]) erro(`api/go.php tem o produto "${s}" que nao esta no go-map.json.`);
}
// 2) api/go-stats.php
const stats = join(raiz, 'api', 'go-stats.php');
if (!existsSync(stats)) erro('api/go-stats.php ausente.');
else if (!/\$hash = "[0-9a-f]{64}";/.test(readFileSync(stats, 'utf8'))) erro('api/go-stats.php sem o hash da chave (SHA-256).');
// 3) .htaccess e robots.txt
const ht = join(raiz, '.htaccess');
if (!existsSync(ht) || !/^\s*RewriteRule\s+\^go\/\(\[a-z0-9-\]\{1,40\}\)\/\?\$\s+\/api\/go\.php\?p=\$1\s+\[L,QSA\]/m.test(readFileSync(ht, 'utf8'))) erro('.htaccess sem a regra: RewriteRule ^go/([a-z0-9-]{1,40})/?$ /api/go.php?p=$1 [L,QSA]');
const rb = join(raiz, 'robots.txt');
if (!existsSync(rb) || !/^Disallow:\s*\/go\/\s*$/m.test(readFileSync(rb, 'utf8'))) erro('robots.txt sem "Disallow: /go/".');
// 4) log nunca na pasta publicada
if (existsSync(join(raiz, 'privado'))) erro('pasta privado/ dentro da pasta publicada: o log mora AO LADO de public_html.');

function* html(dir) {
  for (const f of readdirSync(dir)) {
    if (/^(node_modules|\.git|\.github)$/.test(f)) continue;
    const p = join(dir, f);
    if (statSync(p).isDirectory()) yield* html(p);
    else if (/\.html?$/i.test(f)) yield p;
  }
}
let botoes = 0;
for (const arq of html(raiz)) {
  const rel = '/' + relative(raiz, arq).replace(/\\/g, '/');
  const txt = readFileSync(arq, 'utf8');
  for (const m of txt.matchAll(/<a\b[^>]*>/gi)) {
    const tag = m[0];
    const h = (tag.match(/\bhref\s*=\s*"([^"]*)"/i) || [])[1];
    if (!h) continue;
    const href = h.replace(/&amp;/g, '&');
    const go = href.match(/^\/go\/([a-z0-9-]{1,40})\/?(?:\?(.*))?$/i);
    if (go) {
      botoes++;
      if (!mapa[go[1]]) erro(`${rel}: /go/${go[1]}/ fora do go-map.json`);
      const s = new URLSearchParams(go[2] || '').get('s');
      if (s === null ? !sOpcional : !/^[a-z0-9_-]{1,40}$/.test(s)) erro(`${rel}: /go/${go[1]}/ com s ausente ou invalido (${s ?? 'sem s'}). Use a-z, 0-9, _ e - (ate 40).`);
      const relAttr = (tag.match(/\brel\s*=\s*"([^"]*)"/i) || [, ''])[1].toLowerCase().split(/\s+/);
      if (!relAttr.includes('sponsored') || !relAttr.includes('nofollow')) erro(`${rel}: botao /go/${go[1]}/ sem rel="sponsored nofollow noopener".`);
      continue;
    }
    if (/^https?:\/\//i.test(href)) {
      const b = base(href);
      if (bases.has(b) && !permitidos.has(b) && (!porQuery || temQueryDeParceiro(href, bases.get(b)))) erro(`${rel}: link DIRETO de afiliado (${href}); deveria ser /go/${bases.get(b)}/?s=<pagina>_<posicao>.`);
    }
  }
}
if (botoes === 0) erro('nenhum botao /go/ encontrado na pasta publicada.');

if (erros.length) { console.error(`TRAVA /go/: ${erros.length} ERRO(S)`); erros.slice(0, 40).forEach((e) => console.error('  - ' + e)); process.exit(1); }
console.log(`TRAVA /go/ ok: ${slugs.length} produto(s) no mapa, ${botoes} botao(oes) /go/, nenhum link direto de afiliado.`);
