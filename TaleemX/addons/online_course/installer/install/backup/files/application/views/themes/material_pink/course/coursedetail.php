<?php

$currency_symbol = $this->customlib->getSchoolCurrencyFormat();
$checkLogin      = $this->customlib->checkUserLogin();
$cart_data       = $this->cart->contents();
if (!empty($cart_data)) {

    foreach ($cart_data as $key => $value) {
        $cart_data['course_id'][] = $value['id'];
    }
}

$course_thumbnail = "course_thumbnail" . $coursesList['id'];
$free_course      = $coursesList['free_course'];
$discount         = $coursesList['discount'];
$price            = $coursesList['price'];
$discount_price   = '';
if ($discount != '0.00') {
    $discount_price = number_format($price - $price * $discount / 100, 2);
}

$basevalue = floor($avgRating);
$roundAvg  = round($avgRating, 2);
$enable    = $roundAvg;
if (($roundAvg >= $basevalue + .25) && ($roundAvg < $basevalue + .75)) {
    $enable = $basevalue + .5;
} elseif (($roundAvg >= $basevalue) && ($roundAvg < $basevalue + .25)) {
    $enable = (int) $basevalue;
} elseif (($roundAvg >= $basevalue + .75) && ($roundAvg > $basevalue + .25)) {
    $enable = (int) round($roundAvg, 0);
}
?>

<div class="modal fade" id="addRatingModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog modal-mid" role="document">
        <div class="modal-content modal-media-content">
            <div class="modal-header modal-media-header modal-header-gradient">
                <button type="button" class="modalclose" data-dismiss="modal">&times;</button>
                <h4 class="box-title text-white"> <?php echo $this->lang->line('add') . " " . $this->lang->line('rating') ?></h4>
            </div>
            <div class="modal-body">
                <form  id="addratingform" name="employeeform" method="post" accept-charset="utf-8" enctype="multipart/form-data">
                    <div class="overflowhidden">
                      <div class="form-group">
                                <label for="exampleInputEmail1"><?php echo $this->lang->line('rating'); ?> <small class="req"> *</small></label><br/>
                           <input name="rating" value="0" id="rating_star" type="hidden" postID="1" />
                           <input name="course_id" id="course_id" value=""  type="hidden" />
                                <span class="text-danger"><?php echo form_error('rating'); ?></span>
                            </div>
                           <div class="form-group">
                                <label for="exampleInputEmail1"><?php echo $this->lang->line('review'); ?> <small class="req"> *</small></label>
                              <textarea id="review" name="review" class="form-control" ></textarea>
                              </div>
                         <div class="box-footer ">
                        <button type="submit" class="btn btn-themegreen submit_addevent pull-right"><?php echo $this->lang->line('save'); ?></button>
                     </div>
                    </div>
                </form>
            </div><!--./col-md-12-->
        </div><!--./row-->
    </div>
</div>

<div class="row">
<div class="course-detailtopbg fullwidth">    
  <div class="container">
    <div class="row">
      <div class="col-md-12">         
            <h2 class="text-capitalize mt0 fontmedium"><?php echo $coursesList['title']; ?></h2>
             <h3><?php echo substr($coursesList['description'], 0, 500); ?></h3>
            <ul class="rating">
                <?php if ($courserating != 0 || $courserating != '') {?>
                    <?php for ($i = 1; $i <= 5; $i++) {?>
                        <li class="fa fa-star disable" <?php if ($courserating >= $i) {?> style="color:orange;"<?php }?>></li>
                    <?php }?>
                    <?php if ($totalcourserating != 0) {echo ' (' . $totalcourserating . ' ' . $this->lang->line('rating') . ')';}?>
                    <?php } else {?>
                    <br>
                <?php }?>
            </ul>      
            <form method="post" id="authordata" action="https://dev.webfeb.com/cddemo/home/author">
                <input type="hidden" id="authorid" name="student_id" value="5">
                <input type="hidden" id="author_isadmin" name="is_admin" value="yes">
            </form>
            <ul class="lessons">
                <?php if ($total_lesson != '' && $total_lesson != '0') {?> <li><i class="fa fa-play-circle"></i><?php echo $this->lang->line('lesson') . ": " . $total_lesson; ?></li> <?php }?>
                <?php if (!empty($total_hour_count) && $total_hour_count != '00:00:00') {?><li><i class="fa fa-clock-o"></i><?php echo $total_hour_count . " " . $this->lang->line('hrs'); ?></li> <?php }?>               
                <li><i class="fa fa-calendar"></i><?php echo $this->lang->line('last_updated'); ?>  <?php echo date($this->customlib->getSchoolDateFormat(), strtotime($coursesList['updated_date'])); ?> </li>
            </ul>
          </div>
        </div><!--./col-md-12-->
    </div><!--./row-->
  </div><!--./container-->
