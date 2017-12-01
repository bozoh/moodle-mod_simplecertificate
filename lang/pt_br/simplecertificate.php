<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Language strings for the simplecertificate module
 *
 * @package    mod
 * @subpackage simplecertificate
 * @copyright  Carlos Alexandre S. da Fonseca <carlos.alexandre@outlook.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['modulename'] = 'Certificado Simples';
$string['modulenameplural'] = 'Certificados Simples';
$string['pluginname'] = 'Certificado Simples';
$string['viewcertificateviews'] = '{$a} certificados emitidos';
$string['summaryofattempts'] = 'Resumo dos certificados emitidos';
$string['issued'] = 'Emitidos';
$string['coursegrade'] = 'Nota do curso';
$string['getcertificate'] = 'Obtenha seu certificado';
$string['awardedto'] = 'Obtido para';
$string['receiveddate'] = 'Data de Recebimento';
$string['grade'] = 'Nota';
$string['code'] = 'Código';
$string['report'] = 'Relatório';
$string['opendownload'] = 'Pressione o botão abaixo para salvar o seu certificado no seu computador.';
$string['openemail'] = 'Pressione o botão abaixo e seu certificado será enviado por email.';
$string['openwindow'] = 'Pressione o botão abaixo para visualizar o seu certificado em uma nova tela.';
$string['hours'] = 'horas';
$string['keywords'] = 'certificate, course, pdf, moodle';
$string['pluginadministration'] = 'Administração de Certificado';
$string['deletissuedcertificates'] = 'Remover os certificados emitidos';
$string['nocertificatesissued'] = 'Nenhum certificado emitido';

// Form.
$string['certificatename'] = 'Nome do certificado';
$string['certificateimage'] = 'Arquivo de Imagem do Certificado';
$string['certificatetext'] = 'Texto do Certificado';
$string['certificatetextx'] = 'Posição Horizontal do texto do certificado';
$string['certificatetexty'] = 'Posição Vertical do texto do certificado';
$string['height'] = 'Altura do certificado';
$string['width'] = 'Largura do certificado';
$string['coursename'] = 'Nome alternativo do curso';
$string['intro'] = 'Introdução';
$string['printoutcome'] = 'Imprimir resultado (Outcome)';
$string['printdate'] = 'Tipo de data do certificado';

// Second Page.
$string['secondpageoptions'] = 'Página de Verso do certificado';
$string['enablesecondpage'] = 'Ativar o verso do Certificado';
$string['enablesecondpage_help'] = 'Ativa a edição do verso do certificado, se estiver desativado, somente o código QR do certificado será impresso (se código QR estiver ativo)';
$string['secondimage'] = 'Imagem do verso do certificate';
$string['secondimage_help'] = 'Esta figura será usada no verso do certificado';
$string['secondpagetext'] = 'Texto do verso do certificado';
$string['secondpagex'] = 'Posição Horizontal do texto do verso';
$string['secondpagey'] = 'Posição Vertical do texto do verso';
$string['secondtextposition'] = 'Posição do texto do verso';
$string['secondtextposition_help'] = 'As coordenadas XY (em milímetros) do texto do verso';

// QR Code.
$string['printqrcode'] = 'Imprimir o QR Code do certificado';
$string['printqrcode_help'] = 'Habilita ou desabilita a impressão do QR Code do certificado';
$string['codex'] = 'Posição Horizontal do QR Code do Certificado';
$string['codey'] = 'Posição Vertical do QR Code do Certificado';
$string['qrcodeposition'] = 'Posicionamento do QR Code do Certificado';
$string['qrcodeposition_help'] = 'Essas são as coordenadas XY (em milímetros) do QR Code do certificado';
$string['defaultcodex'] = 'Posição Horizontal padrão do QR code do certificado';
$string['defaultcodey'] = 'Posição Vertical padrão do QR code do certificado';

// Date options.
$string['issueddate'] = 'Data da emissão';
$string['completiondate'] = 'Data do fim do curso';
$string['datefmt'] = 'Formato da Data';

