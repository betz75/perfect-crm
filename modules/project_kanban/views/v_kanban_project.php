<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php init_head(); ?>

    <div id="wrapper">
        <div class="content">
            <div class="row">
                <div class="col-md-12">
                    <div class="_buttons tw-mb-3 sm:tw-mb-5">
                        <div class="row">

                            <div class="col-md-6">

                                <a href="<?php echo admin_url('projects' ); ?>"

                                   class="btn btn-default mleft5 pull-left" data-toggle="tooltip" data-placement="top"

                                   data-title="<?php echo _l('switch_to_list_view'); ?>">

                                    <i class="fa-solid fa-table-list"></i>

                                    <?php echo _l('switch_to_list_view'); ?>

                                </a>

                            </div>

                            <div class="col-md-2"></div>

                            <div class="col-md-4" data-toggle="tooltip" data-placement="top"
                                 data-title="<?php echo _l('search_by_tags'); ?>">
                                <?php echo render_input('search', '', '', 'search', ['data-name' => 'search', 'onkeyup' => 'project_kanban_pipeline();', 'placeholder' => 'Search client'], [], 'no-margin') ?>


                                <?php echo form_hidden('sort_type'); ?>
                                <?php echo form_hidden('sort', (get_option('default_proposals_pipeline_sort') != '' ? get_option('default_proposals_pipeline_sort_type') : '')); ?>
                            </div>

                        </div>

                    </div>

                    <div class="animated mtop5 fadeIn">
                        <?php echo form_hidden('proposalid', 0); ?>
                        <div>
                            <div class="row">
                                <div class="col-md-5">
                                    <div class="kanban-leads-sort">
                                        <span class="bold"><?php echo _l('proposals_pipeline_sort'); ?>: </span>
                                        <a href="#" onclick="project_kanban_pipeline_sort('datecreated'); return false" class="datecreated">
                                            <?php echo _l('proposals_sort_datecreated'); ?>
                                        </a>
                                        |
                                        <a href="#" onclick="project_kanban_pipeline_sort('startdate'); return false" class="startdate">
                                            <?php echo _l('task_single_start_date'); ?>
                                        </a>
                                        |
                                        <a href="#" onclick="project_kanban_pipeline_sort('duedate');return false;" class="duedate">
                                            <?php echo _l('task_single_due_date'); ?>
                                        </a>
                                    </div>

                                </div>

                                <div class="col-md-7 hide">
                                    <h3 id="item_name_text"></h3>
                                </div>


                            </div>
                            <div class="row">
                                <div id="proposals-pipeline">
                                    <div class="container-fluid">
                                        <div id="kan-ban"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="proposal">
    </div>


    <?php init_tail(); ?>

    <div id="convert_helper"></div>

    <div class="modal fade " id="project-preview-modal" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel">
        <div class="modal-dialog modal-xl">
            <div class="modal-content" id="project-preview-content">

            </div>
        </div>
    </div>

    <script>

        $(function() {

            project_kanban_pipeline();

        });


        function project_kanban_pipeline() {

            var pipeline_url = "project_kanban/project_kanban/kanban_content";

            init_kanban(
                pipeline_url ,
                project_kanban_update,
                ".project-status",
                290,
                360
            );

        }

        function project_kanban_pipeline_sort( type ) {
            kan_ban_sort(type, project_kanban_pipeline );
        }


        var project_kanban_process_start = 0;

        function project_kanban_update( ui , object )
        {

            if ( project_kanban_process_start == 1 )
                return true;

            project_kanban_process_start = 1;

            var data = {

                projects: [],

                status: $(ui.item.parent()[0]).attr("data-status-id"),

            };


            $.each($(ui.item.parent()[0]).find("[data-project-id]"), function (idx, el) {

                var id = $(el).attr("data-project-id");

                if (id) {

                    data.projects.push(id);

                }

            });


            $("body").append('<div class="dt-loader"></div>');

            $.post(admin_url+'project_kanban/project_kanban/kanban_status_update',data).done(function ( response ){

                project_kanban_process_start = 0;

                response = JSON.parse(response);

                $("body").find(".dt-loader").remove();

                alert_float('success',response.message);

                project_kanban_pipeline();

            });

        }


        function init_project_preview( project_id )
        {

            $("#project-preview-modal").modal("show");

            $('#project-preview-content').html('<div class="project-kanban-loading-spinner"></div>');

            requestGet("project_kanban/project_kanban/preview/" + project_id )

                .done(function (response) {


                    $('#project-preview-content').html( response );


                })

                .fail(function (data) {

                    $("#project-preview-modal").modal("hide");

                    alert_float("danger", data.responseText);

                });


        }

    </script>


    <style>

        .project-kanban-loading-spinner {
            border: 4px solid #3498db; /* Spinner color */
            border-radius: 50%;
            border-top: 4px solid #fff;
            width: 35px;
            height: 35px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

    </style>

    </body>

    </html>

<?php
