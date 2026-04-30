<?php
$currency_symbol = $this->customlib->getSchoolCurrencyFormat();
?>
<div class="content-wrapper">
    <!-- Main content -->
    <section class="content">         
        <div class="row">
        <!-- left column -->
            <div class="col-md-12">                
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"> <?php echo $this->lang->line('purchase_history'); ?> </h3>                         
                    </div>                 
                    <div class="box-body">
                        <div class="table-responsive">                  
                            <table class="table table-striped table-bordered table-hover all-list" cellspacing="0" data-export-title="<?php echo $this->lang->line('purchase_history'); ?> ">
                                <thead>
                                    <tr>                                 
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
        <!-- /.row -->
    </section>
    <!-- /.content -->    
</div>

<script>
( function ( $ ) {
    'use strict';
    $(document).ready(function () {         
        initDatatable('all-list','user/studentcourse/guestpurchasehistory',[],[],100);
    });
} ( jQuery ) )
</script>