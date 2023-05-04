<?php
defined( 'ABSPATH' ) || exit();
if ( ! class_exists( "Darven_Epi" ) ) {
	class Darven_Epi {
		protected Darven_Epi_Loader $loader;
		protected string $plugin_name;
		protected $version;


		public function __construct() {

			if ( defined( 'PLUGIN_NAME_VERSION' ) ) {
				$this->version = PLUGIN_NAME_VERSION;
			} else {
				$this->version = '1.0.0';
			}
			$this->plugin_name = 'darven-epi';

			$this->load_dependencies();
			$this->set_locale();
			$this->define_admin_hooks();
			$this->define_public_hooks();
			$this->define_public_filters();

		}
		private function autoloader( $class_name ): void {


            $paths = ['admin', 'includes', 'includes/admin/settings','includes/functions', 'public'];
            $base_dir = DARVEN_EPI_DIR_PATH;
			if ( strpos( $class_name, 'Darven_Epi' ) !== false ) {
				try {
                    foreach ($paths as $path){

                        $class_file = $base_dir . $path . DIRECTORY_SEPARATOR;
	                    $class_file  .= 'class-' . str_replace( '_', '-', $class_name ) . '.php';
	                    $class_file  = strtolower( $class_file );

                        if(file_exists($class_file)){
                            require_once $class_file;
                        }
                    }
				} catch (Exception $e){
				}


			}
		}
		public function sample_admin_notice__success() {
			?>
			<div class="notice notice-warning is-dismissible">
				<p><?php _e( 'The Darven Multiple Price Info settings has changed! You can now access it via WooCommerce -> In cash and Installments Price', DARVEN_EPI_LANGUAGE_DOMAIN ); ?> </p>
			</div>
			<?php
		}

		function simplarity_autoloader( $class_name ) {
			if ( false !== strpos( $class_name, 'Darven' ) ) {
				$classes_dir = realpath( plugin_dir_path( __FILE__ ) ) . DIRECTORY_SEPARATOR;

                $class_name = str_replace( '_', '-', $class_name );
				$class_file = 'class-' . strtolower($class_name) . '.php';

				require_once $classes_dir . $class_file;
			}
		}
		private function load_dependencies(): void {


			//add_action( 'admin_notices', array($this, 'sample_admin_notice__success'));
			//spl_autoload_register( array($this, 'autoloader' ));
			//spl_autoload_register( array($this, 'autoloader' ));

			require DARVEN_EPI_DIR_PATH . 'includes/admin/settings/class-darven-epi-general-settings-fields.php';
			require DARVEN_EPI_DIR_PATH . 'includes/admin/settings/class-darven-epi-colorsandstyles-settings-fields.php';
			require DARVEN_EPI_DIR_PATH . 'includes/admin/settings/class-darven-epi-positions-settings-fields.php';
			require DARVEN_EPI_DIR_PATH . 'includes/admin/settings/class-darven-epi-general-settings.php';
			require DARVEN_EPI_DIR_PATH . 'includes/admin/settings/class-darven-epi-compatibility-settings-fields.php';
			require DARVEN_EPI_DIR_PATH . 'includes/functions/class-darven-epi-product-options.php';
			require DARVEN_EPI_DIR_PATH . 'includes/class-darven-epi-i18n.php';
			require DARVEN_EPI_DIR_PATH . 'includes/class-darven-epi-loader.php';
			require DARVEN_EPI_DIR_PATH . 'admin/class-darven-epi-admin.php';
			require DARVEN_EPI_DIR_PATH . 'public/class-darven-epi-public.php';
			require DARVEN_EPI_DIR_PATH . 'includes/functions/class-darven-epi-format-final-price.php';


			$this->loader = new Darven_Epi_Loader();

		}

		private function set_locale(): void
		{

			$plugin_i18n = new Darven_Epi_i18n();
			$this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
		}


		private function define_admin_hooks(): void {

			$plugin_admin = new Darven_Epi_Admin( $this->get_plugin_name(), $this->get_version() );

			$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
			$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		}


		private function define_public_hooks(): void {

			$plugin_public = new Darven_Epi_Public( $this->get_plugin_name(), $this->get_version() );

			$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
			$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

		}
		public function define_public_filters(): void {
			$plugin_final = new Darven_Epi_Format_Final_Price();
			$this->loader->add_filter( 'woocommerce_get_price_html', $plugin_final, 'get_discount_price', 2000);
		}


		public function run(): void {
			$this->loader->run();
		}

		public function get_plugin_name(): string {
			return $this->plugin_name;
		}


		public function get_loader(): Darven_Epi_Loader {
			return $this->loader;
		}


		public function get_version(): string {
			return $this->version;
		}


	}
}
