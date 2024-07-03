<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_317 extends CI_Migration
{
    public function up()
    {
        $table = "`".db_prefix()."invoices`";
        $this->db->query("ALTER TABLE $table
        ADD `shaam_number` varchar(200) COLLATE 'utf8mb4_unicode_ci' NULL
        ");
    }
}
