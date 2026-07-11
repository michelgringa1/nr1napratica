# NR1 na Prática — site afiliado (Astro estático)

Portal independente sobre NR-1, riscos psicossociais e a carreira de Gestor de
NR1. Site 100% estático (Astro), pensado para SEO orgânico no Brasil e deploy na
Hostinger (Apache).

## Comandos

```bash
npm install      # instala dependências
npm run dev      # servidor local em http://localhost:4321
npm run build    # gera o site estático em dist/
npm run preview  # serve o dist/ localmente para conferência
```

## Onde editar as coisas

| O quê | Arquivo |
|---|---|
| Domínio, nome, link de afiliado, data "Atualizado em", **IDs GA4/GTM** | `src/data/site.ts` |
| Autor / persona (E-E-A-T) | `src/data/site.ts` (`AUTHOR`) |
| Helpers de JSON-LD (Article, FAQPage, Breadcrumb) | `src/data/schema.ts` |
| Layout + `<head>` (SEO, OG, canonical) | `src/layouts/Base.astro` |
| Página Review (BOFU) | `src/pages/formacao-gestor-de-nr1-izabella-camargo-review.astro` |
| Página Pilar (TOFU) | `src/pages/nr-1-atualizada-2026.astro` |
| Home | `src/pages/index.astro` |
| Artigos do blog | `src/content/blog/*.md` |
| Redirect de afiliado, HTTPS, cache | `public/.htaccess` |
| robots.txt | `public/robots.txt` |

## Imagens geradas (OG + infográficos)

Geradas por script com `sharp` (devDependency) e commitadas em `public/`:

```bash
node scripts/make-og.mjs      # gera public/og-default.png (1200x630)
node scripts/make-images.mjs  # gera public/images/*.png (ex.: linha do tempo)
```

Rode de novo se editar textos/datas dos gráficos. Não é necessário no deploy (os
PNGs já vão versionados em `public/`).

## Antes de publicar (checklist)

- [ ] **⚠️ Tema jurídico é sensível a datas:** a suspensão de multas do STF é por **90 dias** (a partir de 25/06/2026 → ~fim de set/2026). Revisar pilar e página de multa quando o prazo vencer ou houver nova decisão.
- [ ] **Conferir dados voláteis do review** (preço, bônus, prazo de acesso, Reclame Aqui) na LP oficial — hoje estão com os valores do plano marcados como "conferir".
- [ ] Preencher `ga4Id` / `gtmId` em `src/data/site.ts` (ou deixar vazio).
- [ ] Trocar a persona-placeholder do autor em `src/data/site.ts` pela bio real.
- [ ] Preencher `AUTHOR.sameAs` em `src/data/site.ts` com perfis reais (LinkedIn/Instagram) para reforçar E-E-A-T.
- [ ] Trocar o e-mail em `src/pages/contato.astro`.

## Deploy (GitHub Actions → branch `deploy` → Hostinger)

A Hostinger compartilhada **não roda `npm run build`**. Por isso o deploy é
automatizado assim:

1. Você dá `git push` na branch **`main`** (código-fonte).
2. O GitHub Action (`.github/workflows/deploy.yml`) builda e publica **só o site
   pronto** (conteúdo de `dist/`, com `.htaccess`) na branch **`deploy`**.
3. A Hostinger serve a branch **`deploy`** em `public_html`.

### Configuração única na Hostinger (hPanel)

1. **Avançado → GIT:** repositório `https://github.com/michelgringa1/nr1napratica.git`,
   branch **`deploy`** (NÃO `main`), diretório `public_html`.
2. Ative **Auto-Deployment** e copie o **webhook** gerado → cole em GitHub →
   repo → Settings → Webhooks (assim a Hostinger puxa sozinha a cada build).
   (Sem webhook, é só clicar "Deploy" no hPanel após cada Action.)
3. **SSL:** hPanel → SSL → Let's Encrypt + "Forçar HTTPS".
4. **Teste:** `https://nr1napratica.online/vai/formacao` deve redirecionar com o
   `ref=B106168547F` intacto (confirme o hotlink na área de afiliado da Hotmart).
5. **Search Console:** enviar `sitemap-index.xml` e pedir indexação das páginas
   principais.

> Fluxo de trabalho no dia a dia: editar → `git push` na `main` → o resto é
> automático. Regenerar imagens (`node scripts/make-*.mjs`) só se mudar textos dos
> gráficos.
