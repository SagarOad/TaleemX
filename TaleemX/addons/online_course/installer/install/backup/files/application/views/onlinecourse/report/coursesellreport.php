<?php $this->load->view('layout/course_css.php'); ?>
<div class="content-wrapper">
    <!-- Main content -->
    <section class="content">
        <?php $this->load->view('onlinecourse/report/_coursereport'); ?>
        <div class="row">
        <!-- left column -->
            <div class="col-md-12">
                <div class="box removeboxmius">
                    <div class="box-header ptbnull"></div>
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-search"></i> <?php echo $this->lang->line('course_sell_count_report'); ?>
                        </h3>
                    </div>
            <div class="box-body">
                <div class="row">
                    <div class="table-responsive overflow-visible">
                        <table class="table table-striped table-bordered table-hover course-sell-list" data-export-title="<?php echo $this->lang->line('course_sell_count_report'); ?>">
                            <thead>
                                <tr>
                                    <th><?php echo $this->lang->line('course'); ?></th>
                                    <th><?php echo $this->lang->line('class'); ?></th>
                                    <th><?php echo $this->lang->line('section'); ?></th>
                                    <th><?php echo $this->lang->line('sale_count'); ?></th>
                                    <th><?php echo $this->lang->line('assign_teacher'); ?></th>
                                    <th><?php echo $this->lang->line('created_by'); ?></th>
                                    <th class="noExport"><?php echo $this->lang->line('action'); ?></th>
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

 <div class="modal fade" id="sale_modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content modal-media-content">
            <div class="modal-header modal-media-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="box-title"><span id="coursename_model"></span></h4> 
            </div>
            <div class="scroll-area">
            <div class="modal-body pt0 pb0">
                <div class="row">
                    <div class="col-lg-12 col-md-12 col-sm-12" id="">
                         <div class="pb10 ptt10">
                            <div class="download_label"><?php echo $this->lang->line('course_sell_report'); ?></div>
                            
                                <div class="table-responsive mailbox-messages" id="sale_data"> 
                                 
                                </div>  
                            
                        </div><!-- /.box-body -->                        
                    </div><!--./col-md-12-->       
                </div><!--./row--> 
            </div>
            </div>
        </div>
    </div>    
</div>

<script>
(function ($) {
  "use strict";

  $('.sale_btn').click(function () {
    $('#sale_data').html('');
        var courseid = $(this).attr('course-data-id');
        var coursename = $(this).attr('data-id');
        $.ajax({
            url: '<?php echo base_url(); ?>onlinecourse/coursereport/saledata',
            type: 'post',
            data: {courseid: courseid,coursename:coursename},
            success: function(data){
               $('#sale_data').html(data);
            }
        });
    });
})(jQuery);
</script>

<script>
    function loadcoursedetail(courseid,coursename){
        $('#sale_data').html('');
        $('#coursename_model').html('');
        
        $.ajax({
            url: '<?php echo base_url(); ?>onlinecourse/coursereport/saledata',
            type: 'post',
            data: {courseid: courseid,coursename:coursename},
            success: function(data){
               $('#sale_data').html(data);
               $('#coursename_model').html(coursename);
            }
        });
    }
</script>

<script>
$(document).ready(function() {
     emptyDatatable('course-sell-list','data');
});
</script>
 
<script>
( function ( $ ) {
    'use strict';
    $(document).ready(function () {
        initDatatable('course-sell-list','onlinecourse/coursereport/getsellreport',[],[],100);
    });
} ( jQuery ) )
</script>