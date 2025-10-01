<?php
/**
 * Plugin Name: Find Duplicate Images by Hash
 * Description: Scan the WordPress Media Library and find duplicate images by file hash (MD5). Includes stats, orphan check, and delete option with a beautiful dashboard.
 * Version: 1.2
 * Author: Shah Jalal
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Find_Duplicate_Images_By_Hash {

    public function __construct() {
        add_action('admin_menu', [$this, 'register_admin_page']);
        add_action('admin_post_delete_duplicate_image', [$this, 'delete_duplicate_image']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_styles']);
    }

    public function enqueue_styles($hook) {
        if ($hook !== 'tools_page_find-duplicate-images') return;

        echo '<style>
        .duplicate-stats { display:flex; gap:20px; margin:20px 0; }
        .stat-box { flex:1; background:#fff; border:1px solid #ddd; border-radius:8px; padding:15px; text-align:center; box-shadow:0 1px 3px rgba(0,0,0,0.08); }
        .stat-box .dashicons { font-size:32px; height:32px; width:32px; margin-bottom:8px; color:#2271b1; }
        .stat-number { font-size:18px; font-weight:600; margin-bottom:5px; }
        .stat-label { color:#555; }
        .orphan { color:red; font-weight:600; }
        .attached { color:green; font-weight:600; }
        table.widefat td { vertical-align:top; }
        .image-preview { display:inline-block; text-align:center; margin:5px; }
        .image-preview img { max-width:60px; max-height:60px; display:block; margin:0 auto 5px; border-radius:4px; }
        </style>';
    }

    public function register_admin_page() {
        add_management_page(
            'Find Duplicate Images',
            'Find Duplicate Images',
            'manage_options',
            'find-duplicate-images',
            [$this, 'render_admin_page']
        );
    }

    public function render_admin_page() {
        echo '<div class="wrap"><h1>Duplicate Images (by File Hash)</h1>';

        $duplicates = $this->get_duplicate_images_by_hash();

        if (empty($duplicates)) {
            echo '<p><strong>No duplicate images found 🎉</strong></p></div>';
            return;
        }

        // 📊 Stats
        $total_sets = count($duplicates);
        $total_files = 0;
        $total_size = 0;

        foreach ($duplicates as $ids) {
            foreach ($ids as $id) {
                $path = get_attached_file($id);
                if (file_exists($path)) {
                    $total_files++;
                    $total_size += filesize($path);
                }
            }
        }

        // 🔹 Stats dashboard
        echo '<div class="duplicate-stats">';
        echo '<div class="stat-box"><span class="dashicons dashicons-screenoptions"></span><div class="stat-number">' . $total_sets . '</div><div class="stat-label">Duplicate Sets</div></div>';
        echo '<div class="stat-box"><span class="dashicons dashicons-format-gallery"></span><div class="stat-number">' . $total_files . '</div><div class="stat-label">Duplicate Images</div></div>';
        echo '<div class="stat-box"><span class="dashicons dashicons-database"></span><div class="stat-number">' . size_format($total_size) . '</div><div class="stat-label">Total Size</div></div>';
        echo '</div>';

        // Table
        echo '<table class="widefat striped">';
        echo '<thead><tr><th>Hash</th><th>Attachment IDs</th><th>Status</th><th>Preview & Actions</th></tr></thead><tbody>';

        foreach ($duplicates as $hash => $ids) {
            echo '<tr>';
            echo '<td><code>' . esc_html($hash) . '</code></td>';
            echo '<td>' . implode(', ', array_map('intval', $ids)) . '</td>';
            echo '<td>';

            foreach ($ids as $id) {
                $attached = $this->is_image_attached($id);
                echo '<div>ID ' . intval($id) . ': ';
                echo $attached ? '<span class="attached">✔ Attached</span>' : '<span class="orphan">✖ Orphan</span>';
                echo '</div>';
            }

            echo '</td>';
            echo '<td>';
            foreach ($ids as $id) {
                $url = wp_get_attachment_url($id);
                if ($url) {
                    echo '<div class="image-preview">';
                    echo '<a href="' . esc_url($url) . '" target="_blank"><img src="' . esc_url($url) . '"/></a>';

                    // Delete button
                    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" onsubmit="return confirm(\'Are you sure you want to delete this image?\');">';
                    echo '<input type="hidden" name="action" value="delete_duplicate_image">';
                    echo '<input type="hidden" name="attachment_id" value="' . intval($id) . '">';
                    echo wp_nonce_field('delete_duplicate_image_' . $id, '_wpnonce', true, false);
                    echo '<button type="submit" class="button button-small button-danger">Delete</button>';
                    echo '</form>';

                    echo '</div>';
                }
            }
            echo '</td>';
            echo '</tr>';
        }

        echo '</tbody></table></div>';
    }

    private function get_duplicate_images_by_hash() {
        global $wpdb;

        $attachments = $wpdb->get_results("
            SELECT p.ID, pm.meta_value AS file
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'attachment' 
              AND p.post_mime_type LIKE 'image%'
              AND pm.meta_key = '_wp_attached_file'
        ");

        $hashes = [];
        $duplicates = [];
        $upload_dir = wp_get_upload_dir();

        foreach ($attachments as $att) {
            $file_path = $upload_dir['basedir'] . '/' . $att->file;

            if (file_exists($file_path)) {
                $hash = md5_file($file_path);

                if (!isset($hashes[$hash])) {
                    $hashes[$hash] = [$att->ID];
                } else {
                    $hashes[$hash][] = $att->ID;
                    $duplicates[$hash] = $hashes[$hash];
                }
            }
        }

        return $duplicates;
    }

    private function is_image_attached($attachment_id) {
        global $wpdb;

        // Check if image is set as thumbnail or used in post content/meta
        $count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->posts}
            WHERE post_type != 'attachment' 
            AND (post_content LIKE %s OR post_excerpt LIKE %s OR ID IN (
                SELECT post_id FROM {$wpdb->postmeta} WHERE meta_value = %d
            ))
        ", '%attachment_id="' . $attachment_id . '"%', '%attachment_id="' . $attachment_id . '"%', $attachment_id));

        return $count > 0;
    }

    public function delete_duplicate_image() {
        if (!isset($_POST['attachment_id']) || !current_user_can('delete_posts')) {
            wp_die('Permission denied');
        }

        $attachment_id = intval($_POST['attachment_id']);
        check_admin_referer('delete_duplicate_image_' . $attachment_id);

        wp_delete_attachment($attachment_id, true);

        wp_redirect(admin_url('tools.php?page=find-duplicate-images&deleted=1'));
        exit;
    }
}

new Find_Duplicate_Images_By_Hash();
