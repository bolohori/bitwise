<?php

namespace Oxyrealm\Modules\Bitwise\Admin;

use function Oxyrealm\Modules\Bitwise\aether_m_bitwise;

class ExportImport
{
    public function __construct()
    {
        add_action('load-oxygen_page_ct_export_import', [$this, 'exportImport']);
    }

    public function exportImport()
    {
        wp_enqueue_style('josdejong-jsoneditor', 'https://cdn.jsdelivr.net/npm/jsoneditor@9.5.1/dist/jsoneditor.min.css');
        wp_register_script('josdejong-jsoneditor', 'https://cdn.jsdelivr.net/npm/jsoneditor@9.5.1/dist/jsoneditor.min.js', [], false, true);
        wp_enqueue_script('bitwise-page_ct_export_import', OXYREALM_BITWISE_ASSETS . '/js/admin/page_ct_export_import.js', [
            aether_m_bitwise()->module_id.'-vendor',
            'josdejong-jsoneditor'
        ], false, true);
    }
}
