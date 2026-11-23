#!/usr/bin/env node
/**
 * Gera `src/tokens.css` a partir de `tokens.json` (fonte da verdade, DTCG).
 *
 * Usa o gerador oficial do skill `design-system`
 * (`.agents/skills/design-system/scripts/generate-tokens.cjs`) e ajusta o
 * seletor do par de tema claro: o gerador emite `.dark { ... }` (convenção do
 * skill), mas o legado usa `[data-theme='claro']` — o layout do apps/web
 * aplica `data-theme` no <html>. O CSS gerado é artefato; edite o JSON.
 */
const { execSync } = require('node:child_process');
const fs = require('node:fs');
const path = require('node:path');

const uiRoot = path.resolve(__dirname, '..');
const generator = path.resolve(uiRoot, '../../.agents/skills/design-system/scripts/generate-tokens.cjs');
const config = path.join(uiRoot, 'tokens.json');
const output = path.join(uiRoot, 'src', 'tokens.css');

execSync(`node "${generator}" --config "${config}" -o "${output}"`, { stdio: 'inherit' });

const css = fs.readFileSync(output, 'utf8');
const ajustado = css.replace(/^\.dark \{/m, `[data-theme='claro'] {`);
fs.writeFileSync(output, ajustado);

console.log('Seletor do tema claro ajustado: .dark → [data-theme=\'claro\']');
