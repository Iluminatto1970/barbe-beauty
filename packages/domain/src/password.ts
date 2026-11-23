/**
 * Senhas — migração do legado (Fase 1).
 *
 * O MySQL guardava a senha em claro em `senha` E o hash MD5 em `senha_crip`
 * (ex.: `202cb962ac59075b964b07152d234b70` = md5('123')). Na migração para o
 * Postgres, o `senha_crip` vira o hash canônico da tabela `usuarios.senha`.
 *
 * Estratégia em cascata:
 *  - se o hash é bcrypt (`$2a$`/`$2b$`/`$2y$`) → verifica com bcrypt;
 *  - se é MD5 (32 hex) → verifica com MD5 e sinaliza `deveMigrar` (o login
 *    re-grava o hash como bcrypt na primeira autenticação bem-sucedida);
 *  - qualquer outro formato → senha inválida (sem vazamento de informação).
 *
 * O MD5 usa `node:crypto` (hash puro, sem salt — formato do legado) e o bcrypt
 * usa `bcryptjs` (JS puro, sem dependência nativa).
 */
import { createHash, randomBytes } from 'node:crypto';
import { compare, compareSync, hash, hashSync } from 'bcryptjs';

const MD5_HEX = /^[a-f0-9]{32}$/i;
const BCRYPT = /^\$2[aby]\$\d{2}\$/;

/** True se o hash é MD5 hex de 32 chars (formato do legado). */
export function pareceMd5(hash: string): boolean {
  return MD5_HEX.test(hash);
}

/** True se o hash é bcrypt ($2a$/$2b$/$2y$ + custo). */
export function pareceBcrypt(hash: string): boolean {
  return BCRYPT.test(hash);
}

/**
 * Formato do hash aceito para `verificarSenha` (e qual algoritmo usa).
 * `desconhecido` = nem MD5 nem bcrypt → sempre falso.
 */
export type FormatoHash = 'md5' | 'bcrypt' | 'desconhecido';

export function formatoDoHash(hash: string): FormatoHash {
  if (pareceBcrypt(hash)) return 'bcrypt';
  if (pareceMd5(hash)) return 'md5';
  return 'desconhecido';
}

/**
 * Verifica contra o hash MD5 do legado (comparação em hex, case-insensitive —
 * o PHP `md5()` emite minúsculas; aceitamos maiúsculas por robustez).
 */
export function verificarSenhaMd5(hashMd5: string, senha: string): boolean {
  if (!pareceMd5(hashMd5)) return false;
  const candidato = createHash('md5').update(senha).digest('hex');
  return candidato.toLowerCase() === hashMd5.toLowerCase();
}

/**
 * Verificação em cascata: bcrypt → MD5 → inválido.
 * `deveMigrar` indica que o hash é MD5 e deve ser re-gravado como bcrypt
 * após o login bem-sucedido (ver `gerarHashBcrypt`).
 */
export function verificarSenha(senha: string, hashArmazenado: string): {
  valida: boolean;
  deveMigrar: boolean;
} {
  const formato = formatoDoHash(hashArmazenado);
  if (formato === 'bcrypt') {
    return { valida: compareSync(senha, hashArmazenado), deveMigrar: false };
  }
  if (formato === 'md5') {
    return { valida: verificarSenhaMd5(hashArmazenado, senha), deveMigrar: true };
  }
  return { valida: false, deveMigrar: false };
}

/** Gera um hash bcrypt (para upgrade após login MD5). Custo padrão 10. */
export function gerarHashBcrypt(senha: string, custo = 10): string {
  return hashSync(senha, custo);
}

// Variantes assíncronas (sem bloquear o event loop no servidor) — o node:crypto
// md5 é síncrono; o bcryptjs tem versão async.

export async function verificarSenhaAsync(
  senha: string,
  hashArmazenado: string,
): Promise<{ valida: boolean; deveMigrar: boolean }> {
  const formato = formatoDoHash(hashArmazenado);
  if (formato === 'bcrypt') {
    return { valida: await compare(senha, hashArmazenado), deveMigrar: false };
  }
  if (formato === 'md5') {
    return { valida: verificarSenhaMd5(hashArmazenado, senha), deveMigrar: true };
  }
  return { valida: false, deveMigrar: false };
}

export async function gerarHashBcryptAsync(senha: string, custo = 10): Promise<string> {
  return hash(senha, custo);
}

/** Salt aleatório (não usado pelo MD5 do legado — documentado para o futuro). */
export function saltAleatorio(bytes = 16): string {
  return randomBytes(bytes).toString('hex');
}
