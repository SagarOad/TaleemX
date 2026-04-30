<?php

$currency_symbol = $this->customlib->getSchoolCurrencyFormat();
$cart_data = $this->cart->contents();
 
 if(!empty($cart_data)){

      foreach ($cart_data as $key => $value)
    {
      $cart_data['course_id'][] = $value['id'];
    }
 }
 
$checkLogin      = $this->customlib->checkUserLogin();
?> 

<div id="coursebtnaddModal" class="modal" role="dialog">
    <div class="modal-dialog video-dialogfull">
        <!-- Modal content-->
        <div class="modal-content video-contentfull modal-content-no-shadow bgblack">
            <div class="modalbgzindex">
                <button type="button" onclick="closevideo()"  class="close videoclosebtn" data-dismiss="modal">&times;</button>
            </div>
            <div id="ajaxdata">
            </div><!--Ajax Data -->
        </div><!--./modal-content-->
    </div><!--./modal-dialog-->
</div><!--./coursebtnaddModal-->

<div class="row">  
  <div class="container spaceb50">
  	<div class="row">
  		      <div class="col-md-12">
                <div class="refineheader boxborder-bottom">
                  <div class="row">
                  <div class="col-lg-3 col-md-3 col-sm-12">
                    <h4 class="refinetext filterhide"><a href="javascript:void(0)" class="dshow"><i class="fa fa-times"></i><?php echo $this->lang->line('filter_refine'); ?> </a>
                      <a href="javascript:void(0)" class="dhide displaynone"><i class="fa fa-sliders"></i> <?php echo $this->lang->line('filter_refine'); ?></a></h4>                      
                      <a href="<?php echo base_url(); ?>course" id="reset" class="resettext"><i class="fa fa-refresh"></i>   <?php echo $this->lang->line('reset'); ?></a>
                  </div><!--./col-md-8-->
                      <div class="col-lg-9 col-md-9 col-sm-12">
                        <div class="course-right">
                          <ul class="nav nav-tabs border0 courselist-tabs">
                              <li><a href="#" class="listbtnactive" id="grid"><i class="fa fa-th"></i><?php echo $this->lang->line('card'); ?></a></li>
                              <li><a href="#" class="listbtnactive" id="list"><i class="fa fa-list"></i><?php echo $this->lang->line('list'); ?></a></li>
                          </ul>
                          <div class="btn-group btn-group-sm searchfilterbtn">
                             <button type="button" class="btn btn-default sortvalue" data-sort="bestsell" id="bestsell"><?php echo $this->lang->line('best_seller'); ?></button>
                            <button type="button" class="btn btn-default sortvalue" data-sort="newest" id="newest"><?php echo $this->lang->line('newest'); ?></button>
                            <button type="button" class="btn btn-default sortvalue" data-sort="bestrated" id="bestrated" > <?php echo $this->lang->line('best_rated'); ?></button>
                            <button type="button" class="btn btn-default sortvalue" id="price-desc" data-sort="price-desc">  <?php echo $this->lang->line('price'); ?> <i class="fa fa-long-arrow-down"></i><i class="fa fa-long-arrow-up"></i></button> 
                          </div>
                       </div><!--./course-right-->
                    </div><!--./col-md-8-->
                 </div><!--./row-->
              </div><!--./course-header-->
          </div><!--./col-md-12-->
    <div class="refine-categ-header">       
  		<div class="col-lg-3 col-md-3 col-sm-6 filterleft">
          <div class="sidebarlists">
            <div class="sidebarhide">

              <div class="filter-list">
                    <div class="sidecourse-title"><a data-toggle="collapse" data-target="#category"><?php echo $this->lang->line('category'); ?><i class="fa fa-angle-up"></i></a>
                  </div><!-- ./sidecourse-title -->
                   <div class="catefilterscroll collapse in" id="category">
                       <div class="catecheck">
                          <div class="form-check"> 
           
                          </div>                         
                           <ul class="rating">
                             <?php
