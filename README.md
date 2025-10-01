# Find Duplicate Images by Hash

A high-performance WordPress plugin to scan the Media Library and find duplicate images by file hash (MD5). Optimized for large media libraries (1M+ images) with batch processing, caching, and beautiful UI.

## Features

- 🔍 **Smart Scanning** - MD5 hash-based duplicate detection
- ⚡ **High Performance** - Batch processing for millions of images
- 💾 **Intelligent Caching** - Results cached for 24 hours
- ⏸️ **Stop & Resume** - Pause scan anytime and resume later
- 📊 **Beautiful Statistics Dashboard**
- 🏷️ **Orphan Detection** - Identify unused vs attached images
- 🗑️ **Safe Bulk Delete** - Only delete orphaned duplicates
- 📄 **Pagination** - Handle thousands of duplicate sets
- 📈 **Real-time Progress** - Visual progress bar during scanning
- 💾 **Partial Results** - View duplicates found even if scan is stopped
- ✨ **Modern UI** - Clean, intuitive interface

## Performance Optimizations

### For Large Media Libraries (1M+ images)

1. **Batch Processing**
   - Processes 100 images per batch via AJAX
   - Prevents PHP timeout errors
   - Real-time progress updates

2. **Smart Caching**
   - MD5 hashes stored in postmeta (`_fdi_md5_hash`)
   - Scan results cached for 24 hours
   - Only recalculates when needed

3. **Optimized Database Queries**
   - Efficient batched queries
   - Indexed lookups
   - Minimal memory usage

4. **Manual Scan Trigger**
   - No automatic scanning on page load
   - User-initiated scans only
   - Background processing

## File Structure

```
ditect-duplicate-images/
├── ditect-duplicate-images.php    # Main plugin file
├── src/
│   ├── helpers/
│   │   └── functions.php          # Helper functions (caching, batch processing)
│   ├── templates/
│   │   ├── main-page.php          # Main admin page with scan controls
│   │   ├── stats-dashboard.php    # Statistics dashboard
│   │   ├── pagination.php         # Pagination controls
│   │   └── duplicate-table.php    # Duplicate images table
│   ├── css/
│   │   └── admin-styles.css       # Admin styles (scan UI, progress bar)
│   └── js/
│       └── admin-scripts.js       # AJAX batch processing, progress updates
└── README.md
```

## Architecture

### Main Plugin File (`ditect-duplicate-images.php`)
- Plugin initialization and WordPress hooks
- Asset enqueuing (CSS/JS with localized data)
- Admin page registration
- AJAX handlers for batch processing
- Controller logic for rendering and deletion

### Helper Functions (`src/helpers/functions.php`)
**Scanning Functions:**
- `fdi_get_attachments_batch()` - Get batch of attachments
- `fdi_process_batch()` - Process batch and calculate hashes
- `fdi_get_attachment_hash()` - Get/cache MD5 hash
- `fdi_merge_batch_results()` - Merge batch results

**Caching Functions:**
- `fdi_get_cached_duplicates()` - Retrieve cached results
- `fdi_set_cached_duplicates()` - Store results in cache
- `fdi_clear_cache()` - Clear all cached data
- `fdi_get_scan_progress()` - Get current scan progress
- `fdi_set_scan_progress()` - Update scan progress

**Utility Functions:**
- `fdi_is_image_attached()` - Check if image is used
- `fdi_calculate_stats()` - Calculate statistics
- `fdi_get_pagination_data()` - Calculate pagination
- `fdi_get_last_scan_time()` - Get last scan timestamp

### Templates (`src/templates/`)
- **main-page.php** - Scan controls, progress bar, results
- **stats-dashboard.php** - Statistics boxes
- **pagination.php** - Pagination controls (reusable)
- **duplicate-table.php** - Table with delete actions

### Styles (`src/css/admin-styles.css`)
- Scan card and button styles
- Progress bar animations
- Stats dashboard layout
- Table and image previews
- Pagination controls

### JavaScript (`src/js/admin-scripts.js`)
**AJAX Operations:**
- Start scan process
- Process batches sequentially
- Update progress bar in real-time
- Clear cache

**User Interactions:**
- Pagination navigation
- Delete confirmations
- Button state management

## Installation

1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to **Tools > Find Duplicate Images**

## Usage

