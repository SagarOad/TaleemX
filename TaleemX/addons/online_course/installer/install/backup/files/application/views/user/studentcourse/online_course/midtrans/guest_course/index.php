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
        <link href="<?php echo base_url(); ?>uploads/school_content/admin_small_logo/<?php $this->setting_model->getAdminsmalllogo();?>" rel="shortcut icon" type="image/x-icon">
        <link rel="stylesheet" href="<?php echo base_url(); ?>backend/bootstrap/css/bootstrap.min.css"> 
        <link rel="stylesheet" href="<?php echo base_url(); ?>backend/dist/css/font-awesome.min.css"> 
        <link rel="stylesheet" href="<?php echo base_url(); ?>backend/dist/css/style-main.css">
        <script type="text/javascript"
                src="https://app.midtrans.com/snap/snap.js"
        data-client-key="SB-Mid-client-2uDtZD3V5ZA_pNYW"></script> 
        <script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
		<link rel="stylesheet" href="<?php echo base_url('theme.css'); ?>">
    </head>
    <body class="bg-light-gray">
        <div class="container">
           <form id="payment-form" method="post" action="<?= site_url() ?>/admin/admin/response">
                <input type="hidden" name="result_type" id="result-type" value="">
                <input type="hidden" name="result_data" id="result-data" value="">
            </form>
            <div class="row">
                <div class="paddtop20">
                    <div class="col-md-8 col-md-offset-2 text-center">
                        <img src="<?php echo base_url('uploads/school_content/logo/' . $setting[0]['image']); ?>">
                    </div>
                    <div class="col-md-6 col-md-offset-3 mt20">
                        <div class="paymentbg pb0 paymentbg-width">
                            <div class="invtext"><?php echo $this->lang->line('course_purchase_details'); ?></div>
                            <div class="">
                              <form action="#" method="post">
                                 
                                <table class="table table-bordered table-hover mb0 paytable">
                                    <tr>
                                      <td width="40%" class="font-weight-bold">
                                          <?php echo $this->lang->line('title'); ?> 
                                        </td>
                                      <td class="font-weight-bold text-right">
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
                                        <th><?php echo $this->lang->line('total'); ?></th>
                                        <td class="text-right"><?php echo $currency_symbol.amountFormat($cart_total+$total_processing_charge); ?></td>
                                    </tr>
                                    <tr class="paybtngray">
                                      <td><button type="button" onclick="window.history.go(-1); return false;" name="search" value="" class="btn paybackbtn"><i class="fa fa-chevron-left valign-middle"></i> <?php echo $this->lang->line('back'); ?> </button></td>
                                      <td class="text-right"> <button type="button" name="search" id="pay-button" value="" class="btn btn-info buttondarkgray"><?php echo $this->lang->line('pay_with_midtrans'); ?> <i class="fa fa-chevron-right valign-middle"></i></button></td>
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
<script type="text/javascript">
function changeResult(type, data) {
    $("#result-type").val(type);
    $("#result-data").val(JSON.stringify(data));
}

(function ($) {
  "use strict";

  var resultType = document.getElementById('result-type');
  var resultData = document.getElementById('result-data');

  var payButton = document.getElementById('pay-button');
  payButton.addEventListener('click', function () {
      snap.pay('<?php echo $snap_Token; ?>', {// store your snap token here
          onSuccess: function (result) {
             
              changeResult('success', result);
             document.getElementById("pay-button").disabled = true;
              $.ajax({
                  url: '<?php echo base_url(); ?>students/online_course/midtrans/guestmidtranspay',
                  type: 'POST',
                  data: $('#payment-form').serialize(),
                  dataType: "json",
                  success: function (msg) {

                    window.location.replace("<?php echo base_url(); ?>students/online_course/midtrans/success");

                  } 
              });
          },
          onPending: function (result) {
              console.log('pending');
              console.log(result);
              
          },
          onError: function (result) {
              console.log('error');
              console.log(result);
          },
          onClose: function () {
              console.log('customer closed the popup without finishing the payment');
          }
      })
  });
})(jQuery);
</script>