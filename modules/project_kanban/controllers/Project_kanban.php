<?php

/**
 * @property Projects_model $projects_model
 */


use app\services\projects\HoursOverviewChart;


class Project_kanban extends AdminController
{

    public function __construct()
    {

        parent::__construct();

        if( !staff_can( 'project_kanban' , 'project_kanban' ) )
            access_denied( _l('project_kanban') );


        $this->load->model('projects_model');

    }


    /**
     * Project Status Kanban view index
     */
    public function index()
    {


        $data['bodyclass']       = 'proposals-pipeline';

        $data['switch_pipeline'] = false;

        $data['title']          = _l('leads_switch_to_kanban');


        $this->app_scripts->add('circle-progress-js', 'assets/plugins/jquery-circle-progress/circle-progress.min.js');

        $this->load->view('v_kanban_project', $data);


    }

    public function kanban_content()
    {

        $data['title']              = _l('leads_switch_to_kanban');

        $data['project_statuses']   = $this->projects_model->get_project_statuses();

        $this->load->view('v_kanban_detail', $data);

    }

    /**
     * Project status update function
     */
    public function kanban_status_update()
    {

        $success = false;

        $message = '';

        if( $this->input->is_ajax_request() && $this->input->post('projects') && $this->input->post('status') )
        {


            if (!class_exists('projects_model') )
                $this->load->model('projects_model');


            if (staff_can('create', 'projects') || staff_can('edit', 'projects') )
            {

                $projects   = $this->input->post('projects');
                $status_id  = $this->input->post('status');

                $status     = get_project_status_by_id($status_id);

                foreach ( $projects as $project_id )
                {

                    $project_info = $this->db->select('status')->from(db_prefix().'projects')->where('id',$project_id)->get()->row();

                    if ( $project_info->status != $status_id )
                    {

                        $post_data = [
                            'project_id' => $project_id ,
                            'status_id' => $status_id ,
                            'notify_project_members_status_change' => false ,
                            'mark_all_tasks_as_completed' => false ,
                        ];

                        $success = $this->projects_model->mark_as( $post_data );

                        $message = _l('project_marked_as_failed', $status['name']);

                        if ($success) {

                            $message = _l('project_marked_as_success', $status['name']);

                        }

                    }

                }

            }

        }

        echo json_encode([

            'success' => $success,

            'message' => $message,

        ]);


    }


    public function preview( $project_id )
    {


        $this->load->helper('date');

        $data = [];

        $project = $this->projects_model->get( $project_id );



        $project->settings->available_features = unserialize($project->settings->available_features);

        $data['statuses']                      = $this->projects_model->get_project_statuses();




        $data['project']  = $project;

        $data['currency'] = $this->projects_model->get_currency($project_id);



        $data['project_total_logged_time'] = $this->projects_model->total_logged_time($project_id);


        $percent         = $this->projects_model->calc_progress($project_id);

        $data['members'] = $this->projects_model->get_project_members($project_id);

        foreach ($data['members'] as $key => $member)
        {

            $data['members'][$key]['total_logged_time'] = 0;

            $member_timesheets                          = $this->tasks_model->get_unique_member_logged_task_ids($member['staff_id'], ' AND task_id IN (SELECT id FROM ' . db_prefix() . 'tasks WHERE rel_type="project" AND rel_id="' . $this->db->escape_str($project_id) . '")');



            foreach ($member_timesheets as $member_task) {

                $data['members'][$key]['total_logged_time'] += $this->tasks_model->calc_task_total_time($member_task->task_id, ' AND staff_id=' . $member['staff_id']);

            }

        }




        $data['project_total_days']        = round((human_to_unix($data['project']->deadline . ' 00:00') - human_to_unix($data['project']->start_date . ' 00:00')) / 3600 / 24);

        $data['project_days_left']         = $data['project_total_days'];

        $data['project_time_left_percent'] = 100;

        if ($data['project']->deadline)
        {

            if (human_to_unix($data['project']->start_date . ' 00:00') < time() && human_to_unix($data['project']->deadline . ' 00:00') > time()) {

                $data['project_days_left']         = round((human_to_unix($data['project']->deadline . ' 00:00') - time()) / 3600 / 24);

                $data['project_time_left_percent'] = $data['project_days_left'] / $data['project_total_days'] * 100;

                $data['project_time_left_percent'] = round($data['project_time_left_percent'], 2);

            }

            if (human_to_unix($data['project']->deadline . ' 00:00') < time()) {

                $data['project_days_left']         = 0;

                $data['project_time_left_percent'] = 0;

            }

        }



        $__total_where_tasks = 'rel_type = "project" AND rel_id=' . $this->db->escape_str($project_id);

        if (!staff_can('view', 'tasks')) {

            $__total_where_tasks .= ' AND ' . db_prefix() . 'tasks.id IN (SELECT taskid FROM ' . db_prefix() . 'task_assigned WHERE staffid = ' . get_staff_user_id() . ')';



            if (get_option('show_all_tasks_for_project_member') == 1) {

                $__total_where_tasks .= ' AND (rel_type="project" AND rel_id IN (SELECT project_id FROM ' . db_prefix() . 'project_members WHERE staff_id=' . get_staff_user_id() . '))';

            }

        }



        $__total_where_tasks = hooks()->apply_filters('admin_total_project_tasks_where', $__total_where_tasks, $project_id);



        $where = ($__total_where_tasks == '' ? '' : $__total_where_tasks . ' AND ') . 'status != ' . Tasks_model::STATUS_COMPLETE;



        $data['tasks_not_completed'] = total_rows(db_prefix() . 'tasks', $where);

        $total_tasks                 = total_rows(db_prefix() . 'tasks', $__total_where_tasks);

        $data['total_tasks']         = $total_tasks;



        $where = ($__total_where_tasks == '' ? '' : $__total_where_tasks . ' AND ') . 'status = ' . Tasks_model::STATUS_COMPLETE . ' AND rel_type="project" AND rel_id="' . $project_id . '"';



        $data['tasks_completed'] = total_rows(db_prefix() . 'tasks', $where);



        $data['tasks_not_completed_progress'] = ($total_tasks > 0 ? number_format(($data['tasks_completed'] * 100) / $total_tasks, 2) : 0);

        $data['tasks_not_completed_progress'] = round($data['tasks_not_completed_progress'], 2);


        $data['project_overview_chart'] = (new HoursOverviewChart(

            $id,

            ($this->input->get('overview_chart') ? $this->input->get('overview_chart') : 'this_week')

        ))->get();

        @$percent_circle        = $percent / 100;

        $data['percent_circle'] = $percent_circle;

        $data['percent'] = $percent;



        $other_projects_where = 'id != ' . $project_id;



        $statuses = $this->projects_model->get_project_statuses();



        $other_projects_where .= ' AND (';

        foreach ($statuses as $status) {

            if (isset($status['filter_default']) && $status['filter_default']) {

                $other_projects_where .= 'status = ' . $status['id'] . ' OR ';

            }

        }



        $other_projects_where = rtrim($other_projects_where, ' OR ');



        $other_projects_where .= ')';



        if (!staff_can('view', 'projects')) {

            $other_projects_where .= ' AND ' . db_prefix() . 'projects.id IN (SELECT project_id FROM ' . db_prefix() . 'project_members WHERE staff_id=' . get_staff_user_id() . ')';

        }



        $data['other_projects'] = $this->projects_model->get('', $other_projects_where);

        $data['title']          = $data['project']->name;

        $data['project_status'] = get_project_status_by_id($project->status);




        $this->load->view('v_project_modal' , $data);

    }


}
