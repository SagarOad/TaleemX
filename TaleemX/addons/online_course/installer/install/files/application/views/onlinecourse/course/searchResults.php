<?php
$currency_symbol = $this->customlib->getSchoolCurrencyFormat();
$checkLogin      = $this->customlib->checkUserLogin();
$wishlist        = array();
if ($checkLogin == true) {
    $logindata        = $this->session->userdata();
    $login_id         = $logindata['user']['id'];
    $result           = $this->customlib->getUserData();
    $wishlist         = $this->customlib->getUserWishlist($result['id']);
    $purchased_course = $this->customlib->getPurchasedCourse($result['id']);
}
$cart_data = array();
if ($this->session->has_userdata('cart_data')) {
    $cart_data = $this->session->userdata('cart_data');
}
?>
<div class="row" id="products">

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
                if (!empty($cvalue['discount'])) {
                    $discount = $cvalue['price'] - (($cvalue['price'] * $cvalue['discount']) / 100);
                }
                if (($cvalue["free_course"] == 'yes') && (empty($cvalue["price"]))) {
                    $price      = 'Free';
                    $pricevalue = 0;
                } elseif (($cvalue["free_course"] == 'yes') && (!empty($cvalue["price"]))) {
                    $price      = "Free <span><del>" . $this->customlib->getSchoolCurrencyFormat.$cvalue['price'] . '</del></span>';
                    $pricevalue = 0;
                } elseif (!empty($cvalue["price"]) && (!empty($cvalue["discount"]))) {
                    $discount   = number_format((float) $discount, 2, '.', '');
                    $price      = $this->customlib->getSchoolCurrencyFormat($discount) . ' <span><del>' . $this->customlib->getSchoolCurrencyFormat.$cvalue['price'] . '</del></span> ';
                    $pricevalue = $discount;
                } else {
                    $price      = $this->customlib->getSchoolCurrencyFormat.$cvalue['price'];
                    $pricevalue = $cvalue['price'];
                }
                ?>
               <div class="<?php echo $class ?>">
                <div class="product-item">
                  <div class="girdthumbnail">
                    <div class="product-img">
                      <a href="<?php echo base_url() . $cvalue["url"] ?>"><img src="<?php echo $thumbnail; ?>"  /></a>
                    </div>
                    <div class="proinner caption">
                        <a href="<?php echo base_url() . $cvalue["url"] ?>"><h5><?php echo $cvalue['title'] ?></h5></a>
                        <p class="proinner-discription"><?php echo $cvalue['description'] ?></p>
                        <p class="authers">
                            <span><?php echo $this->lang->line('last') . " " . $this->lang->line('updated') ?>: <?php echo date($this->customlib->getSchoolDateFormat(), strtotime($cvalue["updated_at"])) ?></span><em> <?php echo $this->lang->line('by') ?></em><a href="#"><?php echo $cvalue['name'] . ' ' . $cvalue['surname']; ?>s
                     
                            <form method="post" id="authordata" action="<?php echo base_url() . "home/author" ?>">
                                <input type="hidden" id="authorid" name="student_id" value="<?php echo $cvalue['student_id'] ?>">
                                <input type="hidden" id="author_isadmin" name="is_admin" value="<?php echo $cvalue['is_admin'] ?>">
                            </form>
                        </p>
                        <p class="courseitext fontbold">
                            <span class="begineer"><span class="cou-badge"><?php echo $cvalue["level"] ?></span> </span><i class="fa fa-play-circle"></i> <span><?php echo $cvalue['lesson_count'] ?> <?php echo $this->lang->line('lessons') ?></span><?php if ($cvalue['hours_count'] != '0:0:0' && $cvalue['hours_count'] != '00:00:00') {?><i class="fa fa-clock-o"></i><span><?php echo $cvalue['hours_count'] ?> <?php echo $this->lang->line('hours') ?></span><?php }?><i class="fa fa-language"></i><span><?php echo ucfirst($cvalue['language']); ?></span>
                        </p>
                        <p>
                            <span class="fontbold"><?php echo $this->lang->line('category') ?>:</span><a href="<?php echo base_url() . "front/course/getcoursebycategory/0/" . $cvalue['slug'] ?>"><?php echo $cvalue["category_title"] ?></a><i class="fas fa-caret-right lastsubicon"></i><a href="#"><?php echo $cvalue["title"] ?></a>
                        </p>
                    </div><!--./proinner-->
                    
                    <div class="captionright">
                        <ul class="rating">
                        <?php
