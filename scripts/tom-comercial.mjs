#!/usr/bin/env node
/* Scanner de TOM COMERCIAL para paginas de afiliado (regra global de 07/10/2026 no
   ~/.claude/CLAUDE.md, secao "Paginas de afiliado: tom comercial").

   Procura linguagem que gera duvida em vez de vender, em espanhol, portugues, ingles e alemao:
   plataformas de reclamacao/notas de terceiros (Reclame Aqui, Trustpilot, Puntua...),
   "nao testamos / no hemos cursado / we haven't tried", "declarado / nao verificado",
   contradicao e cautela, "nao e para voce", simbolos de reprovacao. Avisa quando um bloco
   de pros e contras tem mais contras que pros.

   Uso:
     node scan.mjs <pasta-ou-arquivo> [--exclude sobre,legal] [--permitir goodreads] [--json]

   - Le .html (e .md/.mdx/.astro/.njk/.txt como texto cru).
   - Ignora <footer>, comentarios HTML, href e scripts que nao sejam JSON-LD.
   - Ignora paginas com "FINALIDADE: Institucional" e caminhos que contenham algum termo
     de --exclude (ex.: sobre, about, sobre-nos, privacidad, terminos).
   - Sai com codigo 1 se achar bloqueio (serve como trava em script de build).

   A mesma lista de padroes vive em projetos com gerador proprio (ex.: Lupa Inversora,
   build/tom-patterns.mjs). Mudou aqui, mude la. */

import { readFileSync, readdirSync, statSync } from "node:fs";
import { join, extname, relative } from "node:path";

