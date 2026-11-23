# @barbearia/ui

Design system do Barbearia PRO — tokens + componentes React 19, identidade
**dark + dourado** (portada de `app/css/style.css` do legado PHP).

## Arquitetura de tokens (três camadas)

```
Primitive (valores crus)          →  tokens.json → primitive
Semantic  (propósito/tema)        →  tokens.json → semantic + dark.semantic
Component (por componente)        →  tokens.json → component
```

- **Fonte da verdade:** `tokens.json` (formato W3C DTCG). **Nunca edite
  `src/tokens.css` à mão** — ele é gerado.
- **Gerar:** `pnpm --filter @barbearia/ui tokens` — usa o gerador do skill
  `design-system` (`.agents/skills/design-system/scripts/generate-tokens.cjs`)
  e ajusta o seletor do par de tema claro para `[data-theme='claro']` (o gerador
  emite `.dark`; o legado/layout usa `data-theme`).
- **Temas:** `:root` = escuro (padrão do legado); `[data-theme='claro']` =
  override semântico do tema claro.
- **`--bp-*` (camada `semantic.bp`):** aliases de compatibilidade com a
  nomenclatura legada — o `apps/web` consome estes. Não criar novos usos;
  preferir `--color-*`, `--space-*`, `--font-*`.
- **Componentes:** o CSS dos componentes (`components.css`) referencia apenas
  tokens de componente (`--button-*`, `--input-*`, `--card-*`).

## Exemplo (migração do padrão flat)

```css
/* Antes (flat) */
--bp-dourado: #D4AF37;            /* primitive/uso direto misturados */

/* Depois (três camadas) */
--primitive-color-gold-500: #D4AF37;   /* primitive  */
--color-primary: #D4AF37;              /* semantic   */
--button-bg: #D4AF37;                  /* component  */
```

## Validação

```bash
node .agents/skills/design-system/scripts/validate-tokens.cjs --dir apps/web/app --ignore .next
```

## Comandos

```bash
pnpm --filter @barbearia/ui tokens      # regenera src/tokens.css
pnpm --filter @barbearia/ui build
pnpm --filter @barbearia/ui typecheck
```