if (!empty($categorylist)) { 

   foreach ($categorylist as $key => $categorylist_value) {
  ?>

   <div class="form-check">
    <label>
      <input type="radio" name="category_radio" data-title="category" data-value="<?php echo 'category' ?>" class="searchradioresult" data-all='{"title": "category","searchfield": "online_courses.category_id", "searchvalue": "<?php echo $categorylist_value['id']; ?>"}'> <span class="label-text"> <?php echo $categorylist_value['category_name']; ?></span></label><span class="pull-right"><?php echo $categorylist_value['categorycount']; ?></span>
  </div>
                        <?php } }?>
                           </ul>
                      </div>
                      <!-- ./catechek -->
                    </div><!-- ./catefilterscroll-->
                 </div><!--./filter-list -->

              <div class="filter-list">
                <div class="sidecourse-title">
                  <a data-toggle="collapse" data-target="#coursecol"><?php echo $this->lang->line('search_by_course'); ?> <i class="fa fa-angle-up"></i></a>
                </div><!-- ./sidecourse-title -->
                <div id="coursecol" class="collapse in catefilterscroll pr-0">
                  <div class="priceblock">
                      <input type="text" id="search_text" class="priceinput" placeholder= "<?php echo $this->lang->line('enter_keyword'); ?>" />
                      
                      <button type="button" placeholder= "<?php echo $this->lang->line('enter_keyword'); ?>" id="search_by_course" class="prianglebtn"><i class="fa fa-angle-right"></i></button>
                  </div><!--./priceblock-->
                </div><!-- ./catefilterscroll-->
              </div><!--./filter-three -->
              
              <div class="filter-list">
                <div class="sidecourse-title">
                  <a data-toggle="collapse" data-target="#pricecol"><?php echo $this->lang->line('price_range'); ?> <i class="fa fa-angle-up"></i></a>
                </div><!-- ./sidecourse-title -->
                <div id="pricecol" class="collapse in catefilterscroll pr-0">
                  <div class="priceblock">
                   <span class="relative"><span class="pricesign"><?php echo $currency_symbol ?></span><input type="text" id="pricestartrange" class="priceinput" placeholder="" /></span>
                      <span class="pridash">-</span>
                       <span class="relative"><span class="pricesign"><?php echo $currency_symbol ?></span><input type="text" id="priceendrange" class="priceinput" placeholder="" /></span>
                      <button type="button" id="filterprice" class="prianglebtn"><i class="fa fa-angle-right"></i></button>
                  </div><!--./priceblock-->
                </div><!-- ./catefilterscroll-->
              </div><!--./filter-three --> 

              <div class="filter-list">
                <div class="sidecourse-title">
                  <a data-toggle="collapse" data-target="#SoftwareVersion"><?php echo $this->lang->line('price'); ?><i class="fa fa-angle-up"></i></a>
                </div><!-- ./sidecourse-title -->

                <div id="SoftwareVersion" class="catefilterscroll collapse in">
                  <div class="catecheck">
                    <?php $filterPrice["countfree"]; if (!empty($filterPrice["countfree"])) {?>
                            <div class="form-check">
                              <label>
                                <input type="checkbox" <?php if ((isset($searchtype)) && ($searchtype == 'free')) {echo 'checked';}?> name="check" data-title="Price" data-value="<?php echo 'free' ?>" class="searchresult" data-all='{"title": "Price","searchfield": "online_courses.free_course", "searchvalue": "free"}'> <span class="label-text"><?php echo $this->lang->line('free'); ?></span>
                              </label><span class="pull-right"><?php echo $filterPrice['countfree'] ?></span>
                            </div><!--./form-check -->
                       <?php }?>
                       <?php if (!empty($filterPrice["countpaid"])) {?>
                            <div class="form-check">
                              <label>
                                <input type="checkbox" name="check" data-title="Price" data-value="<?php echo 'paid' ?>" class="searchresult" data-all='{"title": "Price","searchfield": "online_courses.free_course", "searchvalue": "paid"}'> <span class="label-text"><?php echo $this->lang->line('paid'); ?></span>
                              </label><span class="pull-right"><?php echo $filterPrice['countpaid'] ?></span>
                            </div><!--./form-check -->
                   <?php }?>
                  </div>
                </div><!-- ./catefilterscroll-->
              </div><!--./filter-list -->            
              </div><!--./filter-list -->

              <div class="filter-list">
                    <div class="sidecourse-title"><a data-toggle="collapse" data-target="#Sales"><?php echo $this->lang->line('sales'); ?><i class="fa fa-angle-up"></i></a>
                  </div><!-- ./sidecourse-title -->
                   <div class="catefilterscroll collapse in" id="Sales">
                       <div class="catecheck">
                        <?php if (!empty($filterSale["no_sale"])) {?>
                        <div class="form-check">
                            <label>
                              <input type="checkbox" data-title="Sales" data-value="<?php echo '0' ?>" class="searchresult" data-all='{"title": "Sales","searchfield": "sales", "searchvalue": "<?php echo '0' ?>"}' name="check"> <span class="label-text"><?php echo $this->lang->line('no_sale'); ?></span>
                            </label><span class="pull-right"><?php echo $filterSale["no_sale"] ?></span>
                        </div><!--./form-check -->
                        <?php }?>
                          <?php if (!empty($filterSale["low_sale"])) { ?>
                        <div class="form-check">
                            <label>
                              <input type="checkbox" data-title="Sales" data-value="<?php echo 'low' ?>" class="searchresult" data-all='{"title": "Sales","searchfield": "sales", "searchvalue": "<?php echo "low" ?>"}' name="check"> <span class="label-text"><?php echo $this->lang->line('low'); ?></span>
                            </label><span class="pull-right"><?php echo $filterSale["low_sale"] ?></span>
                        </div><!--./form-check -->
                      <?php } ?>
                        <?php if (!empty($filterSale["medium_sale"])) {?>
                        <div class="form-check">
                            <label>
                              <input type="checkbox" data-title="Sales" data-value="<?php echo "medium" ?>" class="searchresult" data-all='{"title": "Sales","searchfield": "sales", "searchvalue": "<?php echo "medium" ?>"}' name="check"> <span class="label-text"><?php echo $this->lang->line('medium'); ?></span>
                            </label><span class="pull-right"><?php echo $filterSale["medium_sale"] ?></span>
                        </div><!--./form-check -->
                       <?php }?>
                       <?php if (!empty($filterSale["high_sale"])) {?>
                        <div class="form-check">
                            <label>
                              <input type="checkbox" data-title="Sales" data-value="<?php echo "high" ?>" class="searchresult" data-all='{"title": "Sales","searchfield": "sales", "searchvalue": "<?php echo "high" ?>"}' name="check"> <span class="label-text"><?php echo $this->lang->line('high'); ?></span>
                            </label><span class="pull-right"><?php echo $filterSale["high_sale"] ?></span>
                        </div><!--./form-check -->
                      <?php }?>
                      
            <?php if (!empty($filterSale["top"])) {?>
                        <div class="form-check">
                            <label>
                              <input type="checkbox" data-title="Sales" <?php if ((isset($searchtype)) && ($searchtype == 'mostsale')) {echo 'checked';}?> data-value="<?php echo "top" ?>" class="searchresult" data-all='{"title": "Sales","searchfield": "sales", "searchvalue": "<?php echo "top" ?>"}' name="check"> <span class="label-text"><?php echo $this->lang->line('top_sellers'); ?></span>
                            </label><span class="pull-right"><?php echo $filterSale["high_sale"] ?></span>
                        </div><!--./form-check -->
            <?php }?>
                      </div><!-- ./catechek -->
                    </div><!-- ./catefilterscroll-->
                 </div><!--./filter-list -->

                 <div class="filter-list">
                    <div class="sidecourse-title"><a data-toggle="collapse" data-target="#Rating"><?php echo $this->lang->line('rating'); ?><i class="fa fa-angle-up"></i></a>
                  </div><!-- ./sidecourse-title -->
                   <div class="catefilterscroll collapse in" id="Rating">
                       <div class="catecheck">
                          <div class="form-check"> 
                            <label>
                            <input type="radio" name="radio" data-title="rating" data-value="<?php echo '0' ?>" class="searchradioresult" data-all='{"title": "rating","searchfield": "rating", "searchvalue": "<?php echo '0' ?>"}'> <span class="label-text">
                            
                            <?php echo $this->lang->line('show') . " " . $this->lang->line('all'). " " . $this->lang->line('course'); ?>                            
                            </span></label><span class="pull-right"><?php echo count($coursecount); ?> </span>
                          </div>                         
                           <ul class="rating">
                             <?php
