<?php
defined('BASEPATH') or exit('No direct script access allowed');

// Get CodeIgniter instance
$CI = &get_instance();

// Define table names with prefix
$interaction_table = db_prefix() . 'whatsapp_interactions';
$interaction_messages_table = db_prefix() . 'whatsapp_interaction_messages';

// Create upload directories if they don't exist
$upload_folders = [
    WHATSAPP_MODULE_UPLOAD_FOLDER,
];

foreach ($upload_folders as $folder) {
    $desired_permission = 0755; // Default permission if not specified
    if (!is_dir($folder)) {
        if (!mkdir($folder, $desired_permission, true)) {
            die('Failed to create directory: ' . $folder);
        }
        // Create index.html file to prevent directory listing
        $fp = fopen($folder . '/index.html', 'w');
        fclose($fp);
    }
}

// Set permissions to 0777 for all folders
foreach ($upload_folders as $folder) {
    if (is_dir($folder)) {
        if (!chmod($folder, 0777)) {
            die('Failed to set permissions for directory: ' . $folder);
        }
    }
}
// Create table for WhatsApp official interactions if it doesn't exist
$create_interaction_table_query = "
    CREATE TABLE IF NOT EXISTS `$interaction_table` (
        `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `name` VARCHAR(100) NOT NULL,
        `receiver_id` VARCHAR(20) NOT NULL,
        `last_message` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
        `time_sent` DATETIME NOT NULL,
        `type` VARCHAR(500) NULL,
        `type_id` VARCHAR(500) NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
";
$CI->db->query($create_interaction_table_query);

// Create table for interaction messages if it doesn't exist
$create_interaction_messages_table_query = "
    CREATE TABLE IF NOT EXISTS `$interaction_messages_table` (
        `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `interaction_id` INT(11) UNSIGNED NOT NULL,
        `sender_id` VARCHAR(20) NOT NULL,
        `url` VARCHAR(255) NULL,
        `message` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
        `status` VARCHAR(20) NULL,
        `time_sent` DATETIME NOT NULL,
        `message_id` VARCHAR(500) NULL,
        `staff_id` VARCHAR(500) NULL,
        `type` VARCHAR(20) NULL,
        PRIMARY KEY (`id`),
        FOREIGN KEY (`interaction_id`) REFERENCES `$interaction_table`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
";
$CI->db->query($create_interaction_messages_table_query);

// Check if the last_msg_time column exists in the interaction_table
if (!$CI->db->field_exists('last_msg_time', $interaction_table)) {
    // Add last_msg_time column to interaction_table if it doesn't exist
    $alter_interaction_table_query = "
        ALTER TABLE `$interaction_table`
        ADD COLUMN `last_msg_time` DATETIME NULL AFTER `last_message`
    ";
    $CI->db->query($alter_interaction_table_query);
}

// Check if the wa_no column exists in the interaction_table
if (!$CI->db->field_exists('wa_no', $interaction_table)) {
    // Add wa_no column to interaction_table if it doesn't exist
    $alter_interaction_table_query = "
        ALTER TABLE `$interaction_table`
        ADD COLUMN `wa_no` VARCHAR(20) NULL AFTER `last_msg_time`
    ";
    $CI->db->query($alter_interaction_table_query);
}

// Check if the wa_no_id column exists in the interaction_table
if (!$CI->db->field_exists('wa_no_id', $interaction_table)) {
    // Add wa_no_id column to interaction_table if it doesn't exist
    $alter_interaction_table_query = "
        ALTER TABLE `$interaction_table`
        ADD COLUMN `wa_no_id` VARCHAR(20) NULL AFTER `wa_no`
    ";
    $CI->db->query($alter_interaction_table_query);
}
$alter_interaction_table_query = "
    ALTER TABLE `$interaction_messages_table`
    MODIFY `message` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;
";
$CI->db->query($alter_interaction_table_query);

// Check if the ref_message_id column exists in the interaction_table
if (!$CI->db->field_exists('ref_message_id', $interaction_messages_table)) {
    // Add ref_message_id column to interaction_table if it doesn't exist
    $alter_interaction_table_query = "
        ALTER TABLE `$interaction_messages_table`
        ADD COLUMN `ref_message_id` VARCHAR(500) NULL;
    ";
    $CI->db->query($alter_interaction_table_query);
}
