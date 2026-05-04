<div class="content-wrapper">
    <section class="content-header">
        <h1>
            <i class="fa fa-bell"></i> <?php echo $this->lang->line('push_notification_setting'); ?>
            <small><?php echo $this->lang->line('firebase_cloud_messaging'); ?></small>
        </h1>
    </section>

    <section class="content">
        <div class="row">
            <div class="col-md-12">
                <?php if ($this->session->flashdata('msg')) { echo $this->session->flashdata('msg'); $this->session->unset_userdata('msg'); } ?>
            </div>

            <!-- Configuration card -->
            <div class="col-md-7">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-cog"></i> <?php echo $this->lang->line('configuration'); ?></h3>
                    </div>

                    <form id="pushConfigForm" action="<?php echo base_url('admin/pushnotificationsetting/save'); ?>" method="post" enctype="multipart/form-data" class="form-horizontal">
                        <?php echo $this->customlib->getCSRF(); ?>

                        <div class="box-body">
                            <p class="text-muted small">
                                <?php echo $this->lang->line('push_notification_setting_help'); ?>
                            </p>

                            <div class="form-group">
                                <label class="control-label col-md-4 col-sm-4 col-xs-12">
                                    <?php echo $this->lang->line('status'); ?>
                                </label>
                                <div class="col-md-8 col-sm-8 col-xs-12">
                                    <label class="radio-inline">
                                        <input type="radio" name="is_enabled" value="1" <?php echo (!empty($config['is_enabled']) ? 'checked' : ''); ?>>
                                        <?php echo $this->lang->line('enabled'); ?>
                                    </label>
                                    <label class="radio-inline">
                                        <input type="radio" name="is_enabled" value="0" <?php echo (empty($config['is_enabled']) ? 'checked' : ''); ?>>
                                        <?php echo $this->lang->line('disabled'); ?>
                                    </label>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-md-4 col-sm-4 col-xs-12">
                                    <?php echo $this->lang->line('firebase_project_id'); ?>
                                </label>
                                <div class="col-md-8 col-sm-8 col-xs-12">
                                    <input type="text" class="form-control" name="project_id" placeholder="my-firebase-project-12345" value="<?php echo set_value('project_id', isset($config['project_id']) ? $config['project_id'] : ''); ?>">
                                    <span class="help-block small"><?php echo $this->lang->line('firebase_project_id_help'); ?></span>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-md-4 col-sm-4 col-xs-12">
                                    <?php echo $this->lang->line('service_account_file'); ?>
                                </label>
                                <div class="col-md-8 col-sm-8 col-xs-12">
                                    <input type="file" name="service_account_file" accept="application/json,.json">
                                    <span class="help-block small"><?php echo $this->lang->line('service_account_file_help'); ?></span>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-md-4 col-sm-4 col-xs-12">
                                    <?php echo $this->lang->line('service_account_json'); ?>
                                </label>
                                <div class="col-md-8 col-sm-8 col-xs-12">
                                    <?php
                                    $stored_sa_meta = '';
                                    if (!empty($config['service_account_json'])) {
                                        $sa_dec = json_decode($config['service_account_json'], true);
                                        if (is_array($sa_dec)) {
                                            $em = isset($sa_dec['client_email']) ? $sa_dec['client_email'] : '';
                                            $pj = isset($sa_dec['project_id']) ? $sa_dec['project_id'] : '';
                                            if ($em !== '' || $pj !== '') {
                                                $stored_sa_meta = trim($em . ($pj !== '' ? ' — ' . $pj : ''));
                                            }
                                        }
                                    }
                                    ?>
                                    <?php if ($stored_sa_meta !== '') { ?>
                                        <p class="text-success small" style="margin-bottom:8px;">
                                            <i class="fa fa-check-circle"></i>
                                            <?php echo $this->lang->line('service_account_saved_as'); ?>:
                                            <code><?php echo html_escape($stored_sa_meta); ?></code>
                                        </p>
                                    <?php } ?>
                                    <textarea class="form-control" name="service_account_json" rows="6" placeholder='{ "type": "service_account", "project_id": "...", ... }'></textarea>
                                    <span class="help-block small">
                                        <?php echo $this->lang->line('service_account_json_help'); ?>
                                        <?php if (!empty($config['service_account_json'])) { ?>
                                            <br><strong><?php echo $this->lang->line('service_account_already_uploaded'); ?></strong>
                                        <?php } ?>
                                    </span>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-md-4 col-sm-4 col-xs-12">
                                    <?php echo $this->lang->line('default_channel_id'); ?>
                                </label>
                                <div class="col-md-8 col-sm-8 col-xs-12">
                                    <input type="text" class="form-control" name="default_channel_id" value="<?php echo set_value('default_channel_id', isset($config['default_channel_id']) ? $config['default_channel_id'] : 'default_channel'); ?>">
                                    <span class="help-block small"><?php echo $this->lang->line('default_channel_id_help'); ?></span>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-md-4 col-sm-4 col-xs-12">
                                    <?php echo $this->lang->line('default_sound'); ?>
                                </label>
                                <div class="col-md-8 col-sm-8 col-xs-12">
                                    <input type="text" class="form-control" name="default_sound" value="<?php echo set_value('default_sound', isset($config['default_sound']) ? $config['default_sound'] : 'default'); ?>">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-md-4 col-sm-4 col-xs-12">
                                    <?php echo $this->lang->line('default_click_action'); ?>
                                </label>
                                <div class="col-md-8 col-sm-8 col-xs-12">
                                    <input type="text" class="form-control" name="default_click_action" value="<?php echo set_value('default_click_action', isset($config['default_click_action']) ? $config['default_click_action'] : ''); ?>">
                                    <span class="help-block small"><?php echo $this->lang->line('default_click_action_help'); ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="box-footer">
                            <div class="col-md-offset-4">
                                <?php if ($this->rbac->hasPrivilege('notification_setting', 'can_edit')) { ?>
                                    <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> <?php echo $this->lang->line('save'); ?></button>
                                <?php } ?>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Send-test card -->
            <div class="col-md-5">
                <div class="box box-info">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-paper-plane"></i> <?php echo $this->lang->line('send_test_push'); ?></h3>
                    </div>

                    <form id="pushTestForm" class="form-horizontal">
                        <?php echo $this->customlib->getCSRF(); ?>

                        <div class="box-body">
                            <p class="text-muted small"><?php echo $this->lang->line('send_test_push_help'); ?></p>

                            <div class="form-group">
                                <label class="control-label col-md-4 col-sm-4 col-xs-12">
                                    <?php echo $this->lang->line('device_token'); ?> <small class="req">*</small>
                                </label>
                                <div class="col-md-8 col-sm-8 col-xs-12">
                                    <textarea class="form-control" name="test_token" rows="3" placeholder="dEv1cE_t0K3n_..." required></textarea>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-md-4 col-sm-4 col-xs-12">
                                    <?php echo $this->lang->line('title'); ?> <small class="req">*</small>
                                </label>
                                <div class="col-md-8 col-sm-8 col-xs-12">
                                    <input type="text" class="form-control" name="test_title" value="<?php echo $this->lang->line('test_push_default_title'); ?>" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-md-4 col-sm-4 col-xs-12">
                                    <?php echo $this->lang->line('body'); ?> <small class="req">*</small>
                                </label>
                                <div class="col-md-8 col-sm-8 col-xs-12">
                                    <textarea class="form-control" name="test_body" rows="3" required><?php echo $this->lang->line('test_push_default_body'); ?></textarea>
                                </div>
                            </div>

                            <div id="pushTestResult" class="ss-none">
                                <div class="alert" id="pushTestResultBanner" role="alert"></div>
                                <details>
                                    <summary class="cursor-pointer"><?php echo $this->lang->line('view_raw_fcm_response'); ?></summary>
                                    <pre id="pushTestRaw" class="bg-gray pad" style="white-space: pre-wrap; word-break: break-all; max-height: 240px; overflow:auto;"></pre>
                                </details>
                            </div>
                        </div>

                        <div class="box-footer">
                            <div class="col-md-offset-4">
                                <?php if ($this->rbac->hasPrivilege('notification_setting', 'can_edit')) { ?>
                                    <button type="submit" class="btn btn-info" id="pushTestSubmit">
                                        <i class="fa fa-paper-plane"></i> <?php echo $this->lang->line('send'); ?>
                                    </button>
                                <?php } ?>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
