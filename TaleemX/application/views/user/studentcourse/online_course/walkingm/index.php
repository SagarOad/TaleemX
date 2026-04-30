<?php
$currency_symbol = $this->session->userdata('student')['currency_symbol'];
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#424242" />
        <title><?php echo $this->customlib->getSchoolName(); ?></title>
        <link rel="icon" type="image/svg+xml" href="<?php echo base_url('backend/images/front_theme/TaalimX%20Favicon.svg'); ?>">
        <link rel="stylesheet" href="<?php echo base_url(); ?>backend/bootstrap/css/bootstrap.min.css"> 
        <link rel="stylesheet" href="<?php echo base_url(); ?>backend/dist/css/font-awesome.min.css"> 
        <link rel="stylesheet" href="<?php echo base_url(); ?>backend/dist/css/style-main.css">
        <script src="<?php echo base_url(); ?>backend/custom/jquery.min.js"></script>
		<link rel="stylesheet" href="<?php echo base_url('theme.css'); ?>">
    </head>
    <body class="bg-light-gray">
        <div class="container">
            <div class="row"> 
                <div class="paddtop20">
                    <div class="col-md-8 col-md-offset-2 text-center">
                        <img src="<?php echo base_url('uploads/school_content/logo/' . $setting[0]['image']); ?>">
                    </div>
                    <div class="col-md-6 col-md-offset-3 mt20">
                        <div class="paymentbg pb0 paymentbg-width">
                            <div class="invtext"><?php echo $this->lang->line('course_purchase_details'); ?></div>
                            <div class="">
                              <form action="<?php echo base_url(); ?>students/online_course/walkingm/pay" method="post">
                                <div class="img-container">
                                 <img src="<?php echo base_url(); ?>/uploads/course/course_thumbnail/<?php echo $params['course_thumbnail']; ?>" class="img-responsive center-block">
                                </div> 
                                <table class="table table-bordered table-hover mb0 paytable">
                                  <tr>
                                    <td width="40%" class="font-weight-bold">
                                      <?php echo $this->lang->line('title'); ?> 
                                    </td>
                                    <td width="40%">
                                      <?php echo $params['course_name']; ?>
                                    </td>
                                  </tr>
                                   <tr>
                                    <td class="font-weight-bold">
                                      <?php echo $this->lang->line('description'); ?>
                                    </td>
                                    <td>
                                        <div id="less_desc" class=''>
                                            <?php echo implode(' ', array_slice(explode(' ', $params['description']), 0, 10))."\n"; ?>          
                                        </div>
                                        <div class="hide" id="more_desc">
                                            <?php echo $params['description']; ?>
                                        </div>                                      
                                        <?php if (strlen($params['description']) > 350) {?>
                                            <a id="hideid" class="btnplusview" ><i class="fa fa-angle-down angle-fa"></i> <?php echo $this->lang->line('view_more'); ?></a>
                                            
                                            <a id="showid" class="btnplusview hide" ><i class="fa fa-angle-up angle-fa"></i> <?php echo $this->lang->line('view_less'); ?></a>
                                        <?php }?>                                       
                                    </td>
                                  </tr>
								  <tr>
                                    <td class="font-weight-bold">
                                      <?php echo $this->lang->line('processing_fees'); ?> 
                                    </td>
                                    <td>
                                       <?php
                                        echo $currency_symbol;
                                        if(!empty($params['gateway_processing_charge'])){ echo amountFormat($params['gateway_processing_charge']);}else{echo '0.00';} ?>
                                    </td>
                                  </tr> 
                                  <tr>
                                    <td class="font-weight-bold">
                                      <?php echo $this->lang->line('amount'); ?> 
                                    </td>
                                    <td>
                                       <?php
                                         echo $currency_symbol;
                                        if(!empty($params['total_amount'])){ echo amountFormat($params['total_amount']);}else{echo '0.00';} ?>
                                    </td>
                                  </tr>          
                                   
                                     <tr class="bordertoplightgray">
                                            <td  bgcolor="#fff"><?php echo $this->lang->line('walkingm_email'); ?>:</td>
                                            <td  bgcolor="#fff" class="text-right"> <input type="text" class="form-control" name="email" value="<?php echo set_value('email'); ?>"> <span class="alert-danger"> <?php echo form_error('email');?></span></td>
                                        </tr>
                                        <tr class="bordertoplightgray">
                                            <td  bgcolor="#fff"><?php echo $this->lang->line('walkingm_password'); ?>:</td>
                                            <td  bgcolor="#fff" class="text-right"> <input type="password" class="form-control"  name="password" value="<?php echo set_value('password'); ?>" /> <span class="alert-danger"> <?php echo form_error('password');?></span></td>
                                        </tr>
                                        <tr class="bordertoplightgray">
                                            <td class="text-right" bgcolor="#fff" colspan="2"><span class="alert-danger"> <?php if(!empty($api_error)){ echo print_r($api_error); } ?></span>
                                            </td>
                                        </tr>
                                    <tr class="paybtngray">
                                      <td>
                                        <button type="button" onclick="window.history.go(-1); return false;" name="search"  value="" class="btn paybackbtn"><i class="fa fa-chevron-left valign-middle"></i> <?php echo $this->lang->line('back'); ?> </button>
                                      </td>
                                      <td class="text-right">
                                        <button type="submit"  name="search"  value="" class="btn btn-info buttondarkgray"><?php echo $this->lang->line('pay_with_walkingm'); ?> <i class="fa fa-chevron-right valign-middle"></i></button>
                                      </td>
                                  </tr>    
                                </table>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>  
            </div>
        </div>
    </body>
</html>
<script>
(function ($) {
  "use strict";
    $("#hideid").click(function(){
        $("#hideid").addClass('hide');
        $("#less_desc").addClass('hide');
        $("#showid").removeClass('hide');
        $("#more_desc").removeClass('hide');
    });

    $("#showid").click(function(){
        $("#hideid").removeClass('hide');
        $("#less_desc").removeClass('hide');
        $("#showid").addClass('hide');
        $("#more_desc").addClass('hide');
    });
})(jQuery);
</script>