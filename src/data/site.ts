// Configuração central do site — troque valores aqui, refletem em todo o site.

export const SITE = {
  domain: 'nr1napratica.online',
  url: 'https://nr1napratica.online',
  name: 'NR1 na Prática',
  // Descrição curta usada como fallback de meta description e no schema Organization.
  tagline: 'Portal independente sobre NR-1, riscos psicossociais e a carreira de Gestor de NR1',
  lang: 'pt-BR',
  locale: 'pt_BR',
  // Rota interna de cloaking do link de afiliado (301 no .htaccess).
  affiliatePath: '/vai/formacao/',
  // Link real (fica SÓ no .htaccess, nunca no HTML das páginas).
  affiliateTarget: 'https://hotm.io/nr1napratica',
  // Data de atualização exibida ("Atualizado em ...") — atualize a cada revisão.
  updatedLabel: 'Julho/2026',
  updatedISO: '2026-07-10',
  // Analytics — preencha quando tiver os IDs (deixe vazio para não renderizar).
  ga4Id: 'G-4LMQRL02CF',
  gtmId: '', // ex.: 'GTM-XXXXXXX'
  // Meta Pixel: SÓ o ID (público, client-side). NUNCA colocar o token/CAPI aqui.
  metaPixelId: '369012164131148',
} as const;

// Persona-placeholder de autoria (E-E-A-T). Substitua pela bio real quando tiver.
export const AUTHOR = {
  name: 'Equipe NR1 na Prática',
  // Para artigos assinados por pessoa, troque por um nome/persona consistente.
  penName: 'Rafael Menezes',
  role: 'Analista de conteúdo em SST e gestão de pessoas',
  bio: 'Equipe editorial dedicada a acompanhar as mudanças da NR-1, os riscos psicossociais e o mercado de trabalho para gestores e consultores da área. Conteúdo informativo, com fontes oficiais (MTE, gov.br) e revisão periódica.',
  url: 'https://nr1napratica.online/sobre/',
  // Perfis do autor/portal (E-E-A-T). Preencha com URLs reais quando tiver.
  sameAs: [
    // 'https://www.linkedin.com/in/SEU-PERFIL/',
    // 'https://www.instagram.com/SEU-PERFIL/',
  ] as string[],
} as const;

// Itens de navegação principal.
export const NAV = [
  { label: 'Início', href: '/' },
  { label: 'NR-1 em 2026', href: '/nr-1-atualizada-2026/' },
  { label: 'Review da Formação', href: '/formacao-gestor-de-nr1-izabella-camargo-review/' },
  { label: 'Blog', href: '/blog/' },
  { label: 'Sobre', href: '/sobre/' },
] as const;
