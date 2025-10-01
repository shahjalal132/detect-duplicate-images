<?php
/**
 * Template: Duplicate Images Table
 * 
 * @var array $paginated_duplicates
 */

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
?>

<table class="widefat striped">
    <thead>
        <tr>
            <th>Hash</th>
            <th>Attachment IDs</th>
            <th>Status</th>
            <th>Preview & Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ( $paginated_duplicates as $hash => $ids ) : ?>
            <tr>
                <!-- Hash -->
                <td>
                    <code><?php echo esc_html( $hash ); ?></code>
                </td>

                <!-- Attachment IDs -->
                <td>
                    <?php echo implode( ', ', array_map( 'intval', $ids ) ); ?>
                </td>

                <!-- Status -->
                <td>
                    <?php
                    $orphan_ids = [];
                    foreach ( $ids as $id ) {
                        $attached = fdi_is_image_attached( $id );
                        echo '<div>ID ' . intval( $id ) . ': ';
                        echo $attached ? '<span class="attached">✔ Attached</span>' : '<span class="orphan">✖ Orphan</span>';
                        echo '</div>';

                        if ( !$attached ) {
                            $orphan_ids[] = $id;
                        }
                    }
                    ?>
                </td>

                <!-- Preview & Actions -->
                <td>
                    <!-- Image Previews -->
                    <?php foreach ( $ids as $id ) : ?>
                        <?php $url = wp_get_attachment_url( $id ); ?>
                        <?php if ( $url ) : ?>
                            <div class="image-preview">
                                <a href="<?php echo esc_url( $url ); ?>" target="_blank">
                                    <img src="<?php echo esc_url( $url ); ?>" alt="Image ID <?php echo intval( $id ); ?>">
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>

                    <!-- Delete Orphans Button -->
                    <?php if ( !empty( $orphan_ids ) ) : ?>
                        <form method="post" 
                              action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" 
                              onsubmit="return confirm('Are you sure you want to delete <?php echo count( $orphan_ids ); ?> orphan image(s)?');" 
                              style="margin-top:10px;">
                            <input type="hidden" name="action" value="delete_orphan_duplicates">
                            <input type="hidden" name="orphan_ids" value="<?php echo esc_attr( implode( ',', array_map( 'intval', $orphan_ids ) ) ); ?>">
                            <?php wp_nonce_field( 'delete_orphan_duplicates_' . $hash, '_wpnonce' ); ?>
                            <button type="submit" class="button button-small button-link-delete">
                                Delete <?php echo count( $orphan_ids ); ?> Orphan(s)
                            </button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