//enter how many stars to enable
                $enable    = $cvalue['rating'];
                $max_stars = 5; //enter maximum no.of stars
                $star_rate = is_int($enable) ? 1 : 0;
                for ($i = 1; $i <= $max_stars; $i++) {
                    ?>
    <?php if (round($enable) == $i && !$star_rate) {?>
            <li class="fa fa-star-half "></li>
    <?php } elseif (round($enable) >= $i) {?>
            <li class="fa fa-star"></li>
    <?php } else {?>
        <li class="fa fa-star disable"></li>
    <?php }
                }?>
                            <span>
                                <?php if (!empty($newrating[$cvalue['id']])) {echo "(" . $newrating[$cvalue['id']];}?> 
                                <?php 
                                    if (!empty($newrating[$cvalue['id']])) {
                                        if ($newrating[$cvalue['id']] == 1) {
                                            echo $this->lang->line('rating');
                                        } else {
                                            echo $this->lang->line('ratings');
                                        }
                                    echo ')';
                                }?>
                            </span>
                            <div class="ptsales">
                                <?php echo $cvalue['course_sale']; ?> <?php echo $this->lang->line('sales') ?>
                            </div>
                        </ul>
                  <div class="pt-5">
                    <div class="pull-left pricesell">
                      <div class=""><?php echo $price ?></div>
                    </div>
                      <div class="coursebtn pull-right mt0">
                        <?php
if ($checkLogin == true) {
                    $wishlist_allow = 'no';
                    $course_allow   = 'no';
                    if (in_array($cvalue['id'], array_column($wishlist, 'course_id'))) {
                        $wishlistid     = array_search($cvalue['id'], array_column($wishlist, 'course_id'));
                        $wishlist_added = 'yes';
                    } else {
                        $wishlist_added = 'no';
                    }
                    if (in_array($cvalue['id'], array_column($purchased_course, 'id'))) {
                        $course_allow   = 'yes';
                        $wishlist_allow = 'yes';
                    } else {
                        $course_allow   = 'no';
                        $wishlist_allow = 'no';
                    }
                    if ($cvalue["is_admin"] != 'yes') {
                        if (($login_id == $cvalue["student_id"])) {
                            $course_allow   = 'yes';
                            $wishlist_allow = 'yes';
                            $wishlist_added = 'no';
                        }
                    }
                } else {
                    $course_allow   = 'no';
                    $wishlist_added = 'no';
                    $wishlist_allow = 'no';
                }
                if ($wishlist_allow != 'no') {?>
                  <?php } else {
                    if ($wishlist_added != 'yes') {
                        ?>
                        <a href="#" onclick="addtowishlist('<?php echo $cvalue["id"] ?>','<?php echo $pricevalue ?>')" class="product-item-heart"><i class="fa fa-heart"></i></a>
                     <?php
} else {?>
                      <a href="#" onclick="deletewishlist('<?php echo $wishlist[$wishlistid]['wishlistid'] ?>')" class="product-item-heart"><i class="fa fa-heart text-red"></i></a>
                    <?php }}
                if ($course_allow != 'no') {
                } else {
                    ?>
                        <?php if ($cvalue['free_course'] == 'yes') { ?>
                            <button class="ptaddtocart" type="button" onclick="enroll('<?php echo $cvalue["id"] ?>','<?php echo $pricevalue ?>')" ><i class="fa fa-shopping-cart"></i> <?php echo $this->lang->line('enroll_now') ?></button>
                        <?php } else {
                        if (in_array($cvalue['id'], array_column($cart_data, 'course_id'))) {
                            ?>
                            <button class="ptaddtocart" type="button"><i class="fa fa-shopping-cart"></i> <?php echo $this->lang->line('added_to_cart') ?></button>
                        <?php } else {
                            ?>
                            <button class="ptaddtocart" type="button" onclick="addtocart('<?php echo $cvalue["id"] ?>','<?php echo $pricevalue ?>')" ><i class="fa fa-shopping-cart"></i> <?php echo $this->lang->line('add_to_cart') ?></button>
                        <?php }
                            }}
                        ?>
                        </div>
                    </div><!--./proinner-->
                </div><!--./captionright-->
            </div><!--./girdthumbnail-->
        </div><!--./product-item-->
    </div><!--./col-md-2-->
    <?php }}}}?>
</div><!--./row-->
            
<script type="text/javascript">
    (function ($) {
        "use strict";
            $(document).ready(function() {
                $('#list').click(function(event){event.preventDefault();$('#products .item').addClass('list-group-item');classid = 'item col-md-4 col-sm-6 list-group-item';});
                $('#grid').click(function(event){event.preventDefault();$('#products .item').removeClass('list-group-item');$('#products .item').addClass('grid-group-item');classid = 'item col-md-4 col-sm-6 grid-group-item';});
            });
    })(jQuery);
</script>