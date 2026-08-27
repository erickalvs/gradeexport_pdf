# 📄 gradeexport_pdf — Plugin de Exportação de Notas em PDF para Moodle

> Exportação direta do Livro de Notas do Moodle para um PDF formatado e profissional, sem necessidade de planilhas ou conversões externas.

---

## 📋 Índice

- [Sobre o Plugin](#sobre-o-plugin)
- [Requisitos](#requisitos)
- [Instalação](#instalação)
- [Configuração Inicial (Administrador)](#configuração-inicial-administrador)
- [Como Usar (Professor)](#como-usar-professor)
  - [Exportar por Grupo Específico](#exportar-por-grupo-específico)
  - [Exportar Todos os Grupos](#exportar-todos-os-grupos)
  - [Opção: Ocultar Atividades sem Notas](#opção-ocultar-atividades-sem-notas)
- [Estrutura do PDF Gerado](#estrutura-do-pdf-gerado)
- [Estrutura de Arquivos do Plugin](#estrutura-de-arquivos-do-plugin)
- [Atualização do Plugin](#atualização-do-plugin)
- [Solução de Problemas](#solução-de-problemas)
- [Licença](#licença)

---

## Sobre o Plugin

O **gradeexport_pdf** é um plugin do tipo `gradeexport` para Moodle que permite que professores e coordenadores exportem o Livro de Notas de uma disciplina **diretamente para PDF**, com formatação profissional pronta para impressão.

### Problema que ele resolve

O fluxo padrão de exportação do Moodle gera uma planilha Excel (.xlsx) que exige que o professor:
1. Abra o arquivo no Excel/LibreOffice
2. Ajuste manualmente colunas cortadas
3. Configure a impressão/orientação da página
4. Exporte para PDF manualmente

Com este plugin, o professor clica em **"Download"** e recebe um PDF formatado, pronto para uso.

### Destaques

| Funcionalidade | Descrição |
|---|---|
| Exportação Direta | Um clique gera o PDF imediatamente |
| Separação por Grupos | Ao exportar "Todos", cada grupo recebe páginas separadas com seu próprio cabeçalho |
| Filtro de Colunas Vazias | Remove automaticamente atividades de outros grupos que não tenham nenhuma nota para o grupo atual |
| Cabeçalho Dinâmico | Nome da instituição, subtítulo, disciplina, professor e grupo impresso no topo de cada página |
| Rodapé Completo | Nome do sistema, data/hora de geração e numeração de páginas |
| Paginação Inteligente | Divide cursos com muitas atividades em páginas extras sem quebrar o layout |
| Nomes de Atividades Legíveis | Títulos longos quebram em múltiplas linhas no cabeçalho da tabela |

---

## Requisitos

| Requisito | Versão Mínima |
|---|---|
| Moodle | 4.5.0 (build 2024100700) |
| PHP | 8.1 ou superior |
| TCPDF | Incluído no Moodle (nativo via lib/pdflib.php) |
| Permissão do usuário | moodle/grade:export e gradeexport/pdf:view |

Nenhuma biblioteca externa é necessária. O plugin usa apenas o wrapper TCPDF nativo já incluso no Moodle.

---

## Instalação

### Pré-requisito
Você precisa ter acesso administrativo ao painel do Moodle.

### Método 1 — Via Upload de ZIP (Recomendado)

1. Acesse seu Moodle como Administrador.
2. No menu superior, vá em: Administração do Site -> Plugins -> Instalar Plugins.
3. Clique em "Instalar plugin de arquivo ZIP".
4. Faça upload do arquivo gradeexport_pdf.zip.
5. Clique em "Instalar plugin a partir do arquivo ZIP".
6. Na tela de validação, selecione o tipo de plugin como "Exportação de notas (gradeexport)".
7. Clique em "Instalar plugin".
8. Na página de atualização do banco de dados, clique em "Atualizar banco de dados do Moodle agora".
9. Instalação concluída!

### Método 2 — Via Servidor (SSH/FTP)

1. Extraia o conteúdo do ZIP. A pasta resultante deve se chamar pdf/.
2. Copie a pasta pdf/ para o servidor no caminho: {MOODLE_ROOT}/grade/export/pdf/
3. Como administrador, acesse o Moodle. O sistema detectará o novo plugin automaticamente.
4. Clique em "Atualizar banco de dados do Moodle agora" quando solicitado.

---

## Configuração Inicial (Administrador)

Após a instalação, configure o cabeçalho padrão que aparecerá em todos os PDFs gerados.

1. Acesse: Administração do Site -> Plugins -> Exportação de notas -> Exportação em PDF.
2. Configure os campos:

| Campo | Descrição | Padrão |
|---|---|---|
| Nome da Instituição | Texto em destaque no topo do PDF | UNIVERSIDADE ESTADUAL DE MONTES CLAROS |
| Subtítulo da Instituição | Segunda linha do cabeçalho | CENTRO DE EDUCAÇÃO A DISTÂNCIA |

3. Clique em "Salvar alterações".

As configurações acima são globais e afetam todos os PDFs gerados por qualquer disciplina da plataforma.

---

## Como Usar (Professor)

### Acessando a Exportação

1. Acesse a disciplina desejada.
2. No menu lateral ou superior, clique em "Notas" -> "Exportar" -> "Formato de documento portável (.pdf)".

### Exportar por Grupo Específico

1. Na tela de exportação, use o seletor de Grupos no topo da página.
2. Escolha o grupo desejado (ex: "Polo Montes Claros", "Turma A", etc.).
3. Selecione as atividades a incluir na lista de itens do Livro de Notas.
4. Clique em "Download".

O PDF gerado conterá apenas os alunos do grupo selecionado e terá o nome do grupo no cabeçalho de cada página.

### Exportar Todos os Grupos

1. No seletor de grupos no topo, deixe selecionada a opção "Todos os participantes".
2. Selecione as atividades desejadas.
3. Clique em "Download".

O PDF gerado será dividido em seções. Cada grupo ocupa suas próprias páginas, com o nome do grupo atualizado dinamicamente no cabeçalho.

Estrutura do PDF com todos os grupos:
- Páginas do Grupo A: somente atividades do Grupo A com nota
- Páginas do Grupo B: somente atividades do Grupo B com nota
- Página final "Sem Grupo": alunos matriculados que não pertencem a nenhum grupo

Um aluno que pertence a mais de um grupo aparecerá em todos os grupos que ele pertence.

### Opção: Ocultar Atividades sem Notas

Na parte inferior da tela de exportação, há a seção "Opções do PDF" com a seguinte opção:

[X] Ocultar atividades sem notas lançadas (marcada por padrão)

O que essa opção faz:

Quando marcada, antes de gerar o PDF, o sistema analisa cada coluna de atividade cruzando com as notas dos alunos do grupo atual. Se nenhum aluno do grupo tiver nota lançada naquela atividade, a coluna inteira é removida do PDF desse grupo.

Caso de uso típico:

Suponha que o curso tenha 3 grupos. O Grupo 1 realizou o "Fórum de Discussão 1" e o Grupo 2 realizou o "Fórum de Discussão 2". Quando o professor exportar o Grupo 1, o "Fórum de Discussão 2" não aparecerá no PDF (e vice-versa).

Quando desmarcar:
Desmarque apenas se quiser visualizar no PDF todas as atividades do Livro de Notas, mesmo que estejam sem lançamento para aquele grupo.

---

## Estrutura do PDF Gerado

### Cabeçalho (em todas as páginas)

    UNIVERSIDADE ESTADUAL DE MONTES CLAROS         <- Nome da Instituição (configurável)
           CENTRO DE EDUCAÇÃO A DISTÂNCIA          <- Subtítulo (configurável)

    Disciplina: Nome Completo da Disciplina
    Professor(a): Nome do Professor
    Grupo: Nome do Grupo Atual

> **Nota sobre o Professor(a):** O relatório filtra e exibe automaticamente apenas os usuários matriculados que possuem explicitamente o papel de **Professor** (`editingteacher`). Usuários com outros papéis (como tutores, gerentes ou administradores) não serão listados no cabeçalho, mesmo que tenham permissões de edição de notas.

### Corpo (tabela de notas)

| Nº | Nome Completo | Atividade 1 | Atividade 2 | Total |
|----|--------------|-------------|-------------|-------|
|  1 | Nome do Aluno |        8,50 |           — |  8,50 |

- "—" indica ausência de nota lançada (campo nulo no banco de dados, diferente de 0,0).
- As atividades com notas 0,0 são exibidas como 0,00.
- O nome do arquivo baixado segue o padrão: notas_{sigla_disciplina}_{data_hora}.pdf

### Rodapé (em todas as páginas)

    [ Nome do Sistema ]    [ Data: 27/08/2026 00:17 ]    [ Página 1 de 3 ]

---

## Estrutura de Arquivos do Plugin

    grade/export/pdf/
    ├── db/
    │   └── access.php              # Definição de capacidades (capabilities)
    ├── lang/
    │   ├── en/
    │   │   └── gradeexport_pdf.php # Strings em inglês
    │   └── pt_br/
    │       └── gradeexport_pdf.php # Strings em português (Brasil)
    ├── pix/
    │   └── icon.png                # Ícone do plugin no menu de exportação
    ├── grade_export_pdf.php        # Classe principal: lógica de geração do PDF
    ├── gradeexport_pdf_form.php    # Formulário customizado com as opções extras
    ├── export.php                  # Ponto de entrada da exportação
    ├── index.php                   # Tela de configuração da exportação
    ├── settings.php                # Configurações globais do plugin (admin)
    └── version.php                 # Metadados de versão do plugin

---

## Atualização do Plugin

Quando uma nova versão do plugin for disponibilizada:

1. Acesse: Administração do Site -> Plugins -> Instalar Plugins.
2. Faça upload do novo arquivo gradeexport_pdf.zip.
3. O Moodle detectará que o número de versão em version.php é maior que o instalado.
4. Clique em "Atualizar banco de dados do Moodle agora" para concluir.

Não é necessário desinstalar o plugin antes de atualizar. O Moodle gerencia isso automaticamente comparando os números de versão.

---

## Solução de Problemas

### Erro: corrupted_archive_structure ao instalar o ZIP

Causa: O arquivo ZIP foi criado com uma ferramenta nativa do Windows que não gera a estrutura de diretórios compatível com o Moodle.
Solução: Regenere o ZIP usando Python:

    import os, zipfile
    zf = zipfile.ZipFile('gradeexport_pdf.zip', 'w', zipfile.ZIP_DEFLATED)
    base_dir = 'grade/export/pdf'
    for r, d, files in os.walk(base_dir):
        for f in files:
            zf.write(os.path.join(r, f), os.path.relpath(os.path.join(r, f), 'grade/export').replace(os.sep, '/'))
    zf.close()

---

### Erro: TCPDF ERROR: Some data has already been output

Causa: O Moodle está com o modo Depuração (Debug) ativo e enviando avisos para o navegador antes do PDF ser gerado.
Solução: Desative temporariamente o modo de depuração:
1. Administração do Site -> Desenvolvimento -> Depuração
2. Defina "Nível de mensagem de depuração" como "Nenhum".

---

### Plugin não aparece no menu de Exportação

Causas possíveis:
1. O plugin não está instalado corretamente. Verifique em: Administração -> Plugins -> Visão geral de Plugins.
2. O usuário não tem a permissão gradeexport/pdf:view no contexto do curso.

Verificação de permissão:
- Acesse: Disciplina -> Participantes -> selecione o professor -> Ver perfil -> Papéis atribuídos
- O papel atribuído (ex: "Professor") deve ter a capacidade gradeexport/pdf:view habilitada.

---

### Notas aparecem como "—" mesmo estando lançadas

Causa: O aluno pode não estar oficialmente matriculado na disciplina com a capacidade moodle/grade:view, ou há um problema de cache do gradebook.
Solução:
1. Verifique se o aluno aparece no Livro de Notas padrão do Moodle.
2. Force a recalculação do gradebook: Administração da Disciplina -> Notas -> Recalcular notas.

---

## Licença

Este plugin é software livre, distribuído sob os termos da GNU General Public License v3.0, a mesma licença do Moodle.

    Copyright (C) 2026 - Desenvolvido para UNIMONTES / CEAD
    Este programa é software livre; você pode redistribuí-lo e/ou modificá-lo
    sob os termos da GNU General Public License.

---

Documentação gerada em 27/08/2026 | Versão do Plugin: 1.0.0-beta (build 2026082608)
