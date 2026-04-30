<?php $this->load->view('layout/course_css.php'); ?>
<?php $currency_symbol = $this->customlib->getSchoolCurrencyFormat(); ?>
<html lang="en">
    <head>
        <title><?php echo $this->lang->line('fees_receipt'); ?></title>
        <link rel="stylesheet" href="<?php echo base_url(); ?>backend/bootstrap/css/bootstrap.min.css"> 
        <link rel="stylesheet" href="<?php echo base_url(); ?>backend/dist/css/AdminLTE.min.css">
        <link rel="stylesheet" href="<?php echo base_url(); ?>backend/dist/css/ss-print.css">
    </head>
    <body>       
        <div class="container"> 
            <div class="row">
                <div id="content" class="col-lg-12 col-sm-12 ">
                    <div class="invoice">
                        <div class="row header ">
                            <div class="col-sm-12">
                                <?php
                                ?>
                                <img  src="<?php echo $this->media_storage->getImageURL('/uploads/print_headerfooter/student_receipt/'.$this->setting_model->get_receiptheader());?>">
                                <?php
                                ?>
                            </div>
                        </div> 
                        <?php
                        if ($settinglist[0]['is_duplicate_fees_invoice']) {
                            ?>
                            <div class="row">
                                <div class="col-md-12 text text-center">
                                    <?php echo $this->lang->line('office_copy'); ?>
                                </div>
                            </div>
                            <?php
                        }
                        ?>
                        <div class="row">                           
                            <div class="col-xs-6 text-left">
                                <br/>
                                <address>
                                
                                <?php  if($role=='student'){ ?>  
                                    <strong><?php
                                    echo $courselist['firstname'].' '.$courselist['lastname'] . ' (' . $courselist['admission_no'] . ')';
                                      ?></strong><br>

                                    <?php echo $this->lang->line('father_name'); ?>: <?php echo $courselist['father_name']; ?><br> 
                                    <?php  if($role == 'student'){ echo $this->lang->line('class'); ?>: <?php echo $courselist['class'] . " (" . $courselist['section'] . ")"; } ?> 
                                <?php }else{ ?>
                                    <strong><?php
                                    echo $courselist['firstname'].'  (' . $courselist['admission_no'] . ')';
                                      ?></strong><br>                                    
                                <?php } ?>
                                    
                                </address>
                            </div>
                            <div class="col-xs-6 text-right">
                                <br/>
                                <address>
                                    <strong><?php echo $this->lang->line('date'); ?>: 
                                      <?php echo date($this->customlib->getSchoolDateFormat(), strtotime($courselist['date'])); ?>
                                    </strong><br/>

                                </address>                               
                            </div>
                        </div>
                        <hr style="margin-top: 0px;margin-bottom: 0px;" /> 

                        <div class="row">
                            <?php
                            if (!empty($courselist)) { ?>
                                <table class="table table-striped table-responsive font8pt">
                                    <thead>
                                    <th><?php echo $this->lang->line('course'); ?></th>
                                    <th><?php echo $this->lang->line('payment_type'); ?></th>
                                    <th><?php echo $this->lang->line('transaction_id'); ?></th>
                                    <th class="text-right"><?php echo $this->lang->line('price').' ('.$currency_symbol.')'; ?></th>
                                    </thead>
                                    <tbody>
                                            <tr>
                                                <td><?php echo $courselist['title']; ?></td>
                                                <td><?php echo $this->lang->line(strtolower($courselist['payment_type'])); ?></td>
                                                <td>
                                                    <?php 
                                                    if($courselist['payment_type'] == 'Online'){
                                                       echo $courselist['transaction_id']; 
                                                    }else{
                                                       echo $this->lang->line($courselist['payment_mode']);
                                                    }
                                                    ?>   
                                                    </td>
                                                <td class="text-right"><?php echo amountFormat($courselist['paid_amount']); ?></td>
                                            </tr>
                                    </tbody>
                                </table>
                                <?php } ?>
                        </div>
                    </div>
                </div>
                <div class="row header ">
                    <div class="col-sm-12">
                        <?php echo $this->setting_model->get_receiptfooter(); ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="clearfix"></div>
        <footer>           
        </footer>
    </body>
</html>