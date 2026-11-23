import type { Metadata } from 'next';
import { ClerkProvider } from '@clerk/nextjs';
import '@barbearia/ui/tokens.css';
import '@barbearia/ui/components.css';
import './globals.css';
import './landing.css';
import { clerkAtivo } from '../lib/clerk';

const siteUrl = process.env.NEXT_PUBLIC_SITE_URL ?? 'http://localhost:3000';

export const metadata: Metadata = {
  metadataBase: new URL(siteUrl),
  title: { default: 'Barbearia PRO', template: '%s' },
  description: 'Plataforma de agendamento online para barbearias.',
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="pt-BR" data-theme="escuro">
      <head>
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link
          rel="preconnect"
          href="https://fonts.gstatic.com"
          crossOrigin="anonymous"
        />
        <link
          href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&family=Playfair+Display:wght@600;700&display=swap"
          rel="stylesheet"
        />
      </head>
      {/* Sem chaves do Clerk, o app roda no modo público (sem auth). */}
      <body>
        {clerkAtivo ? (
          <ClerkProvider afterSignOutUrl="/">{children}</ClerkProvider>
        ) : (
          children
        )}
      </body>
    </html>
  );
}
