import type { Metadata } from 'next';
import { SignIn } from '@clerk/nextjs';
import { redirect } from 'next/navigation';
import { clerkAtivo, temaClerk } from '../../../lib/clerk';

export const metadata: Metadata = {
  title: 'Entrar — Barbearia PRO',
  robots: { index: false, follow: false },
};

export default function SignInPage() {
  // Sem as chaves do Clerk (modo público), a rota de auth não faz sentido.
  if (!clerkAtivo) redirect('/');
  return (
    <main className="auth-page">
      <div className="auth-card">
        <h1 className="auth-title">Entrar</h1>
        <p className="auth-subtitle">Acesse o painel da sua barbearia.</p>
        <SignIn
          appearance={temaClerk}
          fallbackRedirectUrl="/"
          signUpUrl="/sign-up"
        />
      </div>
    </main>
  );
}
