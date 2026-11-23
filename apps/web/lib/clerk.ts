/**
 * Flags do Clerk por variável de ambiente. Sem as chaves, o app roda no modo
 * "público" (sem auth) — o site atual continua funcionando; quando as envs
 * forem preenchidas (`apps/web/.env.local`), o Clerk ativa sozinho.
 */
import { dark } from '@clerk/themes';
import type { ClerkAppearanceTheme } from '@clerk/shared/types';

export const clerkAtivo =
  Boolean(process.env.NEXT_PUBLIC_CLERK_PUBLISHABLE_KEY) && Boolean(process.env.CLERK_SECRET_KEY);

/** Aparência padronizada dos componentes do Clerk (dark + dourado dos tokens). */
export const temaClerk: ClerkAppearanceTheme = {
  theme: dark,
  variables: {
    colorPrimary: 'var(--color-primary, #D4AF37)',
    colorBackground: 'var(--color-background, #070707)',
    colorInput: 'var(--color-background-input, #1A1A1A)',
    colorForeground: 'var(--color-foreground, #E8E8E8)',
    colorMutedForeground: 'var(--color-foreground-muted, #9A9A9A)',
    colorInputForeground: 'var(--color-foreground, #E8E8E8)',
    colorNeutral: 'var(--color-foreground-muted, #9A9A9A)',
    colorDanger: 'var(--color-destructive, #E05252)',
    borderRadius: '0px',
  },
};
