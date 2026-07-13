// Helpers para gerar objetos JSON-LD (schema.org) consistentes.
import { SITE, AUTHOR } from './site';

const stripHtml = (s: string) => s.replace(/<[^>]*>/g, '').replace(/\s+/g, ' ').trim();

export interface FaqItem {
  q: string;
  a: string;
}

// --- Entidades (GEO): ancoram o conteúdo a conceitos/pessoas conhecidos,
// ajudando motores generativos (IA) e o Google a entender do que a página trata.
export const ENTITIES = {
  nr1: { '@type': 'Thing', name: 'NR-1 (Norma Regulamentadora nº 1)' },
  riscosPsicossociais: { '@type': 'Thing', name: 'Riscos psicossociais no trabalho' },
  pgr: { '@type': 'Thing', name: 'Programa de Gerenciamento de Riscos (PGR)' },
  gro: { '@type': 'Thing', name: 'Gerenciamento de Riscos Ocupacionais (GRO)' },
  saudeMental: { '@type': 'Thing', name: 'Saúde mental no trabalho' },
  izabella: { '@type': 'Person', name: 'Izabella Camargo' },
  formacao: { '@type': 'Thing', name: 'Formação Gestor de NR1' },
  gestorNr1: { '@type': 'Thing', name: 'Gestor de NR1' },
} as const;

// Especificação de conteúdo "falável" (AEO / busca por voz e assistentes).
const SPEAKABLE = {
  '@type': 'SpeakableSpecification',
  cssSelector: ['.page-hero h1', '.page-hero p'],
};

/** WebSite + Organization para a home. */
export function organizationSchema() {
  return {
    '@context': 'https://schema.org',
    '@type': 'Organization',
    name: SITE.name,
    url: SITE.url + '/',
    description: SITE.tagline,
    logo: new URL('/favicon.svg', SITE.url).href,
    slogan: SITE.tagline,
    areaServed: { '@type': 'Country', name: 'Brasil' },
    knowsAbout: [
      'NR-1',
      'Riscos psicossociais no trabalho',
      'Saúde mental no trabalho',
      'PGR e GRO',
      'Carreira de Gestor de NR1',
    ],
  };
}

export function websiteSchema() {
  return {
    '@context': 'https://schema.org',
    '@type': 'WebSite',
    name: SITE.name,
    url: SITE.url + '/',
    inLanguage: SITE.lang,
  };
}

/** Article com autor, datas, imagem, entidades (GEO) e speakable (AEO). */
export function articleSchema(opts: {
  headline: string;
  description: string;
  url: string; // path, ex.: '/nr-1-atualizada-2026/'
  image?: string;
  datePublished: string;
  dateModified?: string;
  authorName?: string;
  /** Entidade principal do artigo (GEO). Default: NR-1. */
  about?: object;
  /** Entidades mencionadas (GEO). Default: conjunto padrão do nicho. */
  mentions?: object[];
  /** Palavras-chave do artigo. */
  keywords?: string[];
}) {
  const url = new URL(opts.url, SITE.url).href;
  return {
    '@context': 'https://schema.org',
    '@type': 'Article',
    headline: opts.headline,
    description: opts.description,
    image: new URL(opts.image ?? '/og-default.png', SITE.url).href,
    datePublished: opts.datePublished,
    dateModified: opts.dateModified ?? opts.datePublished,
    inLanguage: SITE.lang,
    isAccessibleForFree: true,
    mainEntityOfPage: { '@type': 'WebPage', '@id': url },
    about: opts.about ?? ENTITIES.nr1,
    mentions:
      opts.mentions ??
      [
        ENTITIES.riscosPsicossociais,
        ENTITIES.pgr,
        ENTITIES.gro,
        ENTITIES.izabella,
        ENTITIES.formacao,
        ENTITIES.gestorNr1,
      ],
    ...(opts.keywords ? { keywords: opts.keywords.join(', ') } : {}),
    speakable: SPEAKABLE,
    author: {
      '@type': 'Person',
      name: opts.authorName ?? AUTHOR.penName,
      url: AUTHOR.url,
    },
    publisher: {
      '@type': 'Organization',
      name: SITE.name,
      logo: {
        '@type': 'ImageObject',
        url: new URL('/favicon.svg', SITE.url).href,
      },
    },
  };
}

/** Person (autor) para a página /sobre. Reforça E-E-A-T. */
export function personSchema() {
  const s: Record<string, unknown> = {
    '@context': 'https://schema.org',
    '@type': 'Person',
    name: AUTHOR.penName,
    jobTitle: AUTHOR.role,
    description: AUTHOR.bio,
    url: AUTHOR.url,
    worksFor: { '@type': 'Organization', name: SITE.name, url: SITE.url + '/' },
  };
  if (AUTHOR.sameAs && AUTHOR.sameAs.length) s.sameAs = AUTHOR.sameAs;
  return s;
}

/** FAQPage a partir dos mesmos itens usados no componente FAQ. */
export function faqSchema(items: FaqItem[]) {
  return {
    '@context': 'https://schema.org',
    '@type': 'FAQPage',
    mainEntity: items.map((it) => ({
      '@type': 'Question',
      name: stripHtml(it.q),
      acceptedAnswer: {
        '@type': 'Answer',
        text: stripHtml(it.a),
      },
    })),
  };
}

/** ItemList para páginas de lista (ex.: "15 exemplos", "12 módulos"). AEO. */
export function itemListSchema(name: string, items: string[]) {
  return {
    '@context': 'https://schema.org',
    '@type': 'ItemList',
    name,
    numberOfItems: items.length,
    itemListElement: items.map((it, i) => ({
      '@type': 'ListItem',
      position: i + 1,
      name: it,
    })),
  };
}

/** HowTo, passo a passo (rich result). Passe pares [nome, texto]. */
export function howToSchema(name: string, steps: [string, string][], description?: string) {
  return {
    '@context': 'https://schema.org',
    '@type': 'HowTo',
    name,
    ...(description ? { description } : {}),
    inLanguage: SITE.lang,
    step: steps.map(([stepName, text], i) => ({
      '@type': 'HowToStep',
      position: i + 1,
      name: stepName,
      text,
    })),
  };
}

/** BreadcrumbList. Passe pares [label, path]. */
export function breadcrumbSchema(crumbs: [string, string][]) {
  return {
    '@context': 'https://schema.org',
    '@type': 'BreadcrumbList',
    itemListElement: crumbs.map(([name, path], i) => ({
      '@type': 'ListItem',
      position: i + 1,
      name,
      item: new URL(path, SITE.url).href,
    })),
  };
}
