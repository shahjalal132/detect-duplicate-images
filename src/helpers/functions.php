<?php
/**
 * Helper Functions for Find Duplicate Images Plugin
 */

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Get cached duplicate images or return false if not cached
 * 
 * @return array|false Array of duplicate images or false if not cached
 */
function fdi_get_cached_duplicates() {
    return get_transient( 'fdi_duplicate_images' );
}

/**
 * Set cached duplicate images
 * 
 * @param array $duplicates Array of duplicate images
 * @param int $expiration Cache expiration in seconds (default: 1 hour)
 */
function fdi_set_cached_duplicates( $duplicates, $expiration = 3600 ) {
    set_transient( 'fdi_duplicate_images', $duplicates, $expiration );
}

/**
 * Clear cached duplicate images
 */
function fdi_clear_cache() {
    delete_transient( 'fdi_duplicate_images' );
    delete_transient( 'fdi_scan_progress' );
    delete_option( 'fdi_last_scan_time' );
}

/**
 * Get scan progress
 * 
 * @return array Progress data
 */
function fdi_get_scan_progress() {
    return get_transient( 'fdi_scan_progress' );
}

/**
 * Set scan progress
 * 
 * @param array $progress Progress data
 */
function fdi_set_scan_progress( $progress ) {
    set_transient( 'fdi_scan_progress', $progress, 600 ); // 10 minutes
}

/**
 * Get total attachments count
 * 
 * @return int Total number of image attachments
 */
function fdi_get_total_attachments_count() {
    global $wpdb;
    
    return (int) $wpdb->get_var( "
        SELECT COUNT(DISTINCT p.ID)
        FROM {$wpdb->posts} p
        WHERE p.post_type = 'attachment' 
          AND p.post_mime_type LIKE 'image%'
    " );
}

/**
 * Get batch of attachments for processing
 * 
 * @param int $offset Offset for query
 * @param int $limit Number of items to fetch
 * @return array Array of attachment objects
 */
function fdi_get_attachments_batch( $offset = 0, $limit = 100 ) {
    global $wpdb;

    return $wpdb->get_results( $wpdb->prepare( "
        SELECT p.ID, pm.meta_value AS file
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
        WHERE p.post_type = 'attachment' 
          AND p.post_mime_type LIKE 'image%%'
          AND pm.meta_key = '_wp_attached_file'
        ORDER BY p.ID ASC
        LIMIT %d OFFSET %d
    ", $limit, $offset ) );
}

/**
 * Get or calculate MD5 hash for an attachment
 * 
 * @param int $attachment_id Attachment ID
 * @param string $file_path Full file path
 * @return string|false MD5 hash or false on failure
 */
function fdi_get_attachment_hash( $attachment_id, $file_path ) {
    // Try to get cached hash from postmeta
    $cached_hash = get_post_meta( $attachment_id, '_fdi_md5_hash', true );
    
    if ( $cached_hash ) {
        return $cached_hash;
    }
    
    // Calculate hash if file exists
    if ( file_exists( $file_path ) ) {
        $hash = md5_file( $file_path );
        
        // Cache the hash in postmeta for future use
        if ( $hash ) {
            update_post_meta( $attachment_id, '_fdi_md5_hash', $hash );
        }
        
        return $hash;
    }
    
    return false;
}

/**
 * Process a batch of attachments and return hash data
 * 
 * @param int $offset Starting offset
 * @param int $limit Batch size
 * @return array Processed hash data
 */
function fdi_process_batch( $offset = 0, $limit = 100 ) {
    $attachments = fdi_get_attachments_batch( $offset, $limit );
    $upload_dir  = wp_get_upload_dir();
    $hashes      = [];
    $processed   = 0;

    foreach ( $attachments as $att ) {
        $file_path = $upload_dir['basedir'] . '/' . $att->file;
        $hash      = fdi_get_attachment_hash( $att->ID, $file_path );
        
        if ( $hash ) {
            if ( !isset( $hashes[$hash] ) ) {
                $hashes[$hash] = [];
            }
            $hashes[$hash][] = $att->ID;
        }
        
        $processed++;
    }

    return [
        'hashes'    => $hashes,
        'processed' => $processed
    ];
}

/**
 * Get all duplicate images (from cache or scan)
 * 
 * @param bool $force_rescan Force a new scan
 * @return array Array of duplicate images grouped by hash
 */
function fdi_get_duplicate_images_by_hash( $force_rescan = false ) {
    // Try to get from cache first
    if ( !$force_rescan ) {
        $cached = fdi_get_cached_duplicates();
        if ( $cached !== false ) {
            return $cached;
        }
    }
    
    // If no cache, return empty array (user needs to trigger scan)
    return [];
}

/**
 * Merge batch results into existing duplicates data
 * 
 * @param array $existing Existing duplicates data
 * @param array $new_hashes New batch hash data
 * @return array Merged duplicates
 */
function fdi_merge_batch_results( $existing, $new_hashes ) {
    foreach ( $new_hashes as $hash => $ids ) {
        if ( !isset( $existing[$hash] ) ) {
            $existing[$hash] = [];
        }
        $existing[$hash] = array_merge( $existing[$hash], $ids );
    }
    
    // Filter to only keep duplicates (more than 1 ID per hash)
    $duplicates = [];
    foreach ( $existing as $hash => $ids ) {
        $unique_ids = array_unique( $ids );
        if ( count( $unique_ids ) > 1 ) {
            $duplicates[$hash] = $unique_ids;
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

/**
 * Get last scan time
 * 
 * @return int|false Timestamp or false if never scanned
 */
function fdi_get_last_scan_time() {
    return get_option( 'fdi_last_scan_time', false );
}

/**
 * Set last scan time
 */
function fdi_set_last_scan_time() {
    update_option( 'fdi_last_scan_time', time() );
}
