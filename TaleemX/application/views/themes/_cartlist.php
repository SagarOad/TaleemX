<ul>
<?php
$total = 0;
$count = 0;
$currency_symbol = $this->customlib->getSchoolCurrencyFormat();

if (!empty($cart_data)) {  
    foreach ($cart_data as $key => $cvalue) {
        $course_data = $this->customlib->getCourseDetail($cvalue['id']);

        if (!empty($course_data)) {
            $total += $cvalue['price'];
            ++$count;
            $course_price = amountFormat($cvalue['price']);             

            if ($course_data["course_thumbnail"] !='') {
               $thumbnail = base_url() . "uploads/course/course_thumbnail/" . $course_data["course_thumbnail"];
              
            } else {
              $thumbnail = base_url() . 'backend/images/wordicon.png';
            }
            ?> 
            <li>
                <div class="cartitem">
                    <div class="item-image">
                        <a href="<?php echo base_url(); ?>course/coursedetail/<?php echo $course_data["slug"]; ?>"><img src="<?php echo $thumbnail ; ?>" alt="" class="img-responsive"></a>        
                            
                    </div>
                    <div class="cartdetail">
                        <a href="<?php echo base_url(); ?>course/coursedetail/<?php echo $course_data["slug"]; ?>">
                            <div class="course-name"><?php echo $course_data['title'] ?></div>                                
                            <div class="courseprice"><?php echo $currency_symbol.$course_price ?> </div>
                        </a>
                        <a class="btn btn-warning btn-xs pull-right" title="<?php echo $this->lang->line('delete'); ?>" onclick="removecartheader('<?php echo $cvalue['rowid']; ?>')">  <i class="fa fa-trash-o"></i></a>
                    </div>
                </div>
            </li> 
            <?php 
        }
    }
}?>
</ul> 