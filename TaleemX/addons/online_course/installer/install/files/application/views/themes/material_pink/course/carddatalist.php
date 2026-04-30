<?php 
    $currency_symbol = $this->customlib->getSchoolCurrencyFormat();  
?>

<li class="dropdown" id="card_data">
    <div class="shop-chart" data-toggle="dropdown"><a href="#" class="dropdown-toggle" ><i class="fa fa-shopping-cart"></i><?php if ($cartcount > 0) {?><span id="quick-item-count"><?php echo $cartcount; ?></span><?php }?></a></div>
    <div class="dropdown-menu shop-chart-top13">
        <div class="cart-wrapper cart-list">
            <ul>
                <?php
                $total = 0;
                if (!empty($cartdata)) {
                    foreach ($cartdata as $key => $cvalue) {
                        $course_data = $this->customlib->getCourseDetail($cvalue['course_id']);
                        $total += $cvalue['price'];
                ?>
                <li>
                    <div class="cartitem">
                        <div class="item-image">
                            <a href="<?php echo base_url() . $course_data["url"] ?>"><img src="<?php echo base_url() . 'uploads/course_images/' . $course_data['course_thumbnail'] ?>" alt="" class="img-responsive"></a>
                        </div>
                        <div class="cartdetail">
                            <a href="<?php echo base_url() . $course_data["url"] ?>">
                                <div class="course-name"><?php echo $course_data['course_title'] ?></div>
                                <div class="subtext-name"><?php echo $this->lang->line('by') ?> <?php echo $course_data['student_name'] ?></div>
                                <div class="courseprice"><?php echo $currency_symbol.$cvalue['price']; ?></div>
                            </a>
                            <a class="btn btn-warning btn-xs pull-right" title="delete" onclick="removecartheader('<?php echo $cvalue['course_id']; ?>')"><i class="fa fa-trash-o"></i></a>
                        </div>
                    </div>
                </li>
                <?php }}?>
            </ul>
        </div><!--./cart-wrapper-->
        <div class="cart-footer" style="border:solid thin blue">
            <div class="focarttotal-price"><?php echo $this->lang->line('total') . " " . $currency_symbol.$total ; ?>
                <span><?php echo sizeof($cartdata) . " " . $this->lang->line('item'); ?></span>
            </div>
            <a href="<?php echo base_url() . "cart" ?>" class="gotocartbtn"><?php echo $this->lang->line('go_to_cart') ?> Proceed To checkout </a>
        </div><!--./cart-footer-->
    </div><!--./dropdown-menu-->
</li>