// Date format options.
$string['userdateformat'] = 'Formato definido pelas definições do usuário';

$string['printgrade'] = 'Tipo de nota do certificado';
$string['gradefmt'] = 'Formato da nota';
// Grade format options.
$string['gradeletter'] = 'Nota por Conceito';
$string['gradepercent'] = 'Nota por perrcentual';
$string['gradepoints'] = 'Nota por pontos';
$string['coursetimereq'] = 'Minutos mínimos de participação no curso';
$string['emailteachers'] = 'Enviar email para os Professores';
$string['emailothers'] = 'Enviar email para outros';
$string['emailfrom'] = 'Nome alternativo do remetente do email';
$string['delivery'] = 'Envio';
// Delivery options.
$string['openbrowser'] = 'Visualizar em uma nova janela';
$string['download'] = 'Forçar o download';
$string['emailcertificate'] = 'por Email';
$string['nodelivering'] = 'Sem envio, o usuário vai receber o certificado por outros meios';

// Form options help text.

$string['certificatename_help'] = 'Nome do certificado';
$string['certificatetext_help'] = 'Este é o texto que será usado no certificado, algums marcadores especiais serão substituidos por variáveis do certificado, como o nome do curos, nome do estudante, notas...
Os marcadores são:

<ul>
<li>{USERNAME} -> Nome completo do aluno</li>
<li>{COURSENAME} -> Nome compledo do curso (ou o que estiver definido em "Nome Alternativo do Curso")</li>
<li>{GRADE} -> Nota formatada</li>
<li>{DATE} -> Data formatada</li>
<li>{OUTCOME} -> Resultados (Outcomes)</li>
<li>{TEACHERS} -> Lista de professores</li>
<li>{IDNUMBER} -> User id number</li>
<li>{FIRSTNAME} -> Nome</li>
<li>{LASTNAME} -> Sobrenome</li>
<li>{EMAIL} -> E-mail</li>
<li>{ICQ} -> ICQ</li>
<li>{SKYPE} -> Skype</li>
<li>{YAHOO} -> Yahoo messenger</li>
<li>{AIM} -> AIM</li>
<li>{MSN} -> MSN</li>
<li>{PHONE1} -> 1° Número de telefone</li>
<li>{PHONE2} -> 2° Número de telefone</li>
<li>{INSTITUTION} -> Instituição</li>
<li>{DEPARTMENT} -> Departamento</li>
<li>{ADDRESS} -> Endereço</li>
<li>{CITY} -> Cidade</li>
<li>{COUNTRY} -> País</li>
<li>{URL} -> Home-page</li>
<li>{CERTIFICATECODE} -> Texto do código do certificado</li>
<li>{USERROLENAME} -> Nome do papel do usuário no curso</li>
<li>{TIMESTART} -> Data de inscrição do usuário no curso</li>
<li>{USERIMAGE} -> Imagem do perfil do usuário</li>
<li>{USERRESULTS} -> Resultados (notas) dos usuarios de outras atividades do curso</li>
<li>{PROFILE_xxxx} -> Campos personalizados</li>
</ul>
<p>
Para usar campos personalizados deve usar o prefixo "PROFILE_", por exemplo, se criar um campo com a abreviação (shortname) de aniversario, então deve-se usar o marcador
"PROFILE_ANIVERSARIO"
O texto pode ser um HTML básico, com fontes básicas do HTLM, tabelas, mas evitar o uso de posicionamento.</p>';


$string['textposition'] = 'Posicionamento do Texto do Certificado';
$string['textposition_help'] = 'Essas são as coordenadas XY (em milímetros) do texto do certificado';
$string['size'] = 'Tamanho do Certificado';
$string['size_help'] = 'Esse é o tamanho, Largura e Altura (em milímetros) do certificado, o padrão é A4 paisagem';
$string['coursename_help'] = 'Nome alternativo do curso que vai ser impresso no certificado';
$string['certificateimage_help'] = 'Esta figura será usada no certificado';

