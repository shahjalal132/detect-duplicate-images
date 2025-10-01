<?php
/**
 * Template: Pagination
 * 
 * @var int $current_page
 * @var int $total_pages
 * @var int $start
 * @var int $end
 * @var int $total_items
 * @var string $base_url
 */

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

if ( $total_pages <= 1 ) {
    return;
}
?>

<div class="tablenav">
    <div class="tablenav-pages">
        <span class="displaying-num">
            <?php printf( 'Showing %d-%d of %d duplicate sets', $start, $end, $total_items ); ?>
        </span>

        <span class="pagination-links">
            <!-- First page -->
            <?php if ( $current_page > 1 ) : ?>
                <a class="button" href="<?php echo esc_url( $base_url . '&paged=1' ); ?>" title="First page">&laquo;</a>
            <?php else : ?>
                <span class="button disabled" aria-hidden="true">&laquo;</span>
            <?php endif; ?>

            <!-- Previous page -->
            <?php if ( $current_page > 1 ) : ?>
                <a class="button" href="<?php echo esc_url( $base_url . '&paged=' . ( $current_page - 1 ) ); ?>" title="Previous page">&lsaquo;</a>
            <?php else : ?>
                <span class="button disabled" aria-hidden="true">&lsaquo;</span>
            <?php endif; ?>

            <!-- Current page input -->
            <span class="paging-input">
                <span class="tablenav-paging-text">
                    Page 
                    <input class="current-page" 
                           type="number" 
                           name="paged" 
                           value="<?php echo esc_attr( $current_page ); ?>" 
                           min="1" 
                           max="<?php echo esc_attr( $total_pages ); ?>" 
                           step="1"
                           data-base-url="<?php echo esc_attr( $base_url ); ?>">
                    of <span class="total-pages"><?php echo number_format_i18n( $total_pages ); ?></span>
                </span>
            </span>

            <!-- Next page -->
            <?php if ( $current_page < $total_pages ) : ?>
                <a class="button" href="<?php echo esc_url( $base_url . '&paged=' . ( $current_page + 1 ) ); ?>" title="Next page">&rsaquo;</a>
            <?php else : ?>
                <span class="button disabled" aria-hidden="true">&rsaquo;</span>
            <?php endif; ?>

            <!-- Last page -->
            <?php if ( $current_page < $total_pages ) : ?>
                <a class="button" href="<?php echo esc_url( $base_url . '&paged=' . $total_pages ); ?>" title="Last page">&raquo;</a>
            <?php else : ?>
                <span class="button disabled" aria-hidden="true">&raquo;</span>
            <?php endif; ?>
        </span>
    </div>
</div>

