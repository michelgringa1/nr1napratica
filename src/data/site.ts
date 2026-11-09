// Configuração central do site. Troque valores aqui, refletem em todo o site.

export const SITE = {
  domain: 'nr1napratica.online',
  url: 'https://nr1napratica.online',
  name: 'NR1 na Prática',
  // Descrição curta usada como fallback de meta description e no schema Organization.
  tagline: 'Portal independente sobre NR-1, riscos psicossociais e a carreira de Gestor de NR1',
  lang: 'pt-BR',
  locale: 'pt_BR',
  // Rastreio de cliques (D34): todo botão de afiliado passa por /go/<produto>/?s=<pagina>_<posicao>
  // (api/go.php registra o clique e responde 302 para a Hotmart). Os destinos reais ficam em scripts/go-map.json
  // (fonte única; o PHP é gerado dele com o kit do Gestor, com --sinais). Rota da Formação:
  affiliatePath: '/go/formacao-gestor-nr1/',
  // Documentação apenas (nada lê isto): o link real da Formação está em scripts/go-map.json e, para os links
  // antigos /vai/formacao/ (mantidos), no .htaccess. O .htaccess responde 302 (não 301).
  affiliateTarget: 'https://go.hotmart.com/E107148543R?src=blogreview',
  // Data de atualização exibida ("Atualizado em ..."). Atualize a cada revisão.
  updatedLabel: 'Julho/2026',
  updatedISO: '2026-07-10',
  // Analytics: preencha quando tiver os IDs (deixe vazio para não renderizar).
  ga4Id: 'G-4LMQRL02CF',
  gtmId: '', // ex.: 'GTM-XXXXXXX'
  // Meta Pixel: SÓ o ID (público, client-side). NUNCA colocar o token/CAPI aqui.
  metaPixelId: '369012164131148',
  // Microsoft Clarity: mapa de calor + gravações ("onde clicaram"). Cole o ID do
  // projeto (grátis em clarity.microsoft.com) para ativar. Deixe vazio para não renderizar.
  clarityId: 'xn3u6ydyrv',
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

// ---------------------------------------------------------------------------
// /go/ (D34): rotas dos botões de afiliado. Os slugs têm de existir em scripts/go-map.json (a trava-go confere).
// ---------------------------------------------------------------------------
// Imersão: o `s` do botão É o `src` que a Hotmart já recebia (bannerblg = banner, rvwimsao = links do post), então o
// endereço final sai idêntico ao de antes e a casa de vendas por `src` continua valendo. Só trocar com o Michel.
export const GO_IMERSAO = '/go/imersao-nr1-na-pratica/';

// Código curto de cada página que tem botão da Formação (entra no `s`: <pagina>_<posicao>, até 40 caracteres).
// Página nova com botão da Formação: acrescente aqui, senão o build para (de propósito: slug sem código = botão sem rastreio).
const GO_PAGINAS: Record<string, string> = {
  '/formacao-gestor-de-nr1-izabella-camargo-review/': 'review',
  '/carreira-gestor-de-nr1-com-proposito/': 'carreira',
  '/nr-1-atualizada-2026/': 'pilar',
  '/por-que-se-tornar-gestor-de-nr1-agora/': 'porque-gestor',
  '/blog/gestor-de-nr1-o-que-faz/': 'post-o-que-faz',
  '/blog/quanto-ganha-consultor-nr1/': 'post-quanto-ganha',
  '/blog/como-atuar-com-nr1-sem-diploma/': 'post-sem-diploma',
  '/blog/imersao-nr1-na-pratica-izabella-camargo/': 'post-imersao',
};

/** Endereço do botão da Formação numa página: /go/formacao-gestor-nr1/?s=<codigo-da-pagina>_<posicao>. */
export function goFormacao(pathname: string, pos: string): string {
  const pagina = GO_PAGINAS[pathname];
  if (!pagina) {
    throw new Error(`/go/: a página ${pathname} não tem código em GO_PAGINAS (src/data/site.ts). Acrescente antes de pôr um botão da Formação nela.`);
  }
  const s = `${pagina}_${pos}`;
  if (!/^[a-z0-9_-]{1,40}$/.test(s)) {
    throw new Error(`/go/: posição inválida "${s}" (use a-z, 0-9, _ e -, até 40 caracteres).`);
  }
  return `${SITE.affiliatePath}?s=${s}`;
}
