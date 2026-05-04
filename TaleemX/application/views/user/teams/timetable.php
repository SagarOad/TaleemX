<div class="content-wrapper">
    <section class="content-header">
        <h1><i class="fa fa-mortar-board"></i> <?php echo $this->lang->line('live_class'); ?></h1>
    </section>
    <section class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-search"></i> <?php echo $this->lang->line('live_class'); ?></h3>
                    </div>
                    <div class="box-body">
                        <div class="table-responsive">
                            <div class="download_label"><?php echo $this->lang->line('teams_live_classes'); ?></div>
                            <table class="table table-hover table-striped table-bordered example" data-export-title="<?php echo $this->lang->line('teams_live_classes'); ?>">
                                <thead>
                                    <tr>
                                        <th><?php echo $this->lang->line('class') . " " . $this->lang->line('title'); ?></th>
                                        <th><?php echo $this->lang->line('date_time'); ?></th>
                                        <th><?php echo $this->lang->line('class_duration_minutes'); ?></th>
                                        <th><?php echo $this->lang->line('class'); ?></th>
                                        <th><?php echo $this->lang->line('class') . ' ' . $this->lang->line('host'); ?></th>
                                        <th width="30%"><?php echo $this->lang->line('description'); ?></th>
                                        <th><?php echo $this->lang->line('status'); ?></th>
                                        <th class="text-right"><?php echo $this->lang->line('action'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($conferences as $conference_value) { ?>
                                        <tr>
                                            <td><?php echo $conference_value->title; ?></td>
                                            <td><?php echo $this->customlib->dateyyyymmddToDateTimeformat($conference_value->date); ?></td>
                                            <td><?php echo $conference_value->duration; ?></td>
                                            <td><?php echo $conference_value->class . " (" . $conference_value->section . ")"; ?></td>
                                            <td>
                                                <?php
                                                $name = ($conference_value->create_for_surname == "") ? $conference_value->create_for_name : $conference_value->create_for_name . " " . $conference_value->create_for_surname;
                                                echo $name . " (" . $conference_value->for_create_role_name . " : " . $conference_value->for_create_employee_id . ")";
                                                ?>
                                            </td>
                                            <td><?php echo $conference_value->description; ?></td>
                                            <td>
                                                <?php if ($conference_value->status == 0) { ?>
                                                    <span class="label label-warning"><?php echo $this->lang->line('awaited'); ?></span>
                                                <?php } elseif ($conference_value->status == 1) { ?>
                                                    <span class="label label-default"><?php echo $this->lang->line('cancelled'); ?></span>
                                                <?php } else { ?>
                                                    <span class="label label-success"><?php echo $this->lang->line('finished'); ?></span>
                                                <?php } ?>
                                            </td>
                                            <td class="mailbox-date pull-right">
                                                <?php if ($conference_value->status == 0) { ?>
                                                    <?php if ($role != 'parent') { ?>
                                                        <a href="<?php echo $conference_value->url; ?>" class="btn btn-primary btn-xs join-btn" data-id="<?php echo $conference_value->id; ?>">
                                                            <span><i class="fa fa-video-camera"></i> <?php echo $this->lang->line('join'); ?></span>
                                                        </a>
                                                    <?php } elseif (!empty($teams_setting) && $teams_setting->parent_live_class) { ?>
                                                        <a href="<?php echo $conference_value->url; ?>" class="btn btn-primary btn-xs join-btn" data-id="<?php echo $conference_value->id; ?>">
                                                            <span><i class="fa fa-video-camera"></i> <?php echo $this->lang->line('join'); ?></span>
                                                        </a>
                                                    <?php } ?>
                                                <?php } ?>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
<script type="text/javascript">
    (function ($) {
        "use strict";
        $(document).on('click', 'a.join-btn', function (e) {
            e.preventDefault();
            var id = $(this).data('id');
            var url = $(this).attr('href');
            $.ajax({
                url: "<?php echo site_url("user/teams/add_history"); ?>",
                type: "POST",
                data: {"id": id},
                dataType: 'json',
                success: function (res) {
                    if (res.status == 1) {
                        window.open(url, '_blank');
                    }
                },
                error: function () {
                    alert("Error occured.please try again");
                }
            });
        });
    })(jQuery);
</script>
