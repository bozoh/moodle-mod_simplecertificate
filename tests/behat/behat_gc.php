<?php
use WebDriver\Exception\ElementIsNotSelectable;
use Behat\Gherkin\Node\TableNode as TableNode;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\Mink\Exception\ExpectationException;

/**
 * Cadastro de usuário
 *
 * @package auth_gc
 * @category test
 * @author Carlos Alexandre S. da Fonseca
 * @copyright © 2016 onwards to Carlos Alexandre S. da Fonseca - All rights reserved
 * @license © 2016 Carlos Alexandre S. da Fonseca - All rights reserved
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.
require_once (__DIR__ . '/../../../../lib/behat/behat_base.php');
require_once (__DIR__ . '/../../../../lib/behat/behat_base.php');

// require_once(__DIR__.'/../../../../lib/testing/classes/util.php');
// require_once(__DIR__ . '/../../../../lib/phpunit/classes/phpmailer_sink.php');
// require_once(__DIR__ . '/../../../../lib/phpunit/classes/util.php');
// require_once(__DIR__ . '/../../../../lib/phpunit/classes/base_testcase.php');
// require_once(__DIR__ . '/../../../../lib/phpunit/classes/advanced_testcase.php');
// require_once(__DIR__ . '/../gc_auth_test.php');

/**
 * Log in log out steps definitions.
 *
 * @package core_auth
 * @category test
 * @copyright 2012 David Monllaó
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_gc extends behat_base {
  // private $forms;
  // private $general;
  // private $auth;
  function __construct() {
    // parent::__construct();
    // $this->tool=new auth_gc_testcase();
  }

  /**
   * Ativa o registro de usuario com o plugin GC
   * @Given /^O registro de usuarios tem que estar ativo com a opção "(?P<option_string>(?:[^"]|\\")*)Plugin de registro para a Gestão de conhecimento"$/
   */
  public function ativa_plugin_gc($option_value) {
    set_config('registerauth', 'gc');
    set_config('auth', 'gc');
    unset_config('passwordpolicy');
    // $authplugin = get_auth_plugin($CFG->registerauth);
    $this->add_custom_profile_fields();
  }

  protected function add_custom_profile_fields() {
    global $DB;
    
    $DB->insert_record('user_info_field', 
        array('shortname' => 'identidade', 'name' => 'Nº Documento de Identidade', 'required' => 1, 
              'datatype' => 'text', 'signup' => 1));
    $DB->insert_record('user_info_field', 
        array('shortname' => 'orgexp', 'name' => 'Órgão Expedidor', 'required' => 1, 'datatype' => 'text', 
              'signup' => 1));
    $DB->insert_record('user_info_field', 
        array('shortname' => 'cpf', 'name' => 'CPF', 'required' => 1, 'datatype' => 'text', 'signup' => 1));
    $DB->insert_record('user_info_field', 
        array('shortname' => 'tipomilitar', 'name' => 'Tipo de Militar', 'required' => 1, 'datatype' => 'text', 
              'signup' => 1));
    $DB->insert_record('user_info_field', 
        array('shortname' => 'orgaoformacao', 'name' => 'Órgão de Formação', 'required' => 1, 'datatype' => 'text', 
              'signup' => 1));
    $DB->insert_record('user_info_field', 
        array('shortname' => 'anoformacao', 'name' => 'Ano Formação', 'required' => 1, 'datatype' => 'text', 
              'signup' => 1));
    $DB->insert_record('user_info_field', 
        array('shortname' => 'armaquadroservico', 'name' => 'Arma/Quadro/Serviço', 'required' => 1, 
              'datatype' => 'text', 'signup' => 1));
    $DB->insert_record('user_info_field', 
        array('shortname' => 'preccp', 'name' => 'PREC/CP', 'required' => 1, 'datatype' => 'text', 'signup' => 1));
    $DB->insert_record('user_info_field', 
        array('shortname' => 'orgmilitar', 'name' => 'Organização Militar', 'required' => 1, 'datatype' => 'text', 
              'signup' => 1));
    $DB->insert_record('user_info_field', 
        array('shortname' => 'localom', 'name' => 'Local da OM', 'required' => 1, 'datatype' => 'text', 'signup' => 1));
    $DB->insert_record('user_info_field', 
        array('shortname' => 'logradouro', 'name' => 'Logradouro', 'required' => 0, 'datatype' => 'text', 'signup' => 1));
    $DB->insert_record('user_info_field', 
        array('shortname' => 'numero', 'name' => 'Número', 'required' => 0, 'datatype' => 'text', 'signup' => 1));
    $DB->insert_record('user_info_field', 
        array('shortname' => 'complemento', 'name' => 'Complemento', 'required' => 0, 'datatype' => 'text', 
              'signup' => 1));
    $DB->insert_record('user_info_field', 
        array('shortname' => 'bairro', 'name' => 'Bairro', 'required' => 0, 'datatype' => 'text', 'signup' => 1));
    $DB->insert_record('user_info_field', 
        array('shortname' => 'cidade', 'name' => 'cidade', 'required' => 0, 'datatype' => 'text', 'signup' => 1));
    $DB->insert_record('user_info_field', 
        array('shortname' => 'uf', 'name' => 'UF', 'required' => 0, 'datatype' => 'text', 'signup' => 1));
    $DB->insert_record('user_info_field', 
        array('shortname' => 'cep', 'name' => 'CEP', 'required' => 0, 'datatype' => 'text', 'signup' => 1));
    $DB->insert_record('user_info_field', 
        array('shortname' => 'telefone', 'name' => 'Telefone', 'required' => 0, 'datatype' => 'text', 'signup' => 1));
    $DB->insert_record('user_info_field', 
        array('shortname' => 'celular', 'name' => 'Celular', 'required' => 0, 'datatype' => 'text', 'signup' => 1));
    $DB->insert_record('user_info_field', 
        array('shortname' => 'areainteresse', 'name' => 'Áreas de Interesse', 'required' => 1, 'datatype' => 'text', 
              'signup' => 1));
  }

  /**
   *
   * @Then /^o campo "(?P<nome_string>(?:[^"]|\\")*)" deve ter o valor "(?P<valor_string>(?:[^"]|\\")*)"$/
   *
   * @param unknown $nome          
   * @param unknown $valor          
   */
  public function campo_valor($nome, $valor) {
    $this->execute("behat_forms::the_field_matches_value", array($this->escape($nome), $this->escape($valor)));
  }

  /**
   * Carrega a página selecionada
   * @Given /^[E|e]u estou na página "(?P<page_string>(?:[^"]|\\")*)"$/
   */
  public function eu_estou_na_pagina($page_name) {
    switch ($page_name) {
      case "formulário de cadastro":
        $this->getSession()->visit($this->locate_path('login/signup.php'));
      break;
      
      case "homepage":
        $this->getSession()->visit($this->locate_path('/'));
      break;
      
      default:
        throw new Exception("Página não encontrada $page_name");
        break;
    }
  }

  /**
   * Preenche um campo com um valor
   * @Given /^preencho o campo "(?P<field_name_string>(?:[^"]|\\")*)" com "(?P<field_value_string>(?:[^"]|\\")*)"$/
   */
  public function preencho_o_campo_com($field_name, $field_value) {
    $this->execute('behat_forms::i_set_the_field_to', 
        array($this->escape($field_name), $this->escape($field_value)));
  }

  /**
   * Seleciona uma opção de um select box/list
   * seleciono a opção "DF no campo "UF"
   * @Given /^seleciono a opção "(?P<field_value_string>(?:[^"]|\\")*)" no campo "(?P<field_name_string>(?:[^"]|\\")*)"$/
   */
  public function seleciono_opcao_campo($filed_value, $field_name) {
    $this->preencho_o_campo_com($field_name, $filed_value);
  }

  /**
   * Seleciona uma opção de um option
   * @Given /^marco a opção "(?P<field_value_string>(?:[^"]|\\")*)" no campo "(?P<field_name_string>(?:[^"]|\\")*)"$/
   */
  public function marco_opcao_campo($field_value, $field_name) {
    $this->execute('behat_general::i_click_on', 
        array($this->escape($field_value), 'radio'));
  }

  /**
   * Clica em um botão
   * @Given /^clico no "(?P<type_string>(?:[^"]|\\")*)" "(?P<name_string>(?:[^"]|\\")*)"$/
   */
  public function clico_no($type, $name) {
    $name = $this->escape($name);
    switch ($type) {
      case 'link':
        $this->execute('behat_general::click_link', array($name));
      break;
      
      default: // botão
        $this->execute('behat_general::i_click_on', 
            array($name, 'button'));
      // $this->execute('behat_forms::press_button', $this->escape($button_name));
      break;
    }
  }

  /**
   * Deve ver um texto
   * @Then /^devo ver "(?P<text_string>(?:[^"]|\\")*)"$/
   */
  public function devo_ver($texto) {
    $this->execute('behat_general::assert_page_contains_text', $this->escape($texto));
  }

  /**
   * Seleciona uma opção de um option
   * @Then /^não devo ver "(?P<text_string>(?:[^"]|\\")*)"$/
   */
  public function nao_devo_ver($texto) {
    $this->execute('behat_general::assert_page_not_contains_text', $this->escape($texto));
  }

  /**
   * @Given /^com os seguintes cursos configurados:$/
   *
   * @param unknown $data          
   */
  public function seguintes_cursos(TableNode $data) {
    $this->execute('behat_data_generators::the_following_exist', 
        array('courses', $data));
  }

  protected function get_courses_id($courses) {
    global $DB;
    
    var_export($courses);
    $courses = explode(',', $courses);
    $retval = array();
    foreach ($courses as $course) {
      $retval[] = $DB->get_record('course', array('fullname' => $course), 'id')->id;
    }
    return $retval;
  }

  protected function pre_processs_user_data($data) {
    global $CFG;
    $user = new stdClass();
    
    $user->username = $data['Login'];
    $user->email = $data['E-mail'];
    $user->password = $data['Senha'];
    
    $user->nome = $data['Nome'];
    $user->nomeguerra = $data['Nome de Guerra'];
    $user->postograduacao = $data['Posto-Graduação'];
    
    // Esse valores são criados pelo login/signup.php, mas como
    // não passo por ele, preciso gerar os valores.
    $user->confirmed = 0;
    $user->auth = 'gc';
    $user->lang = current_language();
    $user->firstaccess = 0;
    $user->timecreated = time();
    $user->mnethostid = $CFG->mnet_localhost_id;
    $user->secret = random_string(15);
    
    // Campos obrigatórios
    $user->profile_field_identidade = $data['Nº Documento de Identidade'];
    $user->profile_field_orgexp = $data['Órgão Expedidor'];
    $user->profile_field_cpf = $data['CPF'];
    $user->profile_field_tipomilitar = $data['Tipo de Militar'];
    $user->profile_field_orgaoformacao = $data['Órgão de Formação'];
    $user->profile_field_anoformacao = $data['Ano de Formação'];
    $user->profile_field_armaquadroservico = $data['Arma/Quadro/Serviço'];
    $user->profile_field_preccp = $data['PREC/CP'];
    $user->profile_field_orgmilitar = $data['Organização Militar'];
    $user->profile_field_localom = $data['Local da OM'];
    $user->profile_field_areainteresse = $this->get_courses_id($data['Área de Interesse']);
    
    // Dados Opcionais
    if (!empty($data['Logradouro'])) $user->profile_field_logradouro = $data['Logradouro'];
    if (!empty($data['Número'])) $user->profile_field_numero = $data['Número'];
    if (!empty($data['Complemento'])) $user->profile_field_complemento = $data['Complemento'];
    if (!empty($data['Bairro'])) $user->profile_field_bairro = $data['Bairro'];
    if (!empty($data['Cidade'])) $user->profile_field_cidade = $data['Cidade'];
    if (!empty($data['UF'])) $user->profile_field_uf = $data['UF'];
    if (!empty($data['CEP'])) $user->profile_field_cep = $data['CEP'];
    if (!empty($data['Telefone'])) $user->profile_field_telefone = $data['Telefone'];
    if (!empty($data['Celular'])) $user->profile_field_celular = $data['Celular'];
    
    return $user;
  }

  /**
   * @Given /^com os usuários não confirmado com os dados:$/
   *
   * @param unknown $data          
   */
  public function add_usuarios_nao_confirmados(TableNode $data) {
    global $CFG;
    require_once (__DIR__ . '/../../../../auth/gc/auth.php');
    
    $auth_gc = new auth_plugin_gc();
    foreach ($data->getHash() as $datahash) {
      $user = $this->pre_processs_user_data($datahash);
      $auth_gc->user_signup($user, false);
    }
  }

  /**
   * @Given /^[E|e]u me logo como "(?P<text_string>(?:[^"]|\\")*)"$/
   *
   * @param unknown $username          
   */
  public function me_logo_como($username) {
    $this->execute("behat_auth::i_log_in_as", $this->escape($username));
    $this->nao_devo_ver('Invalid login, please try again');
    $this->preencho_o_campo_com("Tipo de Militar", "---");
    $this->preencho_o_campo_com("Órgão de Formação", "---");
    $this->preencho_o_campo_com("Ano Formação", "---");
    $this->preencho_o_campo_com("Arma/Quadro/Serviço", "---");
    $this->preencho_o_campo_com("PREC/CP", "---");
    $this->preencho_o_campo_com("Organização Militar", "---");
    $this->preencho_o_campo_com("Local da OM", "---");
    $this->clico_no("botão", "Update profile");
  }

  /**
   * @Given /^vou para página de confirmação do usuário "(?P<text_string>(?:[^"]|\\")*)"$/
   *
   * @param unknown $username          
   */
  public function pagina_confirmacao_usuario($username) {
    // global $DB;
    
    // $user_secret = $DB->get_record('user', array(
    // 'username' => $username), 'secret')->secret;
    // $this->getSession()->visit($this->locate_path("/auth/gc/confirm.php?data=$user_secret/$username"));
    $this->getSession()->visit($this->locate_path("/auth/gc/confirm.php?s=$username"));
  }

  /**
   *
   * @Given /^o usuario "(?P<text_string>(?:[^"]|\\")*)" deve estar confirmado$/
   * 
   * @param unknown $username          
   */
  public function usuario_confirmado($username) {
    $username = $this->escape($username);
    $user = get_complete_user_data('username', $username);
    if (!$user->confirmed) {
      throw new Exception("Usuário $username não esta confirmado:\n" . serialize($user));
    }
  }
  
  
  /**
   *
   * @Given /^o usuario "(?P<text_string>(?:[^"]|\\")*)" deve estar removido$/
   *
   * @param unknown $username
   */
  public function usuario_removido($username) {
    $username = $this->escape($username);
    $user = get_complete_user_data('username', $username);
    if ($user) {
      throw new Exception("Usuário $username não foi removido:\n" . serialize($user));
    }
  }
  
  /**
   * 
   * @Given /^vou para a listagem de usuários do site$/ 
   */
  public function listagem_usuario() {
    $this->getSession()->visit($this->locate_path('/admin/user.php'));
    try {
      $this->clico_no('botão', 'Save changes');
    } catch (ElementNotFoundException $e) {
      // Faz nada, as vezes redireciona para uma página de update de configuração para
      // aceitar as configuraões padrões
    }
  }

  /**
   * @Given /^o usuario "(?P<usnermae_string>(?:[^"]|\\")*)" deve estar matriculado no curso "(?P<coursename_string>(?:[^"]|\\")*)"$/
   */
  public function verifica_matricula($username, $coursename) {
    global $DB;
    
    if ($course = $DB->get_record('course', array('fullname'=>$coursename))){
      $course_ctx = context_course::instance($course->id);
      if ($users = get_enrolled_users($course_ctx)) {
        foreach ($users as $user) {
          if ($user->username == $username)
            return;
        }
      }
      //Se chegar aqui é pq o usuário não esta no curso
      throw new Exception('Usuário '.$username.' não está matriculado no '.$coursename);
    } else {
      throw new Exception('Curso '.$coursename.' não encontrado');
    }
  }
}