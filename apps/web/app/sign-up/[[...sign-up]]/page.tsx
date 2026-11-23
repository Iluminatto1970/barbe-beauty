import type { Metadata } from 'next';
import { SignUp } from '@clerk/nextjs';
import { redirect } from 'next/navigation';
import { clerkAtivo, temaClerk } from '../../../lib/clerk';

export const metadata: Metadata = {
  title: 'Criar conta — Barbearia PRO',
  robots: { index: false, follow: false },
};

export default function SignUpPage() {
  // Sem as chaves do Clerk (modo público), a rota de auth não faz sentido.
  if (!clerkAtivo) redirect('/');
  return (
    <main className="auth-page">
      <div className="auth-card">
        <h1 className="auth-title">Criar conta</h1>
        <p className="auth-subtitle">Comece a receber agendamentos online.</p>
        <SignUp
          appearance={temaClerk}
          fallbackRedirectUrl="/"
          signInUrl="/sign-in"
        />
      </div>
    </main>
  );
}
