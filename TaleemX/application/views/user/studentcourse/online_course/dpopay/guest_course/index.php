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
                                <?php if (!empty($error)) { ?>
                                    <div class="alert alert-danger">
                                        <h4><i class="icon fa fa-warning"></i> Payment Error!</h4>
                                        <?php
                                        if (isset($error['ResultExplanation'])) {
                                            echo '<p><strong>Error:</strong> ' . htmlspecialchars($error['ResultExplanation']) . '</p>';
                                        }
                                        if (isset($error['Result'])) {
                                            echo '<p><strong>Error Code:</strong> ' . htmlspecialchars($error['Result']) . '</p>';
                                        }
                                        if (isset($error['TransRef'])) {
                                            echo '<p><strong>Transaction Reference:</strong> ' . htmlspecialchars($error['TransRef']) . '</p>';
                                        }
                                        if (isset($error['message'])) {
                                            echo '<p><strong>Message:</strong> ' . htmlspecialchars($error['message']) . '</p>';
                                        }
                                        ?>
                                    </div>
                                <?php } ?>
                                <form action="<?php echo base_url(); ?>students/online_course/dpopay/guestpay" method="post">
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
                                        $cart_total = $total_processing_charge = 0;
                                        foreach ($cartdata as $value) {
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
                                                    if (!empty($value['price'])) {
                                                        echo amountFormat($value['price']);
                                                    } else {
                                                        echo '0.00';
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                        <tr>
                                            <th><?php echo $this->lang->line('processing_fees'); ?></th>
                                            <th class="text-right"><?php echo $currency_symbol . amountFormat($total_processing_charge); ?></th>
                                        </tr>
                                        <tr>
                                            <th><?php echo $this->lang->line('total'); ?></th>
                                            <th class="text-right"><?php echo $currency_symbol . amountFormat($cart_total + $total_processing_charge); ?></th>
                                        </tr>

                                        <input type="hidden" name="total_cart_amount" value="<?php echo $cart_total + $total_processing_charge; ?>">

                                        <tr>
                                            <td><?php echo ('Credit Card Number'); ?>:</td>
                                            <td class="text-right">
                                                <input type="text" class="form-control" name="creditcardnumber" value="<?php echo set_value('creditcardnumber'); ?>" />
                                                <span class="alert-danger"><?php echo form_error('creditcardnumber'); ?></span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><?php echo ('Credit Card Expiry'); ?>:</td>
                                            <td class="text-right">
                                                <input type="text" class="form-control" name="creditcardexpiry" value="<?php echo set_value('creditcardexpiry'); ?>" placeholder="MMYY">
                                                <span class="alert-danger"><?php echo form_error('creditcardexpiry'); ?></span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><?php echo ('Credit Card CVV'); ?>:</td>
                                            <td class="text-right">
                                                <input type="text" class="form-control" name="creditcardcvv" value="<?php echo set_value('creditcardcvv'); ?>">
                                                <span class="alert-danger"><?php echo form_error('creditcardcvv'); ?></span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><?php echo ('Card Holder Name'); ?>:</td>
                                            <td class="text-right">
                                                <input type="text" class="form-control" name="cardholdername" value="<?php echo set_value('cardholdername'); ?>">
                                                <span class="alert-danger"><?php echo form_error('cardholdername'); ?></span>
                                            </td>
                                        </tr>

                                        <tr class="paybtngray">
                                            <td>
                                                <button type="button" onclick="window.history.go(-1); return false;" class="btn btn-info buttongray">
                                                    <i class="fa fa-chevron-left valign-middle"></i> <?php echo $this->lang->line('back'); ?>
                                                </button>
                                            </td>
                                            <td class="text-right">
                                                <button type="submit" class="btn btn-info buttondarkgray">
                                                    <?php echo $this->lang->line('pay_with') ?: 'Pay with DPO'; ?> <i class="fa fa-chevron-right valign-middle"></i>
                                                </button>
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