if (!empty($filterRating)) {
    $n = 0;
    for ($i = 0; $i < 5; $i++) {
        # code...
        if (!empty($filterRating[$i + 1])) {
            ?>
                           <div class="form-check">
                            <label>
                              <input type="radio" name="radio" data-title="rating" data-value="<?php echo $i + 1 ?>" class="searchradioresult" data-all='{"title": "rating","searchfield": "rating", "searchvalue": "<?php echo $i + 1 ?>"}'> <span class="label-text">
                              <?php for ($j = 0; $j < 5; $j++) {
                          if ($i >= $j) {
                           
                              ?>
                               <li class="fa fa-star"></li>
                                <?php } else {?>
                                  <li class="fa fa-star disable"></li>
                                <?php }}?>  
                              </span>
                            </label><span class="pull-right"><?php echo $filterRating[$i + 1]; ?></span>
                          </div>
                        <?php }}}?>
                           </ul>
                      </div>
                      <!-- ./catechek -->
                    </div><!-- ./catefilterscroll-->
                 </div><!--./filter-list -->
            </div><!--./sidebarhide-->
          </div><!--./sidebarlists-->   
      </div><!--./col-lg-3-->
      <div id="resultdiv">
  		<div class="col-lg-9 col-md-9 col-sm-6 filterright">
        
          <?php if (!empty($new_courselist)) { ?>
      			<div class="row " id="products">
             <?php foreach ($new_courselist as $new_courselist_value) {              
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
                        <a href="<?php echo base_url() . $new_courselist_value["url"] ?>"><img class="group list-group-image img-responsive" src="<?php echo base_url(); ?>uploads/course/course_thumbnail/<?php echo $new_courselist_value['course_thumbnail']; ?>"></a>
                      </div>
                      <div class="proinner caption column-height-equal">
                        <a href="<?php echo base_url() . $new_courselist_value["url"] ?>"><h5><?php echo $new_courselist_value['title']; ?></h5></a>
                        <div class="course-caption"><?php echo $new_courselist_value['description']; ?></div>          
                        
                        <p class="authers"><span class="fontbold"><?php echo $this->lang->line('category'); ?></span> <?php echo $new_courselist_value['category_name']; ?></p>
                        
                        <p class="authers"><span class="fontbold"><?php echo $this->lang->line('last_updated'); ?></span> <?php echo date($this->customlib->getSchoolDateFormat(), strtotime($new_courselist_value['updated_date'])); ?></p>                        
                          
                        <p class="courseitext">
                  	    </span><?php if (!empty($new_courselist_value['total_lesson'])) { ?><i class="fa fa-play-circle"></i> <span> <?php echo  $new_courselist_value['total_lesson'].' '. $this->lang->line('lesson'); } ?></span><?php if (!empty($new_courselist_value['total_hour_count']) && $new_courselist_value['total_hour_count'] != '00:00:00') { ?><i class="fa fa-clock-o"></i><span><?php echo $new_courselist_value['total_hour_count'] . ' ' . $this->lang->line('hrs');  ?></span> <?php }  ?> </p>
                       
                    </div><!--./proinner-->

                    <?php

        $free_course    = $new_courselist_value['free_course'];
        $discount       = $new_courselist_value['discount'];
        $discount_price = '';
        $price          = '';
        if ($new_courselist_value['discount'] != '0.00') {

            $discount = $new_courselist_value['price'] - (($new_courselist_value['price'] * $new_courselist_value['discount']) / 100);

        }

        if (($new_courselist_value["free_course"] == 1) && ($new_courselist_value["price"] == '0.00')) {

            $price = 'Free';

        } elseif (($new_courselist_value["free_course"] == 1) && ($new_courselist_value["price"] != '0.00')) {

            if ($new_courselist_value['price'] > '0.00') {

                $courseprice =  amountFormat($new_courselist_value['price']);

            } else {

                $courseprice = '';

            }

            $price = $this->lang->line('free')." <span><del>" . $currency_symbol . '' .$courseprice . '</del></span>';

        } elseif (($new_courselist_value["price"] != '0.00') && ($new_courselist_value["discount"] != '0.00')) {

            $discount = amountFormat($discount);

            if ($new_courselist_value['price'] > '0.00') {

                $courseprice = $currency_symbol . '' . amountFormat($new_courselist_value['price']);

            } else {

                $courseprice = '';

            }

            $price = $currency_symbol . '' . $discount . ' <span><del>' . $courseprice . '</del></span> ';

        } else {

            $price = $currency_symbol . '' . amountFormat($new_courselist_value['price']);

        }

        ?>
                          <div class="captionright">                 
                       <ul class="rating">                      
                      
                      <?php   if($new_courselist_value['courserating'] !=0 || $new_courselist_value['courserating'] !=''){ ?>
                      
                      <?php
                      
                        $avgRating =  $new_courselist_value['courserating'];                       

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

                        $star_rate = is_int($enable) ? 1 : 0;
                        for ($i = 1; $i <= 5; $i++) { 
                      
                          if (round($enable) == $i && !$star_rate) { ?>
                                  <li class="fa fa-star-half"></li>
                          <?php } elseif (round($enable) >= $i) { ?>
                                  <li class="fa fa-star"></li>
                          <?php } else {?>
                              <li class="fa fa-star disable"></li>
                          <?php }
                          
                        }                        
                      ?>
                      
                      <?php if($new_courselist_value['totalcourserating'] !=0){ echo ' ('.$new_courselist_value['totalcourserating'].' '.$this->lang->line('rating').')'; } ?> 
                      <?php }else{ ?>
                       
                      <?php } ?>         
                    
                    <?php if($new_courselist_value['free_course']==0 && $new_courselist_value['course_sale'] > 0){ ?>
                      <div class="ptsales"><?php echo $new_courselist_value['course_sale']; ?> <?php if($new_courselist_value['course_sale'] == 1){ echo $this->lang->line('sale') ; }else{ echo $this->lang->line('sales') ;} ?></div>
                    <?php } ?>                      
                      
                    </ul>                       
                       
                        <div class="pt-5 align-items-center d-flex justify-content-between flex-direction-sm">
                          <div>
                             <div class="pricesell vh-center"><?php echo $price; ?></div>
                          </div>
                            <div class="coursebtn" id="btn_status_<?php echo $new_courselist_value['id']; ?>">                            
                              <?php if($new_courselist_value['free_course']==1){ ?>
                             
                                <a href="#" class="btn btn-buygreen lesson_ID"  lesson-data="<?php echo $new_courselist_value['id']; ?>"><?php echo $this->lang->line('start_lesson'); ?></a>

                              <?php }else{

                                  if($new_courselist_value['paidstatus'] == '1'){ ?>

                                    <a href="#" class="btn btn-buygreen lesson_ID"  lesson-data="<?php echo $new_courselist_value['id']; ?>"><?php echo $this->lang->line('start_lesson'); ?></a>

                              <?php }else{ 
                              
                                if (in_array($new_courselist_value['id'], array_column($cart_data, 'id'))) {
                              
                              ?>                          
                                  <button class="ptaddtocart" type="button" onclick="addtocart('<?php echo $new_courselist_value['id'] ?>')"><i class="fa fa-shopping-cart"></i><?php echo $this->lang->line('added_to_cart'); ?></button>
                                    
                                 <?php }else{ ?>

                                     <button class="ptaddtocart" type="button" onclick="addtocart('<?php echo $new_courselist_value['id'] ?>')"><i class="fa fa-shopping-cart"></i><?php echo $this->lang->line('add_to_cart'); ?></button>
                                      
                              <?php } } ?>                                 

                            <?php }  ?>                           
                            </div>
                           </div><!--./clear-->
                          </div>
                  </div><!--./girdthumbnail-->
                </div><!--./product-item-->
              </div><!--./col-md-4-->
            <?php } ?>
      			</div><!--./row-->
          <?php }else{?>
            <div class="text-center pt20">
                <img src="<?php echo base_url(); ?>backend/themes/material_pink/images/addnewitem.svg" />
                <p class="text-danger pt-5"><?php echo $this->lang->line('no_record_found'); ?></p>
            </div>
  
          <?php } ?>
        </div>    
  		</div><!--./col-md-9-->
      <div id="pagination">
        <div class="col-lg-12 text-center">
          <?php echo $this->pagination->create_links(); ?>
        </div> 
      </div>
     </div><!--./refine-categ-header--> 
  	</div><!--./row-->
  </div><!--./container-->
