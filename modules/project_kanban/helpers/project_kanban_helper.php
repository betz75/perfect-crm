<?php


/**
 *
 * Project status kanban view
 *
 */
function project_kanban_project_info( $status_id )
{

    $CI = &get_instance();

    if ( !empty( $CI->input->get('search') ) )
        $CI->db->where("c.company like '%".$CI->input->get('search')."%' ",null,false);

    if ( !has_permission('projects', '', 'view')  )
    {
        $CI->db->where('p.id IN (SELECT project_id FROM ' . db_prefix() . 'project_members WHERE staff_id=' . get_staff_user_id() . ')',null,false);
    }

    if ( !empty( $CI->input->get('sort_by') ) && !empty( $CI->input->get('sort') ) )
        $CI->db->order_by($CI->input->get('sort_by') , $CI->input->get('sort') );


    $projects = $CI->db->select('p.start_date, p.deadline, p.status , p.name as project_name, p.clientid , c.company, p.id as project_id ')
                        ->from(db_prefix().'projects p')
                        ->join(db_prefix().'clients c','c.userid = p.clientid')
                        ->where('p.status',$status_id)
                        ->where('p.status != 4')
                        ->get()
                        ->result();

    return $projects;

}
