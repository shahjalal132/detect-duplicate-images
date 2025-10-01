# Find Duplicate Images by Hash

A WordPress plugin to scan the Media Library and find duplicate images by file hash (MD5). Includes stats, orphan detection, pagination, and bulk delete functionality with a beautiful dashboard.

## Features

- 🔍 Scan media library for duplicate images using MD5 hash
- 📊 Beautiful statistics dashboard
- 🏷️ Detect orphaned vs attached images
- 🗑️ Bulk delete orphaned duplicates only (safe deletion)
- 📄 Pagination support for large media libraries (1M+ images)
- ✨ Modern, clean UI

## File Structure

```
ditect-duplicate-images/
├── ditect-duplicate-images.php    # Main plugin file
├── src/
│   ├── helpers/
│   │   └── functions.php          # Helper functions
│   ├── templates/
│   │   ├── main-page.php          # Main admin page template
│   │   ├── stats-dashboard.php    # Statistics dashboard template
│   │   ├── pagination.php         # Pagination controls template
│   │   └── duplicate-table.php    # Duplicate images table template
│   ├── css/
│   │   └── admin-styles.css       # Admin styles
│   └── js/
│       └── admin-scripts.js       # Admin JavaScript
└── README.md
```

## Architecture

### Main Plugin File (`ditect-duplicate-images.php`)
- Plugin initialization and WordPress hooks
- Asset enqueuing (CSS/JS)
- Admin page registration
- Controller logic for rendering and deletion

### Helper Functions (`src/helpers/functions.php`)
- `fdi_get_duplicate_images_by_hash()` - Scan and find duplicates
- `fdi_is_image_attached()` - Check if image is used
- `fdi_calculate_stats()` - Calculate statistics
- `fdi_get_pagination_data()` - Calculate pagination
- `fdi_get_base_url()` - Get base admin URL

### Templates (`src/templates/`)
- **main-page.php** - Main admin page wrapper
- **stats-dashboard.php** - Statistics boxes (total sets, files, size)
- **pagination.php** - Pagination controls (reusable top/bottom)
- **duplicate-table.php** - Table displaying duplicate images with actions

### Styles (`src/css/admin-styles.css`)
- Stats dashboard styling
- Table and image preview styles
- Pagination controls
- Status indicators (attached/orphan)

### JavaScript (`src/js/admin-scripts.js`)
- Pagination page number input handling
- Delete confirmation dialogs
- Loading states for buttons

## Installation

1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to **Tools > Find Duplicate Images**

## Usage

1. Go to **Tools > Find Duplicate Images**
2. The plugin will automatically scan your media library
3. View duplicate sets with their attachment status
4. Click "Delete X Orphan(s)" to remove orphaned duplicates
5. Use pagination controls to navigate through results

## Development

### Constants
- `FDI_PLUGIN_DIR` - Plugin directory path
- `FDI_PLUGIN_URL` - Plugin URL
- `FDI_VERSION` - Current plugin version

### Function Prefix
All helper functions use the `fdi_` prefix to avoid conflicts.

### Coding Standards
- Follows WordPress Coding Standards
- Uses PHP 7.0+ features
- Properly escaped and sanitized data
- Nonce verification for security

## Security

- Permission checks (`current_user_can`)
- Nonce verification for all forms
- Data sanitization and validation
- Double-check orphan status before deletion
- Safe deletion (only orphaned images)

## Version History

- **1.3** - Refactored architecture with separated concerns
- **1.2** - Added pagination support
- **1.1** - Added single delete button for orphans only
- **1.0** - Initial release

## Author

Shah Jalal

## License

GPL v2 or later

