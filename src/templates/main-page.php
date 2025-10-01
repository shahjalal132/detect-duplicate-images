<?php
/**
 * Template: Main Admin Page
 * 
 * @var array $duplicates
 * @var array $stats
 * @var array $pagination_data
 * @var array $paginated_duplicates
 * @var int $current_page
 * @var int|false $last_scan_time
 * @var int $total_attachments
 */

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="wrap">
    <h1>Find Duplicate Images by Hash</h1>

    <!-- Success Message -->
    <?php if ( isset( $_GET['deleted'] ) && intval( $_GET['deleted'] ) > 0 ) : ?>
        <div class="notice notice-success is-dismissible">
            <p><strong><?php echo intval( $_GET['deleted'] ); ?> orphan image(s) deleted successfully!</strong></p>
        </div>
    <?php endif; ?>

    <!-- Scan Status Card -->
    <div class="fdi-scan-card">
        <div class="fdi-scan-info">
            <h2>📸 Media Library Scan</h2>
            <p><strong>Total Images:</strong> <?php echo number_format_i18n( $total_attachments ); ?></p>
            
            <?php if ( $last_scan_time ) : ?>
                <p><strong>Last Scan:</strong> <?php echo human_time_diff( $last_scan_time, current_time( 'timestamp' ) ); ?> ago</p>
            <?php else : ?>
                <p><strong>Status:</strong> <span style="color:#d63638;">Never scanned</span></p>
            <?php endif; ?>
        </div>
        
        <div class="fdi-scan-actions">
            <button id="fdi-start-scan" class="button button-primary button-large">
                <?php echo $last_scan_time ? '🔄 Re-scan Library' : '▶️ Start Scan'; ?>
            </button>
            <?php if ( $last_scan_time ) : ?>
                <button id="fdi-clear-cache" class="button button-secondary">Clear Cache</button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Progress Bar (hidden by default) -->
    <div id="fdi-progress-container" style="display:none;">
        <div class="fdi-progress-bar">
            <div id="fdi-progress-fill" class="fdi-progress-fill"></div>
        </div>
        <p id="fdi-progress-text" class="fdi-progress-text">Initializing scan...</p>
    </div>

    <!-- No Duplicates or Not Scanned -->
    <?php if ( empty( $duplicates ) ) : ?>
        <div class="notice notice-info" style="margin-top:20px;">
            <p><strong>
                <?php if ( $last_scan_time ) : ?>
                    🎉 No duplicate images found! Your media library is clean.
                <?php else : ?>
                    ℹ️ Click "Start Scan" to analyze your media library for duplicate images.
                <?php endif; ?>
            </strong></p>
        </div>
    <?php else : ?>
        
        <!-- Stats Dashboard -->
        <?php include plugin_dir_path( __FILE__ ) . 'stats-dashboard.php'; ?>

        <!-- Top Pagination -->
        <?php
        $base_url = fdi_get_base_url();
        include plugin_dir_path( __FILE__ ) . 'pagination.php';
        ?>

        <!-- Duplicate Images Table -->
        <?php include plugin_dir_path( __FILE__ ) . 'duplicate-table.php'; ?>

        <!-- Bottom Pagination -->
        <?php include plugin_dir_path( __FILE__ ) . 'pagination.php'; ?>

    <?php endif; ?>
</div>