### First-Time Use
1. Go to **Tools > Find Duplicate Images**
2. Click **"▶️ Start Scan"** button
3. Wait for the scan to complete (progress bar shows status)
4. Review duplicate sets and delete orphaned images

### For Large Libraries
- **100k images**: ~1-2 minutes
- **500k images**: ~5-10 minutes
- **1M+ images**: ~10-20 minutes

The scan runs in the background with real-time progress updates.

### After Scanning
- Results are cached for 24 hours
- View duplicates instantly on subsequent visits
- Click **"🔄 Re-scan Library"** to update results
- Click **"Clear Cache"** to force a new scan

### Stop & Resume Feature
- Click **"⏸️ Stop Scan"** at any time during scanning
- Partial results are automatically saved
- View duplicates found so far immediately
- Click **"▶️ Resume Scan"** to continue from where you stopped
- Already scanned images are skipped (uses cached MD5 hashes)
- Perfect for very large libraries or if you need to free up resources

### Deleting Duplicates
1. Review the duplicate sets
2. Check which images are "Orphans" (not attached)
3. Click **"Delete X Orphan(s)"** button
4. Confirm deletion
5. Cache automatically clears and page reloads

## Configuration

### Batch Size
Edit `FDI_BATCH_SIZE` constant in main plugin file (default: 100)
```php
define( 'FDI_BATCH_SIZE', 100 ); // Process 100 images per batch
```

- Lower = slower but more stable
- Higher = faster but may timeout on slow servers

### Cache Duration
Edit cache expiration in `ajax_process_batch()` method:
```php
fdi_set_cached_duplicates( $progress['duplicates'], 3600 * 24 ); // 24 hours
```

### Items Per Page
Edit `PER_PAGE` constant in plugin class (default: 20)
```php
const PER_PAGE = 20;
```

## Database Storage

### Transients (Temporary Cache)
- `fdi_duplicate_images` - Cached duplicate results (24 hours)
- `fdi_scan_progress` - Current scan progress (10 minutes)

### Options (Persistent)
- `fdi_last_scan_time` - Timestamp of last completed scan

### Post Meta (Per Image)
- `_fdi_md5_hash` - Cached MD5 hash for each attachment

## Performance Tips

1. **Run scans during off-peak hours** for very large libraries
2. **Increase PHP memory limit** if processing 1M+ images:
   ```php
   define('WP_MEMORY_LIMIT', '512M');
   ```
3. **Increase PHP max execution time** in `.htaccess`:
   ```apache
   php_value max_execution_time 300
   ```
4. **Use object caching** (Redis/Memcached) for faster transient access

## Troubleshooting

### Scan Timeout
- Reduce `FDI_BATCH_SIZE` to 50 or lower
- Increase PHP `max_execution_time`
- Check server resources

### Memory Errors
- Increase PHP `memory_limit`
- Reduce batch size
- Clear other WordPress transients

### Slow Performance
- Enable object caching
- Optimize database tables
- Check for conflicting plugins

## Security

- ✅ Permission checks (`current_user_can`)
- ✅ Nonce verification for all forms
- ✅ AJAX nonce verification
- ✅ Data sanitization and validation
- ✅ Double-check orphan status before deletion
- ✅ Safe deletion (only orphaned images)

## Browser Compatibility

- Chrome/Edge: ✅ Full support
- Firefox: ✅ Full support
- Safari: ✅ Full support
- IE11: ⚠️ Not supported

## Version History

- **1.5** - Stop & Resume functionality
  - Added stop scan button
  - Partial results display
  - Resume from last position
  - Smart hash caching (skips already scanned images)
  - Improved progress tracking
  - Better UX for interrupted scans

- **1.4** - Performance optimization for large libraries
  - Added batch processing with AJAX
  - Implemented caching system
  - MD5 hash storage in postmeta
  - Manual scan trigger with progress bar
  - Background processing
  
- **1.3** - Refactored architecture with separated concerns
- **1.2** - Added pagination support
- **1.1** - Added single delete button for orphans only
- **1.0** - Initial release

## Requirements

- WordPress 5.0+
- PHP 7.0+
- MySQL 5.6+

## Author

Shah Jalal

## License

GPL v2 or later

## Support

For issues with 1M+ images or performance optimization, please ensure:
1. Server meets minimum requirements
2. PHP memory limit is adequate (512MB+ recommended)
3. Object caching is enabled for best performance
