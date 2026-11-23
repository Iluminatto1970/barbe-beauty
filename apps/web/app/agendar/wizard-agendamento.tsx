'use client';

import { useMemo, useState } from 'react';
import {
  HorarioIndisponivelError,
  horariosDisponiveis,
  reservarSlot,
  trabalhaNesteDia,
  type Agendamento,
} from '@barbearia/domain';
import type { Tenant } from '../../lib/types';
import { criarAgendamento } from './acoes';

const PASSOS = ['Dados', 'Profissional', 'Horário', 'Serviço', 'Resumo'] as const;

interface Props {
  tenant: Tenant;
  agendamentos: Agendamento[];
}

function hojeISO(): string {
  const d = new Date();
  const f = (n: number) => String(n).padStart(2, '0');
  return `${d.getFullYear()}-${f(d.getMonth() + 1)}-${f(d.getDate())}`;
}

function formatarDataBR(iso: string): string {
  const [ano, mes, dia] = iso.split('-');
  return `${dia}/${mes}/${ano}`;
}

function preco(valor: number): string {
  return `R$ ${valor.toFixed(2).replace('.', ',')}`;
}

export function WizardAgendamento({ tenant, agendamentos }: Props) {
  const [passo, setPasso] = useState(0);
  const [nome, setNome] = useState('');
  const [telefone, setTelefone] = useState('');
  const [funcionarioId, setFuncionarioId] = useState('');
  const [data, setData] = useState('');
  const [hora, setHora] = useState('');
  const [servicoNome, setServicoNome] = useState('');
  const [erro, setErro] = useState('');
  const [enviando, setEnviando] = useState(false);
  const [confirmado, setConfirmado] = useState(false);
  const [erroFinal, setErroFinal] = useState('');

  const funcionario = tenant.funcionarios.find((f) => f.id === funcionarioId);
  const servico = tenant.servicos.find((s) => s.nome === servicoNome);

  // Regra de domínio: slots livres = escala menos agendamentos conflitantes (data+hora+funcionario)
  const slots = useMemo(() => {
    if (!funcionario || !data) return [];
    return horariosDisponiveis(funcionario.escala, data, agendamentos);
  }, [funcionario, data, agendamentos]);

  const trabalha = funcionario && data ? trabalhaNesteDia(funcionario.escala, data) : false;

  const passoValido = (() => {
    switch (passo) {
      case 0:
        return nome.trim().length > 0 && telefone.trim().length >= 8;
      case 1:
        return funcionarioId !== '';
      case 2:
        return data !== '' && hora !== '';
      case 3:
        return servicoNome !== '';
      default:
        return true;
    }
  })();

  function avancar() {
    setErro('');
    if (passo === 2 && funcionario) {
      // Invariante central do domínio: só avança se o slot estiver livre
      try {
        reservarSlot(funcionario.escala, data, hora, agendamentos);
      } catch (e) {
        setErro(
          e instanceof HorarioIndisponivelError ? e.message : 'Horário indisponível',
        );
        return;
      }
    }
    setPasso((p) => Math.min(p + 1, PASSOS.length - 1));
  }

  const mensagemWhats = [
    `Olá! Gostaria de confirmar meu agendamento na ${tenant.nome}:`,
    `• Serviço: ${servicoNome}`,
    `• Profissional: ${funcionario?.nome ?? ''}`,
    `• Data: ${formatarDataBR(data)}`,
    `• Horário: ${hora}`,
    `• Nome: ${nome}`,
    `• Telefone: ${telefone}`,
  ].join('\n');

  const linkWhats = `http://api.whatsapp.com/send?1=pt_BR&phone=${tenant.whatsapp}&text=${encodeURIComponent(mensagemWhats)}`;

  /** Grava no banco (a validação de slot é re-feita no servidor). */
  async function confirmar() {
    setEnviando(true);
    setErroFinal('');
    const resultado = await criarAgendamento({
      tenantSlug: tenant.slug,
      nome,
      telefone,
      funcionarioId,
      data,
      hora,
      servicoNome,
    });
    setEnviando(false);
    if (resultado.ok) {
      setConfirmado(true);
    } else {
      setErroFinal(resultado.erro ?? 'Não foi possível confirmar o agendamento.');
    }
  }

  return (
    <div className="wizard">
      <h1>Agende seu horário</h1>
      <p className="sub">
        {tenant.nome} · {tenant.horario}
      </p>

      <ol className="wizard-steps">
        {PASSOS.map((rotulo, i) => (
          <li
            key={rotulo}
            className={`passo ${i === passo ? 'ativo' : i < passo ? 'concluido' : ''}`}
          >
            <span className="num">{i + 1}</span> {rotulo}
          </li>
        ))}
      </ol>

      <div className="passo-conteudo">
        {passo === 0 && (
          <>
            <h2>Seus dados</h2>
            <div className="campos">
              <label>
                Nome
                <input
                  value={nome}
                  onChange={(e) => setNome(e.target.value)}
                  placeholder="Seu nome"
                  autoComplete="name"
                />
              </label>
              <label>
                Telefone (WhatsApp)
                <input
                  value={telefone}
                  onChange={(e) => setTelefone(e.target.value)}
                  placeholder="(11) 99999-9999"
                  inputMode="tel"
                  autoComplete="tel"
                />
              </label>
            </div>
          </>
        )}

        {passo === 1 && (
          <>
            <h2>Escolha o profissional</h2>
            <div className="opcoes">
              {tenant.funcionarios.map((f) => (
                <button
                  key={f.id}
                  type="button"
                  className={`opcao ${f.id === funcionarioId ? 'selecionado' : ''}`}
                  onClick={() => {
                    setFuncionarioId(f.id);
                    setHora('');
                  }}
                >
                  <span className="opcao-titulo">{f.nome}</span>
                  <span className="opcao-sub">
                    {f.escala.diasTrabalho.length} dias por semana
                  </span>
                </button>
              ))}
            </div>
          </>
        )}

        {passo === 2 && (
          <>
            <h2>Data e horário</h2>
            <div className="campos">
              <label>
                Data
                <input
                  type="date"
                  min={hojeISO()}
                  value={data}
                  onChange={(e) => {
                    setData(e.target.value);
                    setHora('');
                  }}
                />
              </label>
            </div>
            {data && !trabalha && (
              <p className="sem-slots">Este profissional não trabalha neste dia. Escolha outra data.</p>
            )}
            {data &&
              trabalha &&
              (slots.length > 0 ? (
                <div className="slots">
                  {slots.map((s) => (
                    <button
                      key={s}
                      type="button"
                      className={`slot ${s === hora ? 'selecionado' : ''}`}
                      onClick={() => setHora(s)}
                    >
                      {s}
                    </button>
                  ))}
                </div>
              ) : (
                <p className="sem-slots">Nenhum horário disponível nesta data.</p>
              ))}
          </>
        )}

        {passo === 3 && (
          <>
            <h2>Escolha o serviço</h2>
            <div className="opcoes">
              {tenant.servicos.map((s) => (
                <button
                  key={s.nome}
                  type="button"
                  className={`opcao ${s.nome === servicoNome ? 'selecionado' : ''}`}
                  onClick={() => setServicoNome(s.nome)}
                >
                  <span className="opcao-titulo">{s.nome}</span>
                  <span className="opcao-sub">{preco(s.valor)}</span>
                </button>
              ))}
            </div>
          </>
        )}

        {passo === 4 && (
          <>
            <h2>Confirme seu agendamento</h2>
            <div className="resumo">
              <div className="resumo-linha">
                <span className="rotulo">Serviço</span>
                <span className="valor">
                  {servicoNome} {servico ? `— ${preco(servico.valor)}` : ''}
                </span>
              </div>
              <div className="resumo-linha">
                <span className="rotulo">Profissional</span>
                <span className="valor">{funcionario?.nome ?? ''}</span>
              </div>
              <div className="resumo-linha">
                <span className="rotulo">Data</span>
                <span className="valor">{formatarDataBR(data)}</span>
              </div>
              <div className="resumo-linha">
                <span className="rotulo">Horário</span>
                <span className="valor">{hora}</span>
              </div>
              <div className="resumo-linha">
                <span className="rotulo">Nome</span>
                <span className="valor">{nome}</span>
              </div>
              <div className="resumo-linha">
                <span className="rotulo">Telefone</span>
                <span className="valor">{telefone}</span>
              </div>
            </div>
          </>
        )}

        {erro && <p className="erro">{erro}</p>}
        {erroFinal && <p className="erro">{erroFinal}</p>}

        {confirmado ? (
          <div className="confirmado">
            <h2>Agendamento confirmado! ✅</h2>
            <p>
              {formatarDataBR(data)} às {hora} com {funcionario?.nome} — {servicoNome}.
              Enviamos seu agendamento para a {tenant.nome}.
            </p>
            <a className="btn-dourado" href={linkWhats} target="_blank" rel="noreferrer">
              Enviar também pelo WhatsApp
            </a>
          </div>
        ) : (
          <div className="botoes">
            {passo > 0 && (
              <button type="button" className="btn-secundario" onClick={() => setPasso((p) => p - 1)}>
                Voltar
              </button>
            )}
            {passo < PASSOS.length - 1 ? (
              <button type="button" className="btn-dourado" disabled={!passoValido} onClick={avancar}>
                Avançar
              </button>
            ) : (
              <button
                type="button"
                className="btn-dourado"
                disabled={enviando}
                onClick={confirmar}
              >
                {enviando ? 'Confirmando…' : 'Confirmar agendamento'}
              </button>
            )}
          </div>
        )}
      </div>
    </div>
  );
}
