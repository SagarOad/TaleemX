<?php
$currency_symbol = $this->customlib->getSchoolCurrencyFormat();
$total_processing_charge=0; 
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
                              <form action="<?php echo base_url(); ?>students/online_course/walkingm/guestpay" method="post">
                                
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
                                    
                                    $cart_total = 0;
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
                                        <th><?php echo $this->lang->line('total'); ?> </th>
                                        <th class="text-right"><?php echo $currency_symbol.amountFormat($cart_total+$total_processing_charge); ?></th>
                                    </tr>
                                    
                                    <tr>
                                      <td class="font-weight-bold">
                                        <label><?php echo $this->lang->line('email'); ?></label>
                                      </td>
                                      <td>
                                        <input type="email" name="email" class="form-control" value="<?php echo $params[0]['email']; ?>"> <span class="alert-danger"><?php echo form_error('email'); ?></span>
                                      </td>
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