</div>

<?php
if (!empty($coursesList['outcomes'])) {

    $outcomes    = json_decode($coursesList['outcomes']);
    $check_empty = '';
    if (array_filter($outcomes)) {
        $check_empty = $outcomes;
    } else {
        $check_empty = '';
    }
}

?>
<div class="row">
  <div class="container spaceb50">
    <div class="row">
      <div class="col-lg-9 col-md-9 col-sm-12 pt20">
         <?php if (!empty($check_empty)) {?>
        <div class="card">
                <div class="cardbody">
                 <h3><?php echo $this->lang->line('what_will_i_learn'); ?>?</h3>
                    <ul class="whatlearn">                        
                        <?php foreach ($outcomes as $outcomes_value) {?>
                          <li><?php echo $outcomes_value; ?></li>
                          <?php }?>
                    </ul>
               </div><!--./cardbody-->
            </div>
        <?php }?>
            <h3 class="coursetitle"><?php echo $this->lang->line('curriculum_for_this_course'); ?> <span> <?php if ($total_lesson != '' && $total_lesson != '0') {?> <i class="fa fa-play-circle"></i><?php echo $this->lang->line('lesson') . ": " . $total_lesson; ?><?php }?>  <?php if (!empty($total_hour_count) && $total_hour_count != '00:00:00') {?>        <i class="fa fa-clock-o"></i><?php echo $total_hour_count . " " . $this->lang->line('hrs'); ?> <?php }?></span></h3>
            <div class="card">
                <div class="cardbody">
                  <div class="panel-group faq" id="accordion">
                    <?php $lessoncount = 0;