</div>  

<script src="<?php echo base_url(); ?>backend/js/jquery.slimscroll.js"></script>
<script type="text/javascript">

  $(document).ready(function() {
      $('#list').click(function(event){event.preventDefault();
        $('#products .item').addClass('list-group-item');
        addclass('list-group-item');
      });
      
      $('#grid').click(function(event){event.preventDefault();
        $('#products .item').removeClass('list-group-item');
        $('#products .item').addClass('grid-group-item');
        addclass('grid-group-item');
      });

  }); 
  
</script>
<script type="text/javascript">
  $(document).ready(function(){
        $(".dshow").click(function(){
          $('.sidebarlists').fadeIn(1000);
          $('.sidebarlists').hide();
          $('.dshow').hide();
          $('.dshow').removeClass('fadeInRightBig').addClass('fadeInLeftBig');
          $('.product-item').removeClass('animated fadeOut faster').addClass('animated fadeIn faster');
          $('.dhide').show();
          $(".filterright").css({"width": "100%"});
          $('.item').removeClass('col-lg-4').addClass('col-md-3');
        });

        $(".dhide").click(function(){
          $('.sidebarlists').fadeOut(1000);
          $('.sidebarlists').show();
          $('.dshow').show();
          $('.dhide').hide();
          $('.product-item').addClass('animated fadeOut faster').removeClass('animated fadeIn faster');
          $('.dshow').removeClass('animated fadeIn faster').addClass('animated fadeOut faster');
          $('.item').addClass('col-lg-4').removeClass('col-md-4');
          $(".filterright").css({"width": "78%"});
        });
   });
