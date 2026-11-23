import { describe, expect, it } from 'vitest';
import {
  diaDaSemana,
  HorarioIndisponivelError,
  horariosDisponiveis,
  normalizarDia,
  normalizarHora,
  reservarSlot,
  trabalhaNesteDia,
} from '../src/scheduling.js';
import type { EscalaFuncionario } from '../src/scheduling.js';

// Escala realista baseada no seed (sql/dados_ficticios.sql): funcionário 11,
// segunda a sábado, slots de 30 min a partir das 08:00.
const escala: EscalaFuncionario = {
  funcionarioId: '11',
  diasTrabalho: [
    'Segunda-Feira',
    'Terça-Feira',
    'Quarta-Feira',
    'Quinta-Feira',
    'Sexta-Feira',
    'Sábado',
  ],
  horarios: ['08:00', '08:30', '09:00', '09:30', '10:00'],
};

// Dias de referência (verificados): 09/08/2026 = domingo, 10/08/2026 = segunda,
// 15/08/2026 = sábado, 17/08/2026 = segunda.
describe('dias da semana', () => {
  it('mapeia datas para 0-6 como o date("w") do PHP', () => {
    expect(diaDaSemana('2026-08-09')).toBe(0); // domingo
    expect(diaDaSemana('2026-08-10')).toBe(1); // segunda
    expect(diaDaSemana('2026-08-15')).toBe(6); // sábado
  });

  it('funcionário trabalha em dias da escala', () => {
    expect(trabalhaNesteDia(escala, '2026-08-10')).toBe(true);
    expect(trabalhaNesteDia(escala, '2026-08-15')).toBe(true);
    expect(trabalhaNesteDia(escala, '2026-08-09')).toBe(false); // domingo
  });

  it('REGRA DE REGRESSÃO: sábado funciona mesmo com o nome sem acento do legado', () => {
    // O PHP monta 'Sabado' (sem acento) e o banco grava 'Sábado' — bug real do
    // listar-horarios.php. A normalização aceita as duas formas.
    expect(normalizarDia('Sabado')).toBe('Sábado');
    expect(normalizarDia('Sábado')).toBe('Sábado');

    // 'Sabado' sem acento é o que o legado gravava no banco — dado não confiável,
    // por isso o cast: a normalização precisa tolerar a variação.
    const diasLegado = ['Segunda-Feira', 'Sabado'];
    const escalaLegado: EscalaFuncionario = {
      funcionarioId: '11',
      diasTrabalho: diasLegado as EscalaFuncionario['diasTrabalho'],
      horarios: ['09:00'],
    };
    expect(trabalhaNesteDia(escalaLegado, '2026-08-15')).toBe(true);
  });

  it('rejeita nome de dia inválido', () => {
    expect(() => normalizarDia('Feriado')).toThrow(/Dia da semana inválido/);
  });

  it('escala vazia nunca trabalha', () => {
    const vazia: EscalaFuncionario = { funcionarioId: 'x', diasTrabalho: [], horarios: ['09:00'] };
    expect(trabalhaNesteDia(vazia, '2026-08-10')).toBe(false);
  });
});

describe('disponibilidade de horários', () => {
  it('não oferece slots em dia sem expediente', () => {
    expect(horariosDisponiveis(escala, '2026-08-09', [])).toEqual([]);
  });

  it('remove o slot ocupado pelo mesmo funcionário na mesma data (tripla data+hora+funcionario)', () => {
    const agendamentos = [{ data: '2026-08-10', hora: '08:30', funcionarioId: '11' }];
    expect(horariosDisponiveis(escala, '2026-08-10', agendamentos)).toEqual([
      '08:00',
      '09:00',
      '09:30',
      '10:00',
    ]);
  });

  it('ignora agendamentos de outros funcionários (isolamento)', () => {
    const agendamentos = [{ data: '2026-08-10', hora: '08:30', funcionarioId: '12' }];
    expect(horariosDisponiveis(escala, '2026-08-10', agendamentos)).toEqual(escala.horarios);
  });

  it('normaliza hora do MySQL (HH:mm:ss) no conflito', () => {
    expect(normalizarHora('08:30:00')).toBe('08:30');
    const agendamentos = [{ data: '2026-08-10', hora: '08:30:00', funcionarioId: '11' }];
    expect(horariosDisponiveis(escala, '2026-08-10', agendamentos)).toEqual([
      '08:00',
      '09:00',
      '09:30',
      '10:00',
    ]);
  });

  it('modelo discreto: agendar 08:00 não bloqueia 08:30 (sem sobreposição/duração)', () => {
    const agendamentos = [{ data: '2026-08-10', hora: '08:00', funcionarioId: '11' }];
    expect(horariosDisponiveis(escala, '2026-08-10', agendamentos)).toContain('08:30');
  });

  it('edição: o slot do próprio agendamento continua visível', () => {
    const agendamentos = [{ data: '2026-08-10', hora: '08:30', funcionarioId: '11' }];
    expect(horariosDisponiveis(escala, '2026-08-10', agendamentos, '08:30')).toEqual(
      escala.horarios,
    );
  });
});

describe('reserva (invariante de conflito)', () => {
  it('reserva slot livre', () => {
    expect(reservarSlot(escala, '2026-08-10', '09:00', [])).toEqual({
      data: '2026-08-10',
      hora: '09:00',
      funcionarioId: '11',
    });
  });

  it('rejeita reserva de slot ocupado pelo mesmo funcionário', () => {
    const agendamentos = [{ id: '1', data: '2026-08-10', hora: '09:00', funcionarioId: '11' }];
    expect(() => reservarSlot(escala, '2026-08-10', '09:00', agendamentos)).toThrow(
      HorarioIndisponivelError,
    );
  });

  it('permite que a edição mantenha o próprio slot (id ignorado)', () => {
    const agendamentos = [{ id: '1', data: '2026-08-10', hora: '09:00', funcionarioId: '11' }];
    expect(() =>
      reservarSlot(escala, '2026-08-10', '09:00', agendamentos, { agendamentoId: '1' }),
    ).not.toThrow();
  });

  it('rejeita segunda reserva no mesmo slot — duplicidade impossível', () => {
    const primeiro = reservarSlot(escala, '2026-08-10', '09:00', []);
    expect(() =>
      reservarSlot(escala, '2026-08-10', '09:00', [primeiro]),
    ).toThrow(HorarioIndisponivelError);
  });

  it('rejeita reserva em dia sem expediente', () => {
    expect(() => reservarSlot(escala, '2026-08-09', '09:00', [])).toThrow(
      HorarioIndisponivelError,
    );
  });

  it('rejeita reserva fora dos slots da escala (checagem que o legado deixava para a UI)', () => {
    expect(() => reservarSlot(escala, '2026-08-10', '07:30', [])).toThrow(
      HorarioIndisponivelError,
    );
  });

  it('round-trip: após reservar, o slot sai da disponibilidade', () => {
    const reserva = reservarSlot(escala, '2026-08-10', '10:00', []);
    expect(horariosDisponiveis(escala, '2026-08-10', [reserva])).not.toContain('10:00');
  });
});
