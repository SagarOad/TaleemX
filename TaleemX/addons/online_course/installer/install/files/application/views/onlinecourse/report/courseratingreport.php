<style type="text/css">
    @media print
    {
        .no-print, .no-print *
        {
            display: none !important;
        }
    }
</style>
<div class="content-wrapper">
    <section class="content">
        <?php $this->load->view('onlinecourse/report/_coursereport'); ?>
        <div class="row">
            <div class="col-md-12">
                <!-- general form elements -->
                <div class="box removeboxmius">
                    <div class="box-header ptbnull">
                        <h3 class="box-title titlefix"> <?php echo $this->lang->line('course_rating_report'); ?></h3>
                        <div class="box-tools pull-right">
                        </div><!-- /.box-tools -->
                    </div><!-- /.box-header -->
                    <div class="box-body">
                        <div class="table-responsive mailbox-messages">
                            <table class="table table-striped table-bordered table-hover course-rating-list" data-export-title="<?php echo $this->lang->line('course_rating_report'); ?>">
                                <thead>
                                    <tr>
                                        <th class="white-space-nowrap" style="width:30%"><?php echo $this->lang->line('title'); ?></th>
                                        <th class="white-space-nowrap" style="width:20%"><?php echo $this->lang->line('class'); ?></th>
                                        <th class="white-space-nowrap" style="width:20%"><?php echo $this->lang->line('rating'); ?></th>
                                        <th class="white-space-nowrap" style="width:20%"><?php echo  $this->lang->line('review_count'); ?></th>
                                        <th class="text-right noExport" style="width:10%"><?php echo $this->lang->line('action'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table><!-- /.table -->
                        </div><!-- /.mail-box-messages -->
                    </div><!-- /.box-body -->
                </div>
            </div>
        </div>
    </section>
</div>

<div class="modal fade" id="rating_detail_modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content modal-media-content">
        <div class="modal-header modal-media-header">
            <button type="button" class="close" data-dismiss="modal">&times;</button>
            <h4 class="box-title"> <?php echo $this->lang->line('rating_details'); ?>
            </h4>
        </div>
        <div class="modal-body pt0 pb0">
                <div id="ratingdetail"></div>
        </div>
      </div>
    </div>
</div>

<script>
  ( function ( $ ) {
    'use strict';
    $(document).ready(function() {
      emptyDatatable('course-rating-list','data');
    });

  } ( jQuery ) );
</script>
<script>
    ( function ( $ ) {
		'use strict';
		$(document).ready(function () {
			initDatatable('course-rating-list','onlinecourse/coursereport/dtgetcourserating',[],[],100);
		});
	} ( jQuery ) );
</script>
<script>
( function ( $ ) {
    'use strict';
    
    $(document).on('click','.detail_id',function(){
       var courseid = $(this).attr('data-id');
       $('#rating_detail_modal').modal({backdrop: 'static',
    keyboard: false});

       $.ajax({
        url: '<?php echo base_url(); ?>onlinecourse/coursereport/courseratingdetail',
        type: 'post',
        data: {courseid:courseid},
        dataType:'json',
        success: function(data){
           if (data.status == "success") {
            $('#ratingdetail').html(data.page);
          } 
        }

      });
    });

    $(document).on('click','.delete_review',function(){
        if(confirm('<?php echo $this->lang->line('delete_confirm'); ?>')){
            var ratingid = $(this).attr('data-id');
            $.ajax({
            url: '<?php echo base_url(); ?>onlinecourse/coursereport/deleterating',
            type: 'post',
            data: {ratingid:ratingid},
            dataType:'json',
            success: function(data){
               if (data.status == "fail") {
                    errorMsg(data.error);
                } else {
                    successMsg(data.message);
                    window.location.reload(true);
                }
            }
          });
        }
    });
} ( jQuery ) );
</script>