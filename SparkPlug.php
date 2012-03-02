<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * SparkPlug
 * http://code.google.com/p/sparkplug/
 *
 * Generator:
 * Generates a basic CRUD environment for any given database table
 * Generated code is defined by the templates found at the bottom of the class
 *
 * Dynamic:
 * Creates a dynamic CRUD environment for any given database table
 *
 * @author Pascal Kriete
 * @author Jason Wheeler (updated to v0.4 to work with CodeIgniter v2.0)
 *
 * Thanks vascopj on the ci forums for his suggestions
 **/

class SparkPlug extends CI_Controller {
	var $CI;				// CI Super Object
	var $table;				// Table specified in the constructor
	
	/* Generated */
	var $ucf_controller;	//Name of controller (ucfirst)
	var $controller;		// What the route says the controller's name is
	var $model_name;		// Name of the model (strtolower, ucfirst)
	
	/* Dynamic */
	var $base_uri;		// URI string of the calling constructor/function (all forms submit to this uri)
	var $request;			// Array of added segments
	
	
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	
		$this->CI =& get_instance();
		
		$this->CI->load->database();
		$this->CI->load->library('session');
		$this->CI->load->helper('form');
		$this->CI->load->helper('url');
	}
	
	private function setTable($table) {
		if (!$this->CI->db->table_exists($table)) {
			die('Table <strong>'.$table.'</strong> does not exist.');
		}
		
		$this->table = $table;
	}
	
	/**
	 * Public Function
	 *
	 * Starts the dynamic scaffolding process
	 */
	function scaffold($name = '') {
		if (empty($name)) {
			echo "Choose a table to scaffold:<br /><br />";
			$tables = $this->db->list_tables();
			forEach ($tables as $table) {
				echo anchor("SparkPlug/scaffold/$table", $table) . "<br />";
			}
		} else {
			$this->setTable($name);

			/* Get rid of the CI default nonsense and set real path */
			$route =& load_class('Router');

			$this->base_uri = $route->directory.'/'.$route->class.'/'.$route->method.'/'.$name;

			/* Did we call a subfunction - catch it here */
			$segs = $this->CI->uri->segment_array();
			$last = array_search($route->method, $segs);	// Everything beyond this is ours
			
			$this->request = array('');
			if ($last < count($segs)) {
				$this->request = array_slice($segs, $last);
			}

			echo $this->_processRequest();
		}
	}
	
	
	/**
	 * Public Function
	 * 
	 * Starts the code generation process
	 */
	function generate($name = '') {
		if (empty($name)) {
			echo "Choose a table to generate:<br /><br />";
			$tables = $this->db->list_tables();
			forEach ($tables as $table) {
				echo anchor("SparkPlug/generate/$table", $table) . "<br />";
			}
		} else {
			$this->setTable($name);

			/* Create model name based on table */
			$this->model_name = ucfirst(strtolower($this->table)) . "_model";
			
			/* Figure out the calling controller - that's the one we want to fix */
			$route =& load_class('Router');
			$this->controller = $this->table;
			$this->ucf_controller = strtolower($this->table);
			
			echo $this->_generate();  //** FUNCTION FOUND BELOW (l.370) **//
		}
	}
	
	
	/************************************** * * * * * **************************************/
	/**************************************           **************************************/
	/**************************************  DYNAMIC  **************************************/
	/**************************************           **************************************/
	/************************************** * * * * * **************************************/
	
	/**
	 * Process any additional uri-segments
	 *
	 * If we have no extra segments we check if anything was submitted
	 */
	function _processRequest() {

		/* Check if something was submitted */
		$action = $this->CI->input->post('action');
		
		switch ($action) {
			case 'add':
				$this->_db_insert();
				break;
			case 'edit':
				$this->_db_edit();
				break;
			case 'delete':
				$this->_db_delete();
				break;
		}
		
		$output = $this->_header();

		/* All forms submit to index, so we may be somewhere else */
		switch (@$this->request[1]) {
			case 'show':
				$output .= $this->_show();
				break;
			case 'add':
				$output .= $this->_insert();
				break;
			case 'edit':
				$output .= $this->_edit();
				break;
			case 'delete':
				$this->_delete();
			default:
			case 'list':
				$output .= $this->_list();
				break;
		}
		
		$output .= $this->_footer();
		
		return $output;
	}
	
	/*****								*****/
	/*****		PROCESS DB ACTIONS		*****/
	/*****								*****/
	
	function _db_insert() {
		unset($_POST['action']);
		unset($_POST['submit']);
		
		$this->CI->db->insert($this->table, $_POST);
		
		$this->CI->session->set_flashdata('msg', 'Entry Added');
		redirect($this->base_uri);
	}
	
	function _db_edit() {
		unset($_POST['action']);
		unset($_POST['submit']);
		
		$this->CI->db->where('id', $_POST['id']);
		$this->CI->db->update($this->table, $_POST);
		
		$this->CI->session->set_flashdata('msg', 'Entry Modified');
		redirect($this->base_uri);
	}
	
	function _db_delete($id) {
		$this->CI->db->where('id', $id);
		$this->CI->db->delete($this->table);

		$this->CI->session->set_flashdata('msg', 'Entry Deleted');
		redirect($this->base_uri);
	}
	
	
	/*****								*****/
	/*****		SHOW FORMS AND DATA		*****/
	/*****								*****/
	
	//Special case - here so that "Delete" can be a link instead of a button
	function _delete() {
		$id = $this->request[2];
		$this->_db_delete($id);
	}

	function _list() {		
		$query = $this->CI->db->get($this->table);
		$fields = $this->CI->db->list_fields($this->table);

		$form = "<h1>List - {$this->table}</h1>";

		$form .= '<table><tr>';
		forEach ($fields as $field) {
		   $form .= '<th>'.ucfirst($field).'</th>';
		}
		$form.= '</tr>';

		forEach ($query->result_array() as $row) {
			$form.= '<tr>';
			forEach ($fields as $field) {
				$form.= '<td>'.$row[$field].'</td>';
			}
			
			$form.= '<td>'.$this->_show_link($row['id']).'</td>'.
							'<td>'.$this->_edit_link($row['id']).'</td>'.
							'<td>'.$this->_delete_link($row['id']).'</td>';

			$form.= '</tr>';
		}
		$form.= '</table>';
		
		$form .= $this->_insert_link();
		
		return $form;
	}
	
	function _show() {
		$form = "<h1>Show - {$this->table}</h1>";
		
		$id = $this->request[2];
		$this->CI->db->where('id', $id);
		$query = $this->CI->db->get($this->table);
		
		$data = $query->result_array();
		
		forEach ($data[0] as $field_name => $field_value) {
			$form .= '<p>
				  <b>'.ucfirst($field_name).':</b>'.$field_value.'
				  </p>';
		}
		$form .= $this->_back_link();
		
		return $form;
	}

	function _insert() {
		$form = "<h1>New - {$this->table}</h1>";
		
		$fields = $this->CI->db->field_data($this->table);
		$form .= form_open($this->base_uri);
		
		forEach($fields as $field) {
			$form .= $this->_insertMarkup($field);
		}

		$form .= form_hidden('action', 'add');
		$form .= form_submit('submit', 'Insert').'</p>';
		$form .= form_close();
		
		$form .= $this->_back_link();
		
		return $form;
	}
	
	function _edit() {
		$form = "<h1>Edit - {$this->table}</h1>";

		$id = $this->request[2];
		$this->CI->db->where('id', $id);
		$query = $this->CI->db->get($this->table);
		
		$data = $query->result_array();
		
		$fields = $this->CI->db->field_data($this->table);
		
		$form .= form_open($this->base_uri);
		
		forEach($fields as $field) {
			$form .= $this->_editMarkup($field, $data[0]);
		}

		$form .= form_hidden('action', 'edit');
		$form .= '<p>'.$this->_back_link();
		$form .= form_submit('submit', 'Update').'</p>';
		$form .= form_close();
		return $form;		
	}
	
	/**
	 * Dynamic Forms
	 */

	function _insertMarkup($field) {
		if ($field->primary_key) {
			return '<input type="hidden" name="'.$field->name.'" value="" />';
		}
		
		else {
			
			$form_markup = "\n\t<p>\n";
			$form_markup .= '	<label for="'.$field->name.'">'.ucfirst($field->name).'</label>';
			$form_markup .= "<br/>\n\t";

			switch ($field->type) {
				case 'int':
					$form_markup .= form_input($field->name, '');
					break;
				case 'blob':
					$form_markup .= form_textarea($field->name, '');
					break;
				case 'datetime':
					$form_markup .= form_input($field->name, date("Y-m-d H:i:s"));
					break;
				case 'string':
				case 'char':
				case 'varchar':
				default:
					$form_markup .= form_input($field->name, '');
					break;
				
			}
		
			$form_markup .= "\t</p>\n";
			return $form_markup;
			
		}
		
	}
	
	function _editMarkup($field, $data) {
		if ($field->primary_key) {
			return '<input type="hidden" name="'.$field->name.'" value="'.$data[$field->name].'" />';
		}

		else {

			$form_markup = "\n\t<p>\n";
			$form_markup .= '	<label for="'.$field->name.'">'.ucfirst($field->name).'</label>';
			$form_markup .= "<br/>\n\t";
		
			switch ($field->type) {
				case 'int':
					$form_markup .= form_input($field->name, $data[$field->name]);
					break;
				case 'blob':
					$form_markup .= form_textarea($field->name, $data[$field->name]);
					break;
				case 'datetime':
					$form_markup .= form_input($field->name, $data[$field->name]);
					break;
				case 'string':
				case 'char':
				case 'varchar':
				default:
					$form_markup .= form_input($field->name, $data[$field->name]);
					break;
			}
		
			$form_markup .= "\n\t</p>\n";
			return $form_markup;
			
		}
	}
	
	/*****								*****/
	/*****		HELPER FUNCTIONS		*****/
	/*****								*****/
	
	function _delete_link($id) {
		return anchor($this->base_uri.'/delete/'.$id, 'Delete');
	}
	
	function _edit_link($id) {
		return anchor($this->base_uri.'/edit/'.$id, 'Edit');
	}
	
	function _show_link($id) {
		return anchor($this->base_uri.'/show/'.$id, 'View');
	}
	
	function _insert_link() {
		return anchor($this->base_uri.'/add', 'New');
	}
	
	function _back_link() {
		return anchor($this->base_uri, 'Back');
	}
	
	function _header() {
		return '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
				"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
				<html lang="en">
				<head>
				<meta http-equiv="Content-type" content="text/html; charset=utf-8">
				<meta name="Developer" content="Pascal Kriete" />
				<title>Scaffolding - '.ucfirst($this->table).'</title>
				</head>
				<body>
				<p style="color: green">'.$this->CI->session->flashdata('msg').'</p>';
	}
	
	function _footer() {
		return '</body></html>';
	}
	
	
	/************************************** * * * * * **************************************/
	/**************************************           **************************************/
	/************************************** GENERATED **************************************/
	/**************************************           **************************************/
	/************************************** * * * * * **************************************/
	
	/**
	 * Main function for the generation process
	 *
	 * Computes file paths
	 * Calls part-generators
	 * Creates files
	 */
	function _generate() {
		/* Make crud model */
		
		$output = $this->_header();
		$output .= "<h3>Running SparkPlug...</h3>";

		$model_path = APPPATH.'models/'.strtolower($this->model_name).'.php';
		$model_text = $this->_generate_model();
		
		file_put_contents($model_path, $model_text);
		$output .= $model_path.' created<br/>';
		
		/* Generate views for crud functions in subfolder */

		$view_folder = APPPATH.'views/'.strtolower($this->controller);
		$view_text = $this->_generate_views();
		
		$dir_created = @mkdir($view_folder);
		$output .= $dir_created ? $view_folder.' created<br/>' : $view_folder.' already exists - no need to create<br/>';
		
		forEach ($view_text as $view_name => $view) {
			$view_path = $view_folder.'/'.$view_name.'.php';
			
			file_put_contents($view_path, $view);
			$output .= $view_path.' created<br/>';
		}
		
		/* Create the controller to tie it all up */

		$controller_path = APPPATH.'controllers/'.$this->ucf_controller.'.php';
		$controller_text = $this->_generate_controller();
		file_put_contents($controller_path, $controller_text);
		$output .= $controller_path.' created<br/>';
		
		$output .= '<br/>Scaffold completed.  Click '.anchor($this->controller, 'here').' to get started.';
		$output .= $this->_footer();
		
		return $output;
	}
	
	/*****								*****/
	/*****			MODEL				*****/
	/*****								*****/
	
	/**
	 * Generates the model code
	 *
	 * Gets the user defined layout from the template
	 * Replaces all tags
	 * Calls _fix_indent for multi-line replacements
	 */
	function _generate_model() {
		
		$model_text = $this->_model_text();
		$fields = $this->CI->db->list_fields($this->table);
		
		/* REPLACE TAGS */
		$model_text = str_replace("{model_name}", $this->model_name, $model_text);
		$model_text = str_replace("{table}", $this->table, $model_text);
		
		
		/* Replace Variable Initialization */
		list($model_text, $indent) = $this->_fix_indent($model_text, 'variables');

		$var_init = '';
		forEach ($fields as $field) {
			$var_init .= $indent.'var $'.$field."	= '';\n";
		}
		$model_text = str_replace("{variables}\n", $var_init, $model_text);


		/* Replace Variable Setters */
		list($model_text, $indent) = $this->_fix_indent($model_text, 'set_variables_from_post');
		
		$var_set = '';
		forEach ($fields as $field) {
			$var_set .= $indent.'$this->'.$field.'	= $_POST[\''.$field."'];\n";
		}
		$model_text = str_replace("{set_variables_from_post}\n", $var_set, $model_text);

		return $model_text;
	}


	/*****								*****/
	/*****			VIEWS				*****/
	/*****								*****/
	
	/**
	 * Generates the View Files
	 *
	 * Grabs all of the view templates as defined in the array
	 * Replaces tags
	 */
	function _generate_views() {
		
		/* Template function = _<viewname>_view */
		$views = array('index', 'edit', 'list', 'new', 'show');

		$view_text = array();

		forEach ($views as $view) {
			$view_funct = '_'.$view.'_view';
			
			if (method_exists($this, $view_funct)) {
				//$view_text[$view] = $this->_header();
				$text = $this->$view_funct();
				$text = str_replace('{controller}', $this->controller, $text);
				$text = str_replace('{form_fields_create}', $this->_form_fields('create'), $text);
				$text = str_replace('{form_fields_update}', $this->_form_fields('update'), $text);
				$view_text[$view] = $text;
				//$view_text[$view] .= $this->_footer();
			}
		}
		
		return $view_text;
	}
	
	
	/*****								*****/
	/*****			CONTROLLER			*****/
	/*****								*****/
	
	/**
	 * Generates the controller
	 *
	 * Gets controller template
	 * Replaces tags
	 */
	function _generate_controller() {
		$text = $this->_controller_text();

		$text = str_replace('{ucf_controller}', $this->ucf_controller, $text);
		$text = str_replace('{controller}', $this->controller, $text);
		$text = str_replace('{model}', $this->model_name, $text);
		$text = str_replace('{view_folder}', strtolower($this->controller), $text);
		return $text;
	}
	
	
	/*****								*****/
	/*****		HELPER FUNCTIONS		*****/
	/*****								*****/
	
	/**
	 * Function to fix indentation for multi-line replacements
	 * 
	 * Cuts the indent off the tag and applies to to all lines
	 * that replace it.
	 */
	function _fix_indent($text, $tag) {
		$pattern = '/\n[\t ]*?\{'.$tag.'\}/';
		preg_match($pattern, $text, $matches);
		$indent = str_replace("\n", '', $matches[0]);
		$indent = str_replace('{'.$tag.'}', '', $indent);
		// Remove tag indent to fix first one
		$text = preg_replace($pattern, "\n{".$tag."}", $text);
		
		return array($text, $indent);
	}
	
	/**
	 * Gateway to markup functions
	 *
	 * Calls markup functions to create meta-type relevant fields
	 */
	function _form_fields($action) {
		
		$query = $this->CI->db->get($this->table);		
		$fields = $this->CI->db->field_data($this->table);
		$form = '';
		
		forEach($fields as $field) {
			if ($action == 'update') {
				$form .= $this->_getEditMarkup($field);
			} else {
				$form .= $this->_getMarkup($field);
			}
		}
				
		return $form;
	}

	/**
	 * Creates form element for a given field
	 *
	 * Adds *NOW* to datetime field
	 * Indents elements for clean html
	 */
	function _getMarkup($field) {
		if ($field->primary_key) {
			return '<input type="hidden" name="'.$field->name.'" value="" />';
		}
		
		else {
		
			$form_markup = "\n\t<p>\n";
			$form_markup .= '	<label for="'.$field->name.'">'.ucfirst($field->name).'</label>';
			$form_markup .= "<br/>\n\t";
			
			switch ($field->type) {
				case 'int':
					$form_markup .= form_input($field->name);
					break;
				case 'blob':
					$form_markup .= form_textarea($field->name);
					break;
				case 'datetime':
					$form_markup .= '<input type="text" name="'.$field->name.'" value="<?php echo date("Y-m-d H:i:s"); ?>" maxlength="500" size="50"  />';
					break;
				case 'string':
				case 'char':
				case 'varchar':
				default:
					$form_markup .= form_input($field->name);
					break;
			}
			
			$form_markup .= "\t</p>\n";
			return $form_markup;
		
		}
	}
	
	/**
	 * Creates form elements for an existing row
	 *
	 * Adds existing data to each element
	 */
	function _getEditMarkup($field) {
		if ($field->primary_key) {
			return '<input type="hidden" name="'.$field->name.'" value=<?php echo $result["'.$field->name.'"]?> />';
		}
		
		else {
		
			$form_markup = "\n\t<p>\n";
			$form_markup .= '	<label for="'.$field->name.'">'.ucfirst($field->name).'</label>';
			$form_markup .= "<br/>\n\t";

			switch ($field->type) {
				case 'int':
					$form_markup .= '<input type="text" name="'.$field->name.'" value="<?php echo $result["'.$field->name.'"]?>" maxlength="500" size="50" />';
					break;
				case 'blob':
					$form_markup .= '<textarea name="'.$field->name.'" cols="90" rows="12" ><?php echo $result["'.$field->name.'"]?></textarea>';
					break;
				case 'datetime':
					$form_markup .= '<input type="text" name="'.$field->name.'" value="<?php echo $result["'.$field->name.'"]?>" maxlength="500" size="50"  />';
					break;
				case 'string':
				case 'char':
				case 'varchar':
				default:
					$form_markup .= '<input type="text" name="'.$field->name.'" value="<?php echo $result["'.$field->name.'"]?>" maxlength="500" size="50"  />';
					break;
			}
			$form_markup .= "\n\t</p>\n";
			return $form_markup;
		
		}
	}
	
	/*****								*****/
	/*****			TEMPLATES			*****/
	/*****								*****/
	
	/**
	 * Controller Template - Tags:
	 *
	 * {model}						= model name
	 * {ucf_controller}				= UC controller name
	 * {controller}					= controller name formated for url
	 * {view_folder}				= name of the generated view folder
	 *
	 */
	
	function _controller_text() {
		return
'<?php
class {ucf_controller} extends CI_Controller {

	function __construct() {
		parent::__construct();
		
		$this->load->database();
		$this->load->model(\'{model}\');
		$this->load->helper(\'url\');
		$this->load->helper(\'form\');
		$this->load->library(\'session\');
	}
		
	function index() {
		redirect(\'{controller}/show_list\');
	}

	function show_list() {
		$data[\'results\'] = $this->{model}->get_all();
		$this->load->view(\'{view_folder}/list\', $data);
	}

	function show($id) {
		$data[\'result\'] = $this->{model}->get($id);
		$this->load->view(\'{view_folder}/show\', $data);		
	}

	function new_entry() {
		$this->load->view(\'{view_folder}/new\');
	}

	function create() {
		$this->{model}->insert();
		
		$this->session->set_flashdata(\'msg\', \'Entry Created\');
		redirect(\'{controller}/show_list\');
	}

	function edit($id) {
		$res = $this->{model}->get($id);
		$data[\'result\'] = $res[0];
		$this->load->view(\'{view_folder}/edit\', $data);
	}

	function update() {
		$this->{model}->update();
		
		$this->session->set_flashdata(\'msg\', \'Entry Updated\');
		redirect(\'{controller}/show_list\');
	}

	function delete($id) {
		$this->{model}->delete($id);
		
		$this->session->set_flashdata(\'msg\', \'Entry Deleted\');
		redirect(\'{controller}/show_list\');
	}
}
?>';
	}
	
	/**
	 * Model Template - Tags:
	 *
	 * {model_name}					= $this->model_name (by default: ucfirst(strlower({table})))
	 * {table}						= table name
	 * {variables}					= variable initilizations
	 * {set_variables_from_post}	= all variables set equal to their POST counterparts
	 *
	 */
	
	function _model_text() {
		return
'<?php
class {model_name} extends CI_Model {
	{variables}

	function __construct() {
		parent::__construct();
	}

	function insert() {
		{set_variables_from_post}

		$this->db->insert(\'{table}\', $this);
	}

	function get($id) {
		$query = $this->db->get_where(\'{table}\', array(\'id\' => $id));
		return $query->result_array();
	}
	
	function get_all() {
		$query = $this->db->get(\'{table}\');
		return $query->result_array();
	}
	
	function get_field_data() {
		return $this->db->field_data(\'{table}\');
	}

	function update() {
		{set_variables_from_post}

		$this->db->update(\'{table}\', $this, array(\'id\' => $_POST[\'id\']));
	}

	function delete($id) {
		$this->db->delete(\'{table}\', array(\'id\' => $id));
	}
}
?>';
	}
	
	/**
	 * View Templates
	 *
	 * {controller} = current controller
	 * {form_fields_create} = Empty form entry fields for all database fields
	 * {form_fields_update} = Filled in form entry fields for all database fields
	 */

	/* INDEX */
	function _index_view() {
		
		return 'You should not see this after scaffolding - index controller redirect by default.';
		
	}

	/* LIST */
	function _list_view() {
		return
'<p style="color: green"><?php echo $this->session->flashdata(\'msg\'); ?></p>

<h1>List - {controller}</h1>

<?php if (!empty($results)): ?>
<table>
	<tr>
	<?php forEach (array_keys($results[0]) as $key): ?>
		<th><?php echo ucfirst($key); ?></th>
	<?php endforeach; ?>
	</tr>

<?php forEach ($results as $row): ?>
	<tr>
	<?php forEach ($row as $field_value): ?>
		<td><?php echo $field_value ?></td>
	<?php endForEach; ?>
		<td> <?php echo anchor("{controller}/show/".$row[\'id\'], \'View\'); ?></td>
		<td> <?php echo anchor("{controller}/edit/".$row[\'id\'], \'Edit\'); ?></td>
		<td> <?php echo anchor("{controller}/delete/".$row[\'id\'], \'Delete\'); ?></td>
	</tr>
<?php endforeach; ?>
</table>
<?php endIf; ?>
<?php echo anchor("{controller}/new_entry", "New"); ?>';

	}
	
	/* SHOW */
	function _show_view() {
		return
'<h1>Show - {controller}</h1>

<?php foreach ($result[0] as $field_name => $field_value): ?>
<p>
	<b><?php echo ucfirst($field_name); ?>:</b> <?php echo $field_value ?>
</p>
<?php endforeach; ?>
<?php echo anchor("{controller}/show_list", "Back"); ?>';
	}

	/* EDIT */
	function _edit_view() {
		return 
'<h1>Edit - {controller}</h1>

<?php echo form_open(\'{controller}/update\'); ?>
{form_fields_update}
<p>
	<?php echo form_submit(\'submit\', \'Update\'); ?>
</p>
<?php echo form_close(); ?>
<?php echo anchor("{controller}/show_list", "Back"); ?>';
	}
	
	/* NEW */
	function _new_view() {
		return 
'<h1>New - {controller}</h1>

<?php echo form_open(\'{controller}/create\'); ?>
{form_fields_create}
<p>
	<?php echo form_submit(\'submit\', \'Create\'); ?>
</p>
<?php echo form_close(); ?>
<?php echo anchor("{controller}/show_list", "Back"); ?>';
	}
	
}

?>