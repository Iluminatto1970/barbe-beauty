# 💈 Sistema de Gestão para Barbearia

Um sistema completo de gestão para barbearias desenvolvido em PHP com MySQL, oferecendo funcionalidades tanto para clientes quanto para administradores.

## 🚀 Funcionalidades

### Para Clientes (Frontend)

- **Agendamento Online**: Sistema completo de agendamento com seleção de funcionário, data, horário e serviços
- **Catálogo de Produtos**: Visualização de produtos disponíveis
- **Catálogo de Serviços**: Lista de serviços oferecidos com preços
- **Integração WhatsApp**: Contato direto via WhatsApp
- **Design Responsivo**: Interface adaptável para dispositivos móveis

### Para Administradores (Backend)

- **Dashboard Completo**: Indicadores de performance, agendamentos e financeiro
- **Gestão de Agendamentos**: Controle completo da agenda por funcionário
- **Gestão de Funcionários**: Cadastro, horários, dias de trabalho e comissões
- **Gestão Financeira**: Contas a pagar/receber, relatórios de vendas
- **Controle de Estoque**: Produtos, fornecedores e alertas de estoque baixo
- **Gestão de Clientes**: Cadastro completo com histórico e cartão fidelidade
- **Sistema de Permissões**: Controle granular de acesso por módulo
- **Relatórios**: Diversos relatórios gerenciais

## 🛠️ Tecnologias Utilizadas

### Frontend

- **HTML5** e **CSS3**
- **JavaScript** e **jQuery**
- **Bootstrap** para responsividade
- **Owl Carousel** para sliders
- **Font Awesome** para ícones
- **AJAX** para interações dinâmicas

### Backend

- **PHP** (versão 7.0+)
- **MySQL** para banco de dados
- **PDO** para conexão com banco
- **Sistema de Sessões** para autenticação
- **DOMPDF** para geração de relatórios

## 📋 Pré-requisitos

- **XAMPP** ou servidor web com PHP 7.0+
- **MySQL** 5.6+
- **Navegador web** moderno

## 🔧 Instalação

1. **Clone ou baixe o projeto** para a pasta `htdocs` do XAMPP:

   ```
   c:\xampp\htdocs\barbearia\
   ```

2. **Importe o banco de dados**:

   - Acesse o phpMyAdmin
   - Crie um banco chamado `barbearia`
   - Importe o arquivo `barbearia.sql`

3. **Configure a conexão**:

   - Não edite `sistema/conexao.php`: ele lê as credenciais de variáveis de ambiente (fallback local: `localhost`/`root`/senha vazia)
   - Defina no ambiente (ou em um `.env` não versionado usado pelo Docker):
     ```
     DB_HOST=localhost
     DB_PORT=3306
     DB_NAME=barbearia
     DB_USER=root
     DB_PASSWORD=
     ```
   - Em produção use um usuário próprio, com senha forte, fora do repositório

4. **Acesse o sistema**:
   - Frontend: `http://localhost/barbearia`
   - Backend: `http://localhost/barbearia/sistema`

## 🔐 Acesso Padrão

**Administrador:**

## dados_ficticios.sql

- **Email**: admin@barbeariaelite.com
- **Senha**: 123

## barbearia.sql

- **Email**: admin@admin
- **Senha**: 123

## 📁 Estrutura do Projeto

```
barbearia/
├── ajax/                    # Scripts AJAX
├── css/                     # Estilos CSS
├── fonts/                   # Fontes
├── images/                  # Imagens do frontend
├── js/                      # Scripts JavaScript
├── sistema/                 # Sistema administrativo
│   ├── painel/             # Painel administrativo
│   │   └── paginas/        # Módulos do sistema
│   ├── conexao.php         # Configuração do banco
│   └── autenticar.php      # Autenticação
├── agendamentos.php        # Página de agendamentos
├── produtos.php            # Página de produtos
├── servicos.php            # Página de serviços
├── index.php               # Página inicial
└── barbearia.sql           # Estrutura do banco
```

## 🗄️ Estrutura do Banco de Dados

### Tabelas Principais

- **usuarios**: Funcionários e administradores
- **agendamentos**: Controle de horários e serviços
- **clientes**: Cadastro de clientes
- **servicos**: Catálogo de serviços
- **produtos**: Controle de estoque
- **receber/pagar**: Gestão financeira
- **horarios**: Horários disponíveis
- **config**: Configurações do sistema

## 🎨 Personalização

### Configurações do Sistema

Acesse o painel administrativo para personalizar:

- Nome da barbearia
- Logo e imagens
- Informações de contato
- Textos da página inicial
- Configurações de WhatsApp e redes sociais

Para alterar o horário de funcionamento no Google Maps, siga os passos abaixo:

abra o arquivo `index.php` e encontre a linha 1290:

<p><i class="fa fa-clock-o"></i>Seg-Sex: 8h às 18h | Sáb: 8h às 16h</p>

### Estilos

- Edite `css/style.css` para personalizar o frontend
- Modifique `sistema/painel/css/` para o backend

## 📱 Recursos Especiais

- **URLs Amigáveis**: Sistema de reescrita via .htaccess
- **Validação em Tempo Real**: Verificação de horários disponíveis
- **Cadastro Automático**: Clientes são cadastrados automaticamente no agendamento
- **Sistema de Cartão Fidelidade**: Controle de pontos por cliente
- **Alertas de Estoque**: Notificações quando produtos estão em baixa
- **Comissões**: Cálculo automático de comissões por funcionário

## 🔒 Segurança

- Autenticação por sessões PHP
- Senhas criptografadas (MD5)
- Sistema de permissões granular
- Validações de entrada de dados
- Proteção contra SQL Injection (PDO)

---

**Desenvolvido com ❤️ para facilitar a gestão de barbearias**
