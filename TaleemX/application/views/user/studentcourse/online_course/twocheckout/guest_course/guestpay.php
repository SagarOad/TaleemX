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
                              <form action="<?php echo base_url(); ?>students/online_course/twocheckout/guestpay" method="post">
                                
                                <table class="table table-bordered table-hover mb0 paytable">
                                  <?php 
                                    $cartdata = $this->cart->contents();
                                    $cart_total = 0;
                                    foreach ($cartdata as  $value) {
                                        
                                        $cart_total += $value['price'];

                                        ?>
                                    <tr>
                                        <td width="40%" class="font-weight-bold">
                                          <?php echo $this->lang->line('title'); ?> 
                                        </td>
                                        <td width="40%">
                                          <?php echo $value['name']; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="font-weight-bold">
                                          <?php echo $this->lang->line('amount'); ?> 
                                        </td>
                                        <td>
                                           <?php
                                            echo $currency_symbol;
                                            if(!empty($value['price'])){ echo number_format((float)$value['price'], 2, '.', ''); }else{echo '0.00';} ?>
                                        </td>
                                    </tr> 
                                  <?php } ?>

                                    <tr>
                                        <td class="font-weight-bold">
                                            <label><?php echo $this->lang->line('phone'); ?></label>
                                        </td>
                                        <td>
                                            <input type="text" name="phone" class="form-control" value="<?php echo set_value('phone',$params[0]['contact_no']); ?>"> <span class="alert-danger"><?php echo form_error('phone'); ?></span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="font-weight-bold">
                                            <label><?php echo $this->lang->line('email'); ?></label>
                                        </td>
                                        <td>
                                            <input type="email" name="email" class="form-control" value="<?php echo set_value('email',$params[0]['email']); ?>"> <span class="alert-danger"><?php echo form_error('email'); ?></span>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th><?php echo $this->lang->line('cart_total'); ?> </th>
                                        <td><?php echo $currency_symbol.number_format((float)$cart_total, 2, '.', ''); ?></td>
                                    </tr>

                                    <input type="hidden" name="total_cart_amount" value="<?php echo $cart_total; ?>">
                                    
                                    <tr class="paybtngray">
                                      <td><button type="button" onclick="window.history.go(-1); return false;" name="search"  value="" class="btn paybackbtn"><i class="fa fa-chevron-left valign-middle"></i> <?php echo $this->lang->line('back'); ?> </button></td>
                                      <td><button type="button" id="buy-button"  name="search"  value="" class="btn btn-info buttondarkgray"> <?php echo $this->lang->line('pay_with_twocheckout'); ?> <i class="fa fa-chevron-right valign-middle"></i></button></td>
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

<script src="<?php echo base_url();?>backend/custom/jquery.min.js"></script>
         
<script>
    (function(document, src, libName, config) {
        var script = document.createElement('script');
        script.src = src;
        script.async = true;
        var firstScriptElement = document.getElementsByTagName('script')[0];
        script.onload = function() {
            for (var namespace in config) {
                if (config.hasOwnProperty(namespace)) {
                    window[libName].setup.setConfig(namespace, config[namespace]);
                }
            }
            window[libName].register();
        };

        firstScriptElement.parentNode.insertBefore(script, firstScriptElement);
    })(document, 'https://secure.2checkout.com/checkout/client/twoCoInlineCart.js', 'TwoCoInlineCart', {
        "app": {
            "merchant": "<?php echo $api_config->api_publishable_key; ?>"
        },
        "cart": {
            "host": "https:\/\/secure.2checkout.com"
        }
    }); 
</script>
<script type="text/javascript">
          	 
    window.document.getElementById('buy-button').addEventListener('click', function() {

        TwoCoInlineCart.events.subscribe('cart:closed', function(e) {
            alert();
                
        });

        TwoCoInlineCart.setup.setMerchant("<?php echo $api_config->api_publishable_key; ?>");
        TwoCoInlineCart.setup.setMode('DYNAMIC'); // product type
        TwoCoInlineCart.register();

        TwoCoInlineCart.products.add({
            name: "Student Fees",
            quantity: 1,
            price: "<?php echo $amount;?>",
        });

        TwoCoInlineCart.cart.setOrderExternalRef("<?php echo md5(time()); ?>");
        TwoCoInlineCart.cart.setExternalCustomerReference("<?php echo md5("1".time()); ?>"); // external customer reference 
        TwoCoInlineCart.cart.setCurrency("<?php echo $currency; ?>");
        TwoCoInlineCart.cart.setTest(false);
        TwoCoInlineCart.cart.setReturnMethod({
            type: 'redirect',
            url: "<?php echo base_url() ?>user/gateway/twocheckout/success",
        });

        TwoCoInlineCart.cart.checkout(); // start checkout process
    });

    setTimeout(function() {
        $('#buy-button').removeClass('disabled');
    }, 3000);
</script>