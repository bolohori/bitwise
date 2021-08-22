let mix = require('laravel-mix');

mix
  .js('assets/js/admin/page_ct_export_import.js', 'dist/js/admin')
  ;

mix
  .extract({ to: 'dist/js/vendor.js' })
  .options({ runtimeChunkPath: 'dist/js' })
  .disableNotifications();