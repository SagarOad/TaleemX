<?php
$currency_symbol = $this->customlib->getSchoolCurrencyFormat();
?>
<input type="hidden" name="actual_price" value="<?php echo $paymentdata['actual_amount']; ?>">
<input type="hidden" name="paid_amount" value="<?php echo $paymentdata['total_amount']; ?>">
<input type="hidden" name="course_name" value="<?php echo $paymentdata['course_name']; ?>">
<input type="hidden" name="student_id" value="<?php echo $studentid; ?>">
<input type="hidden" name="class_section_id" value="<?php echo $class_section_id; ?>">
<input type="hidden" name="courses_id" value="<?php echo $paymentdata['course_id']; ?>">
<input type="hidden" name="pay_class_id" value="<?php echo $class_id ; ?>">

<div class="col-lg-12">
    <div class="form-horizontal">
        <div class="form-group">
            <label class="col-sm-3 control-label"><?php echo $this->lang->line('course_name'); ?>:</label>
             <div class="col-sm-9">
                <label class=" control-label"><?php if(!empty($paymentdata['course_name'])){ echo $paymentdata['course_name'];} ?></label>
             </div>
        </div>
        <div class="form-group">
            <label class="col-sm-3 control-label"><?php echo $this->lang->line('date'); ?> <small class="req"> *</small></label>
            <div class="col-sm-9">
                <input type="text" name="collected_date" type="text" class="form-control date_fee" autocomplete="off" readonly="readonly">
               <span class="text-danger"><?php echo form_error('collected_date'); ?></span>
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-3 control-label"> <?php echo $this->lang->line('payment_mode'); ?></label>
            <div class="col-sm-9">
                <label class="radio-inline">
                    <input type="radio" name="payment_mode_fee" value="cash" checked="checked"><?php echo $this->lang->line('cash'); ?> </label>
                <label class="radio-inline">
                    <input type="radio" name="payment_mode_fee" value="cheque"><?php echo $this->lang->line('cheque'); ?></label>
                <label class="radio-inline">
                    <input type="radio" name="payment_mode_fee" value="dd"><?php echo $this->lang->line('dd'); ?></label>
                <label class="radio-inline">
                    <input type="radio" name="payment_mode_fee" value="bank_transfer"><?php echo $this->lang->line('bank_transfer'); ?></label>
                <label class="radio-inline">
                    <input type="radio" name="payment_mode_fee" value="upi"><?php echo $this->lang->line('upi'); ?></label>
                <label class="radio-inline">
                    <input type="radio" name="payment_mode_fee" value="card"><?php echo $this->lang->line('card'); ?></label>
                <span class="text-danger" id="payment_mode_error"></span>
            </div>
            <span id="form_collection_payment_mode_fee_error" class="text text-danger"></span>
        </div>
        <div class="form-group">
            <label class="col-sm-3 control-label"><?php echo $this->lang->line('note'); ?></label>
            <div class="col-sm-9">
                <textarea class="form-control" rows="5" name="fee_note" id="description"></textarea>
                <span id="form_fee_note_error" class="text text-danger"></span>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-5"></div>
    <div class="col-md-4">
        <span class="pull-right">
            <h3><?php echo $this->lang->line('total_pay'); ?></h3>
        </span>
    </div>
    <div class="col-md-3">
        <span class="pull-right"><h3><?php  echo $currency_symbol; ?><?php if(!empty($paymentdata['total_amount'])){ echo amountFormat($paymentdata['total_amount']);}else{ echo '0.00';} ?></h3></span>
    </div>
</div>