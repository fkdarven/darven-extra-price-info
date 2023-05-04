<?php

if(!class_exists('Darven_Epi_Admin')){
	class Darven_Epi_Admin
	{

	private string $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private string $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles(): void {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Plugin_Name_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Plugin_Name_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
		$page = $_GET['page'] ?? null;

		if($page === 'darven-epi-admin') {
			wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/admin_styles.css', array(), $this->version, 'all' );
		}

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts(): void {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Plugin_Name_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Plugin_Name_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		global $wp;

		$tab = $_GET['tab'] ?? null;
		$page = $_GET['page'] ?? null;

		if($page === 'darven-epi-admin'){
			if( $tab === 'colorsandstyles' ){
				wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/colorsandstyles.js', [ 'jquery', 'wp-color-picker' ], $this->version, false );
			}elseif( $tab === 'positions'){
				//wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/colorsandstyles.js', ['jquery', 'wp-color-picker'], $this->version, false);
			}else{
				wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/general.js' , ['jquery'], $this->version, false);
			}
		}


	}
	}
}