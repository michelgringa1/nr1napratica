import { defineCollection, z } from 'astro:content';
import { glob } from 'astro/loaders';

const blog = defineCollection({
  loader: glob({ pattern: '**/*.md', base: './src/content/blog' }),
  schema: z.object({
    title: z.string(),
    description: z.string(),
    // Título curto para <title> (SEO), se diferente do H1.
    seoTitle: z.string().optional(),
    funnel: z.enum(['TOFU', 'MOFU', 'BOFU']).default('MOFU'),
    tag: z.string().default('Carreira'),
    pubDate: z.coerce.date(),
    updatedDate: z.coerce.date().optional(),
    draft: z.boolean().default(false),
    // FAQ opcional → renderiza acordeão + gera FAQPage (featured snippet).
    faq: z.array(z.object({ q: z.string(), a: z.string() })).optional(),
  }),
});

export const collections = { blog };
