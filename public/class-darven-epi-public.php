<?php

if(!class_exists('Darven_Epi_Public')){

	class Darven_Epi_Public {

		private $plugin_name;
		private $version;


		public function __construct( $plugin_name, $version ) {

			$this->plugin_name = $plugin_name;
			$this->version = $version;

		}


		public function enqueue_styles(): void {

			wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/styles.css', array(), $this->version, 'all' );
			include
			wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__) . 'css/custom_styles.css', array(), $this->version, 'all' );



		}

		public function enqueue_scripts(): void {

			wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/frontend.js', array( 'jquery' ), $this->version, false );

		}

	}
}