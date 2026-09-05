# Memórias em Nuvem

> Viva o presente e preserve seu legado.

O **Memórias em Nuvem** é uma aplicação web desenvolvida em PHP voltada para a organização pessoal, preservação de memórias e gerenciamento de rotinas do dia a dia[cite: 3, 5]. A plataforma centraliza diário pessoal em blocos interativos, álbuns de fotos, cápsulas do tempo, controle financeiro, lembretes inteligentes e registros de saúde.

---

## Funcionalidades Principais

* **Diário Interativo:** Criação e edição de entradas utilizando o EditorJS, com suporte a blocos de texto, títulos, listas, anexos de mídia e links diretos para faixas do Spotify e vídeos do YouTube[cite: 5].
* **Autenticação Dupla:** Login tradicional por CPF e senha com proteção contra tentativas de força bruta (bloqueio temporário), além de login social via Google OAuth 2.0 com fluxo de cadastro complementar[cite: 1, 2, 3].
* **Galeria & Álbuns de Fotos:** Envio de imagens com geração de diretórios segregados por usuário e agrupamento em álbuns temáticos[cite: 2, 5].
* **Cápsulas do Tempo:** Criação de mensagens com data de desbloqueio programada para destinatários específicos, com anexos de foto, áudio e ilustrações[cite: 5].
* **Gestão Financeira:** Controle de transações (receitas e despesas categorizadas), metas financeiras e transações recorrentes[cite: 5].
* **Saúde & Rotina:** Gerenciamento de medicamentos, controle de consultas médicas, exames laboratoriais e monitoramento diário de humor, água e sono[cite: 5].
* **Lembretes com Notificações:** Sistema de agendamento de tarefas e lembretes com controle de disparo de alertas periódicos e expiração[cite: 5].

---

## Tecnologias Utilizadas

* **Linguagem Backend:** PHP 8.2+[cite: 5]
* **Banco de Dados:** MySQL / MariaDB via PDO[cite: 2, 5]
* **Autenticação:** Google OAuth 2.0 Client (`google/apiclient`)[cite: 2, 3]
* **Editor de Texto Rico:** EditorJS (com plugins de Header, List e Paragraph)
* **Frontend:** HTML5, CSS3 estruturado com variáveis customizadas, Flexbox/Grid e JavaScript Vanilla[cite: 3]
* **Disparo de E-mails:** APIs SendGrid e Brevo

---

## Requisitos do Sistema

* **Servidor Web:** Apache (XAMPP recomendado)[cite: 2, 4]
* **PHP:** Versão 8.2 ou superior (necessário para compatibilidade nativa com Monolog 3 e Google Client)
* **Extensões PHP:** `pdo_mysql`, `curl`, `fileinfo`, `session`
* **Banco de Dados:** MySQL 5.7+ ou MariaDB 10.4+[cite: 5]

---

## Como Instalar e Rodar Localmente

### 1. Clonar ou Mover o Projeto
Coloque a pasta do projeto dentro do diretório raiz do seu servidor Apache[cite: 2]:
```text
C:\xampp\htdocs\backup_arquivos\memorias_em_nuvem\