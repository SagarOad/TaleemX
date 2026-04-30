<?php $this->load->view('layout/course_css.php'); ?>
<?php
$currency_symbol = $this->customlib->getSchoolCurrencyFormat();
$result =   $this->customlib->getLoggedInUserData();

?>
<div class="content-wrapper">
  <section class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="box box-primary">
          <div class="box-header with-border pb0">
            <h3 class="box-title header_tab_style"><i class="fa fa-search"></i><?php echo $this->lang->line('course_list'); ?> </h3>
            <div class="nav-tabs-custom border0 navcustom-right posiright5 tab mb0">
              <ul class="nav nav-tabs pull-right">
                <li><a href="#tab_1" data-toggle="tab" class="tablinks miusttop10" title="<?php echo $this->lang->line('list_view'); ?>" onclick="openCourse(event, 'course_card_tab')" ><i class="fa fa-list"></i></a></li>
                <li ><a href="#tab_2"  data-toggle="tab" class="tablinks miusttop10" title="<?php echo $this->lang->line('card_view'); ?>"  onclick="openCourse(event, 'course_detail_tab')"><i class="fa fa-th"></i></a></li>               
              </ul>
            </div>
          </div>
      <div id="course_card_tab" class="tabcontent"> 
          <div class="nav-tabs-custom border0 navnoshadow">
            <div class="tab-content">
              <div class="download_label"><?php echo $this->lang->line('course_list') ; ?></div>
              <div class="tab-pane active table-responsive no-padding" id="tab_1">
                 <table class="table table-striped table-bordered table-hover course-list course-table" data-export-title="<?php echo $this->lang->line('course_list'); ?>">
                  <thead>
                    <tr>
                      <th class="white-space-nowrap"><?php echo $this->lang->line('title'); ?></th>
                      <?php  $role   =   $result["role"]; if($role=='student'){ ?>
                      <th class="white-space-nowrap"><?php echo $this->lang->line('class'); ?></th> 
                      <?php } ?>
                      <th class="white-space-nowrap"><?php echo $this->lang->line('section'); ?></th>    
                      <th class="white-space-nowrap"><?php echo $this->lang->line('lesson'); ?></th>
                      <?php  if($this->customlib->get_online_course_curriculam_status("online_course_quiz")==""){ ?>     
                      <th class="white-space-nowrap"><?php echo $this->lang->line('quiz'); ?></th>
                      <?php } ?>
                      <?php  if($this->customlib->get_online_course_curriculam_status("online_course_exam")==""){ ?> 
                      <th class="white-space-nowrap"><?php echo $this->lang->line('exam'); ?></th>
                      <?php } ?>
                      <?php  if($this->customlib->get_online_course_curriculam_status("online_course_assignment")==""){ ?> 
                      <th class="white-space-nowrap" ><?php echo $this->lang->line('assignment'); ?></th>
                      <?php } ?>
                      <th class="white-space-nowrap"><?php echo $this->lang->line('total_hour_count'); ?></th>
                      <th class="white-space-nowrap text-right"><?php echo $this->lang->line('price').' ('.$currency_symbol.')'; ?></th>
                      <th class="white-space-nowrap text-right"><?php echo $this->lang->line('current_price').' ('.$currency_symbol.')'; ?></th>
                      <th class="white-space-nowrap text-right"><?php echo $this->lang->line('last_updated'); ?></th>
                      <th class="text-right noExport white-space-nowrap text-right"><?php echo $this->lang->line('action'); ?></th>
                    </tr>
                  </thead>
                  <tbody>
                  </tbody>
                </table>
              </div>
            </div>
          </div>   
      </div>