export const TOM_BLOQUEIA = [
  ["plataforma de reclamações/notas", /\b(reclame\s*aqui|reclameaqui|trustpilot|puntua(\.net)?|goodreads|sitejabber|ripoff\s*report|scam\s*adviser|scamadviser|better\s+business\s+bureau|consumidor\.gov|kununu|verbraucherzentrale\s+warnt)\b/i],
  ["nota de 1 estrela", /\b(1|una|uma|one|ein|einem)[\s-](estrella|estrela|star|stern)(s|e|en)?\b/i],
  ["não testamos (es)", /\bno\s+hemos\s+(cursado|le[ií]do|asistido|probado|usado|comprado|podido\s+(comprobar|verificar|contrastar))\b/i],
  ["não testamos (pt)", /\bn[aã]o\s+(testamos|cursamos|lemos|assistimos|compramos|usamos|conseguimos\s+(verificar|comprovar|confirmar))\b/i],
  ["não testamos (en)", /\bwe\s+(haven'?t|have\s+not|did\s+not|didn'?t)\s+(tested|tried|taken|read|attended|used|bought|been\s+able\s+to\s+verify)\b/i],
  ["declarado/não verificado (es)", /\b(declarad[oa]s?\s+por|solo\s+(lo\s+)?declara|no\s+verificad[oa]s?|sin\s+verificar)\b/i],
  ["declarado/não verificado (pt)", /\b(declarad[oa]s?\s+por|n[aã]o\s+verificad[oa]s?|sem\s+verifica[cç][aã]o)\b/i],
  ["declarado/não verificado (en)", /\b(self[\s-]reported|unverified|not\s+verified|could\s+not\s+be\s+verified)\b/i],
  ["contradição/cautela (es)", /\b(se\s+contradicen|piden\s+prudencia|se[nñ]ales\s+de\s+alerta|banderas\s+rojas|desconf[ií]a|desconfiamos)\b/i],
  ["contradição/cautela (pt)", /\b(se\s+contradizem|pedem\s+cautela|sinais\s+de\s+alerta|bandeiras\s+vermelhas|desconfie|desconfiamos)\b/i],
  ["contradição/cautela (en)", /\b(sources\s+contradict|red\s+flags|warning\s+signs|be\s+wary)\b/i],
  ["não testamos (de)", /\b(wir\s+haben\s+(?:[\wäöüß]+\s+){0,3}(nicht|noch\s+nicht)\s+(getestet|ausprobiert|gelesen|besucht|gekauft|genutzt)|nicht\s+selbst\s+getestet|ungetestet)\b/i],
  ["declarado/não verificado (de)", /\b(nach\s+eigenen\s+angaben|eigenangabe|unbest[aä]tigt|nicht\s+verifiziert|nicht\s+[uü]berpr[uü]ft)\b/i],
  ["contradição/cautela (de)", /\b(widerspr[uü]chlich|warnsignale|rote\s+flaggen|vorsicht\s+geboten|abzocke|betrugsverdacht|misstrauisch)\b/i],
  ["não é para você (de)", /\b(nicht\s+f[uü]r\s+(dich|sie)\s+geeignet|nichts\s+f[uü]r\s+(dich|sie)|ist\s+nicht\s+f[uü]r\s+(dich|sie))\b/i],
  ["não é para você", /\b(no\s+es\s+para\s+ti|n[aã]o\s+[eé]\s+para\s+voc[eê]|not\s+for\s+you|isn'?t\s+for\s+you)\b/i],
  ["símbolo de reprovação", /[✖✗✘❌]/],
];

const args = process.argv.slice(2);
const alvo = args.find((a) => !a.startsWith("--"));
const asJson = args.includes("--json");
const exIdx = args.indexOf("--exclude");
const excluir = exIdx >= 0 ? (args[exIdx + 1] || "").split(",").filter(Boolean) : [];
// --permitir goodreads: exceção por projeto (registrada no CLAUDE.md do projeto) para citar
// uma plataforma de notas só com nota POSITIVA. Sem a flag, tudo continua bloqueado.
const pmIdx = args.indexOf("--permitir");
const permitir = pmIdx >= 0 ? (args[pmIdx + 1] || "").toLowerCase().split(",").filter(Boolean) : [];

if (!alvo) {
  console.log("Uso: node scan.mjs <pasta-ou-arquivo> [--exclude sobre,about] [--json]");
  process.exit(2);
}

const EXT = [".html", ".htm", ".md", ".mdx", ".astro", ".njk", ".txt"];
const arquivos = [];
(function walk(p) {
  const st = statSync(p);
  if (st.isDirectory()) {
    for (const f of readdirSync(p)) if (!f.startsWith(".") && f !== "node_modules") walk(join(p, f));
  } else if (EXT.includes(extname(p).toLowerCase())) arquivos.push(p);
})(alvo);

const bloqueios = [];
const avisos = [];
for (const arq of arquivos) {
  const rel = relative(alvo, arq) || arq;
  if (excluir.some((e) => rel.toLowerCase().includes(e.toLowerCase()))) continue;
  const bruto = readFileSync(arq, "utf8");
  if (bruto.includes("FINALIDADE: Institucional")) continue;
  const html = bruto
    .replace(/<footer[\s\S]*?<\/footer>/gi, "")
    .replace(/<!--[\s\S]*?-->/g, "")
    .replace(/\shref="[^"]*"/gi, "")
    .replace(/<script(?![^>]*ld\+json)[\s\S]*?<\/script>/gi, "")
    .replace(/<style[\s\S]*?<\/style>/gi, "");
  const texto = html.replace(/<[^>]+>/g, " ").replace(/\s+/g, " ");
  for (const [rotulo, re] of TOM_BLOQUEIA) {
    const g = new RegExp(re.source, re.flags.includes("g") ? re.flags : re.flags + "g");
    for (const m of texto.matchAll(g)) {
      const i = m.index;
      if (permitir.includes(m[0].toLowerCase().replace(/\s+/g, ""))) continue;
      bloqueios.push({ arquivo: rel, regra: rotulo, trecho: texto.slice(Math.max(0, i - 50), i + 60).trim() });
    }
  }
  for (const m of html.matchAll(/<div class="proscons">([\s\S]*?)<\/div>\s*<\/div>/g)) {
    const lados = [...m[1].matchAll(/<ul>([\s\S]*?)<\/ul>/g)].map((x) => (x[1].match(/<li\b/g) || []).length);
    if (lados.length >= 2 && lados[1] > lados[0])
      avisos.push({ arquivo: rel, regra: "prós e contras desequilibrados", trecho: `${lados[0]} prós × ${lados[1]} contras` });
  }
}

if (asJson) {
  console.log(JSON.stringify({ arquivos: arquivos.length, bloqueios, avisos }, null, 2));
} else {
  console.log(`Tom comercial: ${arquivos.length} arquivo(s) lido(s) em ${alvo}`);
  if (avisos.length) {
    console.log(`\nAVISO (${avisos.length}):`);
    for (const a of avisos) console.log(`  ${a.arquivo}: ${a.regra}: ${a.trecho}`);
  }
  if (bloqueios.length) {
    console.log(`\nBLOQUEIO (${bloqueios.length}):`);
    for (const b of bloqueios) console.log(`  ${b.arquivo}: ${b.regra}: "...${b.trecho}..."`);
  } else console.log("\nNenhuma linguagem que gere dúvida.");
}
process.exit(bloqueios.length ? 1 : 0);
