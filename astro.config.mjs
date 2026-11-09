// @ts-check
import { defineConfig } from 'astro/config';
import sitemap from '@astrojs/sitemap';

// Tabelas escritas em markdown (posts do blog) saem sem contêiner. A tabela de 3 colunas do post da Imersão
// tem 347 px de largura mínima e estourava a tela a 320 e 360 px. Cada <table> do markdown ganha um
// <div class="table-scroll"> (CSS em global.css: overflow-x auto, sem borda nem margem), que não muda
// nada em telas largas. As tabelas das páginas .astro já usam o <div class="table-wrap"> à mão.
function rehypeTabelaRolavel() {
  const envolver = (no) => {
    if (!no.children) return;
    no.children = no.children.map((filho) => {
      envolver(filho);
      return filho.type === 'element' && filho.tagName === 'table'
        ? { type: 'element', tagName: 'div', properties: { className: ['table-scroll'] }, children: [filho] }
        : filho;
    });
  };
  return (arvore) => envolver(arvore);
}

// https://astro.build/config
export default defineConfig({
  site: 'https://nr1napratica.online',
  trailingSlash: 'always',
  markdown: {
    rehypePlugins: [rehypeTabelaRolavel],
  },
  integrations: [
    sitemap({
      // /vai/ é redirect de afiliado e a política (noindex) ficam fora do sitemap
      filter: (page) =>
        !page.includes('/vai/') && !page.includes('/politica-de-privacidade/'),
      // lastmod: data da última atualização relevante do site (bump ao revisar em massa)
      serialize(item) {
        item.lastmod = '2026-07-11T00:00:00.000Z';
        return item;
      },
    }),
  ],
  build: {
    // gera dist/pagina/index.html — URLs limpas com barra final
    format: 'directory',
  },
});
