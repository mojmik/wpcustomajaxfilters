<?php
namespace MajaxWP;

Class MajaxHandler {	
	const ACTION = 'majax';
	const NONCE =  'majax-ajax';

	public $ajaxRender;

	function __construct() {		
				
	}

	public function register()  {		

		$this->ajaxRender=new MajaxRender();
		
		$this->ajaxRender->regShortCodes();
		
		add_action('wp_ajax_filter_rows', [$this,'handleShow'] );
		add_action('wp_ajax_nopriv_filter_rows', [$this,'handleShow'] );
		
		add_action('wp_ajax_filter_count_results', [$this,'handleCount'] );
		add_action('wp_ajax_nopriv_filter_count_results', [$this,'handleCount'] );

        add_action('wp_loaded', [$this, 'register_script']);
	}
	
	public function register_script()    {	
        wp_register_script('majax-script', MAJAX_PLUGIN_URL . 'majax.js',array('jquery'));
        wp_localize_script('majax-script', 'majax', $this->get_ajax_data());
        wp_enqueue_script('majax-script');
	}
	
	private function get_ajax_data() {
        return array(
			'ajax_url' =>  admin_url( 'admin-ajax.php' ),
            'action' => self::ACTION,
            'nonce' => wp_create_nonce(MajaxHandler::NONCE)
        );
	}
	
	public function handleShow()    {
        $this->handle("show");
	}
	public function handleCount()    {
        $this->handle("count");
	}
	private function handle($action="")    {
        
        check_ajax_referer(MajaxHandler::NONCE,'security');
		if ($action=="show") $this->ajaxRender->filter_rows_continuous();
		if ($action=="count") $this->ajaxRender->filter_count_results();
        
        die();
	}
	
	
	function logWrite($val) {
	 file_put_contents(plugin_dir_path( __FILE__ ) . "log.txt",date("d-m-Y h:i:s")." ".$val."\n",FILE_APPEND | LOCK_EX);
	}
}