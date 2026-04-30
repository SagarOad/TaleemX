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
                              <div class="img-container">
                               <img src="<?php echo base_url(); ?>/uploads/course/course_thumbnail/<?php echo $session_data['course_thumbnail']; ?>" class="img-responsive center-block">
                              </div> 
                                <table class="table table-bordered table-hover mb0 paytable">
                                  <tr>
                                    <td width="40%" class="font-weight-bold">
                                      <?php echo $this->lang->line('title'); ?> 
                                    </td>
                                    <td width="40%">
                                      <?php echo $session_data['course_name']; ?>
                                    </td>
                                  </tr>
                                   <tr>
                                    <td class="font-weight-bold">
                                      <?php echo $this->lang->line('description'); ?>
                                    </td>
                                    <td>
										<div id="less_desc" class=''>
											<?php echo implode(' ', array_slice(explode(' ', $session_data['description']), 0, 10))."\n"; ?>			
										</div>
										<div class="hide" id="more_desc">
											<?php echo $session_data['description']; ?>
										</div>										
										<?php if (strlen($session_data['description']) > 350) {?>
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
                                        if(!empty($session_data['gateway_processing_charge'])){ echo amountFormat($session_data['gateway_processing_charge']);}else{ echo '0.00';} ?>
                                    </td>
                                  </tr>
								  
                                  <tr>
                                    <td class="font-weight-bold">
                                      <?php echo $this->lang->line('amount'); ?> 
                                    </td>
                                    <td>
                                       <?php
                                        echo $currency_symbol;
                                        if(!empty($session_data['total_amount'])){ echo amountFormat($session_data['total_amount']);}else{ echo '0.00';} ?>
                                    </td>
                                  </tr>   
                                  <input type="hidden" name="key" value="<?php echo $mkey ?>" />
                                    <input type="hidden" name="hash" value="<?php echo $hash ?>"/>
                                    <input type="hidden" name="txnid" value="<?php echo $tid ?>" />

                                    <input class="form-control" type="hidden" name="amount" value="<?php if(!empty($amount)){ echo $amount;} ?>"  readonly/>

                                    <input class="form-control" type="hidden" name="firstname" id="firstname" value="<?php echo $session_data['name']; ?>" readonly/>

                                    <input class="form-control" type="hidden" name="email" id="email" value="<?php echo $session_data['email']; ?>" readonly/>

                                    <input class="form-control"  type="hidden" name="phone" value="<?php echo $session_data['contact_no']; ?>" readonly />

                                    <textarea class="form-control displaynone" name="productinfo" readonly><?php echo $productinfo ?></textarea>

                                    <input name="surl" value="<?php echo $sucess ?>" size="64" type="hidden" />
                                    <input name="furl" value="<?php echo $failure ?>" size="64" type="hidden" />                             
                                    <input type="hidden" name="service_provider" value="payu_paisa" size="64" />
                                    <input name="curl" value="<?php echo $cancel ?> " type="hidden" />

                                    <tr class="paybtngray">
                                       <td><button type="button" onclick="window.history.go(-1); return false;" name="search" value="" class="btn paybackbtn"><i class="fa fa-chevron-left valign-middle"></i> <?php echo $this->lang->line('back')?></button></td>

                                     <td><button type="button" class="btn btn-info pull-right submit_button buttondarkgray" ><?php echo $this->lang->line('pay_with_payu')?> <i class="fa fa-chevron-right valign-middle"></i></button></td>
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
          var url = "<?php echo site_url('students/online_course/payu/checkout') ?>";
          $.ajax({
              type: "POST",
              url: url,
              data: $("#payuForm").serialize(),
              dataType: "Json",
              success: function (response)
              {
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
          e.preventDefault();
      });
  });
})(jQuery);
</script>
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