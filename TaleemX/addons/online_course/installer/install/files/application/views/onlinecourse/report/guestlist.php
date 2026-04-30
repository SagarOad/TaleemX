<?php $this->load->view('layout/course_css.php'); ?>
<div class="content-wrapper">   
    
    <!-- Main content -->
    <section class="content">
        <?php $this->load->view('onlinecourse/report/_coursereport'); ?>
        <div class="row">
            <div class="col-md-12">
                <div class="box removeboxmius">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-search"></i> <?php echo $this->lang->line('guest_report'); ?></h3>
                    </div>
                    <div class="box-body"> 
                        <div class="table-responsive">
                            <table class="table table-switch table-striped table-bordered table-hover all-list" cellspacing="0" data-export-title="<?php echo $this->lang->line('guest_report'); ?>">
                                <thead>
                                    <tr>                                   
                                        <th class="noExport"><?php echo $this->lang->line('image'); ?></th>
                                        <th><?php echo $this->lang->line('name'); ?></th>
                                        <th><?php echo $this->lang->line('admission_no'); ?></th>
                                        <th><?php echo $this->lang->line('email'); ?></th>
                                        <th><?php echo $this->lang->line('mobile_number'); ?></th>
                                        <th><?php echo $this->lang->line('date_of_birth'); ?></th>
                                        <th><?php echo $this->lang->line('gender'); ?></th>
                                        <th><?php echo $this->lang->line('address'); ?></th>									 
                                        <th><?php echo $this->lang->line('action'); ?></th>
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
    </section>
</div>

<script>
( function ( $ ) {
    'use strict';
    $(document).ready(function () {
         
        initDatatable('all-list','onlinecourse/coursereport/getguestlist',[],[],100);
    });
} ( jQuery ) )
</script>
<script>
    function deleteguest(id){
        if(confirm('<?php echo $this->lang->line('are_you_sure'); ?>')){
            $.ajax({
                url: '<?php echo base_url() ?>onlinecourse/coursereport/delete',
                type:'post',
                data:{id:id},
                dataType: "json",
                success: function(response){                     
                    successMsg(response.msg);
                    $('.all-list').DataTable().ajax.reload();
                }
            })
        }
    }

    function changestatus(id, status) {
        
        if (status != 'yes') {
            if (confirm('<?php echo $this->lang->line("are_you_sure_you_active_account");?>')) {
                $.ajax({
                    type: "POST",
                    url: "<?php echo base_url() ?>onlinecourse/coursereport/changestatus",
                    data: {'id': id, 'status':status},
                    dataType: "json",
                    success: function (data) {
                        successMsg(data.msg);
                        $('.all-list').DataTable().ajax.reload();
                    }
                });
            }  
        } else if (confirm('<?php echo $this->lang->line("are_you_sure_you_deactive_account"); ?>')) {
            $.ajax({
                type: "POST",
                url: "<?php echo base_url() ?>onlinecourse/coursereport/changestatus",
                data: {'id': id, 'status':status},
                dataType: "json",
                success: function (data) {
                    successMsg(data.msg);
                    $('.all-list').DataTable().ajax.reload();
                }
            });
        } 
        
    }
</script>