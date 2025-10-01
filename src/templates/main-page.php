<?php
/**
 * Template: Main Admin Page
 * 
 * @var array $duplicates
 * @var array $stats
 * @var array $pagination_data
 * @var array $paginated_duplicates
 * @var int $current_page
 */

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="wrap">
    <h1>Duplicate Images (by File Hash)</h1>

    <!-- Success Message -->
    <?php if ( isset( $_GET['deleted'] ) && intval( $_GET['deleted'] ) > 0 ) : ?>
        <div class="notice notice-success is-dismissible">
            <p><strong><?php echo intval( $_GET['deleted'] ); ?> orphan image(s) deleted successfully!</strong></p>
        </div>
    <?php endif; ?>

    <!-- No Duplicates Found -->
    <?php if ( empty( $duplicates ) ) : ?>
        <p><strong>No duplicate images found 🎉</strong></p>
    <?php else : ?>
        
        <!-- Stats Dashboard -->
        <?php
        include plugin_dir_path( __FILE__ ) . 'stats-dashboard.php';
        ?>

        <!-- Top Pagination -->
        <?php
        $base_url = fdi_get_base_url();
        include plugin_dir_path( __FILE__ ) . 'pagination.php';
        ?>

        <!-- Duplicate Images Table -->
        <?php
        include plugin_dir_path( __FILE__ ) . 'duplicate-table.php';
        ?>

        <!-- Bottom Pagination -->
        <?php
        include plugin_dir_path( __FILE__ ) . 'pagination.php';
        ?>

    <?php endif; ?>
</div>

