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
    }),
  ],
  build: {
    // gera dist/pagina/index.html — URLs limpas com barra final
    format: 'directory',
  },
});
