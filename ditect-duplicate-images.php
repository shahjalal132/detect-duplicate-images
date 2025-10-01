<?php
/**
 * Plugin Name: Find Duplicate Images by Hash
 * Description: Scan the WordPress Media Library and find duplicate images by file hash (MD5). Includes stats, orphan check, and delete option with a beautiful dashboard.
 * Version: 1.3
 * Author: Shah Jalal
 */

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define( 'FDI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'FDI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'FDI_VERSION', '1.3' );

// Include helper functions
require_once FDI_PLUGIN_DIR . 'src/helpers/functions.php';

/**
 * Main Plugin Class
 */
class Find_Duplicate_Images_By_Hash {

    /**
     * Items per page for pagination
     */
    const PER_PAGE = 20;

    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'register_admin_page' ] );
        add_action( 'admin_post_delete_orphan_duplicates', [ $this, 'delete_orphan_duplicates' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    /**
     * Enqueue CSS and JavaScript assets
     * 
     * @param string $hook Current admin page hook
     */
    public function enqueue_assets( $hook ) {
        if ( $hook !== 'tools_page_find-duplicate-images' ) {
            return;
        }

        // Enqueue CSS
        wp_enqueue_style(
            'fdi-admin-styles',
            FDI_PLUGIN_URL . 'src/css/admin-styles.css',
            [],
            FDI_VERSION
        );

        // Enqueue JavaScript
        wp_enqueue_script(
            'fdi-admin-scripts',
            FDI_PLUGIN_URL . 'src/js/admin-scripts.js',
            [ 'jquery' ],
            FDI_VERSION,
            true
        );
    }

    /**
     * Register admin page under Tools menu
     */
    public function register_admin_page() {
        add_management_page(
            'Find Duplicate Images',
            'Find Duplicate Images',
            'manage_options',
            'find-duplicate-images',
            [ $this, 'render_admin_page' ]
        );
    }

    /**
     * Render the main admin page
     */
    public function render_admin_page() {
        // Check user permissions
        if ( !current_user_can( 'manage_options' ) ) {
            wp_die( 'You do not have sufficient permissions to access this page.' );
        }

        // Pagination setup
        $current_page = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;

        // Get all duplicates
        $duplicates = fdi_get_duplicate_images_by_hash();

        // Calculate statistics
        $stats = fdi_calculate_stats( $duplicates );
        extract( $stats ); // $total_sets, $total_files, $total_size

        // Get pagination data
        $pagination_data = fdi_get_pagination_data( $stats['total_sets'], self::PER_PAGE, $current_page );
        extract( $pagination_data ); // $total_pages, $offset, $start, $end

        // Slice duplicates for current page
        $paginated_duplicates = array_slice( $duplicates, $offset, self::PER_PAGE, true );

        // Include main template
        include FDI_PLUGIN_DIR . 'src/templates/main-page.php';
    }

    /**
     * Handle deletion of orphan duplicate images
     */
    public function delete_orphan_duplicates() {
        // Check permissions and nonce
        if ( !isset( $_POST['orphan_ids'] ) || !current_user_can( 'delete_posts' ) ) {
            wp_die( 'Permission denied' );
        }

        // Sanitize and parse orphan IDs
        $orphan_ids = explode( ',', sanitize_text_field( $_POST['orphan_ids'] ) );
        $orphan_ids = array_map( 'intval', $orphan_ids );

        // Get hash for nonce verification
        $hash = $this->extract_hash_from_post();

        // Verify nonce
        check_admin_referer( 'delete_orphan_duplicates_' . $hash );

        // Delete orphaned images
        $deleted_count = 0;
        foreach ( $orphan_ids as $id ) {
            // Double-check that this image is truly orphaned before deleting
            if ( !fdi_is_image_attached( $id ) ) {
                wp_delete_attachment( $id, true );
                $deleted_count++;
            }
        }

        // Redirect back with success message
        wp_redirect( fdi_get_base_url() . '&deleted=' . $deleted_count );
        exit;
    }

    /**
     * Extract hash from POST data for nonce verification
     * 
     * @return string The hash value
     */
    private function extract_hash_from_post() {
        $hash = '';

        if ( isset( $_POST['_wpnonce'] ) ) {
            // Extract hash from nonce field name
            foreach ( $_POST as $key => $value ) {
                if ( strpos( $key, 'delete_orphan_duplicates_' ) === 0 ) {
                    $hash = str_replace( 'delete_orphan_duplicates_', '', $key );
                    break;
                }
            }
        }

        // Fallback: Find the hash by checking one of the orphan IDs
        if ( empty( $hash ) && !empty( $_POST['orphan_ids'] ) ) {
            $orphan_ids = explode( ',', sanitize_text_field( $_POST['orphan_ids'] ) );
            $orphan_ids = array_map( 'intval', $orphan_ids );

            if ( !empty( $orphan_ids ) ) {
                $file_path = get_attached_file( $orphan_ids[0] );
                if ( $file_path && file_exists( $file_path ) ) {
                    $hash = md5_file( $file_path );
                }
            }
        }

        return $hash;
    }
}

// Initialize the plugin
new Find_Duplicate_Images_By_Hash();

