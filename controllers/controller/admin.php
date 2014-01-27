<?php
/**
 * Introduce special type for controllers which render pages inside admin area
 * 
 * @author Max Tsiplyakov <makstsiplyakov@gmail.com>
 */
abstract class PMLI_Controller_Admin extends PMLI_Controller {
	/**
	 * Admin page base url (request url without all get parameters but `page`)
	 * @var string
	 */
	public $baseUrl;
	/**
	 * Parameters which is left when baseUrl is detected
	 * @var array
	 */
	public $baseUrlParamNames = array('page', 'pagenum', 'order', 'order_by', 'type', 's', 'f');
	/**
	 * Whether controller is rendered inside wordpress page
	 * @var bool
	 */
	public $isInline = false;
	/**
	 * Constructor
	 */
	public function __construct() {
		
		$remove = array_diff(array_keys($_GET), $this->baseUrlParamNames);
		if ($remove) {
			$this->baseUrl = remove_query_arg($remove);
		} else {
			$this->baseUrl = $_SERVER['REQUEST_URI'];
		}
		parent::__construct();							

		wp_enqueue_style('pmli-admin-style', PMLI_ROOT_URL . '/static/css/admin.css');
	
		wp_enqueue_script('pmli-script', PMLI_ROOT_URL . '/static/js/pmli.js', array('jquery'));		
		wp_enqueue_script('pmli-admin-script', PMLI_ROOT_URL . '/static/js/admin.js', array('jquery', 'jquery-ui-core', 'jquery-ui-resizable', 'jquery-ui-dialog', 'jquery-ui-datepicker', 'jquery-ui-draggable', 'jquery-ui-droppable', 'pmxi-admin-script'));		
		
	}	
	
	/**
	 * @see Controller::render()
	 */
	protected function render($viewPath = NULL)
	{
		// assume template file name depending on calling function
		if (is_null($viewPath)) {
			$trace = debug_backtrace();
			$viewPath = str_replace('_', '/', preg_replace('%^' . preg_quote(PMLI_Plugin::PREFIX, '%') . '%', '', strtolower($trace[1]['class']))) . '/' . $trace[1]['function'];
		}
		
		// render contextual help automatically
		$viewHelpPath = $viewPath;
		// append file extension if not specified
		if ( ! preg_match('%\.php$%', $viewHelpPath)) {
			$viewHelpPath .= '.php';
		}
		$viewHelpPath = preg_replace('%\.php$%', '-help.php', $viewHelpPath);
		$fileHelpPath = PMLI_Plugin::ROOT_DIR . '/views/' . $viewHelpPath;
				
		if (is_file($fileHelpPath)) { // there is help file defined
			ob_start();
			include $fileHelpPath;
			add_contextual_help(PMLI_Plugin::getInstance()->getAdminCurrentScreen()->id, ob_get_clean());
		}
		
		parent::render($viewPath);
	}
	
}