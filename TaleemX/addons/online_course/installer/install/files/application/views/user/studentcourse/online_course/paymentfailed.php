<div class="content-wrapper" style="min-height: 1126px;">
    <section class="content-header">
        <h1>
            <i class="fa fa-money"></i> <?php echo $this->lang->line('payment')?>
        </h1>
    </section>

    <!-- Main content -->
    <section class="invoice">

        <div class="row">
            <div class="alert alert-warning alert-dismissible">

                <h4><i class="icon fa fa-warning"></i><?php echo $this->lang->line('cancelled')?></h4>
                <?php echo $this->lang->line('you_have_cancelled_this_payment')?>
            </div>       
        </div>      
    </section>    
    <div class="clearfix"></div>
</div>