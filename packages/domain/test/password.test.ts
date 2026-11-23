import { describe, expect, it } from 'vitest';
import {
  formatoDoHash,
  gerarHashBcrypt,
  gerarHashBcryptAsync,
  pareceBcrypt,
  pareceMd5,
  verificarSenha,
  verificarSenhaAsync,
  verificarSenhaMd5,
} from '../src/password';

// hash real do legado: senha_crip = md5('123') em sql/barbearia.sql
const MD5_123 = '202cb962ac59075b964b07152d234b70';

describe('password — cascata MD5 → bcrypt (migração do legado)', () => {
  it('reconhece os formatos de hash', () => {
    expect(pareceMd5(MD5_123)).toBe(true);
    expect(pareceMd5('xyz')).toBe(false);
    expect(pareceMd5('202CB962AC59075B964B07152D234B70')).toBe(true); // maiúsculo
    expect(pareceBcrypt('$2b$10$abcdefghijklmnopqrstuv')).toBe(true);
    expect(pareceBcrypt('$2a$12$abcdefghijklmnopqrstuv')).toBe(true);
    expect(pareceBcrypt(MD5_123)).toBe(false);
    expect(formatoDoHash(MD5_123)).toBe('md5');
    expect(formatoDoHash('$2b$10$abcdefghijklmnopqrstuv')).toBe('bcrypt');
    expect(formatoDoHash('outro')).toBe('desconhecido');
  });

  it('verifica o hash MD5 real do dump (123 → 202cb962…)', () => {
    expect(verificarSenhaMd5(MD5_123, '123')).toBe(true);
    expect(verificarSenhaMd5(MD5_123, 'senha-errada')).toBe(false);
    expect(verificarSenhaMd5('não-hex', '123')).toBe(false);
  });

  it('cascata: MD5 valida com deveMigrar=true; bcrypt valida sem migrar', () => {
    const md5 = verificarSenha('123', MD5_123);
    expect(md5).toEqual({ valida: true, deveMigrar: true });

    const hashBcrypt = gerarHashBcrypt('123');
    const bcrypt = verificarSenha('123', hashBcrypt);
    expect(bcrypt).toEqual({ valida: true, deveMigrar: false });
    expect(verificarSenha('outra', hashBcrypt).valida).toBe(false);
  });

  it('formato desconhecido nunca valida (sem vazamento)', () => {
    expect(verificarSenha('123', 'qualquer-coisa')).toEqual({
      valida: false,
      deveMigrar: false,
    });
  });

  it('upgrade: hash bcrypt gerado volta a validar (login pós-migração)', async () => {
    const novoHash = await gerarHashBcryptAsync('123');
    expect(await verificarSenhaAsync('123', novoHash)).toEqual({
      valida: true,
      deveMigrar: false,
    });
    expect(await verificarSenhaAsync('errada', novoHash)).toEqual({
      valida: false,
      deveMigrar: false,
    });
  });
});
