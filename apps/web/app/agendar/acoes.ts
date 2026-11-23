'use server';

/**
 * Server action do wizard: grava o agendamento no banco multi-tenant com as
 * regras do domain (revalida o slot contra o banco no momento da ação).
 */
import { salvarAgendamentoOnline, type InputAgendamentoOnline } from '../../lib/agendar';

export async function criarAgendamento(
  input: InputAgendamentoOnline,
): Promise<{ ok: boolean; erro?: string; id?: string }> {
  return salvarAgendamentoOnline(input);
}
