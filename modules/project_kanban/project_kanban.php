<?php

defined("BASEPATH") or exit("No direct script access allowed");

/*
Module Name: Project Kanban
Description: Easily track your projects with the project kanban view
Author: Halil
Author URI: https://www.fiverr.com/halilaltndg
Version: 1.0.1
*/


define("PROJECT_KANBAN_MODULE_NAME", "project_kanban");

hooks()->add_action("admin_init", "project_kanban_manage_permission");

hooks()->add_action("admin_init", "project_kanban_module_init_menu_items");


get_instance()->load->helper('project_kanban/project_kanban');


/**
 * @note Language uploading
 */
register_language_files(PROJECT_KANBAN_MODULE_NAME, [ PROJECT_KANBAN_MODULE_NAME ]);


/**
 * @note permission
 *
 * @return void
 */
function project_kanban_manage_permission()
{

    $capabilities = [];

    $capabilities["capabilities"] = [

        "project_kanban"      => _l('project_kanban_permission') ,

    ];

    register_staff_capabilities("project_kanban", $capabilities , _l('project_kanban_permission') );

}


/**
 * @note menu
 *
 * @return void
 */
function project_kanban_module_init_menu_items()
{

    $CI = & get_instance();

    if( staff_can( 'project_kanban' , 'project_kanban' ) )
    {

        $CI->app_menu->add_sidebar_menu_item("project_kanban", [

            'name'      => _l("project_kanban"),

            'position'  => 31,

            'icon'      => 'fa fa-grip-vertical',

            'href'      => admin_url('project_kanban'),

            'badge'     => [],

        ]);


    }

}



hooks()->add_action("app_admin_footer", "project_kanban_module_footer");

function project_kanban_module_footer()
{


    if( staff_can( 'project_kanban' , 'project_kanban' ) )
    {

     echo " 
        <script> var lang_project_kanban = '"._l('project_kanban')."'; </script>
        
        <script src='" . base_url("modules/project_kanban/assets/project_kanban_js.js?v=1") ."'></script> 
        ";

    }


}

