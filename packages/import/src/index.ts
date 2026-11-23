import { parseDump } from './parse-dump.js';
import { mapearImportacao } from './map.js';
import type { Importacao } from './map.js';

export { parseDump, splitValues, desescapar, DumpInvalidoError } from './parse-dump.js';
export type { TabelaDump } from './parse-dump.js';
export { mapearImportacao } from './map.js';
export type { Importacao } from './map.js';

/**
 * Atalho de uma linha: parseia o dump e já mapeia para o tenant.
 * (Em produção, o leitor da fonte vira SELECT no MySQL ao vivo; o mapeamento
 * permanece o mesmo.)
 */
export function importarDump(sql: string, tenantId: string): Importacao {
  return mapearImportacao(tenantId, parseDump(sql));
}
