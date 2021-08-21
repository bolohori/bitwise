<?php

namespace Oxyrealm\Modules\Bitwise;

use Oxyrealm\Modules\Bitwise\Command\Setting;

class Command
{
    public function __construct()
    {
        if (!class_exists('WP_CLI')) {
            return;
        }

        new Setting;
    }
}
