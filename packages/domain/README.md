# @barbearia/domain

Regras de negócio do Barbearia PRO em **TypeScript puro**, extraídas do sistema legado
PHP, com **testes de invariante** (Vitest). Sem dependências de runtime — nada de
banco/HTTP aqui; só funções puras.

## Módulos

| Módulo | Regras portadas | Fonte no legado |
|---|---|---|
| `scheduling.ts` | dias trabalhados, slots, disponibilidade, reserva (conflito) | `app/ajax/listar-horarios.php`, `app/ajax/agendar.php`, tabelas `dias`/`horarios`/`agendamentos` |
| `commission.ts` | cálculo de comissão (Porcentagem / R$) | `app/sistema/painel/paginas/agenda/inserir-servico.php`, `config.tipo_comissao`, `servicos.comissao` |
| `service-completion.ts` | fluxo "concluir serviço": receber + comissão (se pago na hora) + fidelidade + data de retorno | `agenda/inserir-servico.php` (canônica) e `servicos_agenda/salvar.php` |
| `finance.ts` | contas a pagar/receber: criação/edição, baixas, baixa de receber gera comissão, totais e vencidas | `receber/{salvar,baixar,listar}.php`, `pagar/{salvar,baixar,listar}.php` |
| `password.ts` | verificação de senha em cascata MD5 → bcrypt (migração do legado): `verificarSenha` + `deveMigrar` + `gerarHashBcrypt` | `usuarios.senha_crip` (MD5 hex, ex.: `202cb962…` = md5('123')) |

## Modelo de conflito (agendamento)

O legado não tem duração nem sobreposição parcial: o conflito é a **tripla
(data, hora, funcionario)** — um slot discreto de 30 min por funcionário. Agendar
09:00 não bloqueia 09:30 do mesmo funcionário.

## Descobertas do legado (corrigidas/documented na extração)

1. **Bug do sábado**: `listar-horarios.php` monta `'Sabado'` (sem acento), mas o banco
   grava `'Sábado'` — sábado nunca era reconhecido como dia trabalhado.
   `normalizarDia()` aceita ambas as formas (teste de regressão em
   `test/scheduling.test.ts`).
2. **Inconsistência do cartão fidelidade**: `agenda/inserir-servico.php` reinicia o
   cartão quando `cartoes >= quantidade_cartoes`; `servicos_agenda/salvar.php` sempre
   soma 1. A extração adota a regra com reinício como canônica.
3. **Sem validação de entrada**: comissão/valor negativos não eram validados;
   percentual > 100 passava. A extração valida negativos/não-numéricos (erro), mas
   mantém o pass-through de percentual > 100 para não mudar comportamento.
4. **Checagem fora da UI**: `agendar.php` não conferia se a hora era um slot da escala
   do funcionário (só conflito). `reservarSlot()` agora exige o slot na escala.
5. **Comissão duplicada na dupla baixa**: `receber/baixar.php` criava a comissão a
   pagar a cada clique em "baixar"; um duplo clique gerava DUAS comissões. A extração
   lança erro ao baixar conta já baixada.
6. **`pago` e `data_pgto` dessincronizados**: o UPDATE de `salvar.php` não recalculava
   `pago` ao editar (limpar `data_pgto` deixava `pago='Sim'`). Aqui `pago` é derivado
   de `dataPgto` (null = pendente, no MySQL o sentinela era `'0000-00-00'`).
7. **Uma comissão por receber**: `agenda/inserir-servico.php` só cria a comissão
   quando o receber nasce pago; pendente, ela sai na baixa (`baixarReceber`). Um port
   anterior criava sempre — duplicaria a comissão na baixa. Corrigido com teste.

> O `password.ts` usa `node:crypto` (MD5 — o legado não tinha salt) e `bcryptjs`
> (JS puro): a única dependência de runtime do pacote; os demais módulos seguem puros.

## Comandos

```bash
pnpm --filter @barbearia/domain test       # testes de invariante
pnpm --filter @barbearia/domain build      # compila para dist/
pnpm --filter @barbearia/domain typecheck
```
