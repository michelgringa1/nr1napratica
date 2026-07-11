// @ts-check
import { defineConfig } from 'astro/config';
import sitemap from '@astrojs/sitemap';

// https://astro.build/config
export default defineConfig({
  site: 'https://nr1napratica.online',
  trailingSlash: 'always',
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
