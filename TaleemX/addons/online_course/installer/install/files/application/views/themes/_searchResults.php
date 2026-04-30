<?php

$currency_symbol = $this->customlib->getSchoolCurrencyFormat();
$checkLogin      = $this->customlib->checkUserLogin();
$wishlist        = array();
 
if ($checkLogin == true) {
    $logindata        = $this->session->userdata();    
    $result           = $this->customlib->getUserData();    
}

$cart_data = $this->cart->contents();
 
if(!empty($cart_data)){
    foreach ($cart_data as $key => $value)
    {
      $cart_data['course_id'][] = $value['id'];
    }
}
?>

<div class="col-lg-12 col-md-12 col-sm-6 filterright">
     
        <div class="row row-flex" id="products">

        <?php

        if (!empty($courselist)) {
        $a = array();
            foreach ($courselist as $key => $courses) {
                foreach ($courses as $key => $cvalue) {
                    if (in_array($cvalue['id'], $a)) {

                    } else {
                        array_push($a, $cvalue['id']);
                        $discount  = 0;
                        $thumbnail = base_url() . 'backend/images/wordicon.png';
                        if (!empty($cvalue["course_thumbnail"])) {
                            $thumbnail = base_url() . 'uploads/course/course_thumbnail/'.$cvalue["course_thumbnail"];
                        } else {
                            $thumbnail = base_url() . 'backend/images/wordicon.png';
                        }                   
                    
                        if($this->session->userdata('active_class_name')){
                            $class_name = $this->session->userdata('active_class_name')['class_name'];               
                        }else{
                            $class_name = "grid-group-item"; 
                        }
                    ?>  
                
            <div class="item col-lg-4 col-md-4 col-sm-12 <?php echo $class_name; ?>">
                <div class="product-item">
                    <div class="girdthumbnail">
                        <div class="product-img">
                            <a href="<?php echo base_url() . $cvalue["url"] ?>"><img class="group list-group-image img-responsive" src="<?php echo base_url(); ?>uploads/course/course_thumbnail/<?php echo $cvalue['course_thumbnail']; ?>"></a>
                        </div>
                        <div class="proinner caption column-height-equal">
                            <a href="<?php echo base_url() . $cvalue["url"] ?>"><h5><?php echo $cvalue['title']; ?></h5></a>
                            
                            <div class="course-caption"><?php echo $cvalue['description']; ?></div>
                            <p class="authers"><span class="fontbold"><?php echo $this->lang->line('category'); ?></span> <?php echo $cvalue['category_name']; ?></span>
                            
                            <p class="authers">
                                <span class="fontbold"><?php echo $this->lang->line('last_updated'); ?></span> <?php echo date($this->customlib->getSchoolDateFormat(), strtotime($cvalue['updated_date'])); ?>
                            </p>
                            <p class="courseitext">
                                 <?php if (!empty($cvalue['total_lesson'])) { ?><i class="fa fa-play-circle"></i> <span> <?php echo  $cvalue['total_lesson'].' '. $this->lang->line('lesson'); } ?></span><?php if (!empty($cvalue['total_hour_count']) && $cvalue['total_hour_count'] != '00:00:00') { ?><i class="fa fa-clock-o"></i><span><?php echo $cvalue['total_hour_count'] . ' ' . $this->lang->line('hrs');  ?></span> <?php }  ?> 
                            </p>        
                        </div><!--./proinner-->

                        <?php

                        $free_course    = $cvalue['free_course'];
                        $discount       = $cvalue['discount'];                        
                        $discount_price = '';
                        $price          = '';

                        if ($cvalue['discount'] != '0.00') {
                            $discount = $cvalue['price'] - (($cvalue['price'] * $cvalue['discount']) / 100);
                        }
                
                        if (($cvalue["free_course"] == 1) && ($cvalue["price"] == '0.00')) {
                            $price = $this->lang->line('free');
                        } elseif (($cvalue["free_course"] == 1) && ($cvalue["price"] != '0.00')) {
                            if ($cvalue['price'] > '0.00') {
                                $courseprice =  amountFormat($cvalue['price']);
                            } else {
                                $courseprice = '';
                            }
                            $price = $this->lang->line('free')." <span><del>" . $currency_symbol . '' .$courseprice . '</del></span>';
                        } elseif (($cvalue["price"] != '0.00') && ($cvalue["discount"] != '0.00')) {
                            $discount = amountFormat($discount);
                            if ($cvalue['price'] > '0.00') {
                                $courseprice = $currency_symbol . '' . amountFormat($cvalue['price']);
                            } else {
                                $courseprice = '';
                            }
                            $price = $currency_symbol . '' . $discount . ' <span><del>' . $courseprice . '</del></span> ';
                        } else {
                            $price = $currency_symbol . '' . amountFormat($cvalue['price']);
                        }

                        ?>
                        <div class="captionright">                       
                            <ul class="rating">            
                      
                            <?php                             
                                    
                      
                                if(!empty($cvalue['courserating'][0])){
                                    $courserating = $cvalue['courserating'][0]['rating'];
                                }elseif($cvalue['courserating'] > 0){
                                    $courserating = $cvalue['courserating'];
                                }
                            
                                if(!empty($courserating)){ ?>
                            
                                <?php for ($i = 1; $i <= 5; $i++) { ?>
                                    <li class="fa fa-star disable" <?php if ($courserating >= $i) { ?> style="color:orange;"<?php } ?>></li>
                                <?php } ?>
                                
                                
                      
                                <?php  if($cvalue['totalcourserating'] !=0){ echo ' ('.$cvalue['totalcourserating'].' '.$this->lang->line('rating').')'; } ?> 
                                
                                <?php }  ?>                             
                    
                            <?php  if($cvalue['free_course']==0 && $cvalue['course_sale'] > 0){ ?>
                                <div class="ptsales"><?php echo $cvalue['course_sale']; ?> <?php if($cvalue['course_sale'] == 1){ echo $this->lang->line('sale') ; }else{ echo $this->lang->line('sales') ;} ?></div>
                            <?php } ?>  
                     
                            </ul>             
                       
                            <div class="pt-5 align-items-center d-flex justify-content-between flex-direction-sm">
                                <div>
                                    <div class="pricesell vh-center"><?php echo $price; ?> </div>
                                </div>

                                <div class="coursebtn" id="btn_status_<?php echo $cvalue['id']; ?>">        
                                    
                                <?php if($cvalue['free_course']==1){ ?>
                               
                                    <a href="#" class="btn btn-buygreen lesson_ID"  lesson-data="<?php echo $cvalue['id']; ?>"><?php echo $this->lang->line('start_lesson'); ?></a>

                                <?php }else{

                                  if($cvalue['paidstatus'] == '1'){ ?>

                                    <a href="#" class="btn btn-buygreen lesson_ID"  lesson-data="<?php echo $cvalue['id']; ?>"><?php echo $this->lang->line('start_lesson'); ?></a>

                                <?php }else{ 
                              
                                    if (in_array($cvalue['id'], array_column($cart_data, 'id'))) {
                              
                                ?>                          
                                    <button class="ptaddtocart" type="button" onclick="addtocart('<?php echo $cvalue['id'] ?>')"><i class="fa fa-shopping-cart"></i><?php echo $this->lang->line('added_to_cart'); ?>  </button>
                                    
                                <?php }else{ ?>

                                    <button class="ptaddtocart" type="button" onclick="addtocart('<?php echo $cvalue['id'] ?>')"><i class="fa fa-shopping-cart"></i><?php echo $this->lang->line('add_to_cart'); ?></button>
                                      
                                <?php } } ?>                                 

                                <?php }  ?> 
                                </div>
                           </div><!--./clear-->
                        </div>
                    </div><!--./girdthumbnail-->
                </div><!--./product-item-->
            </div><!--./col-md-4-->   
                
            <?php }}}}else{?>
            <div class="text-center pt20">
                <img src="<?php echo base_url(); ?>backend/themes/material_pink/images/addnewitem.svg" />
                <p class="text-danger pt-5"><?php echo $this->lang->line('no_record_found'); ?></p>
            </div>
  
            <?php } ?>
        </div><!--./row-->
     
</div><!--./row-->
<!--  <script type="text/javascript">
   $(document).ready(function() {
    $('.captionright').css({
      'height': $('.captionright').height()
    });
   });  
</script> -->
<script type="text/javascript">
    (function ($) {
        "use strict";
        $(document).ready(function() {
            $('#list').click(function(event){event.preventDefault();
                $('#products .item').addClass('list-group-item');
                $('#products .item').removeClass('grid-group-item'); 
                addclass('list-group-item');
            });
    
            $('#grid').click(function(event){event.preventDefault();
                $('#products .item').removeClass('list-group-item');
                $('#products .item').addClass('grid-group-item'); 
                addclass('grid-group-item');
            });
        });
    })(jQuery);
</script>
<script src="<?php echo base_url(); ?>backend/js/online_course.js"></script>