$string['printoutcome_help'] = 'Você pode escolher qualquer resultado (outcome) definido neste curso. Será impresso o nome do resultado e o resultado recebido  pelo usuário. Um exemplo poderia ser: Resultado: Proficiente.';

$string['printdate_help'] = 'Esta é a data que será impressa, você pose escolher entre a data que o aluno completou o curso, ou a data de emissão do certificado.
Também pode-se escolher a data que uma certa atividade foi corrigida, se em algum dos casos o aluno não tiver a data, então a data de emissão será usada.';
$string['datefmt_help'] = 'Coloque um formato de data válido aceito pelo PHP (<a href="http://www.php.net/manual/en/function.strftime.php"> Date Formats</a>). ou deixe-o em branco para usar o valor de formatação padrão definido pela a configuração de idioma do usuário.';
$string['printgrade_help'] = 'Pode-se escolher a nota que será impressa no certificado, esta pode ser a nota final do curso ou a nota em uma atividade.';
$string['gradefmt_help'] = 'Pode-se escolher o formato da nota que são:<br>
<ul>
<li>Nota por percentual: Imprime a nota como um percentual.</li>
<li>Nota pot pontos: Imprime a nota por pontos, o valor da nota tirada.</li>
<li>Nota por conceito: Imprime o conceito relacionado a nota obtida (A, A+, B, ...).</li>
</ul>';

$string['coursetimereq_help'] = 'Coloque o tempo mínimo de participação (em minutos) que um aluno deve ter para conseguir obter o certificado';
$string['emailteachers_help'] = 'Quando habilitado, os professores recebem os emails toda vez que um aluno emitir um certificado.';
$string['emailothers_help'] = 'Digite os endereços de emails que vão receber o alerta de emissão de certificado.';
$string['emailfrom_help'] = 'Nome a ser usado como remetente dos email enviados';
$string['delivery_help'] = 'Escolha como o certificado deve ser entregue aos alunos:<br>
<ul>
<li>Visualizar em uma nova janela: Abre uma nova janela no navegador do aluno contendo o certificado.</li>
<li>Forçar o download: Abre uma janela de download de arquivo para o aluno salvar em seu computador.</li>
<li>por Email: Envia o certificado para o email do aluno, e abre o certificado em uma nova janela do navegador.</li>
</ul><p>
Depois que estudante emite seu certificado, se ele clicar na atividade certificado aparecerá a data de emissão do certificado e ele poderá revisar ocertificado emitido</p>';


// Form Sections.
$string['issueoptions'] = 'Opções de Emissão';
$string['designoptions'] = 'Opções de Design';

// Emails text.
$string['emailstudentsubject'] = 'Seu certificado do curso {$a->course}';
$string['emailstudenttext'] = '
Olá {$a->username},

	Segue em anexo o certificado do curso: {$a->course}.


ESTA É UMA MENSAGEM AUTOMÁTICA, NÃO RESPONDA POR FAVOR';

$string['emailteachermail'] = '
{$a->student} recebeu o certificado: \'{$a->certificate}\' para o curso
{$a->course}.

Você pode vê-lo aqui:

    {$a->url}';

$string['emailteachermailhtml'] = '
{$a->student} recebeu o certificado: \'<i>{$a->certificate}</i>\'
para o curso {$a->course}.

Você pode vê-lo aqui:

    <a href="{$a->url}">Certificate Report</a>.';


// Admin settings page.
$string['defaultwidth'] = 'Largura Padrão';
$string['defaultheight'] = 'Altura Padrão';
$string['defaultcertificatetextx'] = 'Posição Horizontal padrão do texto do certificado';
$string['defaultcertificatetexty'] = 'Posição Vertical padrão do texto do certificado';

// Erros.
$string['filenotfound'] = 'Arquivo não encontrado';
$string['cantdeleteissue'] = 'Ocorreu um erro ao remover os certificados emitidos';
$string['requiredtimenotmet'] = 'Você precisa ter ao menos {$a->requiredtime} minutos nesse curso para emitir este certificado';

