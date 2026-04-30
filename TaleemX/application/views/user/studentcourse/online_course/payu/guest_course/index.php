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
                            <form class="" action="<?php echo $action; ?>/_payment" method="post" id="payuForm" name="payuForm">  
                                <table class="table table-bordered table-hover mb0 paytable">
                                  <tr>
                                      <td width="40%" class="font-weight-bold">
                                        <?php echo $this->lang->line('course'); ?> 
                                      </td>
                                      <td width="40%" class="font-weight-bold text-right">
                                        <?php echo $this->lang->line('amount'); ?> 
                                      </td>
                                  </tr>
                                  <?php 
                                    $cartdata = $this->cart->contents();
                                    $cart_total = $total_processing_charge=0;
                                    foreach ($cartdata as  $value) {
                                      $cart_total += $value['price'];
                                      $total_processing_charge += $value['gateway_processing_charge'];
                                  ?>
                                    <tr> 
                                        <td width="40%">
                                          <?php echo $value['name']; ?>
                                        </td>
                                        <td class="text-right">
                                          <?php
                                            echo $currency_symbol;
                                            if(!empty($value['price'])){ echo amountFormat($value['price']); }else{echo '0.00';} ?> 
                                        </td>
                                    </tr> 
                                  <?php } ?>
									<tr>
                                        <th><?php echo $this->lang->line('processing_fees'); ?></th>
                                        <th class="text-right"><?php echo $currency_symbol.amountFormat($total_processing_charge); ?></th>
                                    </tr>
									
                                    <tr>
                                        <th ><?php echo $this->lang->line('total'); ?> </th>
                                        <th class="text-right"><?php echo $currency_symbol.($cart_total+$total_processing_charge); ?></th>
                                    </tr>                                 
                                
                                   
                                    <tr>
                                      <td class="font-weight-bold">
                                        <label><?php //echo $this->lang->line('email'); ?></label>
                                      </td> 
                                  <input type="hidden" name="key" value="<?php echo $mkey ?>" />
                                    <input type="hidden" name="hash" value="<?php echo $hash ?>"/>
                                    <input type="hidden" name="txnid" value="<?php echo $tid ?>" />


                                    <input class="form-control" type="hidden" name="firstname" id="firstname" value="<?php echo $session_data[0]['guest_name']; ?>" readonly/>

                                     <input class="form-control" type="hidden" name="email" id="email" value="<?php echo $session_data[0]['email']; ?>" readonly/>

                                    <input class="form-control"  type="text" name="phone" value="<?php echo $session_data[0]['contact_no']; ?>" /> 


                                    <input type="hidden" name="amount" value="<?php echo amountFormat($cart_total+$total_processing_charge); ?>">

                                   <textarea class="form-control displaynone" name="productinfo" readonly><?php echo $productinfo ?></textarea> 

                                    <input name="surl" value="<?php echo $sucess ?>" size="64" type="hidden" />
                                    <input name="furl" value="<?php echo $failure ?>" size="64" type="hidden" />                             
                                    <input type="hidden" name="service_provider" value="payu_paisa" size="64" />
                                    <input name="curl" value="<?php echo $cancel ?> " type="hidden" />

                                    <tr class="paybtngray">
                                       <td><button type="button" onclick="window.history.go(-1); return false;" name="search" value="" class="btn paybackbtn"><i class="fa fa-chevron-left valign-middle"></i> <?php echo $this->lang->line('back')?></button></td>

                                     <td class="text-right"><button type="button" class="btn btn-info pull-right submit_button buttondarkgray" ><?php echo $this->lang->line('pay_with_payu')?> <i class="fa fa-chevron-right valign-middle"></i></button></td>
                                     </tr>              
                                </table>
                                <script src="<?php echo base_url(); ?>backend/custom/jquery.min.js"></script>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>  
            </div>
        </div>
    </body>
</html>
<script type="text/javascript">
(function ($) {
  "use strict";

  $(document).ready(function () {
      $(".submit_button").click(function (e) {
e.preventDefault();
          var url = "<?php echo site_url('students/online_course/payu/guestcheckout') ?>";
          $.ajax({
              type: "POST",
              url: url,
              data: $("#payuForm").serialize(),
              dataType: "json",
              success: function (response)
              {
               var decode = JSON.stringify(response);
			   console.log(response);
                  if (response.status == "success") {
                      $('form#payuForm').submit();
                  } else if (response.status == "fail") {
                      $.each(response.error, function (index, value) {
                          var errorDiv = '.' + index + '_error';
                          $(errorDiv).empty().append(value);
                      });
                  }
              }
          });
          //
      });
  });
})(jQuery);
</script>