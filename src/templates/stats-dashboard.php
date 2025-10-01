<?php
/**
 * Template: Statistics Dashboard
 * 
 * @var int $total_sets
 * @var int $total_files
 * @var int $total_size
 */

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="duplicate-stats">
    <div class="stat-box">
        <span class="dashicons dashicons-screenoptions"></span>
        <div class="stat-number"><?php echo esc_html( $total_sets ); ?></div>
        <div class="stat-label">Duplicate Sets</div>
    </div>
    <div class="stat-box">
        <span class="dashicons dashicons-format-gallery"></span>
        <div class="stat-number"><?php echo esc_html( $total_files ); ?></div>
        <div class="stat-label">Duplicate Images</div>
    </div>
    <div class="stat-box">
        <span class="dashicons dashicons-database"></span>
        <div class="stat-number"><?php echo esc_html( size_format( $total_size ) ); ?></div>
        <div class="stat-label">Total Size</div>
    </div>
</div>

