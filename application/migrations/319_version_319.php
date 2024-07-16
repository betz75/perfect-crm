<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_319 extends CI_Migration
{
    public function up()
    {
        $table = "`".db_prefix()."estimates`";
        $this->db->query("ALTER TABLE $table
        ADD `exchange_rate` decimal(20, 2) COLLATE 'utf8mb4_unicode_ci' NULL
        ");
    }
}
