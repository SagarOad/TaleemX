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
        <script type="text/javascript"
                src="https://app.midtrans.com/snap/snap.js"
        data-client-key="SB-Mid-client-2uDtZD3V5ZA_pNYW"></script> 
        <script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
		<link rel="stylesheet" href="<?php echo base_url('theme.css'); ?>">
    </head>
    <body class="bg-light-gray">
        <div class="container">
           <form id="payment-form" method="post" action="#">
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
								  <tr>
                                    <td class="font-weight-bold" colspan='2'>
                                     <div class="alert alert-danger alert-dismissible"><?php echo $error;?></div>
                                    </td>
                                    
                                  </tr> 
                                    <tr class="paybtngray">
                                      <td><button type="button" onclick="window.history.go(-1); return false;" name="search" value="" class="btn paybackbtn"><i class="fa fa-chevron-left valign-middle"></i> <?php echo $this->lang->line('back'); ?> </button></td>
                                      <td> <button type="button" name="search" id="pay-button" value="" class="btn btn-info buttondarkgray"><?php echo $this->lang->line('pay_with_midtrans'); ?> <i class="fa fa-chevron-right valign-middle"></i></button></td>
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
                  url: '<?php echo base_url(); ?>students/online_course/midtrans/midtrans_pay',
                  type: 'POST',
                  data: $('#payment-form').serialize(),
                  dataType: "json",
                  success: function (msg) {
                      window.location.href = "<?php echo base_url(); ?>students/online_course/midtrans/success";
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