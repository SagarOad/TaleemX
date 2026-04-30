<?php
$currency_symbol = $this->customlib->getSchoolCurrencyFormat();
?>
<div class="content-wrapper">
    <section class="content">
        <?php $this->load->view('onlinecourse/report/_coursereport'); ?>
        <div class="row">
            <div class="col-md-12">
                <div class="box removeboxmius">
                    <div class="box-header ptbnull"></div>
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-search"></i> <?php echo $this->lang->line('select_criteria'); ?></h3>
                    </div>
                    <form role="form" action="<?php echo site_url('onlinecourse/coursereport/get_exam_form_parameter') ?>" method="post" class="" id="report_form" >
                        <div class="box-body row">
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
                                <div class="col-md-4 hide">
                                    <div class="form-group">
                                        <label for="exampleInputEmail1"><?php echo $this->lang->line('exam'); ?></label><small class="req"> *</small>
                                        <select  id="exam_id" name="exam_id" class="form-control">
                                          <option value=""><?php echo $this->lang->line('select'); ?></option>
                                        </select>
                                       <span class="text-danger" id="error_exam_id"></span>
                                    </div>
                                </div>
                            <div class="form-group">
                                <div class="col-sm-12">
                                    <button type="submit" name="search" value="search_filter" class="btn btn-primary btn-sm checkbox-toggle pull-right"><i class="fa fa-search"></i> <?php echo $this->lang->line('search'); ?></button>
                                </div>
                            </div>
                        </div>
                    </form>
                    <div class="">
                        <div class="box-header ptbnull"></div>
                        <div class="box-header ptbnull">
                            <h3 class="box-title titlefix"><i class="fa fa-money"></i> <?php echo $this->lang->line('course_exam_report'); ?></h3>
                        </div>
                        <div class="box-body table-responsive">
                            <div class="download_label"><?php echo $this->lang->line('course_exam_report'); ?></div>
                             <table class="table table-striped table-bordered table-hover record-list" data-export-title="<?php echo $this->lang->line('course_exam_report'); ?>">
                                <thead>
                                    <tr>
                                        <th><?php echo $this->lang->line('course') ?></th>
                                        <th><?php echo $this->lang->line('exam') ?></th>
                                        <th width="7%"><?php echo $this->lang->line('attempt') ?></th>
                                        <th width="7%"><?php echo $this->lang->line('exam_from') ?></th>
                                        <th width="7%"><?php echo $this->lang->line('exam_to'); ?></th>
                                        <th width="7%"><?php echo $this->lang->line('duration') ?></th>
                                        <th width="7%"><?php echo $this->lang->line('total_students');?></th>
                                        <th width="7%"><?php echo $this->lang->line('total_guest'); ?></th>
                                        <th width="7%"><?php echo $this->lang->line('total_student_guest'); ?></th>
                                        <th width="7%"><?php echo $this->lang->line('questions') ?></th>
                                        <th width="7%"><?php echo $this->lang->line('exam_published'); ?></th>
                                        <th width="7%"><?php echo $this->lang->line('result_published'); ?></th>
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
</section>
</div>
<script>
<?php
if ($search_type == 'period') { ?>
        $(document).ready(function () {
            showdate('period');
        });
<?php } ?>
</script>
<script>
    $(document).ready(function () {
        $('.select2').select2();
    });
$(document).ready(function() {
     emptyDatatable('record-list','data');
});
</script>

<script type="text/javascript">
$(document).ready(function(){ 
$(document).on('submit','#report_form',function(e){
    e.preventDefault(); // avoid to execute the actual submit of the form.
    var $this = $(this).find("button[type=submit]:focus");  
    var form = $(this);
    var url = form.attr('action');
    var form_data = form.serializeArray();
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
                if(response.status=='fail'){
                    $.each(response.error, function(key, value) {
                        $('#error_' + key).html(value);
                    });
                }else{
                    initDatatable('record-list','onlinecourse/coursereport/get_exam_report_data',response.params);
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
</script>