</script>
<script>
var array = Array();
var testarray = Array();
var x = 0;

   $(".searchradioresult").click(function(){
  $(".sortvalue").removeClass("active");
  var dataall = $(this).data("all");
  testarray[0] = dataall ;
  
  $.ajax({
            type:'POST',
            url: '<?php echo base_url(); ?>course/filterRecords/',
            data:{'searchdata':testarray},
            success: function (res) {              
              $("#resultdiv").html(res);
              $("#pagination").addClass('hide');
            }
        });
  });

   function add_element_to_array(data)
{
 array[x] = data;
 x++;
}

function delete_element_to_array(i,n)
  {
    for (var y=0; y<array.length; y++)
    {
      if(typeof array[y] != 'undefined') {
        if((array[y].title == n) && (array[y].searchvalue == i) ){
          array.splice(y,1);
        }
      }
    }
  }


$(".searchresult").click(function(){
$(".sortvalue").removeClass("active");
  var e = "<hr/>";
    var dataall = $(this).data("all");
  var datavalue = $(this).attr("data-value");
  var datatitle = $(this).attr("data-title");
            if($(this).prop("checked") == true){
                  add_element_to_array(dataall);
             }else{
              delete_element_to_array(datavalue,datatitle);
             }

    console.log(array);
       for (var y=0; y<array.length; y++)
   {
     e += "Element " + y + " = " + array[y] + "<br/>";
   }
        $("#resultdiv").html("<center><img src='"+base_url+"backend/images/loading.gif' /></center>");
         
        $.ajax({
            type:'POST',
            url: '<?php echo base_url(); ?>course/filterRecords/',
            data:{'searchdata':array},
            success: function (res) {
              
              if(res != "<br>") {
                $("#resultdiv").html(res);          
                $("#pagination").addClass('hide');
                $("#resultdiv").show();
              }else{
                $("#search_by_course").trigger( "click" ); 
              }
              
            }
        });
  });

  $("#filterprice").click(function(){
  $(".sortvalue").removeClass("active");
  var startrange = $("#pricestartrange").val();
  var endrange = $("#priceendrange").val();
  
  $.ajax({
            type:'POST',
            url: '<?php echo base_url(); ?>course/filterRecordsByPrice/',
            data:{'startrange':startrange,'endrange':endrange},
            success: function (res) {
            $("#resultdiv").html(res);
            $("#pagination").addClass('hide');
            }
        });
  });
