#!/usr/bin/env node
// Trava de publicação para sites SEM gerador próprio (HTML escrito à mão), modelo A.
// Roda no GitHub Actions antes de publicar e também na máquina do Michel.
// Sai com código 1 só em ERRO: se falhar, nada vai ao ar. AVISO não bloqueia.
//
//   node scripts/gate.mjs <pasta-publicada> [flags]      (a pasta vem PRIMEIRO)
//
//   --sem-travessao | --sem-travessao-ambos   proíbe o travessão longo (—) | longo e curto (— e –) no texto visível
//   --travessao-bruto                          procura o travessão no arquivo inteiro (meta, alt, JSON-LD, sitemap), não só no texto visível
//   --estrito                                  link interno quebrado, URL do sitemap sem arquivo e canônica errada viram ERRO (padrão: AVISO)
//   --dominio https://site.com                 confere o domínio das URLs do sitemap e das canônicas
//   --ignorar-links /ir/,/go/,/api/            prefixos de link interno que não têm arquivo na pasta (redirecionadores)
//   --excluir-tom sobre,legal   --permitir-tom goodreads   --sem-tom   --tom-como-aviso   (scanner de tom comercial)
//
// ERROS: home ausente; JSON-LD ilegível; padrão de credencial em .html/.js/.json/.txt/.xml/.css; home com noindex;
//        travessão (se pedido); linguagem negativa (scanner de tom, a menos que --tom-como-aviso);
//        com --estrito: link/imagem interna sem arquivo, URL do sitemap sem arquivo ou fora do domínio,
//        canônica que não aponta para a própria página ou fica fora do domínio.
// AVISOS: título, meta description ou canônica faltando; canônica fora do sitemap; e tudo o que o --estrito eleva a erro.
//
// Como o servidor resolve um endereço (igual ao .htaccess dos sites): arquivo exato, pasta/index.html ou endereço + ".html".
// Para sites com gerador, o gate fica no projeto (build/seo-gate.mjs); este arquivo é o mínimo comum.

