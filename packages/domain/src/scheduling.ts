/**
 * Regras de agendamento — portadas do sistema legado PHP (Barbearia PRO).
 *
 * Fontes:
 *  - `app/ajax/listar-horarios.php` — disponibilidade de slots
 *  - `app/ajax/agendar.php` — validação de conflito na reserva/edição
 *  - schema `sql/barbearia.sql` — tabelas `dias`, `horarios`, `agendamentos`
 *
 * Modelo legado:
 *  - `dias` guarda os dias da semana (nomes em português) em que o funcionário trabalha;
 *  - `horarios` guarda os slots do funcionário (TIME `HH:mm:ss`, granularidade de 30 min);
 *  - o CONFLITO é a tripla (data, hora, funcionario): não pode existir mais de um
 *    agendamento com a mesma tripla — o modelo não tem duração nem sobreposição
 *    parcial: cada slot é um intervalo discreto;
 *  - na edição, o próprio agendamento mantém o seu slot (espelha o `$res[0]['id'] != $id`
 *    de `agendar.php`).
 *
 * Correção deliberada vs. legado:
 *  - o PHP monta o nome do dia com `'Sabado'` (sem acento) e falha ao casar com o
 *    `'Sábado'` gravado no banco — sábado nunca aparecia como dia trabalhado.
 *    `normalizarDia()` aceita ambas as formas (bug corrigido na extração);
 *  - `reservarSlot()` também exige que o slot pertença à escala do funcionário
 *    (o legado deixava essa checagem para a UI).
 */

export const DIAS_SEMANA_PT = [
  'Domingo',
  'Segunda-Feira',
  'Terça-Feira',
  'Quarta-Feira',
  'Quinta-Feira',
  'Sexta-Feira',
  'Sábado',
] as const;

export type DiaSemana = (typeof DIAS_SEMANA_PT)[number];

const ALIASES_DIAS: Record<string, DiaSemana> = {
  domingo: 'Domingo',
  segunda: 'Segunda-Feira',
  'segunda-feira': 'Segunda-Feira',
  terca: 'Terça-Feira',
  'terca-feira': 'Terça-Feira',
  'terça-feira': 'Terça-Feira',
  quarta: 'Quarta-Feira',
  'quarta-feira': 'Quarta-Feira',
  quinta: 'Quinta-Feira',
  'quinta-feira': 'Quinta-Feira',
  sexta: 'Sexta-Feira',
  'sexta-feira': 'Sexta-Feira',
  sabado: 'Sábado', // legado gravava 'Sabado' sem acento — normalizar corrige o bug
  sábado: 'Sábado',
};

/** Normaliza qualquer variação de nome de dia para a forma canônica do banco. */
export function normalizarDia(dia: string): DiaSemana {
  const canonico = ALIASES_DIAS[dia.trim().toLowerCase()];
  if (!canonico) {
    throw new Error(`Dia da semana inválido: "${dia}"`);
  }
  return canonico;
}

/** Dia da semana (0 = domingo ... 6 = sábado), igual ao `date('w')` do PHP. */
export function diaDaSemana(data: string): number {
  return new Date(`${data}T12:00:00`).getDay();
}

/** Normaliza hora `HH:mm:ss` (TIME do MySQL) para `HH:mm`. */
export function normalizarHora(hora: string): string {
  return hora.slice(0, 5);
}

export interface Agendamento {
  /** id — presente quando o registro já existe (edição). */
  id?: string;
  data: string; // 'YYYY-MM-DD'
  hora: string; // 'HH:mm' ou 'HH:mm:ss' (TIME do MySQL)
  funcionarioId: string;
}

export interface EscalaFuncionario {
  funcionarioId: string;
  diasTrabalho: DiaSemana[];
  horarios: string[]; // slots 'HH:mm' (ou 'HH:mm:ss')
}

export function trabalhaNesteDia(escala: EscalaFuncionario, data: string): boolean {
  const dia = DIAS_SEMANA_PT[diaDaSemana(data)];
  if (!dia) return false; // data inválida
  return escala.diasTrabalho.some((d) => normalizarDia(d) === dia);
}

/**
 * Slots disponíveis para data+funcionário, espelhando `listar-horarios.php`:
 * ocupado = existe agendamento com a mesma tripla (data, hora, funcionario).
 * Se `horaSelecionada` for informada, o slot continua aparecendo (edição).
 */
export function horariosDisponiveis(
  escala: EscalaFuncionario,
  data: string,
  agendamentos: Agendamento[],
  horaSelecionada?: string,
): string[] {
  if (!trabalhaNesteDia(escala, data)) return [];

  const ocupados = new Set(
    agendamentos
      .filter((a) => a.funcionarioId === escala.funcionarioId && a.data === data)
      .map((a) => normalizarHora(a.hora)),
  );

  const selecionada = horaSelecionada ? normalizarHora(horaSelecionada) : undefined;

  return escala.horarios
    .map(normalizarHora)
    .filter((hora) => !ocupados.has(hora) || hora === selecionada);
}

export class HorarioIndisponivelError extends Error {
  constructor(hora: string) {
    super(`Horário ${hora} já está ocupado`);
    this.name = 'HorarioIndisponivelError';
  }
}

export interface ReservarSlotOpcoes {
  /** id do agendamento em edição — permite manter o próprio slot (edição). */
  agendamentoId?: string;
}

/**
 * Invariante central da reserva (espelha `agendar.php`):
 *  - o funcionário precisa trabalhar na data;
 *  - a hora precisa ser um slot da escala do funcionário;
 *  - não pode existir OUTRO agendamento com a mesma tripla (data, hora, funcionario).
 */
export function reservarSlot(
  escala: EscalaFuncionario,
  data: string,
  hora: string,
  agendamentos: Agendamento[],
  opcoes: ReservarSlotOpcoes = {},
): Agendamento {
  if (!trabalhaNesteDia(escala, data)) {
    throw new HorarioIndisponivelError(hora);
  }

  const horaNorm = normalizarHora(hora);
  if (!escala.horarios.map(normalizarHora).includes(horaNorm)) {
    throw new HorarioIndisponivelError(horaNorm);
  }

  const conflito = agendamentos.find((a) => {
    if (a.funcionarioId !== escala.funcionarioId || a.data !== data) return false;
    if (normalizarHora(a.hora) !== horaNorm) return false;
    // Só o PRÓPRIO agendamento (id definido e igual) é ignorado na edição.
    // Agendamento sem id = novo/pendente de gravação — sempre conflita
    // (equivale ao `$res[0]['id'] != $id` do PHP, onde id novo é '').
    if (a.id !== undefined && a.id === opcoes.agendamentoId) return false;
    return true;
  });
  if (conflito) {
    throw new HorarioIndisponivelError(horaNorm);
  }

  return { data, hora: horaNorm, funcionarioId: escala.funcionarioId };
}
