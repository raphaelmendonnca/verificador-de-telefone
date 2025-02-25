# Verificador de Números v1.0 - Plugin WordPress

Um plugin WordPress que permite verificar se números de telefone/WhatsApp estão cadastrados em uma base de dados interna. Ideal para empresas e organizações que desejam oferecer um serviço de verificação de números para seus usuários.

![Banner Verificador de Números](https://caminho-para-sua-imagem-de-banner.jpg)

## Descrição

O plugin **Verificador de Números** cria um sistema simples e eficiente para gerenciar uma base de dados de números de telefone/WhatsApp e permite que os visitantes do seu site verifiquem se determinado número está cadastrado nessa base.

### Principais Características

- ✅ Cadastro e gerenciamento de números de telefone
- ✅ Shortcode `[verificador_numeros]` para adicionar o formulário de verificação em qualquer página ou post
- ✅ Painel de administração completo para gerenciar números
- ✅ Sistema de logs de verificações com dados como IP e resultado da consulta
- ✅ Opções de exportação e importação de números via CSV
- ✅ Mensagens personalizáveis para resultados positivos e negativos
- ✅ Interface amigável e responsiva

## Instalação

1. Faça o download do arquivo ZIP deste repositório
2. No painel administrativo do WordPress, navegue até **Plugins > Adicionar Novo > Enviar Plugin**
3. Escolha o arquivo ZIP que você baixou e clique em **Instalar Agora**
4. Após a instalação, clique em **Ativar Plugin**

Alternativamente, você pode instalar o plugin manualmente:

1. Faça o download deste repositório
2. Extraia o conteúdo para a pasta `/wp-content/plugins/verificador-numeros/` do seu WordPress
3. Ative o plugin através do menu 'Plugins' no WordPress

## Uso

### Adicionando o Formulário de Verificação

Use o shortcode `[verificador_numeros]` em qualquer página ou post onde deseja exibir o formulário de verificação de números.

### Administração

Após a ativação, um novo menu **Verificador de Números** estará disponível no painel administrativo do WordPress com as seguintes opções:

- **Verificador de Números**: Painel principal para adicionar, importar e exportar números
- **Configurações**: Personalize mensagens e opções de retenção de logs
- **Logs**: Visualize e gerencie o histórico de verificações realizadas

### Adicionando Números

Existem duas formas de adicionar números à base de dados:

1. **Individualmente**: Use o formulário no painel principal
2. **Importação em Massa**: Faça upload de um arquivo CSV contendo os números (um por linha)

### Configurações Personalizáveis

No menu de **Configurações**, você pode:

- Personalizar a mensagem exibida quando um número é encontrado
- Personalizar a mensagem exibida quando um número não é encontrado
- Definir o período de retenção dos logs de verificação

## Requisitos

- WordPress 5.0 ou superior
- PHP 7.2 ou superior
- MySQL 5.6 ou superior

## FAQ

### O plugin funciona com qualquer formato de número?

Sim. O plugin automaticamente limpa formatações como parênteses, traços e espaços, armazenando apenas os dígitos numéricos.

### É possível limitar o número de verificações por usuário?

Sim. O plugin tem um limite de 50 tentativas por sessão para evitar uso abusivo.

### As verificações são registradas?

Sim. Por padrão, o plugin armazena informações como número verificado, IP do usuário, resultado da verificação e data/hora. O período de retenção pode ser configurado.

## Suporte

Para suporte ou solicitações de recursos, abra uma [issue no GitHub](link-para-issues-do-seu-repositório) ou entre em contato pelo site [raphamendonca.com](https://raphamendonca.com).

## Changelog

### 1.0
- Primeira versão estável para produção
- Correção de bug: mensagem específica ao tentar adicionar número duplicado
- Melhorias na experiência de usuário e na interface de administração
- Otimização do código e da performance

### 0.1.4 beta
- Melhorias na interface de administração
- Ajustes no sistema de importação e exportação

### 0.1.3 beta
- Adicionado sistema de importação/exportação de números via CSV
- Melhorias na interface do usuário

### 0.1.2 beta
- Implementação do sistema de logs
- Correções de segurança

### 0.1.1 beta
- Lançamento inicial beta

## Licença

Este projeto é licenciado sob a [GPL v2 ou posterior](https://www.gnu.org/licenses/gpl-2.0.html).

## Créditos

Desenvolvido por [Rapha Mendonça](https://raphamendonca.com).