import { readFileSync, readdirSync, statSync, existsSync } from 'node:fs';
import { join, relative, dirname, resolve } from 'node:path';
import { spawnSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const args = process.argv.slice(2);
const COM_VALOR = ['--excluir-tom', '--permitir-tom', '--ignorar-links', '--dominio'];
const pasta = args.find((a, i) => !a.startsWith('--') && !COM_VALOR.includes(args[i - 1]));
const flag = (f) => args.includes(f);
const valor = (f) => { const i = args.indexOf(f); return i >= 0 ? args[i + 1] : null; };
if (!pasta || !existsSync(pasta)) { console.error('Informe a pasta publicada. Ex.: node scripts/gate.mjs build'); process.exit(2); }

const erros = [], avisos = [];
const erro = (arq, msg) => erros.push(`${arq}: ${msg}`);
const aviso = (arq, msg) => avisos.push(`${arq}: ${msg}`);
const estr = (arq, msg) => (flag('--estrito') ? erro : aviso)(arq, msg);
const ignorarLinks = (valor('--ignorar-links') || '').split(',').filter(Boolean);
let dominio = null;
if (valor('--dominio')) { try { dominio = new URL(valor('--dominio')); } catch { console.error('--dominio precisa ser uma URL completa. Ex.: https://site.com'); process.exit(2); } }

// ---------- arquivos
const todos = [];
(function andar(p) {
  for (const f of readdirSync(p)) {
    if (f.startsWith('.') && f !== '.htaccess') continue;
    if (f === 'node_modules') continue;
    const c = join(p, f);
    if (statSync(c).isDirectory()) andar(c); else todos.push(c);
  }
})(pasta);
const htmls = todos.filter((f) => /\.html?$/i.test(f));
const sitemaps = todos.filter((f) => /sitemap.*\.xml$/i.test(f));
const rel = (f) => relative(pasta, f).replace(/\\/g, '/');
const raiz = resolve(pasta);

if (!existsSync(join(pasta, 'index.html'))) erro('index.html', 'a home não existe na pasta publicada');

// ---------- credenciais (qualquer .html, .js, .json, .txt, .xml, .css)
const CREDENCIAIS = [
  [/xkeysib-[a-f0-9]{20,}/i, 'chave do Brevo'],
  [/\bEAA[A-Za-z0-9]{40,}/, 'token da Meta'],
  [/\bAIza[0-9A-Za-z_-]{30,}/, 'chave de API do Google'],
  [/\bsk-ant-[A-Za-z0-9_-]{20,}/, 'chave da Anthropic'],
  [/\bsk-[A-Za-z0-9]{40,}/, 'chave de API (sk-)'],
  [/\b\d{8,10}:AA[A-Za-z0-9_-]{30,}/, 'token de bot do Telegram'],
  [/-----BEGIN (RSA |EC )?PRIVATE KEY-----/, 'chave privada'],
];
for (const f of todos.filter((x) => /\.(html?|js|mjs|json|txt|xml|css)$/i.test(x))) {
  let txt; try { txt = readFileSync(f, 'utf8'); } catch { continue; }
  for (const [re, nome] of CREDENCIAIS) if (re.test(txt)) erro(rel(f), `parece conter ${nome}`);
}

// ---------- resolução de endereços
const decodificar = (s) => { try { return decodeURIComponent(s); } catch { return s; } };
// arquivo que o servidor entrega para um caminho absoluto no disco, ou null (inclusive se sair da pasta publicada)
const arquivoDe = (alvo) => {
  if (relative(raiz, alvo).startsWith('..')) return null;
  if (existsSync(alvo) && statSync(alvo).isFile()) return alvo;
  const idx = join(alvo, 'index.html');
  if (existsSync(idx)) return idx;
  if (existsSync(alvo + '.html')) return alvo + '.html';
  return null;
};
// endereço do site ("/reviews/x", "/", "/a.html") -> arquivo
const resolverCaminho = (p) => {
  const limpo = decodificar(p.split('#')[0].split('?')[0]);
  return arquivoDe(resolve(raiz, '.' + (limpo.startsWith('/') ? limpo : '/' + limpo)));
};

// ---------- sitemap
const sitemapLocs = new Set();
for (const f of sitemaps) {
  for (const m of readFileSync(f, 'utf8').matchAll(/<loc>\s*([^<\s]+)\s*<\/loc>/g)) sitemapLocs.add(m[1]);
}

// ---------- travessão
const travessaoAmbos = flag('--sem-travessao-ambos');
const proibeTravessao = flag('--sem-travessao') || travessaoAmbos || flag('--travessao-bruto');
const reTravessao = travessaoAmbos ? /.{0,30}[—–].{0,30}/ : /.{0,30}—.{0,30}/;
if (flag('--travessao-bruto')) {
  // arquivo inteiro: pega também meta description, alt, JSON-LD e o sitemap
  const travessoes = travessaoAmbos ? /[—–]/g : /—/g;
  for (const f of [...htmls, ...sitemaps]) {
    const txt = readFileSync(f, 'utf8');
    const n = (txt.match(travessoes) || []).length;
    if (n) erro(rel(f), `travessão no arquivo (${n}x): "${(txt.match(reTravessao) || [''])[0].trim()}"`);
  }
}

// ---------- cada página
for (const f of htmls) {
  const r = rel(f);
  const html = readFileSync(f, 'utf8');
  const noindex = /<meta[^>]+name=["']robots["'][^>]+content=["'][^"']*noindex/i.test(html);
  const institucional = /FINALIDADE:\s*(Institucional|Anúncio|Anuncio)/i.test(html);

  // JSON-LD precisa ser JSON válido
  for (const m of html.matchAll(/<script[^>]+type=["']application\/ld\+json["'][^>]*>([\s\S]*?)<\/script>/gi)) {
    try { JSON.parse(m[1]); } catch (e) { erro(r, `JSON-LD ilegível (${e.message.slice(0, 60)})`); }
  }

  if (r === 'index.html' && noindex) erro(r, 'a home está com noindex');

  if (!noindex && !institucional) {
    if (!/<title[^>]*>[^<]+<\/title>/i.test(html)) aviso(r, 'sem <title>');
    if (!/<meta[^>]+name=["']description["']/i.test(html)) aviso(r, 'sem meta description');
    // a ordem dos atributos varia (rel antes ou depois de href): acha a tag <link> e lê o href dentro dela
    const tagCan = html.match(/<link\b[^>]*\brel=["']canonical["'][^>]*>/i)?.[0];
    const can = tagCan?.match(/\bhref=["']([^"']+)["']/i)?.[1];
    if (!can) aviso(r, 'sem canônica');
    else {
      if (sitemapLocs.size && !sitemapLocs.has(can) && !sitemapLocs.has(can.replace(/\/$/, ''))) aviso(r, `canônica ${can} não está no sitemap`);
      let u = null; try { u = new URL(can, dominio || 'https://sem-dominio.invalid'); } catch { /* inválida */ }
      if (!u) estr(r, `canônica inválida: ${can}`);
      else if (dominio && u.origin !== dominio.origin) estr(r, `canônica fora do domínio: ${can}`);
      else if (resolverCaminho(u.pathname) !== resolve(f)) estr(r, `canônica ${can} não aponta para esta página`);
    }
  }

  // travessão em texto visível (só nos projetos que proíbem; o modo bruto já olhou o arquivo inteiro)
  // --sem-travessao proíbe só o longo (—), regra de Myron e ReviewCast; --sem-travessao-ambos proíbe também o curto (–), regra global.
  if (proibeTravessao && !flag('--travessao-bruto')) {
    const visivel = html.replace(/<script[\s\S]*?<\/script>/gi, '').replace(/<style[\s\S]*?<\/style>/gi, '').replace(/<!--[\s\S]*?-->/g, '').replace(/<[^>]+>/g, ' ');
    const m = visivel.match(reTravessao);
    if (m) erro(r, `travessão no texto: "${m[0].trim()}"`);
  }

  // links e imagens internos (href e src; absolutos "/x" e relativos "x", "../x"), sem olhar o conteúdo de <script>
  const semScripts = html.replace(/<!--[\s\S]*?-->/g, '').replace(/(<script\b[^>]*>)[\s\S]*?<\/script>/gi, '$1</script>');
  for (const m of semScripts.matchAll(/\s(?:href|src)=["']([^"']+)["']/gi)) {
    const ref = m[1].trim();
    if (/^([a-z][a-z0-9+.-]*:|\/\/|#|\{)/i.test(ref)) continue;   // https:, mailto:, tel:, //cdn, #âncora, {{modelo}}
    const caminho = ref.split('#')[0].split('?')[0];
    if (!caminho) continue;
    if (caminho.startsWith('/') && ignorarLinks.some((i) => caminho.startsWith(i))) continue;
    const alvo = caminho.startsWith('/') ? resolverCaminho(caminho) : arquivoDe(resolve(dirname(f), decodificar(caminho)));
    if (!alvo) estr(r, `link interno sem arquivo: ${ref}`);
  }
}

for (const u of sitemapLocs) {
  let url; try { url = new URL(u); } catch { estr('sitemap', `URL inválida: ${u}`); continue; }
  if (dominio && url.origin !== dominio.origin) { estr('sitemap', `URL fora do domínio: ${u}`); continue; }
  if (!resolverCaminho(url.pathname)) estr('sitemap', `URL sem arquivo: ${url.pathname}`);
}

// ---------- tom comercial (cópia do scanner global, na mesma pasta deste arquivo)
if (!flag('--sem-tom')) {
  const scanner = join(dirname(fileURLToPath(import.meta.url)), 'tom-comercial.mjs');
  if (!existsSync(scanner)) aviso('tom', 'tom-comercial.mjs não encontrado ao lado do gate: tom não conferido');
  else {
    const a = [scanner, pasta];
    if (valor('--excluir-tom')) a.push('--exclude', valor('--excluir-tom'));
    if (valor('--permitir-tom')) a.push('--permitir', valor('--permitir-tom'));
    const r = spawnSync(process.execPath, a, { encoding: 'utf8' });
    if (r.status === 1) {
      const linhas = r.stdout.split('\n').filter((l) => l.startsWith('  '));
      // --tom-como-aviso: ao converter um site que já está no ar, o tom antigo não pode barrar a 1ª publicação.
      for (const l of linhas.slice(0, 25)) (flag('--tom-como-aviso') ? aviso : erro)('tom', l.trim());
    } else if (r.status !== 0) aviso('tom', `scanner terminou com código ${r.status}`);
  }
}

// ---------- resultado
console.log(`Trava (modelo A): ${htmls.length} página(s) HTML em ${pasta}`);
if (avisos.length) { console.log(`\nAVISO (${avisos.length}):`); avisos.slice(0, 40).forEach((a) => console.log('  ' + a)); if (avisos.length > 40) console.log(`  … e mais ${avisos.length - 40}`); }
if (erros.length) { console.log(`\nERRO (${erros.length}):`); erros.forEach((e) => console.log('  ' + e)); process.exit(1); }
console.log('\nNenhum erro.');