</script>
<script type="text/javascript">
   $(".sortvalue").click(function(){
   
    $(".sortvalue").removeClass("active");
    $(this).addClass("active");
  var datavalue = $(this).attr("data-sort"); 
      
      if(datavalue == 'price-desc'){
        $("#price-desc").attr("data-sort",'price-asc');
      }
       if(datavalue == 'price-asc'){
        $("#price-desc").attr("data-sort",'price-desc');
      }
     var coursedata = "<?php echo sizeof($new_courselist) ?>";
     var pricestartrange = $("#pricestartrange").val();
     var priceendrange = $("#priceendrange").val();
    $("#resultdiv").html("<center><img src='"+base_url+"backend/images/loading.gif' /></center>");
    $.ajax({
          type:'POST',
          url: '<?php echo base_url(); ?>course/sortRecords/',
          data:{'searchradio':testarray,'searchcheck':array,'sortdata':datavalue,'pricestartrange':pricestartrange,'priceendrange':priceendrange,'coursedata':coursedata},
          success: function (res) {
             
            $("#resultdiv").html(res);
            $("#pagination").addClass('hide');
          }
      }); 
    });
    
    $(document).ready(function(){
      $('.modal').on('hidden.bs.modal', function () {
        $(this).find('form').trigger('reset');     
      });
    });
</script>