<?php 
$this->load->view('layout/course_css.php'); 
$currency_symbol = $this->customlib->getSchoolCurrencyFormat();
?>
<div class="content-wrapper">
    <!-- Main content -->
    <section class="content">
        <?php $this->load->view('onlinecourse/report/_report'); ?>
        <div class="row">
        <!-- left column -->
            <div class="col-md-12">
                <div class="box removeboxmius">
                    <div class="box-header ptbnull"></div>
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-search"></i> <?php echo $this->lang->line('student_course_purchase_report'); ?></h3>
                    </div>
                    <form id="form1" action="<?php echo base_url(); ?>onlinecourse/checkvalidation"  method="post">
                        <div class="box-body">
                        <?php echo $this->customlib->getCSRF(); ?>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><?php echo $this->lang->line('search_type') ; ?><small class="req"> *</small></label>
                                        <select class="form-control" id="search_type" name="search_type" onchange="showdate(this.value)">
                                        <?php foreach ($searchlist as $key => $search) {
                                            ?>
                                            <option value="<?php echo $key ?>" <?php
                                            if ((isset($search_type)) && ($search_type == $key)) {
                                                echo "selected";
                                            }
                                            ?>><?php echo $search ?></option>
                                        <?php } ?>
                                        </select>
                                        <span class="text-danger"><?php echo form_error('search_type'); ?></span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                       <label><?php echo $this->lang->line('payment_type'); ?><small class="req"> *</small></label>
                                        <select class="form-control" name="payment_type">										
                                        <?php foreach ($payment_type as $key => $payment_type_value) {
                                            ?>
                                            <option value="<?php echo $key ?>" <?php
                                            if ((isset($payment_type)) && ($payment_type == $key)) {
                                                echo "selected";
                                            }
                                            ?>><?php echo $payment_type_value ?></option>
                                        <?php } ?>
                                        </select>
                                        <span class="text-danger"><?php echo form_error('payment_type'); ?></span>
                                    </div>
                                </div>
                                <div id='date_result'></div>
                                <div class="form-group">
                                    <div class="col-sm-12">
                                        <button type="submit" name="search" value="search_filter" class="btn btn-primary btn-sm pull-right"><i class="fa fa-search"></i> <?php echo $this->lang->line('search'); ?></button>
                                    </div>
                                </div>                          
                            </div>
                        </div> 
                    </form>
            <div class="box-body">
                <div class="row">
                    <div class="download_label"><?php echo $this->lang->line('student_course_purchase_report'); ?></div>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered table-hover all-list" cellspacing="0" data-export-title="<?php echo $this->lang->line('student_course_purchase_report'); ?>">
                                <thead>
                                    <tr>
                                        <th><?php echo $this->lang->line('student'); ?></th>
                                        <th><?php echo $this->lang->line('admission_no'); ?></th>
                                        <th><?php echo $this->lang->line('date'); ?></th>
                                        <th><?php echo $this->lang->line('course'); ?></th>
                                        <th><?php echo $this->lang->line('course_provider'); ?></th>
                                        <th><?php echo $this->lang->line('payment_type'); ?></th>
                                        <th><?php echo $this->lang->line('payment_method'); ?></th>
                                        <th><?php echo $this->lang->line('price').' ('.$currency_symbol.')'; ?></th>
                                    </tr>
                                </thead>
                                    <tbody>
                                    </tbody>
                            </table>
                       </div>    
                    </div>
                </div>  
                </div>
            </div>
        </div>
        <!-- /.row -->
    </section>
    <!-- /.content -->
    <div class="clearfix"></div>
</div>
<script>
( function ( $ ) {
    'use strict';

    $(document).ready(function () {
       $('#form1').on('submit', (function (e) {
        e.preventDefault();
        var search= 'search_filter';
        var formData = new FormData(this);
        formData.append('search', 'search_filter');

        $.ajax({
            url: '<?php echo base_url(); ?>onlinecourse/coursereport/checkvalidation',
            type: "POST",
            data: formData,
            dataType: 'json',
            contentType: false,
            cache: false,
            processData: false,
            success: function (data) {
                if (data.status == "fail") {
                    var message = "";
                    $.each(data.error, function (index, value) {
                        message += value;
                    });
                    toastr.error(message);
                } else { 
                    initDatatable('all-list', 'onlinecourse/coursereport/coursereport/',data.param);
                }
            }
        });
        }
       ));
   });
} ( jQuery ) );
</script>

<script>
( function ( $ ) {
    'use strict';
    var search_type = $('#search_type').val();
    if (search_type == 'period') {
        $(document).ready(function () {
            showdate('period');
        });
     }
 } ( jQuery ) );
</script>