$quizcount                             = 0;
$sectioncount                          = 0;
$examcount          = 0;
$assignmentcount   = 0;
foreach ($sectionList as $sectionList_key => $sectionList_value) {
    $sectioncount                        = $sectioncount + 1;?>
                    <?php $sectionID = $sectionList_value->id;?>
                      <div class="panel panel-info">
                        <div class="panel-heading" data-toggle="collapse" data-parent="#accordion"  data-target="#<?php echo $sectionList_key; ?>">
                            <h4 class="panelh3 accordion-toggle"><?php echo "<b>" . $this->lang->line('section') . ' ' . $sectioncount . '</b>: ' . $sectionList_value->section_title; ?><span><i class="fa fa-play-circle"></i><?php if (!empty($sectionList_value->total_lessons)) {echo $sectionList_value->total_lessons;}?> <?php echo $this->lang->line('lesson'); ?></span></h4>
                        </div>
                        <div id="<?php echo $sectionList_key; ?>" class="panel-collapse collapse in">
                            <ul class="introlist"> 
                               <?php if (!empty($lessonquizdetail[$sectionID])) {?>
                                <?php foreach ($lessonquizdetail[$sectionID] as $lessonquizdetail_value) {

        if($lessonquizdetail_value['type'] == 'lesson') {
                                    $lessoncount = $lessoncount + 1;
                                    if ($lessonquizdetail_value['type'] != '') {?>
                                        <li><?php echo "<b>" . $this->lang->line($lessonquizdetail_value['type']) . " " . $lessoncount . ": " . "</b>" . $lessonquizdetail_value['lesson_title']; ?><span><?php if ($lessonquizdetail_value['lesson_type'] == 'video') {echo $lessonquizdetail_value['duration'];}?></span></li>
                                    <?php }                       
                                }else if($lessonquizdetail_value['type'] == 'quiz' && $this->customlib->get_online_course_curriculam_status("online_course_quiz")==""){ 
                                    $quizcount = $quizcount + 1;
                                    if ($lessonquizdetail_value['type'] != '') { ?>
                                        <li><?php echo "<b>" . $this->lang->line($lessonquizdetail_value['type']) . " " . $quizcount . ": " . "</b>" . $lessonquizdetail_value['quiz_title']; ?></li>
                                    <?php }                            
                                }else if($lessonquizdetail_value['type'] == 'exam' && $this->customlib->get_online_course_curriculam_status("online_course_exam")==""){ 
                                    $examcount = $examcount + 1;
                                    if ($lessonquizdetail_value['type'] != '') {?>
                                        <li><?php echo "<b>" . $this->lang->line($lessonquizdetail_value['type']) . " " . $examcount . ": " . "</b>" . $lessonquizdetail_value['course_exam_name']; ?></li>
                                    <?php }
                                }else if($lessonquizdetail_value['type'] == 'assignment' && $this->customlib->get_online_course_curriculam_status("online_course_assignment")==""){ 
                                    $assignmentcount = $assignmentcount + 1;
                                    if ($lessonquizdetail_value['type'] != '') {?>
                                        <li><?php echo "<b>" . $this->lang->line($lessonquizdetail_value['type']) . " " . $assignmentcount . ": " . "</b>" . $lessonquizdetail_value['assignment_title']; ?></li>
                                    <?php } 
                                }



                            }}?>
                            </ul>
                        </div><!--#/collapseOne-->
                    </div><!--./panel-info-->
                <?php }?>
                    </div><!--./panel-group-->
              </div><!--./cardbody-->
            </div><!--./card-->
            <div class="card">
                <div class="cardbody">
                  <h3> <?php echo $this->lang->line('description');?></h3>
                  <p><?php echo $coursesList['description']; ?>.</p>
              </div><!--./cardbody-->
            </div><!--./card-->
            <div class="card">
                <div class="cardbody">
                  <h3><?php echo $this->lang->line('other_related_courses');?></h3>
                   <?php if (!empty($otherrelatedcourses)) {
    ?>
                  <div class="row">
                   <?php
$limitdata = array_slice($otherrelatedcourses, 0, 3);
    foreach ($limitdata as $key => $value) {     

        $discount  = 0;
        $thumbnail = base_url() . 'backend/images/wordicon.png';
        $discount_price = '';
        $price          = '';

        if ($value['discount'] != '0.00') {
            $discount = $value['price'] - (($value['price'] * $value['discount']) / 100);
        }

        if (($value["free_course"] == 1) && ($value["price"] == '0.00')) {
            $price = 'Free';
        } elseif (($value["free_course"] == 1) && ($value["price"] != '0.00')) {
            if ($value['price'] > '0.00') {
                $courseprice =  amountFormat($value['price']);
            } else {
                $courseprice = '';
            }

            $price = $this->lang->line('free')." <span><del>" . $currency_symbol . '' .$courseprice . '</del></span>';
        } elseif (($value["price"] != '0.00') && ($value["discount"] != '0.00')) {
            $discount = amountFormat($discount);
            if ($value['price'] > '0.00') {
                $courseprice = $currency_symbol . '' . amountFormat($value['price']);
            } else {
                $courseprice = '';
            }
            $price = $currency_symbol . '' . $discount . ' <span><del>' . $courseprice . '</del></span> ';
        } else {
            $price = $currency_symbol . '' . amountFormat($value['price']);
        } 
        
        $thumbnail = base_url() . 'backend/images/wordicon.png';
        if (!empty($value["course_thumbnail"])) {
            $thumbnail = base_url() . "uploads/course/course_thumbnail/" . $value["course_thumbnail"];

        } else {
            $thumbnail = base_url() . 'backend/images/wordicon.png';
        }

        ?>

      <div class="col-lg-4 col-md-4 col-sm-12">
        <div class="product-item">
            <div class="girdthumbnail">
              <div class="product-img">
                <a href="<?php echo base_url() . $value["url"] ?>"><img class="group list-group-image img-responsive" src="<?php echo $thumbnail; ?>"></a>
              </div>
              <div class="proinner caption column-height-equal">
                <a href="#"><h5><?php echo $value['title'] ?></h5></a>
                <p ><?php echo substr($value['description'], 0, 500) ?></p>
                <p class="authers"><span><?php echo $this->lang->line('last_updated'); ?>  <?php echo date($this->customlib->getSchoolDateFormat(), strtotime($value['updated_date'])); ?>
                 </span> </p>
                 <p class="courseitext">
                 <i class="fa fa-play-circle"></i> <span><?php echo $value["lesson_count"] ?> <?php echo $this->lang->line('lessons');?></span><?php if ($value["hours_count"] != '0:0:0' && $value["hours_count"] != '00:00:00') {?><i class="far fa-clock"></i><span><?php echo $value["hours_count"] ?> <?php echo $this->lang->line('hours') ?></span><?php }?>   </p>                             
            </div><!--./proinner-->
            <div class="captionright">
                <ul class="rating">
      <?php
        if ($value['rating'] > 0) {
            $enablestar = $value['rating']; //enter how many stars to enable
            $max_stars  = 5; //enter maximum no.of stars
            $star_rate  = is_int($enablestar) ? 1 : 0;
            for ($i = 1; $i <= $max_stars; $i++) {
                ?>
                        <?php if (round($enablestar) == $i && !$star_rate) {?>
                          <li class="fa fa-star-half"></li>
                        <?php } elseif (round($enablestar) >= $i) {?>
                          <li class="fa fa-star"></li>
                        <?php } else {?>
                          <li class="fa fa-star disable"></li>
                        <?php }
            }?>
            <span><?php if (!empty($otherrating[$value['id']])) {echo "(" . $otherrating[$value['id']];}?> <?php if (!empty($otherrating[$value['id']])) {
                if ($otherrating[$value['id']] == 1) {echo $this->lang->line('rating');} else {echo $this->lang->line('ratings');}
                echo ')';}?></span>
            <?php } else {echo "&nbsp";}if ($value['course_sale'] > 0) {?>
                        <div class="ptsales fontbold"><?php echo $value['course_sale']; ?> <?php echo $this->lang->line('sales'); ?> </div>
                      <?php }?>                             
                              </ul>
                              <div class="pt-5 align-items-center d-flex justify-content-between flex-direction-sm">
                                  <div>
                                      <div class="pricesell vh-center"><?php echo $price; ?></div>
                                  </div>
                                  <div class="coursebtn">                                 
                                    <?php if($value['free_course']==1){ ?>                               
                                        <a href="#" class="btn btn-buygreen lesson_ID"  lesson-data="<?php echo $value['id']; ?>"><?php echo $this->lang->line('start_lesson'); ?>  </a>

                                    <?php 
                                        }else{

                                        if($value['otherpaidstatus'] == '1'){ ?>

                                            <a href="#" class="btn btn-buygreen lesson_ID"  lesson-data ="<?php echo $value['id']; ?>"><?php echo $this->lang->line('start_lesson'); ?> </a>

                                        <?php }else{ 
                              
                                        if (in_array($value['id'], array_column($cart_data, 'id'))) {
                              
                                    ?>                          
                                        <button class="ptaddtocart" type="button" onclick="addtocartfromcoursedetails('<?php echo $value['id'] ?>')"><i class="fa fa-shopping-cart"></i><?php echo $this->lang->line('added_to_cart'); ?></button>
                                    
                                        <?php }else{ ?>

                                            <button class="ptaddtocart" type="button" onclick="addtocartfromcoursedetails('<?php echo $value['id'] ?>')"><i class="fa fa-shopping-cart"></i><?php echo $this->lang->line('add_to_cart'); ?></button>
                                      
                                        <?php } } ?> 
                                    <?php }  ?>     
                             
                                  </div>
                              </div><!--./clear-->
                          </div><!--./captionright-->
                        </div><!--./girdthumbnail-->
                      </div><!--./product-item-->
                    </div><!--./col-md-4-->
                    <?php }?>
                  </div><!--./row-->
                   <?php }?>
              </div><!--./card-body-->
          </div><!--./card-->        

            <div class="card">
                <div class="cardbody">
                 <h3> <?php echo $this->lang->line('student_feedback'); ?></h3>
                 <div class="row">
                   <div class="col-lg-3 col-md-3 col-sm-12">
                      <div class="ratin-gaverage">
                          <div class="mumrating"> <?php if ($enable > 0) {echo $enable;} else {echo "<h4>" . $this->lang->line('not_rated') . "</h4>";}?></div>
                          <ul class="rating font20">
                              <?php
