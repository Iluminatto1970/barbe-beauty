import type { Metadata } from 'next';
import Link from 'next/link';
import { buscarAgendamentos } from '../../lib/repo';
import { resolverTenant } from '../../lib/resolve-tenant';
import { WizardAgendamento } from './wizard-agendamento';

interface Props {
  searchParams: Promise<{ tenant?: string }>;
}

export async function generateMetadata({ searchParams }: Props): Promise<Metadata> {
  const tenant = (await resolverTenant(searchParams))!;
  return {
    title: `Agendamento — ${tenant.nome}`,
    description: `Agende seu horário na ${tenant.nome}: escolha o profissional, o horário e o serviço.`,
  };
}

export default async function AgendarPage({ searchParams }: Props) {
  const tenant = (await resolverTenant(searchParams))!;
  const agendamentos = await buscarAgendamentos(tenant.slug);

  return (
    <main className="wizard-page">
      <header className="wizard-topo">
        <Link className="marca" href={`/?tenant=${tenant.slug}`}>
          {tenant.nome.toUpperCase()}
        </Link>
        <Link className="voltar" href={`/?tenant=${tenant.slug}`}>
          ← Voltar ao site
        </Link>
      </header>

      <WizardAgendamento tenant={tenant} agendamentos={agendamentos} />
    </main>
  );
}
