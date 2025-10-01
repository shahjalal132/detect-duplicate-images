<?php
/**
 * Helper Functions for Find Duplicate Images Plugin
 */

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Get all duplicate images by file hash
 * 
 * @return array Array of duplicate images grouped by hash
 */
function fdi_get_duplicate_images_by_hash() {
    global $wpdb;

    $attachments = $wpdb->get_results( "
        SELECT p.ID, pm.meta_value AS file
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
        WHERE p.post_type = 'attachment' 
          AND p.post_mime_type LIKE 'image%'
          AND pm.meta_key = '_wp_attached_file'
    " );

    $hashes     = [];
    $duplicates = [];
    $upload_dir = wp_get_upload_dir();

    foreach ( $attachments as $att ) {
        $file_path = $upload_dir['basedir'] . '/' . $att->file;

        if ( file_exists( $file_path ) ) {
            $hash = md5_file( $file_path );

            if ( !isset( $hashes[$hash] ) ) {
                $hashes[$hash] = [ $att->ID ];
            } else {
                $hashes[$hash][]   = $att->ID;
                $duplicates[$hash] = $hashes[$hash];
            }
        }
    }

    return $duplicates;
}

/**
 * Check if an image is attached to any post or used in content
 * 
 * @param int $attachment_id The attachment ID to check
 * @return bool True if attached, false if orphaned
 */
function fdi_is_image_attached( $attachment_id ) {
    global $wpdb;

    // Check if image is set as thumbnail or used in post content/meta
    $count = $wpdb->get_var( $wpdb->prepare( "
        SELECT COUNT(*) FROM {$wpdb->posts}
        WHERE post_type != 'attachment' 
        AND (post_content LIKE %s OR post_excerpt LIKE %s OR ID IN (
            SELECT post_id FROM {$wpdb->postmeta} WHERE meta_value = %d
        ))
    ", '%attachment_id="' . $attachment_id . '"%', '%attachment_id="' . $attachment_id . '"%', $attachment_id ) );

    return $count > 0;
}

/**
 * Calculate statistics for duplicate images
 * 
 * @param array $duplicates Array of duplicate images
 * @return array Array containing total_sets, total_files, and total_size
 */
function fdi_calculate_stats( $duplicates ) {
    $total_sets  = count( $duplicates );
    $total_files = 0;
    $total_size  = 0;

    foreach ( $duplicates as $ids ) {
        foreach ( $ids as $id ) {
            $path = get_attached_file( $id );
            if ( file_exists( $path ) ) {
                $total_files++;
                $total_size += filesize( $path );
            }
        }
    }

    return [
        'total_sets'  => $total_sets,
        'total_files' => $total_files,
        'total_size'  => $total_size
    ];
}

/**
 * Get pagination data
 * 
 * @param int $total_items Total number of items
 * @param int $per_page Items per page
 * @param int $current_page Current page number
 * @return array Pagination data
 */
function fdi_get_pagination_data( $total_items, $per_page, $current_page ) {
    $total_pages = ceil( $total_items / $per_page );
    $offset      = ( $current_page - 1 ) * $per_page;
    $start       = $offset + 1;
    $end         = min( $offset + $per_page, $total_items );

    return [
        'total_pages' => $total_pages,
        'offset'      => $offset,
        'start'       => $start,
        'end'         => $end
    ];
}

/**
 * Get base admin URL for the plugin page
 * 
 * @return string Base URL
 */
function fdi_get_base_url() {
    return admin_url( 'tools.php?page=find-duplicate-images' );
}