$(function () {
    $('#pushTestForm').on('submit', function (e) {
        e.preventDefault();

        var $form    = $(this);
        var $btn     = $('#pushTestSubmit');
        var $result  = $('#pushTestResult');
        var $banner  = $('#pushTestResultBanner');
        var $raw     = $('#pushTestRaw');
        var btnHtml  = $btn.html();

        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');
        $banner.removeClass('alert-success alert-danger').empty();
        $raw.empty();
        $result.removeClass('ss-none').show();

        $.ajax({
            url: '<?php echo base_url('admin/pushnotificationsetting/sendtest'); ?>',
            type: 'POST',
            data: $form.serialize(),
            dataType: 'json'
        }).done(function (resp) {
            if (resp && resp.status === 1) {
                $banner.addClass('alert-success').text(resp.message + (resp.fcm_message_id ? ' (' + resp.fcm_message_id + ')' : ''));
            } else {
                $banner.addClass('alert-danger').text((resp && resp.message) ? resp.message : '<?php echo $this->lang->line('test_push_failed'); ?>');
            }
            if (resp && resp.raw) {
                try {
                    var parsed = JSON.parse(resp.raw);
                    $raw.text(JSON.stringify(parsed, null, 2));
                } catch (e) {
                    $raw.text(resp.raw);
                }
            } else if (resp && resp.diagnostic) {
                // No raw FCM response means the request never left the server.
                // Surface the audit-log diagnostic so the admin can see exactly
                // what the library decided ('skipped', 'failed', etc.).
                $raw.text(JSON.stringify(resp.diagnostic, null, 2));
            } else {
                $raw.text('(no FCM response — see application/logs/ for cURL/auth errors)');
            }
        }).fail(function (xhr) {
            $banner.addClass('alert-danger').text('<?php echo $this->lang->line('test_push_failed'); ?> (HTTP ' + xhr.status + ')');
            $raw.text(xhr.responseText || '');
        }).always(function () {
            $btn.prop('disabled', false).html(btnHtml);
        });
    });
});
</script>