///enter how many stars to enable
$max_stars = 5; //enter maximum no.of stars
$star_rate = is_int($enable) ? 1 : 0;
for ($i = 1; $i <= $max_stars; $i++) {
    ?>
                                    <?php if (round($enable) == $i && !$star_rate) {?>
                                            <li class="fa fa-star-half"></li>
                                    <?php } elseif (round($enable) >= $i) {?>
                                            <li class="fa fa-star"></li>
                                    <?php } else {?>
                                        <li class="fa fa-star disable"></li>
                                    <?php }
}?>

                          </ul>
                          <p><?php echo $this->lang->line('average_rating');?>  </p>
                      </div>
                    </div><!--./col-md-3-->                     
                    <div class="col-lg-9 col-md-9 col-sm-12" >
                       <ul class="bar-container">
                            <?php
if (!empty($percentvalue)) {

    foreach ($percentvalue as $pkey => $percentvalue) {
        # code...
        ?>
                           <li>
                               <div class="progress">
                                    <div class="progress-bar progress-bar-warning" style="width:<?php echo $percentvalue ?>%" role="progressbar" aria-valuenow="<?php echo $percentvalue ?>"
                   aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <div>
                                 <span class="rating">
                                <?php for ($j = 1; $j <= 5; $j++) {
            if ($j <= (5 - $pkey)) {
                ?>
                                     <i class="fa fa-star "></i>
                                   <?php } else {?>
                                    <i class="fa fa-star disable"></i>
                                  <?php }}?>
                                 </span>
                                  <span><?php echo $percentvalue; ?>%</span>
                                </div>
                           </li>
                        <?php }} else {
     
    for ($n = 1; $n <= 5; $n++) {
        # code...
        ?>
                                    <li>
                                        <div class="progress">
                                            <div class="progress-bar progress-bar-warning width0" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <div>
                                            <span class="rating">
                                                <?php 
                                                    for ($j = 1; $j <= 5; $j++) {
                                                    if ($j <= (5 - $n)) {
                                                ?>
                                                    <i class="fa fa-star "></i>
                                                <?php } else {?>
                                                    <i class="fa fa-star disable"></i>
                                                <?php }}?>
                                            </span>
                                            <span>0%</span>
                                        </div>
                                    </li>
                                <?php }}?>
                                </ul>
                            </div><!--./col-md-9-->                 
                        </div><!--./row-->
                    </div><!--./cardbody-->
                </div><!--./card-->
                <?php if (!empty($coursereview)) {   ?>
                <div class="card">
                    <div class="cardbody">
                        <h3><?php echo $this->lang->line('reviews'); ?></h3>           
                        <div id="courseratingdata">
                            <div class="row">
                                <?php   
                                foreach ($coursereview as $coursereview_value) {                                      
                                    
                                    if($coursereview_value['image'] !=''){
                                        if($coursereview_value['guest_id'] > 0){
                                            $student_img = "uploads/guest_images/".$coursereview_value['image'];
                                        }else{
                                            $student_img = $coursereview_value['image'];    
                                        }                                        
                                    }else{
                                        if ($coursereview_value['gender'] == 'Female') {
                                            $student_img = "uploads/student_images/default_female.jpg".img_time();
                                        }elseif ($coursereview_value['gender'] == 'Male') {
                                            $student_img = "uploads/student_images/default_male.jpg".img_time();
                                        }else{
                                            $student_img = "uploads/student_images/no_image.png";
                                        }
                                    }
                                ?>
                            <div class="clearfix pt10">  
                                <div class="col-md-4 col-lg-3 col-xs-12 col-sm-6">                        
                                    <div class="reviewer">
                                        <div class="reviewer-img">
                                            <img src="<?php echo base_url(); ?><?php echo $student_img; ?>">
                                        </div>
                                        <div class="review-time">
                                            <div class="re-time">
                                                <?php echo $this->customlib->dateformat($coursereview_value['date']); ?>
                                            </div>
                                            <div class="reviewer-name">
                                                <?php 
                                                    echo $coursereview_value['rating_provider_name'];
                                                
                                                    if($coursereview_value['middlename'] != 'null'){
                                                        echo ' '.$coursereview_value['middlename'];
                                                    }
                                                    if($coursereview_value['lastname'] != 'null'){
                                                        echo ' '.$coursereview_value['lastname'];
                                                    }
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                <!--./reviewer-->
                                </div>
                                <!--./col-md-4-->
                                <div class="col-md-8 col-lg-9 col-xs-12 col-sm-6">
                                    <ul class="rating ptmd10">
                                    <?php if ($coursereview_value['rating'] != 0 || $coursereview_value['rating'] != '') {?>
                                        <?php for ($i = 1; $i <= 5; $i++) {?>
                                        <li class="fa fa-star disable" <?php if ($coursereview_value['rating'] >= $i) {?> style="color:orange;"<?php }?>></li>
                                        <?php }?>
                                    <?php }?>
                                    </ul>
                                    <p><?php echo $coursereview_value['review']; ?></p>
                                </div>
                                <!--./col-md-8-->
                            </div>
                            <?php } ?>                             
                            </div>
                            <div class="row pt10 pb10">
                                <div class="col-md-12 col-lg-12"></div>
                            </div>           
                        </div><!--./row-->             
                    </div><!--./cardbody-->
                </div><!--./card-->
            <?php }?>
            </div><!--./col-lg-9-->
            <div class="col-lg-3 col-md-3 col-sm-12 coursesidebar">
                <div class="shadow-1">
                    <?php if (!empty($coursesList['course_url'])) {  ?>
                    <div class="">
                        <div class="course-imgplay">
                            <?php if ($coursesList['course_provider'] == "html5") {?>
                                <video id="videoPlayer" controls>
                                    <source src="<?php echo $coursesList['course_url']; ?>" type="video/mp4">
                                </video>                
                            <?php } elseif ($coursesList['course_provider'] == "youtube") {?>
                                <iframe width="100%" src="//www.youtube.com/embed/<?php echo $coursesList['video_id']; ?>" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen=""></iframe>                
                            <?php } elseif ($coursesList['course_provider'] == "vimeo") {?>                            
                                <iframe src="<?php echo $coursesList['course_url']; ?>" width="100%" frameborder="0" allow="autoplay; fullscreen" allowfullscreen></iframe>                                 
                            <?php } elseif ($lesson['video_provider'] == "s3_bucket") {?>                             
                                <video controls width="100%">
                                    <source src="<?php echo $lesson['s3_url'] ?>">
                                </video>                                 
                            <?php }?>
                        </div>
                    <?php } else { ?>
                        <div class="course-imgplay">
                            <img src="<?php echo base_url(); ?>uploads/course/course_thumbnail/<?php echo $coursesList['course_thumbnail']; ?>" class="img-responsive">             
                        </div>                        
                    <?php }?>                    
                        <div class="proinner">
                            <div class="course-around">                                
                                <?php

                                    $free_course    = $coursesList['free_course'];
                                    $discount       = $coursesList['discount'];
                                    $price          = $coursesList['price'];                                  

                                    if ($coursesList['discount'] != '0.00') {                        
                                        $discount = $coursesList['price'] - (($coursesList['price'] * $coursesList['discount']) / 100);                        
                                    }

                                    if (($coursesList["free_course"] == 1) && ($coursesList["price"] == '0.00')) {
                        
                                        $price = 'Free';
                        
                                    } elseif (($coursesList["free_course"] == 1) && ($coursesList["price"] != '0.00')) {
                        
                                        if ($coursesList['price'] > '0.00') {                    
                                            $courseprice =  amountFormat($coursesList['price']);                    
                                        } else {                    
                                            $courseprice = '';                    
                                        }
                        
                                        $price = $this->lang->line('free')." <span><del><small>" . $currency_symbol . '' .$courseprice . '</small></del></span>';
                        
                                    } elseif (($coursesList["price"] != '0.00') && ($coursesList["discount"] != '0.00')) {
                        
                                        $discount = amountFormat($discount);
                        
                                        if ($coursesList['price'] > '0.00') {                    
                                            $courseprice = $currency_symbol . '' . amountFormat($coursesList['price']);                
                                        } else {                    
                                            $courseprice = '';                    
                                        }
                        
                                        $price = $currency_symbol . '' . $discount . ' <span><del><small>' . $courseprice . '</small></del></span> ';
                        
                                    } else {                    
                                        $price = $currency_symbol . '' . amountFormat($coursesList['price']);                    
                                    }                    
                                ?>
                                <div class="current-price"><?php echo $price; ?></div>                     
                                
                                <?php if($coursesList['free_course']==1){ ?>
                               
                                        <a href="#" class="btn btn-buygreen lesson_ID mt10 mb20 full-width" data-toggle="modal" lesson-data="<?php echo $coursesList['id']; ?>"><?php echo $this->lang->line('start_lesson'); ?></a>

                                    <?php 
                                        }else{

                                        if($coursesList['paidstatus'] == '1'){ ?>

                                            <a href="#" class="btn btn-buygreen lesson_ID mt10 mb20 full-width" lesson-data="<?php echo $coursesList['id']; ?>"><?php echo $this->lang->line('start_lesson'); ?></a>

                                        <?php }else{ 
                              
                                        if (in_array($coursesList['id'], array_column($cart_data, 'id'))) {
                              
                                    ?>                          
                                        <button class="purchasedbtn" type="button" onclick="addtocartfromcoursedetails('<?php echo $coursesList['id'] ?>')"><i class="fa fa-shopping-cart"></i> <?php echo $this->lang->line('added_to_cart'); ?></button>
                                    
                                        <?php }else{ ?>

                                            <button class="purchasedbtn" type="button" onclick="addtocartfromcoursedetails('<?php echo $coursesList['id'] ?>')"><i class="fa fa-shopping-cart"></i> <?php echo $this->lang->line('add_to_cart'); ?></button>
                                      
                                        <?php } } ?> 
                                    <?php }  ?>                                
                                
                                <h5><?php echo $this->lang->line('includes'); ?> :</h5>
                                <ul class="listwithi">
                                    <?php if (!empty($total_hour_count) && $total_hour_count != '00:00:00') {?>        
                                    <li><i class="fa fa-clock-o"></i><?php echo $total_hour_count . " " . $this->lang->line('hrs'); ?> <?php echo $this->lang->line('on_demand_videos'); ?> </li> <?php }?>
                                    <?php if ($total_lesson != '' && $total_lesson != '0') {?> 
                                    <li><i class="fa fa-play-circle"></i><?php echo $this->lang->line('lesson') . ": " . $total_lesson; ?></li> <?php }?>
                                    <li><i class="fa fa-compass"></i><?php echo $this->lang->line('full_lifetime_access'); ?> </li>
                                    <li><i class="fa fa-mobile"></i><?php echo $this->lang->line('access_on_mobile_and_tv'); ?> </li>
                                </ul>
                            </div>
                        </div>
                    <!--./proinner-->
                    </div>
                    
                    <br/>
                <!--./course-box-->
                </div>
            </div><!--./col-lg-3-->
        </div><!--./row-->
    </div><!--./container-->
</div><!--./row-->