<?php $currency_symbol = $this->customlib->getSchoolCurrencyFormat(); ?>

<div class="content-wrapper">
    <section class="content-header">
        <h1><i class="fa fa-money"></i></h1>
    </section>
    <!-- Main content -->
    <section class="content">
        <?php $this->load->view('onlinecourse/report/_coursereport'); ?>
        <div class="row">
            <div class="col-md-12">
                <div class="box removeboxmius">
                    <div class="box-header ptbnull"></div>
                    <form id='feesforward' action="<?php echo site_url('onlinecourse/coursereport/exam_result_report_validation') ?>"  method="post"  accept-charset="utf-8">
                        <div class="box-header with-border">
                            <h3 class="box-title"><i class="fa fa-search"></i> <?php echo $this->lang->line('select_criteria'); ?></h3>
                            <div class="box-tools pull-right">
                            </div>
                        </div>
                        <div class="box-body">
                            <?php echo $this->customlib->getCSRF(); ?>
                            <div class="row">
                                <div class="col-md-12">
                                    <?php if ($this->session->flashdata('msg')) { ?>
                                    <?php echo $this->session->flashdata('msg');
                                    $this->session->unset_userdata('msg'); ?>
                                    <?php }?>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="exampleInputEmail1"><?php echo $this->lang->line('course') ?><small class="req"> *</small></label>
                                        <select  id="course_id" name="course_id" class="form-control select2"  onchange="get_exam_list(this.value,1)">
                                            <option value=""><?php echo $this->lang->line('select'); ?></option>
                                            <?php
                                            foreach ($course_list as $course_list_key => $course_list_value) { ?>
                                                <option value="<?php echo $course_list_value['id']; ?>"><?php echo $course_list_value['title']; ?></option>
                                            <?php $count++; } ?>
                                        </select>
                                       <span class="text-danger" id="error_course_id"></span>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="exampleInputEmail1"><?php echo $this->lang->line('exam'); ?></label><small class="req"> *</small>
                                        <select  id="exam_id" name="exam_id" class="form-control"></select>
                                       <span class="text-danger" id="error_exam_id"></span>
                                    </div>
                                </div>

                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <button type="submit" name="action" value ="search" class="btn btn-primary pull-right btn-sm"><i class="fa fa-search"></i> <?php echo $this->lang->line('search'); ?></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                            <div class="">
                                <div class="box-header ptbnull"></div>
                                <div class="box-header with-border">
                                    <h3 class="box-title titlefix"> <?php echo $this->lang->line('course_exam_result_report'); ?></h3>
                                </div>
                                <div class="box-body">
                                    <div class="download_label"><?php echo $this->lang->line('course_exam_result_report'); ?></div>
                                    <div class="table-responsive">
                                          <table class="table table-striped table-bordered table-hover record-list" data-export-title="<?php echo $this->lang->line('course_exam_result_report'); ?>">
                                            <thead>
                                                <tr>
                                                    <th><?php echo $this->lang->line('student'); ?>/<?php echo $this->lang->line('guest'); ?></th>
                                                    <th><?php echo $this->lang->line('total_attempt'); ?></th>
                                                    <th><?php echo $this->lang->line('remaining_attempt'); ?></th>
                                                    <th><?php echo $this->lang->line('exam_submitted'); ?></th>
                                                    <th class="text-right"><?php echo $this->lang->line('action') ?></th>
                                                </tr>
                                            </thead>
                                            <tbody id="set_data">
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</div>

<div id="myModal" class="modal fade" role="dialog">
    <div class="modal-dialog pup100">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><?php echo $this->lang->line('exam') ?></h4>
            </div>
            <div class="modal-body">
                <div class="result_exam" ></div>
            </div>
        </div>
    </div>
</div>


<script>

$(document).on('submit','#feesforward',function(e){
    e.preventDefault(); // avoid to execute the actual submit of the form.
    var $this       = $(this).find("button[type=submit]:focus");
    var form        = $(this);
    var url         = form.attr('action');
    var form_data   = form.serializeArray();
        $.ajax({
            url: url,
            type: "POST",
            dataType:'JSON',
            data: form_data, // serializes the form's elements.
            beforeSend: function () {
                $('[id^=error]').html("");
                $this.button('loading');
            },
            success: function(response) { // your success handler
                if(response.status=="fail"){
                    $.each(response.error, function(key, value) {
                    $('#error_' + key).html(value);
                    });
                }else{
                     initDatatable('record-list','onlinecourse/coursereport/get_result_report_data',response.param);
                }
            },
            error: function() { // your error handler
                $this.button('reset');
            },
            complete: function() {
                $this.button('reset');
            }
         });
    });

    $(document).on('click', '.student_result', function () {
        var $this = $(this);
        var examid = $this.data('examid');
        var student_or_guest_id = $this.data('student_or_guest_id');
        var type = $this.data('type');
        $.ajax({
                type: 'POST',
                url: baseurl + "onlinecourse/coursereport/exam_details",
                data: {'examid':examid,'student_or_guest_id':student_or_guest_id,'type':type},
                dataType: 'JSON',
                beforeSend: function () {
                    $this.button('loading');
                },
                success: function (data) {
                    if (data.status) {
                        $('.result_exam').html(data.page);
                        $('#myModal').modal('show');
                    }
                    $this.button('reset');
                },
                error: function (xhr) { // if error occured
                    alert("<?php echo $this->lang->line('error_occurred_please_try_again'); ?>");
                    $this.button('reset');
                },
                complete: function () {
                    $this.button('reset');
                }
        });
    });

    function get_exam_list(course_id,exam_id) {
        $('#exam_id').html("");
        var base_url = '<?php echo base_url() ?>';
        var div_data = '<option value=""><?php echo $this->lang->line('select'); ?></option>';
         $.ajax({
                type: "POST",
                url: base_url + "onlinecourse/coursereport/get_exam_list",
                data: {'course_id': course_id},
                dataType: "json",
                beforeSend: function () {
                    $('#exam_id').addClass('dropdownloading');
                },
                success: function (data) {
                    $.each(data.examList, function (i, obj)
                    {
                        var sel = "";
                        if (exam_id == obj.section_id) {
                            sel = "selected";
                        }
                        div_data += "<option value=" + obj.id + " " + sel + ">" + obj.exam + "</option>";
                    });
                    $('#exam_id').append(div_data);
                },
                complete: function () {
                    $('#exam_id').removeClass('dropdownloading');
                }
            });
    }
    
    $(document).ready(function () {
        $('.select2').select2();
    });

</script>


