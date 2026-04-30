<?php $this->load->view('layout/course_css.php'); ?>
<html>
  <head>
    <link rel="stylesheet" href="<?php echo base_url(); ?>backend/dist/css/style-main.css">
    <link rel="stylesheet" href="<?php echo base_url(); ?>backend/bootstrap/css/bootstrap.min.css">
  </head>  
    <body class="bg-light-gray">
      <div class="payment-main">
       <div class="container">
        <div class="row">
          <div class="col-lg-12">
              <div class="successpayment">
                <div class="successpayment-circle">
                  <div class="successpayment-icon"><i class="checkmark">✓</i></div>
                </div>
                  <h1><?php echo $this->lang->line('processing'); ?></h1>
                  <p class="mb20"><?php echo $this->lang->line('your_payment_status_is_pending'); ?>. </p>
                  <a href="<?php echo base_url(); ?>user/studentcourse" class="btn btn-info btn-lg"><?php echo $this->lang->line('go_to_home'); ?></a>
              </div>
            </div>  
          </div>  
        </div>  
      </div>  
    </body>
</html>