<div id="course_detail_tab" class="tabcontent">
  <section class="content">
    <div class="row">
      <div class="col-md-12">
          <div class="row flex-row">
            <?php             
              
            if (!empty($new_courselist)) {
            foreach ($new_courselist as $new_courselist_value) {
               
            $role   =   $result["role"];
            ?>
        
            <div class="col-lg-3 col-md-4 col-sm-6 col-xs-12">
              <div class="coursebox">              
              <a href="#" class="coursedetail text-dark" data-toggle="modal" data-target="#coursedetailmodal" data-id="<?php echo $new_courselist_value['id']; ?>">
                <div class="coursebox-img">
                  <img src="<?php echo base_url(); ?>uploads/course/course_thumbnail/<?php echo rawurlencode($new_courselist_value['course_thumbnail']); ?>">

                  <?php if($role !='guest'){ ?>
                  <div class="author-block author-wrap">
				    
					   <?php if (!empty($new_courselist_value['image']) && ($role == 'student' || $role == 'parent')) {?>
                        <img class="img-circle" src="<?php echo base_url(); ?>uploads/staff_images/<?php echo rawurlencode($new_courselist_value['image']); ?>" alt="User Image">
                    <?php } else {
                    if($new_courselist_value['gender']=='Female'){
                        $file= "uploads/staff_images/default_female.jpg";
                    }else{
                        $file ="uploads/staff_images/default_male.jpg";
                    }
                        ?>
                          <img class="img-circle" src="<?php echo base_url(); ?><?php echo $file; ?>" alt="">
                    <?php }?>                
				   
                    <?php if($role == 'student' || $role == 'parent'){ ?>
                      <span class="authorname"><?php echo $new_courselist_value['name']; ?> <?php echo $new_courselist_value['surname']; ?> (<?php echo $new_courselist_value['employee_id']; ?>)</span>
                       <span class="description"><span><?php echo $this->lang->line('last_updated'); ?> </span> <?php echo date($this->customlib->getSchoolDateFormat(), strtotime($new_courselist_value['updated_date']));  ?></span>

                    <?php }else{ ?>
                       <span class="authorname"><br> </span>
                    <?php }?>
                 </div> <?php }  ?>
 
                </div>
                <div class="coursebox-body">
                  <h4><?php echo $new_courselist_value['title']; ?></h4>
                  <div class="course-caption"><?php echo $new_courselist_value['description']; ?></div>                   
                 
                  <div class="classstats">
                    <?php if($role=='student' || $role == "parent"){ 
                     if(isset($new_courselist_value['class'])){ ?>
                      <span class="pr5"> 
                        <i class="fa fa-list-alt"></i><?php echo $this->lang->line('class'); ?>: <?php echo $new_courselist_value['class']; ?>
                      </span>
                    <?php } } ?> 
                    
                     <?php if($role == "guest"){ ?>
                    <span class="pr10">                    
                      <?php echo $this->lang->line('last_updated'); ?>: <?php echo date($this->customlib->getSchoolDateFormat(), strtotime($new_courselist_value['updated_date'])); ?>               
                    </span>
                     <?php } ?>
                     
                    <?php  if (!empty($new_courselist_value['total_lesson'])) {?>
                    <span class="pr5">
                     <i class="fa fa-play-circle"></i>  
                      <?php echo $this->lang->line('lesson') . ' ' . $new_courselist_value['total_lesson']; ?>
                    </span>
                    <?php } ?>

                    <span class="pr5">
                     <?php if (!empty($new_courselist_value['total_hour_count']) && $new_courselist_value['total_hour_count'] != '00:00:00') {?>
                     <?php echo $new_courselist_value['total_hour_count'] . " " . $this->lang->line('hrs');
                     } ?>
                   </span>
                   <br>
               
                    <?php  if (!empty($new_courselist_value['total_exam'])) {?>
                    <span class="pr5 <?php echo $this->customlib->get_online_course_curriculam_status("online_course_exam");?>">
                    <i class="fa fa-rss ftlayer"></i>  
                    <?php echo $this->lang->line('exam') . ' ' . $new_courselist_value['total_exam']; ?>
                    </span>
                    <?php } ?>

                     <?php  if (!empty($new_courselist_value['total_quiz'])) {?>
                    <span class="pr5 <?php echo $this->customlib->get_online_course_curriculam_status("online_course_quiz");?>">
                    <i class="fa fa-question-circle"></i>  
                    <?php echo $this->lang->line('quiz') . ' ' . $new_courselist_value['total_quiz']; ?>
                    </span>
                    <?php } ?>

					<?php  if (!empty($new_courselist_value['total_assignment'])) {?>
					<span class="pr5 <?php echo $this->customlib->get_online_course_curriculam_status("online_course_assignment");?>">
					<i class="fa fa-flask ftlayer"></i>  
					<?php echo $this->lang->line('assignment') . ' ' . $new_courselist_value['total_assignment']; ?>
					</span>
					<?php } ?>
					
					<?php  if (empty($new_courselist_value['total_exam']) && empty($new_courselist_value['total_quiz']) && empty($new_courselist_value['total_assignment'])) {?>					
					<span class="pr5"></span>					
					<?php } ?>				
					
                    
                  </div>
                  <div class="classstats full-width">
                  <?php
        $free_course    = $new_courselist_value['free_course'];
        $discount       = $new_courselist_value['discount'];
        $price          = $new_courselist_value['price'];
        $discount_price = '';
        $price          = '';
        if ($new_courselist_value['discount'] != '0.00') {
            $discount = $new_courselist_value['price'] - (($new_courselist_value['price'] * $new_courselist_value['discount']) / 100);
        }
        if (($new_courselist_value["free_course"] == 1) && ($new_courselist_value["price"] == '0.00')) {
            $price = $this->lang->line('free');
        } elseif (($new_courselist_value["free_course"] == 1) && ($new_courselist_value["price"] != '0.00')) {
            if ($new_courselist_value['price'] > '0.00') {
                $courseprice = $currency_symbol . '' . amountFormat($new_courselist_value['price']);
            } else {
                $courseprice = '';
            }
            $price = $this->lang->line('free')." <span><del>" . $courseprice . '</del></span>';
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
                      <?php echo $price; ?>
                    
                    </div>
                    
                   <div class="coursecard ptt10">
                    
                    <?php 
                    if($new_courselist_value['courserating'] !=0 || $new_courselist_value['courserating'] !=''){ ?>
                      <?php for ($i = 1; $i <= 5; $i++) { ?>
                          <span class="fa fa-star" <?php if ($new_courselist_value['courserating'] >= $i) { ?> style="color:orange;"<?php } ?>></span>
                      <?php } ?>
                      <?php if($new_courselist_value['totalcourserating'] !=0){ echo ' ('.$new_courselist_value['totalcourserating'].' '.$this->lang->line('rating').')'; } ?> 
                    <?php }else{ ?>
                      <br>
                    <?php }  ?>

                 </div>   
                  
                    <?php if($role != 'parent'){ ?>
                    <div class="row mt-5">
                    <div class="col-md-9 col-sm-9 col-xs-9">						
                      <div class="courssprogress">						  
							<?php  
                            $course_progress	=	intval($new_courselist_value['course_progress']); ?>	 	
							<div class="progress-bar <?php if($course_progress < '100'){ ?> progress-bar-warning <?php }elseif($course_progress == '100'){ ?> progress-bar-info <?php } ?>" role="progressbar" aria-valuenow="45" id="progressbar<?= $new_courselist_value["id"]; ?>"  aria-valuemin="0" aria-valuemax="100" style="width: <?php echo $course_progress;?>%">
							</div>	
                            
                      </div>					  
                    </div>
                    <div class="col-md-3 col-sm-3 col-xs-3 text-right pr-rtl-0">
						<span id="progressbarval<?= $new_courselist_value["id"]; ?>">
						<?php echo intval($new_courselist_value['course_progress']); ?>%
						</span>&nbsp;
						<?php if($course_progress == '100' && isset($new_courselist_value['online_course_certificate_template_id'])){?>
						<span>
							<a data-toggle="tooltip"  data-original-title="<?php echo $this->lang->line('download_certificate'); ?>" href='<?php echo base_url("course/downloadcertificatepdf/".$new_courselist_value['certificate_template_id']."/".$userid."/".$new_courselist_value['id']);?>'><i class="fa fa-download"> </i></a>
						</span>
						<?php } ?>
					</div>
				</div>
          <?php } ?>
       </div>  
          </a>  

          <div class="coursebtn">				
                    <?php
                    $start_btn_label = $this->lang->line('start');
                    if (empty($start_btn_label)) {
                        $start_btn_label = $this->lang->line('start_lesson');
                    }
                    if (empty($start_btn_label)) {
                        $start_btn_label = 'Start';
                    }
                    ?>
					<a href="#" class="btn btn-add coursedetail" data-toggle="modal" data-target="#coursedetailmodal" data-id="<?php echo $new_courselist_value['id']; ?>"><?php echo $this->lang->line('preview'); ?></a>        
					<?php if ($free_course == '1') {  ?>
					<?php if ($loginsession['role'] != 'parent') {  ?>				  
                    <a href="#" class="btn btn-buygreen lesson_ID" data-toggle="modal" data-target="#coursemodal" lesson-data="<?php echo $new_courselist_value['id']; ?>"><?php echo $start_btn_label; ?></a>
					<?php }else{}?>					
					<?php } else {  if($new_courselist_value['paidstatus'] == '1'){   ?>					
					<?php if ($loginsession['role'] != 'parent') {  ?>
                    <a href="#" class="btn btn-buygreen lesson_ID" data-toggle="modal" data-target="#coursemodal" lesson-data="<?php echo $new_courselist_value['id']; ?>"><?php echo $start_btn_label; ?></a>
					<?php }else{}?>					
					<?php }else{ ?>	
                  <?php if($new_courselist_value['course_progress'] > 0){ ?>				
                    <a href="#" class="btn btn-buygreen lesson_ID" data-toggle="modal" data-target="#coursemodal" lesson-data="<?php echo $new_courselist_value['id']; ?>"><?php echo $start_btn_label; ?></a>
					<?php }else{ 
				  if(!empty($paymentgateway)){
                   
                  ?>
                     <a href="<?php echo base_url(); ?>students/online_course/course_payment/payment/<?php echo $new_courselist_value['id']; ?>" class="btn btn-buygreen"><?php echo $this->lang->line('buy_now'); ?></a>
                                 
                  <?php 
				  }
				  } ?>
                  <?php } } ?>	          		
                </div>					
              </div>
            </div><!--./col-lg-3-->
            <?php }}else{?>
              <div class="col-lg-12">
                <div class="alert alert-danger full-width">
                  <?php echo $this->lang->line('no_record_found') ?>
                </div>
              </div>  
            <?php } ?>
          </div><!--./row-->        
      </div>
    </div>
  </section>
</div>
    </div><!--./box box-primary -->
    </div>
    </div>
    </section>
</div>

<!-- Modal -->
<div id="coursemodal" class="modal fade overflow-lg pr15" role="dialog">
  <div class="modal-dialog video-dialogfull">
    <div class="video-contentfull">
        <div id="course_model_body"></div>
    </div>
  </div>
</div>

<!-- set online course exam data  -->
<div id="exam_modal" class="modal fade overflow-lg" role="dialog">
  <div class="modal-dialog video-dialogfull">
        <div id="exam_modal_body"></div>
  </div>
</div>
<!-- set online course exam data  -->

<div id="paymentprocessingmodal" class="modal fade"  role="dialog">
    <div class="modal-dialog ">
      <div class="modal-content">    
        <div class="modal-header modal-media-header">
          <button type="button" class="close" data-dismiss="modal">×</button>
          <h4 class="box-title" id="title"><?php echo $this->lang->line('processing_payment'); ?></h4>
        </div>    
        <button type="button" class="close" data-dismiss="modal" >&times;</button>
          <div class="scroll-area">
            <div class="modal-body paddbtop">
              <div class="row">              
                <table class="table ">               
                  <tr>
                    <td><?php echo $this->lang->line('amount');?></td>
                    <td><span id="p_paid_amount"></span></td>
                  </tr>
                  <tr>
                    <td><?php echo $this->lang->line('payment_method');?></td>
                    <td><span id="p_payment_mode"></span></td>
                  </tr>
                  <tr>
                    <td><?php echo $this->lang->line('payment_type');?></td>
                    <td><span id="p_payment_type"></span></td>
                  </tr>
                  <tr>
                    <td><?php echo $this->lang->line('transaction_id');?></td>
                    <td><span id="p_transaction_id"></span></td>
                  </tr>
                  <tr>
                    <td><?php echo $this->lang->line('note');?></td>
                    <td><span id="p_note"></span></td>
                  </tr>
                  <tr>
                    <td><?php echo $this->lang->line('date');?></td>
                    <td><span id="p_date"></span></td>
                  </tr>
                </table>
              </div><!--./row-->
          </div><!--./modal-body-->
      </div>
    </div>
  </div>
</div><!--#/coursedetailmodal-->

<div id="coursedetailmodal" class="modal fade" role="dialog">
  <div class="modal-dialog modalwrapwidth">
    <div class="modal-content">
      <div class="modal-header"><button type="button" class="close" data-dismiss="modal" onclick="stopvideo()">&times;</button></div>
        <div class="scroll-area-full-mobile">
          <div class="modal-body paddbtop">
              <div class="row">
                <div id="coursedetail1_id">
                </div>
              </div><!--./row-->
          </div><!--./modal-body-->
      </div>
    </div>
  </div>
</div><!--#/coursedetailmodal-->

<div class="modal fade" id="addRatingModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
  <div class="modal-dialog ">
    <div class="modal-content modal-media-content">
      <div class="modal-header modal-media-header p0">
        <div class="modal-header modal-media-header">
          <button type="button" class="close button" data-dismiss="modal">&times;</button>

          <h4 class="box-title"><?php echo $this->lang->line('add_rating'); ?></h4>
        </div>
      </div>
      <div class="modal-body pb0">
        <form id="addratingform" method="post" enctype="multipart/form-data">
          <div class="overflowhidden">
            <div class="form-group">
              <label><?php echo $this->lang->line('rating'); ?> <small class="req"> *</small></label>
              <input name="course_id" id="course_id" value=""  type="hidden" />
              <span onclick="rate('1')" id='rate1' class="fa fa-star"></span>
              <span onclick="rate('2')" id='rate2' class="fa fa-star"></span>
              <span onclick="rate('3')" id='rate3' class="fa fa-star"></span>
              <span onclick="rate('4')" id='rate4' class="fa fa-star"></span>
              <span onclick="rate('5')" id='rate5' class="fa fa-star"></span>
              <input type="hidden" autocomplete="off" class="form-control" id="rate" name="rate">
              <span class="text-danger"><?php echo form_error('rating'); ?></span>
            </div>
            <div class="form-group">
              <label><?php echo $this->lang->line('review'); ?> <small class="req"> *</small></label>
              <textarea id="review" name="review" cols="74" rows="5" class="form-control"></textarea>
            </div>
            <div class="modal-footer pr0">
              <button type="submit" class="btn btn-info pull-right"><?php echo $this->lang->line('save'); ?></button>
           </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.5.0/Chart.min.js"></script>

<script type="text/javascript">
  $(function(){   // when student or guest directly starts lesson from front  page   
		if (sessionStorage.length != 0) {          
										  
			var coureseID =  sessionStorage.getItem("lesson_id");
			if(coureseID != null){
				$("#coursemodal").modal('show');
				startlesson(coureseID);         
			}
         }
  });
</script>
<script>

function openCourse(evt, courseName) {
  var i, tabcontent, tablinks;
  tabcontent = document.getElementsByClassName("tabcontent");
  for (i = 0; i < tabcontent.length; i++) {
    tabcontent[i].style.display = "none";
  }
  tablinks = document.getElementsByClassName("tablinks");
  for (i = 0; i < tablinks.length; i++) {
    tablinks[i].className = tablinks[i].className.replace(" active", "");
  }
  document.getElementById(courseName).style.display = "block";
  evt.currentTarget.className += " active";
}

(function ($) {
  "use strict";

  $(document).ready(function(){
    $('#course_detail_tab').show();
  })

  $('.lesson_ID').click(function(){
     var coureseID = $(this).attr('lesson-data');
     startlesson(coureseID);	
  });

  $('.coursedetail').click(function(){
    var courseID = $(this).attr('data-id');
	  $('#coursedetail1_id').html('');
    $.ajax({
     url  : "<?php echo base_url(); ?>user/studentcourse/coursedetail",
     type : 'post',
     data : {courseID:courseID},
     beforeSend: function () {
      $('#coursedetail1_id').html('Loading...  <i class="fa fa-spinner fa-spin"></i>');
     },
     success : function(response){
       $('#coursedetail1_id').html(response);
     }
    });
  })  

})(jQuery);
</script>

<script>
( function ( $ ) {
  'use strict';
    $(document).ready(function () {
      initDatatable('course-list','user/studentcourse/getcourselist',[],[],100,
                    [{ "bSortable": false, "aTargets": [ -1,] ,'sClass': 'dt-body-right'}, { "bSortable": false, "aTargets": [ -2,] ,'sClass': 'dt-body-right'},{ "bSortable": false, "aTargets": [ -3,] ,'sClass': 'dt-body-right'}, { "bSortable": false, "aTargets": [ -4,] ,'sClass': 'dt-body-right'} ]);       
    });
} ( jQuery ) )
</script>

<script>
( function ( $ ) {
  'use strict';
  $('a[data-toggle="tab"]').on('shown.bs.tab', function(e){
    $($.fn.dataTable.tables(true)).DataTable()
      .columns.adjust();
  });
} ( jQuery ) )
</script>

<script>
  function loadpaymentcoursedetail(paymentid){
  $('#paymentprocessing').html('');
  $.ajax({
    url  : "<?php echo base_url(); ?>user/studentcourse/get_processingpayment",
    type : 'post',
    data : {id:paymentid}, 
    dataType:'json',
    beforeSend: function () {
     $('#paymentprocessing').html('Loading...  <center><i class="fa fa-spinner fa-spin"></i></center>');
    },
    success : function(response){
      $("#paymentprocessingmodal").modal();
      $('#p_actual_price').html(response.actual_price);
      $('#p_paid_amount').html(response.paid_amount);
      $('#p_payment_mode').html(response.payment_mode);
      $('#p_payment_type').html(response.payment_type);
      $('#p_transaction_id').html(response.transaction_id);
      $('#p_note').html(response.note);
      $('#p_date').html(response.date);
      $('#coursedetailmodal').modal('hide');
    }
  });
}

function loadcoursedetail(courseID){
	$('#coursedetail1_id').html('');
  $.ajax({
    url  : "<?php echo base_url(); ?>user/studentcourse/coursedetail",
    type : 'post',
    data : {courseID:courseID},
    beforeSend: function () {
     $('#coursedetail1_id').html('Loading...  <center><i class="fa fa-spinner fa-spin"></i></center>');
    },
    success : function(response){
      $("#coursedetailmodal").modal();
      $('#coursedetail1_id').html(response);
    }
  });
}

function afterprint(courseID){   
  startlesson(courseID);
}
</script>
<script>
	function stopMediaIn($scope){
		if (!$scope || !$scope.length) { return; }
		$scope.find('video, audio').each(function(){
			try {
				this.pause();
				this.currentTime = 0;
				this.removeAttribute('src');
				$(this).find('source').removeAttr('src');
				this.load();
			} catch(e) {}
		});
		$scope.find('iframe').each(function(){
			try { this.src = 'about:blank'; } catch(e) {}
		});
	}

	function stopvideo(){
		stopMediaIn($('#coursedetailmodal'));
		$('#coursedetail1_id').html('');
		$('#coursedetailmodal').modal('hide');
	}

	$(document).on('hide.bs.modal', '#coursedetailmodal', function(){
		stopMediaIn($(this));
	});
	$(document).on('hidden.bs.modal', '#coursedetailmodal', function(){
		$('#coursedetail1_id').html('');
	});

( function ( $ ) {
  'use strict';
  
  $(document).on('click', '.print_btn', function () {
    var courseid = $(this).attr('data-id');
    $.ajax({
        url: '<?php echo site_url("user/studentcourse/printinvoice") ?>',
        type: 'post',
        data: {courseid:courseid},
        success: function (response) {
            popup(response);
        }
    });
  });

  $(document).on('click','.ratethis', function(){
    $('#course_id').val($(this).attr('data-course-id'));
    $.ajax({
        url: '<?php echo site_url("user/studentcourse/checkratingstatus") ?>',
        type: 'post',
        data: {courseid:$(this).attr('data-course-id')},
        dataType: 'json',
        success: function (response) {
          $('#review').val(response.review);
          var i = 1;
          for (i = 1; i <= 5; i++) {
            if(response.rating >= i){
              $('span#rate'+i).css("color", "orange");
            }else{
              $('span#rate'+i).css("color", "");
            }
          }
          $('#rate').val(response.rating);
        }
    });
  });

} ( jQuery ) )
</script>
<script>
function popup(data)
{
    var base_url = '<?php echo base_url() ?>';
    var frame1 = $('<iframe />');
    frame1[0].name = "frame1";
    frame1.css({"position": "absolute", "top": "-1000000px"});
    $("body").append(frame1);
    var frameDoc = frame1[0].contentWindow ? frame1[0].contentWindow : frame1[0].contentDocument.document ? frame1[0].contentDocument.document : frame1[0].contentDocument;
    frameDoc.document.open();
    //Create a new HTML document.
    frameDoc.document.write('<html>');
    frameDoc.document.write('<head>');
    frameDoc.document.write('<title></title>');
    frameDoc.document.write('<link rel="stylesheet" href="' + base_url + 'backend/bootstrap/css/bootstrap.min.css">');
    frameDoc.document.write('<link rel="stylesheet" href="' + base_url + 'backend/dist/css/font-awesome.min.css">');
    frameDoc.document.write('<link rel="stylesheet" href="' + base_url + 'backend/dist/css/ionicons.min.css">');
    frameDoc.document.write('<link rel="stylesheet" href="' + base_url + 'backend/dist/css/AdminLTE.min.css">');
    frameDoc.document.write('<link rel="stylesheet" href="' + base_url + 'backend/dist/css/skins/_all-skins.min.css">');
    frameDoc.document.write('<link rel="stylesheet" href="' + base_url + 'backend/plugins/iCheck/flat/blue.css">');
    frameDoc.document.write('<link rel="stylesheet" href="' + base_url + 'backend/plugins/morris/morris.css">');
    frameDoc.document.write('<link rel="stylesheet" href="' + base_url + 'backend/plugins/jvectormap/jquery-jvectormap-1.2.2.css">');
    frameDoc.document.write('<link rel="stylesheet" href="' + base_url + 'backend/plugins/datepicker/datepicker3.css">');
    frameDoc.document.write('<link rel="stylesheet" href="' + base_url + 'backend/plugins/daterangepicker/daterangepicker-bs3.css">');
    frameDoc.document.write('</head>');
    frameDoc.document.write('<body>');
    frameDoc.document.write(data);
    frameDoc.document.write('</body>');
    frameDoc.document.write('</html>');
    frameDoc.document.close();
    setTimeout(function () {
        window.frames["frame1"].focus();
        window.frames["frame1"].print();
        frame1.remove();
    }, 500);
    return true;
}
</script>

<script type="text/javascript">

function rate(val) {

    $('#rate').val(val);

    for (i = 1; i <= 5; i++) {
        $("#rate" + i).attr("style", "color:none;");
    }

    for (i = 1; i <= val; i++) {
        $("#rate" + i).attr("style", "color:#f1b624f0;");
    }
}

$("#addratingform").on('submit', (function (e) {
    e.preventDefault();
    $.ajax({
        url: '<?php echo base_url() ?>user/studentcourse/rating',
        type: "POST",
        data: new FormData(this),
        dataType: 'json',
        contentType: false,
        cache: false,
        processData: false,
        success: function (data) {
            if (data.status == "fail") {
                var message = "";
                $.each(data.error, function (index, value) {
                    message += value;
                });
                errorMsg(message);
            } else {
                if(data.message == "<?php echo $this->lang->line('something_went_wrong'); ?>"){
                  errorMsg(data.message);
                }else{
                  successMsg(data.message);
                  window.location.reload(true);
                }  
            }
        },
        error: function () {
        }
    });
}));

function startlesson(coureseID){
  $('#course_model_body').html('');   
  $.ajax({
     url  : "<?php echo base_url(); ?>user/studentcourse/startlesson",
     type : 'post',
     data : {coureseID:coureseID},
     success : function(response){
        $('#course_model_body').html(response);
        sessionStorage.clear(); 
     }
  });
}
</script>

<script>
 $(document).on("click",".lesson_ID_detail",function() {
    var coureseID = $(this).attr('lesson-data');
    $('#course_model_body').html('');
    $.ajax({
      url  : "<?php echo base_url(); ?>user/studentcourse/startlesson",
      type : 'post',
      data : {coureseID:coureseID},
      success : function(response){
        $('#course_model_body').html(response);
          sessionStorage.clear();

          $('#coursedetailmodal').modal('hide');
       }
     });
  }); 

</script>