// Settings.
$string['certlifetime'] = 'Manter os certificados emitidos por: (em Meses)';
$string['certlifetime_help'] = 'Está opção especifica por quanto tempo deve ser guardado um certificado emitido. Certificados emitidos mais velhos que o tempo determinado nesta opção será automaticamente removidos.';
$string['neverdeleteoption'] = 'Nunca remover';

$string['variablesoptions'] = 'Outras Opções';
$string['getcertificate'] = 'Obter o Certificado';
$string['verifycertificate'] = 'Verificar Certificado';

$string['qrcodefirstpage'] = 'Imprimir o código QR na primeira página';
$string['qrcodefirstpage_help'] = 'Imprime o código QR na primeira página';

// Tabs String.
$string['standardview'] = 'Emitir um certificado de teste';
$string['issuedview'] = 'Certificados emitidos';
$string['bulkview'] = 'Operações em lote';

$string['cantissue'] = 'O certificado não pode ser emitido pois o usuário não atigiu a meta da atividade';

// Bulk Texts.
$string['onepdf'] = 'Download de um único arquivo pdf com todos os certificados';
$string['multipdf'] = 'Download de um arquivo zip com os pdfs dos certidicados';
$string['sendtoemail'] = 'Enviar os certificados para o email do usuário';
$string['showusers'] = 'Mostrar';
$string['completedusers'] = 'Usuários que atingiram os objetivos definidos';
$string['allusers'] = 'Todos os usuários';
$string['bulkaction'] = 'Escolha a operação em lote';
$string['bulkbuttonlabel'] = 'Enviar';
$string['emailsent'] = 'Os emails foram enviados';

$string['issueddownload'] = 'Certificado [id: {$a}] baixado';
$string['defaultperpage'] = 'Por página';
$string['defaultperpage_help'] = 'Quantidade de certificados exibidos por página (Max. 200)';
$string['certificateverification'] = 'Verificação do Certificado';
$string['invalidcode'] = 'Código do certificado é inválido';

// For Capabilities.
$string['simplecertificate:addinstance'] = "Adicionar uma atividade cerfiticado simples";
$string['simplecertificate:manage'] = "Gerenciar uma atividade certificado simples";
$string['simplecertificate:view'] = "Visualizar um certificado simples";

$string['usercontextnotfound'] = 'Contexto de usuário não encontrado';
$string['usernotfound'] = 'Usuário não encontrado';
$string['coursenotfound'] = 'Curso não encontrado';
$string['issuedcertificatenotfound'] = 'Certificado não encontrado';
$string['awardedsubject'] = 'Notificão de obtenção de certificado: {$a->certificate} emitido para {$a->student}';
$string['certificatenot'] = 'Simple certificate instance not found';
$string['modulename_help'] = 'A atividade certificado simples possibilita que seja criado certificados costumisáveis que podem ser emitidos pelos participantes que completaram os requerimentos espicificados.';
$string['timestartdatefmt'] = 'Formato da data de inicio da inscrição';
$string['timestartdatefmt_help'] = 'Coloque um formato de data válido aceito pelo PHP (<a href="http://www.php.net/manual/pt_BR/function.strftime.php">Formato de datas</a>). ou deixe-o em branco para usar o valor de formatação padrão definido pela a configuração de idioma do usuário.';
$string['certificatecopy'] = 'CÓPIA';
$string['upgradeerror'] = 'Erro durante o upgrade $a';
$string['notreceived'] = 'Certificado não emitido';

// Verify envent.
$string['eventcertificate_verified'] = 'Certificado verificado';
$string['eventcertificate_verified_description'] = 'O usuário com o id {$a->userid} verificou o certificado com o id {$a->certificateid}, emetido para o usuário com id {$a->certiticate_userid}.';

$string['deleteall'] = "Remover todos";
$string['deleteselected'] = "Remover Selecionados";
