<?php

if ( ! class_exists( "Darven_Epi_i18n" ) ) {
	class Darven_Epi_i18n {
		public function load_plugin_textdomain(): void {
			load_plugin_textdomain( 'darven-epi',
				false,
				dirname( plugin_basename( __FILE__ ), 2 ) . 'i18n/languages/' );
		}
	}
}