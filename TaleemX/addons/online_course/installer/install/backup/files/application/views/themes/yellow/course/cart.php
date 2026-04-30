<?php 
    $currency_symbol = $this->customlib->getSchoolCurrencyFormat();   
    $checkLogin               = $this->customlib->checkUserLogin();
 
    if ($checkLogin == true) {    
        $role  = $this->result["role"];
        if($role=='student'){
            $student_id = $this->result["student_id"];
        }else{
            $student_id = $this->result["guest_id"];
        }    
    } else {    
        $student_id = 0;
    }
    
    $box_layout_width = '';
    $checkoutstatus = 1;
?> 

<div class="spaceb40">
    <div class="boxheader-border">
        <div class="row">
            <div class="col-md-12">
                <div class="course-header boxborder-bottom">
                    <h4 class="titlefix"><?php echo $this->lang->line('cart') ?></h4>
                </div><!--./course-header-->
                <div class="course-body">
                <?php 
                    if ($this->session->flashdata('msg')) {    
                        echo $this->session->flashdata('msg');
                        $this->session->unset_userdata('msg');
                    }
                    ?>
                    <form method="post" id="checkoutform" action="">
                        <div class="row">
                        
                            <div class="col-lg-9 col-md-9 col-lg-12">
                                <h5><?php echo sizeof($course_data) . " " . $this->lang->line('courses_in_cart'); ?></h5>
                                
                                <?php if (empty($course_data)) {
                                    echo $this->lang->line('your_cart_is_empty_please_add_courses'); 
                                } ?>
                                
                                <ul class="cart-course-shop">
                                <?php
                                    $total = 0; 
                                    $image="";
                                    $cart_total=0;  
                                    if (!empty($course_data)) { 
                                        foreach ($course_data as $key => $value) {
                                            if (!empty($value['course_thumbnail'])) {
                                                $image = base_url() . 'uploads/course/course_thumbnail/'. $value['course_thumbnail'];
                                            } else {
                                                $image = base_url() . 'backend/images/wordicon.png';
                                            }

                                            $free_course    = $value['free_course'];
                                            $discount       = $value['discount'];
                                            $price          = $value['price'];
                                            $discount_price = '';
                                            $price          = '';

                                        if ($value['discount'] != '0.00') {
                                            $discount = $value['price'] - (($value['price'] * $value['discount']) / 100);
                                        }

                                        if (($value["free_course"] == 1) && ($value["price"] == '0.00')) {
                                            $price = 'Free';
                                        } elseif (($value["free_course"] == 1) && ($value["price"] != '0.00')) {
                                
                                        } elseif (($value["free_course"] == 1) && ($value["price"] != '0.00')) {
                                            if ($value['price'] > '0.00') {
                                        } elseif (($value["free_course"] == 1) && ($value["price"] != '0.00')) {
                                                $courseprice = $currency_symbol . '' . $value['price'];
                                            } else {
                                                $courseprice = '';
                                            }
                                            $price = "Free <span><del>" . $courseprice . '</del></span>';
                                        } elseif (($value["price"] != '0.00') && ($value["discount"] != '0.00')) {
                                            $discount = number_format((float) $discount, 2, '.', '');
                                            if ($value['price'] > '0.00') {
                                                $courseprice = $currency_symbol . '' . $value['price'];
                                            } else {
                                                $courseprice = '';
                                            }
                                
                                            $price = ' <span><del>' . $currency_symbol.amountFormat($value['price']) . '</del></span> ( ' . $value['discount'] .'% '.$this->lang->line('off').' ) ';
                                        } else {           
                                
                                        }                
                                       
                                            $total = $cart_data[$value["id"]]['price'];
                                            $cart_total = $cart_total + $total ;
                                            
                                        ?>
                                    <li>
                                        <div class="cart-course-wrapper">
                                          <div class="d-flex full-width align-items-sm-center">  
                                            <div class="image">
                                                <a href="<?php echo base_url(); ?>course/coursedetail/<?php echo $value["slug"] ?>"><img src="<?php echo $image ?>" alt="hlo test" class="img-responsive"></a>
                                            </div>
                                            <div class="mainright">
                                                <a href="<?php echo base_url(); ?>course/coursedetail/<?php echo $value["slug"] ?>">
                                                    <div class="name text-capitalize"><?php echo $value["title"] ?></div>
                                                    <input type="hidden" name="course_id" value="<?php echo $value['id']; ?>">
                                                </a>  
                                                <a href="">
                                                    <div class="instructor"><?php echo $this->lang->line('by') ?> <?php echo $value["staff_name"].' '.$value["staff_surname"] ?> </div>
                                                </a>
                                            </div><!--./mainright-->
                                         </div>   
                                            <div class="removebtn">
                                                <div>
                                                    <a onclick="return confirm('<?php echo $this->lang->line('are_you_sure_want_to_delete'); ?>')"title="<?php echo $this->lang->line('delete'); ?>" href="<?php echo base_url() . "cart/removecart/" . $value['id'] ?>" ><i class="fa fa-trash"></i></a>
                                                </div>
                                            </div><!--./removebtn-->
                                            <div class="pricetext">
                                                <div class="current-pricetext" id="pricediv<?php echo $value['id'] ?>">
                                                <?php
                                                    $courseamoount = amountFormat($total);
                                                    echo $currency_symbol.$catprice = $courseamoount ?>
                                                </div>
                                                <?php if ($catprice != $price) {echo $price;}?>
                                                <br>
                                                <span id="discountdiv<?php echo $value["id"] ?>"></span>
                                            </div><!--./price-->
                                        </div><!--./cart-course-wrapper-->
                                    </li>
                                    <?php 
                                    }}  ?>
                                </ul>
                                <div class="row pt20">
                                    <div class="col-md-4 col-sm-4">
                                        <a class="gotocartbtn btn btn-success" href="<?php echo base_url() . "course" ?>"> <?php echo $this->lang->line("continue_shopping"); ?></a>                                      
                                    </div>
                                </div>
                            </div><!--./col-lg-9-->
                            <div class="col-lg-3 col-md-3 col-sm-12 total-sticky">
                                <h4><?php echo $this->lang->line('cart_total');   ?></h4>
                                <div class="cart-footer">
                                    <div class="carttotal-price"><?php echo $this->lang->line('subtotal') ?>
                                        <span>
                                            <?php
                                                $total = amountFormat($cart_total);
                                                echo $currency_symbol.$total ;
                                            ?>
                                        </span>
                                    </div>                      
                                    <div class="carttotal-price"><?php echo $this->lang->line('total') ?>
                                        <span id="total_span">
                                            <?php echo $currency_symbol.amountFormat($cart_total); ?>
                                        </span>
                                        <input type="hidden" name="total_amount" id="total_amount" value="<?php echo $cart_total ?>">
                                    </div>
                                    <button class="gotocartbtn" type="button" onclick="checkLoginforcart('<?php echo $checkoutstatus ; ?>')"><?php echo $this->lang->line('proceed_to_checkout') ?></button>
                                </div>
                                <?php if ($this->session->has_userdata('user')) {?>
                                    <input type="hidden" name="record_id" value="<?php echo  $student_id; ?>">
                                <?php }?>                    
                            </div><!--./col-lg-3-->
                        </div>
                    </form>
                </div><!--./course-body-->
            </div><!--./col-md-12-->
        </div><!--./row-->
    </div><!--./boxheader-border-->
</div><!--./container-->

<script type="text/javascript">
    <?php if (!empty($this->session->has_userdata('user'))) {?>
         updateTotal_amount('<?php echo $total; ?>');     
    <?php }?>
    
    function checkout()
    { 
        var total = $("#total_amount").val();
        if(total != '0'){
            var urt = '<?php echo base_url() ?>checkout';
            $.ajax({
                url: urt,
                type: "POST",
                data: $('#checkoutform').serialize(),
                dataType: 'json',
                success: function (data) {                  
                    window.location.href='<?php echo base_url() . "checkout/billpayment" ?>';                     
                },
            });
        }else{
            toastr.error('<?php echo $this->lang->line('cart_amount_should_be_greater_than'); ?>')
        }
    }
    
    function updateTotal_amount(updateamount){
          $.ajax({
            url: '<?php echo base_url() ?>front/Checkout/total_amount/'+updateamount,
            success: function (data) {
              
            },
        });
    } 
    
</script>