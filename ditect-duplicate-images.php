<?php
/**
 * Plugin Name: Find Duplicate Images by Hash
 * Description: Scan the WordPress Media Library and find duplicate images by file hash (MD5). Includes stats, orphan check, and delete option with a beautiful dashboard.
 * Version: 1.5
 * Author: Shah Jalal
 */

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define( 'FDI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'FDI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'FDI_VERSION', '1.5' );
define( 'FDI_BATCH_SIZE', 100 ); // Process 100 images per batch

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
        
        // AJAX handlers
        add_action( 'wp_ajax_fdi_start_scan', [ $this, 'ajax_start_scan' ] );
        add_action( 'wp_ajax_fdi_process_batch', [ $this, 'ajax_process_batch' ] );
        add_action( 'wp_ajax_fdi_stop_scan', [ $this, 'ajax_stop_scan' ] );
        add_action( 'wp_ajax_fdi_clear_cache', [ $this, 'ajax_clear_cache' ] );
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
        
        // Localize script with AJAX data
        wp_localize_script( 'fdi-admin-scripts', 'fdiAjax', [
            'ajaxurl'    => admin_url( 'admin-ajax.php' ),
            'nonce'      => wp_create_nonce( 'fdi_ajax_nonce' ),
            'batchSize'  => FDI_BATCH_SIZE,
            'totalItems' => fdi_get_total_attachments_count()
        ] );
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

        // Get scan status
        $last_scan_time    = fdi_get_last_scan_time();
        $total_attachments = fdi_get_total_attachments_count();
        $is_scan_stopped   = fdi_is_scan_stopped();
        $scan_progress     = fdi_get_scan_progress();
        
        // Get all duplicates (from cache)
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
        
        // Clear cache after deletion to refresh results
        if ( $deleted_count > 0 ) {
            fdi_clear_cache();
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
    
    /**
     * AJAX: Start scan process
     */
    public function ajax_start_scan() {
        check_ajax_referer( 'fdi_ajax_nonce', 'nonce' );
        
        if ( !current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Permission denied' ] );
        }
        
        $resume = isset( $_POST['resume'] ) && $_POST['resume'] === 'true';
        
        // Check if we're resuming a stopped scan
        if ( $resume ) {
            $progress = fdi_get_scan_progress();
            
            if ( $progress && isset( $progress['processed'] ) ) {
                // Resume from where we left off
                fdi_set_scan_stopped( false );
                
                wp_send_json_success( [
                    'total'     => $progress['total'],
                    'processed' => $progress['processed'],
                    'resuming'  => true,
                    'message'   => 'Resuming scan'
                ] );
                return;
            }
        }
        
        // Start fresh scan
        // Clear existing cache and progress
        fdi_clear_cache();
        
        // Get total count
        $total = fdi_get_total_attachments_count();
        
        // Initialize progress
        fdi_set_scan_progress( [
            'total'      => $total,
            'processed'  => 0,
            'duplicates' => []
        ] );
        
        wp_send_json_success( [
            'total'     => $total,
            'processed' => 0,
            'resuming'  => false,
            'message'   => 'Scan started'
        ] );
    }
    
    /**
     * AJAX: Process a batch of images
     */
    public function ajax_process_batch() {
        check_ajax_referer( 'fdi_ajax_nonce', 'nonce' );
        
        if ( !current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Permission denied' ] );
        }
        
        $offset = isset( $_POST['offset'] ) ? intval( $_POST['offset'] ) : 0;
        
        // Get current progress
        $progress = fdi_get_scan_progress();
        if ( !$progress ) {
            wp_send_json_error( [ 'message' => 'Scan not initialized' ] );
        }
        
        // Process batch
        $result = fdi_process_batch( $offset, FDI_BATCH_SIZE );
        
        // Merge results with existing duplicates
        $progress['duplicates'] = fdi_merge_batch_results( 
            $progress['duplicates'], 
            $result['hashes'] 
        );
        $progress['processed'] += $result['processed'];
        
        // Update progress
        fdi_set_scan_progress( $progress );
        
        // Check if complete
        $is_complete = $progress['processed'] >= $progress['total'];
        
        if ( $is_complete ) {
            // Save final results to cache
            fdi_set_cached_duplicates( $progress['duplicates'], 3600 * 24 ); // Cache for 24 hours
            fdi_set_last_scan_time();
            fdi_set_scan_stopped( false ); // Mark as complete, not stopped
        } else {
            // Save partial results even if not complete
            fdi_set_cached_duplicates( $progress['duplicates'], 3600 * 24 );
        }
        
        wp_send_json_success( [
            'processed'   => $progress['processed'],
            'total'       => $progress['total'],
            'complete'    => $is_complete,
            'duplicates'  => count( $progress['duplicates'] ),
            'percentage'  => round( ( $progress['processed'] / $progress['total'] ) * 100, 2 )
        ] );
    }
    
    /**
     * AJAX: Stop scan process
     */
    public function ajax_stop_scan() {
        check_ajax_referer( 'fdi_ajax_nonce', 'nonce' );
        
        if ( !current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Permission denied' ] );
        }
        
        // Get current progress
        $progress = fdi_get_scan_progress();
        
        if ( $progress ) {
            // Save partial results to cache
            if ( !empty( $progress['duplicates'] ) ) {
                fdi_set_cached_duplicates( $progress['duplicates'], 3600 * 24 );
            }
            
            // Mark scan as stopped
            fdi_set_scan_stopped( true );
            
            wp_send_json_success( [
                'message'    => 'Scan stopped',
                'processed'  => $progress['processed'],
                'total'      => $progress['total'],
                'duplicates' => count( $progress['duplicates'] )
            ] );
        } else {
            wp_send_json_error( [ 'message' => 'No scan in progress' ] );
        }
    }
    
    /**
     * AJAX: Clear cache and rescan
     */
    public function ajax_clear_cache() {
        check_ajax_referer( 'fdi_ajax_nonce', 'nonce' );
        
        if ( !current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Permission denied' ] );
        }
        
        fdi_clear_cache();
        
        wp_send_json_success( [ 'message' => 'Cache cleared' ] );
    }
}

// Initialize the plugin
new Find_Duplicate_Images_By_Hash();

