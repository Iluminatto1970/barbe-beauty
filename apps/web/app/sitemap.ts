import type { MetadataRoute } from 'next';
import { listarTenantsPublicos } from '../lib/repo';

// O sitemap reflete os tenants do banco (não pode ser pré-renderizado).
export const dynamic = 'force-dynamic';

const base = process.env.NEXT_PUBLIC_SITE_URL ?? 'http://localhost:3000';

export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  const tenants = await listarTenantsPublicos();
  // Em produção com subdomínios: https://{slug}.minhasaas.com
  return tenants.map((t) => ({
    url: `${base}/?tenant=${t.slug}`,
    changeFrequency: 'weekly',
    priority: 0.8,
  }));
}
