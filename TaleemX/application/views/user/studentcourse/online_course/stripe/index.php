<?php
$currency_symbol = $params['currency_symbol'];

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
		<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.0.0/jquery.min.js"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.2.0/js/bootstrap.min.js"></script>

		<link href="<?php echo base_url(); ?>backend/toast-alert/toastr.css" rel="stylesheet"/>
		<script src="<?php echo base_url(); ?>backend/toast-alert/toastr.js"></script>
		<script src="<?php echo base_url(); ?>backend/js/sstoast.js"></script>
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
                                      
                                </table>   
                           

                               <div class="divider"></div>

                            <div id="stripe-payment-message" class="hidden"></div>

                            <form id="stripe-payment-form" class="paddtlrb" action="<?php echo site_url('students/online_course/stripe/complete'); ?>" method="POST">                             


                                <input type='hidden' id='baseurl' value='<?php echo site_url(); ?>'>
                                <input type='hidden' id='publishable_key' value='<?php echo $params['api_publishable_key']; ?>'>
                                <input type='hidden' id='currency' value='<?php echo $this->currency_name; ?>'>
                                <input type='hidden' id='description' value='<?php echo 'Online Course Fees'; ?>'>

                                <input type="hidden" name="student_id" value="<?php echo $student_data['id']; ?>">
                                <input type="hidden" name="total" id="amount" value="<?php echo convertBaseAmountCurrencyFormat($params['total_amount'] * 100); ?>">
                                  
                                        <div id="stripe-payment-element">
                                            <!--Stripe.js will inject the Payment Element here to get card details-->
                                        </div>
                                        <div class="button-between">
                                            <button type="button" onclick="window.history.go(-1); return false;" name="search" value="" class="btn paybackbtn"><i class="fa fa fa-chevron-left"></i> <?php echo $this->lang->line('back') ?></button>
                                            <button type="submit" class="pay btn btn-primary pull-right" id="submit-button"  data-loading-text="<i class='fa fa-circle-o-notch fa-spin'></i> Processing"> <i class="fa fa-money"></i> Pay Now </button>
                                            <div id="payment-reinitiate" class="hidden" >
                                <button class="btn btn-primary" type="button" onclick="reinitiateStripe()"> <i class="fa fa-money"></i>  Reinitiate Payment</button>
                            </div>
                                        </div>
                                     
                                </div>    

                            </form>
                            </div>
                        </div>
                    </div>
                </div>  
            </div>
        </div>
    </body>
</html>

<script src="https://js.stripe.com/v3/"></script>
 <script src="<?php echo base_url('backend/js/stripe-checkout-course.js') ?>" defer></script>