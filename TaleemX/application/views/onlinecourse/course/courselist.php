<script src="<?php echo base_url(); ?>backend/plugins/ckeditor/ckeditor.js"></script>
<!-- new linkes added for ckeditor -->
<link rel="stylesheet" href="<?php echo base_url(); ?>backend/plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.min.css">
<script src="<?php echo base_url(); ?>backend/plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.all.min.js"></script>
<!-- new linkes added for ckeditor -->
<?php 
$this->load->view('layout/course_css.php'); 
$currency_symbol = $this->customlib->getSchoolCurrencyFormat();
?>
<div class="content-wrapper">
    <section class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="box box-primary">
                    <div class="box-header with-border pb0">
                       <div class="row">
                          <div class="col-lg-4 col-md-3 col-sm-4">
                            <h3 class="box-title header_tab_style"><?php echo $this->lang->line('course_list'); ?></h3>
                          </div>
                          <div class="col-lg-8 col-md-9 col-sm-8">
                            <div class="nav-tabs-custom mb0 pull-right">
                                <ul class="nav nav-tabs tab border0 ">
              						<li><a href="#tab_2"  data-toggle="tab"  class="miusttop10 border0" onclick="openCourse(event, 'course_detail_tab')" title="<?php echo $this->lang->line('card_view'); ?>" data-placement="bottom"><i class="fa fa-th"></i></a></li>
              						<li><a href="#tab_1" data-toggle="tab" class="miusttop10 border0" onclick="openCourse(event, 'course_card_tab')" title="<?php echo $this->lang->line('list_view'); ?>" data-placement="bottom"><i class="fa fa-list"></i></a></li>			
									<?php if($this->rbac->hasPrivilege('online_course', 'can_add')) { ?> 
              						<li><button type="button" data-placement="bottom" class="btn btn-sm btn-primary miusttop5 rounded-pill" data-toggle="modal" data-backdrop="static" data-keyboard="false" data-target="#add_course_modal"><i class="fa fa-plus"></i> <?php echo $this->lang->line('add_course'); ?></button></li>   
									<?php } ?>								
                                </ul>                                
                                </div><!--./nav-tabs-custom -->
                                  <form class="navbar-form pull-right miusttop5" id="search_area" role="search" action="<?php echo site_url('onlinecourse/course/index'); ?>" method="GET">
                                    <?php echo $this->customlib->getCSRF(); ?>
                                    <div class="input-group">
                                      <input type="text" value="<?php echo $search_course;?>" name="search_course" id="search_course" class="form-control search-form sm-input-28" placeholder="<?php echo $this->lang->line('search_by_course_name'); ?>">
                                      <span class="input-group-btn">
                                        <button type="submit" name="search" id="search-btn" style="" class="btn btn-flat topsidesearchbtn"><i class="fa fa-search"></i></button>
                                      </span>
                                    </div>
                                  </form>
                                </div>  
                        </div>  
                    </div>

<div id="course_card_tab" class="tabcontent">
   <?php if (isset($new_courselist)) {   ?>
    <div class="nav-tabs-custom border0 navnoshadow">
        <div class="tab-content">          
            <div class="tab-pane active table-responsive no-padding" id="tab_1">            
                   <table class="table table-striped table-bordered table-hover course-list course-table nth-til8" data-export-title="<?php echo $this->lang->line('course_list'); ?>">
                    <thead>
                        <tr>
                            <th class="white-space-nowrap"><?php echo $this->lang->line('title'); ?></th>
                            <th class="white-space-nowrap"><?php echo $this->lang->line('class'); ?></th>
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
                            <th class="white-space-nowrap"><?php echo $this->lang->line('price').' ('.$currency_symbol.')'; ?></th>
                            <th class="white-space-nowrap"><?php echo $this->lang->line('current_price').' ('.$currency_symbol.')'; ?></th>
                            <th class="white-space-nowrap"><?php echo $this->lang->line('last_updated'); ?></th>
                            <th class="text-right noExport white-space-nowrap">&nbsp &nbsp <?php echo $this->lang->line('action'); ?> </th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
      </div>
    </div>
    <?php }?>
</div>

<div id="course_detail_tab" class="tabcontent">
  <section class="content">
		<?php if (!empty($new_courselist)) { ?>
          <div class="row flex-row" >
            <?php foreach ($new_courselist as $new_courselist_value) { ?>
              <div class="col-lg-3 col-md-4 col-sm-6 col-xs-12">
                  <div class="coursebox">                  
                    <a href="#" class="course_detail_id text-dark" data-id="<?php echo $new_courselist_value['id']; ?>" data-backdrop="static" data-keyboard="false" data-toggle="modal" data-target="#course_detail_modal">                    
                    <div class="coursebox-img">
                      <img src="<?php echo base_url(); ?>uploads/course/course_thumbnail/<?php echo rawurlencode($new_courselist_value['course_thumbnail']); ?>">
                      <div class="author-block author-wrap">			  
                      <?php if (!empty($new_courselist_value['image'])) {
                        ?>
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

                        <span class="authorname"><?php echo $new_courselist_value['name'] . ' ' . $new_courselist_value['surname']; ?> (<?php echo $new_courselist_value['employee_id']; ?>)</span>
                        <span class="description"><span><?php echo $this->lang->line('last_updated'); ?> </span> <?php echo date($this->customlib->getSchoolDateFormat(), strtotime($new_courselist_value['updated_date'])); ?></span>
                      </div>
                    </div>
                    <div class="coursebox-body">
                    <h4><?php echo $new_courselist_value['title']; ?> </h4><div class="course-caption"><?php echo $new_courselist_value['description']; ?></div>                      
                    <div class="classstats ">
                        <b><?php echo $this->lang->line('category'); ?></b>: <?php echo $new_courselist_value['category_name']; ?> 
                    </div>                        
                    <div class="classstats">
						<span class="pr5"><i class="fa fa-list-alt"></i><?php echo $this->lang->line('class'); ?>: <?php echo $new_courselist_value['class']; ?></span>
						
						<?php if(!empty($new_courselist_value['total_lesson'])) { ?>
							<span class="pr5"><i class="fa fa-play-circle"></i><?php echo $this->lang->line('lesson') . ' ' . $new_courselist_value['total_lesson']; ?></span>		
						<?php } ?>
						
						<?php if (!empty($new_courselist_value['total_hour_count']) && $new_courselist_value['total_hour_count'] != '00:00:00') { ?>
							<span  class="pr5"><?php echo $new_courselist_value['total_hour_count'] . ' ' . $this->lang->line('hrs'); ?></span>
						<?php }else{ ?>
							<span class="pr5"></span>
						<?php } ?>
						
						<br>
						
						<?php if($this->customlib->get_online_course_curriculam_status("online_course_exam")=="" && !empty($new_courselist_value['total_exam'])){ ?>
							<span  class="pr5"><i class="fa fa-rss ftlayer"></i><?php echo $this->lang->line('exam') . ' ' . $new_courselist_value['total_exam']; ?></span>
						<?php } ?>
						
						<?php if($this->customlib->get_online_course_curriculam_status("online_course_quiz")=="" && !empty($new_courselist_value['total_quiz'])){ ?>
							<span  class="pr5"><i class="fa fa-question-circle"></i><?php echo $this->lang->line('quiz') . ' ' . $new_courselist_value['total_quiz']; ?></span>
						<?php } ?>
						
						<?php if($this->customlib->get_online_course_curriculam_status("online_course_assignment")=="" && !empty($new_courselist_value['total_assignment'])){ ?>
							<span  class="pr5"><i class="fa fa-flask ftlayer"></i><?php echo $this->lang->line('assignment') . ' ' . $new_courselist_value['total_assignment']; ?></span>
						<?php }else{ ?>
							<span class="pr5"></span>
						<?php } ?>
					</div>

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

            $price = $this->lang->line('free') ." <span><del>" . $courseprice . '</del></span>';

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
                      <div class="classstats">
                        <div>
                            <?php echo $price; ?>
                        </div>
                      </div>
                    </div>                    
                    </a>                   
                    
                    <div class="coursebtn">
                      <?php if($this->rbac->hasPrivilege('online_course', 'can_view')) {?> 					  
					  <a href="#" class="btn btn-add course_detail_id" data-id="<?php echo $new_courselist_value['id']; ?>" data-backdrop="static" data-keyboard="false" data-toggle="modal" data-target="#course_detail_modal"><?php echo $this->lang->line('manage'); ?></a>
					  <?php } ?>					 
					  <a href="#" class="btn btn-buygreen course_preview_id pull-right" data-id="<?php echo $new_courselist_value['id']; ?>" data-backdrop="static" data-keyboard="false" data-toggle="modal" data-target="#course_preview_modal"><?php echo $this->lang->line('preview'); ?></a>
                    </div>
                  </div>
                </div><!--./col-lg-3-->
                <?php }?>
              </div><!--./row-->
			<?php }else{ 
        ?>
        <div class="text-center">
          <span class="dataTables_empty">No data available in table <br> <br></span>
          <img src="<?php echo base_url(); ?>backend/images/addnewitem.svg" width="150"><br><br> 
          <span class="text-success bolds"><i class="fa fa-arrow-left"></i> <?php echo $this->lang->line('no_record_found_as_per_your_search_criteria');?></span>
        </div>
        <?php  } ?>
        <div class="row text-center">
           <div class="course-pagination"><?php echo $this->pagination->create_links(); ?></div>
        </div>      
    </section>
</div>
    </div><!--./box box-primary -->
    </div>
    </div>
    </section>
</div>

<div class="modal fade pl0 pr0" id="course_detail_modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
  <div class="modal-dialog modal-full-width" role="document">
    <div class="modal-content modal-media-content h-100">
      <div class="modal-header">
        <button id="close_btn" type="button" onclick="stopvideo()" class="close" data-dismiss="modal">&times;</button> 
      </div>  
      <div class="modal-body p0">
        <div id="course_detail"></div>
      </div>
    </div>
  </div>
</div>

<style type="text/css">
/* Make sure the Select2 multi-select inside the Add Course modal renders
   correctly (full width, visible dropdown popup, wrapping choices). */
#add_course_modal .select2-container,
#add_course_modal .select2 { width: 100% !important; display: block; }
#add_course_modal .select2-container--default .select2-selection--multiple {
    min-height: 38px;
}
#add_course_modal .select2-container--default .select2-selection--multiple .select2-selection__rendered {
    display: block;
    padding: 2px 6px;
}
#add_course_modal .select2-container--default .select2-selection--multiple .select2-selection__choice {
    float: none;
    display: inline-block;
    margin: 2px 4px 2px 0;
    max-width: 100%;
    white-space: normal;
    word-break: break-word;
}
/* Dropdown popup must sit above the Bootstrap modal backdrop/content. */
.select2-container.select2-container--open { z-index: 20000 !important; }
</style>
<div class="modal fade" id="add_course_modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog modal-lg course_modal modal-fullscreen" role="document">
        <div class="modal-content modal-media-content">
            <div class="modal-header modal-media-header">
              <button type="button" class="close" data-dismiss="modal">&times;</button>
              <h4 class="modal-title"><?php echo $this->lang->line('add_course'); ?></h4>
            </div>
   
    <div class="modal-body pt0 pb0">
      <form id="add_course_form_ID" method="post" class="ptt10" enctype="multipart/form-data">
         <div class="">
      <?php echo $this->customlib->getCSRF(); ?>
        <div class="row scroll-area-16">
          <div class="col-lg-8 col-md-8 col-sm-8">
              <div class="form-group">
                <label><?php echo $this->lang->line('title'); ?><small class="req"> *</small></label>
                <input autofocus="" id="title" name="title"  placeholder="" type="text" class="form-control"/><span class="text-danger"><?php echo form_error('title'); ?></span>
              </div>
              <label><?php echo $this->lang->line('outcomes'); ?></label>
                <table id="outcometableID" width="100%">
                  <tr id="outcomerow0">
                    <td width="98%">
                      <div class="form-group">
                      <input type="text" name="outcomes[]" class="form-control">
                      <span class="text-danger"><?php echo form_error('outcomes'); ?></span>
                      </div>
                    </td>
                    <td valign="top" width="30">
                      <button type="button" onclick="add_outcomerow()" class="plusgreenbtn addplus"><i class="fa fa-plus"></i></button>
                    </td>
                  </tr>
                </table>
                <div class="form-group">
                    <label><?php echo $this->lang->line('description'); ?><small class="req"> *</small></label>
                   <textarea rows="10" id="description" name="description" placeholder="" type="text" class="form-control" ><?php echo set_value('description'); ?></textarea>
                   <span class="text-danger"><?php echo form_error('description'); ?></span>
                </div>  
          </div><!--./col-lg-8-->
          <div class="col-lg-4 col-md-4 col-sm-4">
						<div class="form-group">
              <label><?php echo $this->lang->line('inline_preview_image'); ?> (700px X 400px) <small class="req"> *</small></label>
                <input autofocus="" id="course_thumbnail" name="course_thumbnail"  placeholder="" type="file" class="filestyle form-control"/><span class="text-danger"><?php echo form_error('course_thumbnail'); ?></span>
            </div>		

                            <div class="form-group">
                                <label><?php echo $this->lang->line('class'); ?><small class="req"> *</small></label>
                                <select autofocus="" id="class_id" name="class" class="form-control class-list" >
                                <option value=""><?php echo $this->lang->line('select'); ?></option>
                                <?php

if (!empty($classlist)) {

    foreach ($classlist as $classlist_value) {

        ?>

                                    <option value="<?php echo $classlist_value['id'] ?>" ><?php echo $classlist_value['class'] ?></option>
                                    <?php
}
}
?>
                                </select>
                                <span class="text-danger"><?php echo form_error('class'); ?></span>
                            </div>				
                        <div class="form-group">
                          <label> <?php echo $this->lang->line('section') ?><small class="req"> *</small></label>
                          <select id="section_id" style="background-image:none" name="section[]" class="form-control section-list" multiple="multiple">
                          </select>
                        </div>					

                        <?php
$result = $this->customlib->getUserData();

$roleid = $result["role_id"];

if ($roleid != "2") {

    ?>

                        <div class="form-group">
                            <label><?php echo $this->lang->line('assign_teacher'); ?><small class="req"> *</small></label>
                            <select  id="teacher_id" name="teacher" class="form-control" >
                            <option value=""><?php echo $this->lang->line('select'); ?></option>
                            <?php

if (!empty($allTeacherList)) {

        foreach ($allTeacherList as $allTeacherList_value) {?>

                                    <option value="<?php echo $allTeacherList_value['id']; ?>"><?php echo $allTeacherList_value['name'] . ' ' . $allTeacherList_value['surname']; ?> (<?php echo $allTeacherList_value['employee_id'];?>)</option>

                            <?php
}

    }

    ?>
                            </select>
                            <span class="text-danger"><?php echo form_error('teacher'); ?></span>
                        </div>
                <?php }?>        

            <div class="row">
              <div class="col-md-12"><label><?php echo $this->lang->line('course_preview_url'); ?></label></div>
              <div class="form-group">
                <div class="col-md-4">
                  <select  id="course_provider" onclick="checkCourseProvider()" name="course_provider" class="form-control">
                  <?php
                    $__preview_providers = isset($course_preview_providers) && is_array($course_preview_providers) ? $course_preview_providers : $course_provider;
                    foreach ($__preview_providers as $key => $cpvalue) { ?>
                  <option value="<?php echo $key ?>"><?php echo $cpvalue ?></option>
                  <?php }?>
                  </select>
                </div>
              </div>
              <div class="col-md-8 hide" id="course_url_div">
                  <div class="form-group">
                    <input autofocus="" id="course_url" name="course_url"  placeholder="" type="text" class="form-control" />
                  </div>
              </div>
              <div class="col-md-8 hide" id="s3_file_div">
                  <div class="form-group">         
                    <input autofocus="" id="s3_file" name="s3_file"  placeholder="" type="file" class="form-control filestyle" />
                  </div>
              </div>
            </div><!--./row-->
            <div class="row">
              <div class="col-md-12">
                <div class="form-group">
                  <button type="button"
                          id="add_course_extract_subtitles_btn"
                          class="btn btn-default">
                    <i class="fa fa-closed-captioning"></i> Extract subtitles
                  </button>
                  <span id="add_course_extract_subtitles_status"
                        class="hide"
                        style="color:#27ae60; font-weight:600; margin-left:10px;">
                    <i class="fa fa-check-circle"></i> subtitles extracted
                  </span>
                  <input type="hidden" id="pending_transcript_segments_json" name="pending_transcript_segments_json" value="">
                  <input type="hidden" id="pending_transcript_full" name="pending_transcript_full" value="">
                  <input type="hidden" id="pending_transcript_source" name="pending_transcript_source" value="">
                </div>
              </div>
            </div><!--./row-->
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label><?php echo $this->lang->line('price'); ?> (<?php echo $currency_symbol; ?>)<small class="req"> *</small></label>
                        <input autofocus="" id="course_price" name="course_price"  placeholder="" type="text" class="form-control"/><span class="text-danger"><?php echo form_error('course_price'); ?></span>
                    </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                        <label><?php echo $this->lang->line('discount'); ?> (%)</label>
                        <input autofocus="" id="course_discount" name="course_discount"  placeholder="" type="text" class="form-control"/><span class="text-danger"><?php echo form_error('course_discount'); ?></span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label><?php echo $this->lang->line('free_course'); ?></label>
                    <div class="checkbox mt4">
                        <label><input type="checkbox" value="1" name="free_course" autocomplete="off" class="form-check-input"></label>
                    </div>
                    </div>
                </div>
            </div><!--./row-->
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label><?php echo $this->lang->line('course_category'); ?><small class="req"> *</small></label>
                        <select name="category_id" id="category_id" class="form-control">
                            <option value=""><?php echo $this->lang->line('select'); ?></option>
                            <?php foreach($category_result as $key => $category_result_value){ ?>
                            <option value="<?php echo $category_result_value['id']; ?>"><?php echo $category_result_value['category_name']; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label><?php echo $this->lang->line('front_site_visibility'); ?></label>
                        <select name="front_side_visibility" class="form-control">
                            <?php foreach($front_side_visibility as $key => $front_side_visibility_value){ ?>
                                   <option value="<?php echo $key; ?>"><?php echo $front_side_visibility_value; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label><?php echo $this->lang->line('certificate'); ?><small class="req"> </small></label>
                        <select name="certificate_template_id" class="form-control">
                            <option value=""><?php echo $this->lang->line('select'); ?></option>
                            <?php foreach($certificateList as $certificate_key => $certificate_value){ ?>
                                   <option value="<?php echo $certificate_value['id']; ?>"><?php echo $certificate_value['certificate_name']; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
            </div>
                </div><!--/.col (left) -->
           </div><!--./row-->
        </div><!--./scroll-area-->
          <div class="row">
            <div class="box-footer row">  
              <div class="pull-right">
              <a id="add_course_btn" class="btn btn-info"><span id="loader_btn"></span> <?php echo $this->lang->line('save'); ?></a>
            </div>
          </div>
        </div>    
       </form>          
     </div>           
        </div>
    </div>
  </div>
  <div class="modal fade pr0" id="edit_course_modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog modal-full-width course_modal" role="document">
      <div class="modal-content modal-media-content h-100 rounded-0">
        <div class="modal-header modal-media-header">
          <button type="button" class="close button" data-dismiss="modal">&times;</button>
          <h4 class="box-title"><?php echo $this->lang->line('edit_course'); ?></h4>
		      <!-- <div class="box-header ptbnull noborder">
            
            <div class="box-tools pull-right">              
            </div>
          </div> -->
        </div>
        <div class="modal-body pb0">
          <div id="edit_course"></div>
         </div>
      </div>
    </div>
  </div>
  <div class="modal fade" id="add_section_modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content modal-media-content">
        <div class="modal-header modal-media-header">
            <button type="button" class="close" data-dismiss="modal">&times;</button>
            <h4 class="box-title"><?php echo $this->lang->line('add_section'); ?></h4>
        </div>
        <form id="formadd" method="post" class="ptt10">
            <div class="modal-body pt0 pb0">
              <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12">
                    <input type="hidden" name="add_course_id" id="course_id">
                      <div class="row">
                        <div class="col-sm-12">
                          <div class="form-group">
                              <label for="pwd"><?php echo $this->lang->line('title'); ?></label><small class="req"> *</small>
                              <input type="text" id="title" autocomplete="off" class="form-control" name="title">
                              <span id="title_error" class="text-danger"></span>
                          </div>
                        </div>
                      </div>
                </div>
              </div>
              <div class="">
                <div class="box-footer pr0">
                  <a  onclick="saveSection()" class="btn btn-info pull-right"><span id="section_loader"></span> <?php echo $this->lang->line('save'); ?></a>
                </div>
              </div>
            </div>
         </form>   
      </div>
    </div>
  </div>

  <div class="modal fade" id="order_section_modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content modal-media-content">
        <div class="modal-header modal-media-header">
            <button type="button" class="close close_btn" data-dismiss="modal">&times;</button>
            <h4 class="box-title"> <?php echo $this->lang->line('order_section'); ?> 
            </h4>
        </div>
        <div class="modal-body pt0 pb0">
          <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12">
              <div id="order_section"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="edit_section_modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content modal-media-content">
        <div class="modal-header modal-media-header">
            <button type="button" class="close" data-dismiss="modal">&times;</button>
            <h4 class="box-title"> <?php echo $this->lang->line('edit_section'); ?>
            </h4>
        </div>
        <form id="edit_section_form" method="post" class="ptt10">
            <div class="modal-body pt0 pb0">
              <span id="loader_section"></span>
              <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12">
                    <input type="hidden" name="section_id" id="edit_sectionID">
                    <input type="hidden" name="online_course_id" id="online_course_id">
                      <div class="row">
                        <div class="col-sm-12">
                          <div class="form-group">
                            <label for="pwd"><?php echo $this->lang->line('title'); ?></label><small class="req"> *</small>
                            <input type="text" id="edit_title" autocomplete="off" class="form-control" name="edit_title">
                            <span id="title_error" class="text-danger"></span>
                          </div>
                        </div>
                      </div><!--./row-->
                </div><!--./col-md-12-->
              </div><!--./row-->
              <div class="">
                <div class="box-footer pr0">
                  <a id="edit_section_btn" class="btn btn-info pull-right"><span id="section_loaders"></span> <?php echo $this->lang->line('save'); ?></a>
                </div>
              </div>
            </div>
        </form>    
      </div>
    </div>
  </div>

    <div class="modal fade" id="add_lesson_modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content modal-media-content">
                <div class="modal-header modal-media-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="box-title"> <?php echo $this->lang->line('add_lesson'); ?>
                    </h4>
                </div>
				<div  class="scroll-area">
                <form id="add_lesson_form" method="post" enctype="multipart/form-data" class="ptt10">
                <div class="modal-body pt0 pb0">
                    <div class="row">
                        <div class="col-lg-12 col-md-12 col-sm-12">
                                <input type="hidden" name="lesson_course_id" id="lesson_course_id">
                                <input type="hidden" name="add_lesson_section_id" id="add_lesson_section_id">
                                <div class="row">
                                    <div class="col-sm-12">
                                        <div class="form-group">
                                            <label for="pwd"><?php echo $this->lang->line('title'); ?></label><small class="req"> *</small>
                                            <input type="text" id="title" autocomplete="off" class="form-control" name="title">
                                            <span id="title_error" class="text-danger"></span>
                                        </div>
                                    </div>
                                    <div class="col-sm-12">
                                        <div class="form-group">
                                            <label for="pwd"><?php echo $this->lang->line('lesson_type'); ?></label><small class="req"> *</small>
                                            <select class="form-control" name="lesson_type" onchange="getcontent(this.value)">
                                            <option value=""><?php echo $this->lang->line('select') ?></option>
                                            <?php foreach ($lesson_type as $key => $lvalue) {?>
                                                <option value="<?php echo $key ?>"><?php echo $lvalue ?></option>
                                            <?php }?>
                                            </select>
                                            <span id="lesson_type_error" class="text-danger"><?php echo form_error('lesson_type'); ?></span>
                                            <div class="form-group displaynone" id="attachment"><br>
                                            <label><?php echo $this->lang->line('attachment'); ?> 
                                            </label><small class="req"> *</small>
                                            <input id="lesson_attachment" name="lesson_attachment[]"  type="file" class="filestyle form-control" data-height="26"  value="<?php echo set_value('documents'); ?>" multiple=""/>                                        
                                            <span class="text-danger"><?php echo form_error('lesson_attachment'); ?></span>
                                            </div>
                                            <div id="video_detail" class="displaynone"><br>
                                            <div class="form-group">
                                                <label><?php echo $this->lang->line('video_provider'); ?></label>
                                                <select class="form-control" id="lesson_provider" onclick="checkLessonProvider()" name="lesson_provider">
                                                    <?php
                                                    $__lesson_providers = isset($course_preview_providers) && is_array($course_preview_providers) ? $course_preview_providers : $course_provider;
                                                    foreach ($__lesson_providers as $key => $cpvalue) { ?>
                                                        <option value="<?php echo $key ?>"><?php echo $cpvalue ?></option>
                                                    <?php }?>
                                                </select>
                                                <span class="text-danger"><?php echo form_error('lesson_provider'); ?></span>
                                            </div>
                                            <div class="form-group" id="lesson_url_div">
                                                <label><?php echo $this->lang->line('video_url'); ?></label><small class="req"> *</small>
                                                <input autofocus="" name="lesson_url" id="lesson_url" type="text" class="form-control" value="" />
                                                <span class="text-danger"><?php echo form_error('video_url'); ?></span>
                                            </div>
                                            <div class="form-group">
                                                <button type="button"
                                                        id="add_lesson_extract_subtitles_btn"
                                                        class="btn btn-default">
                                                    <i class="fa fa-closed-captioning"></i> Extract subtitles
                                                </button>
                                                <span id="add_lesson_extract_subtitles_status"
                                                      class="hide"
                                                      style="color:#27ae60; font-weight:600; margin-left:10px;">
                                                    <i class="fa fa-check-circle"></i> subtitles extracted
                                                </span>
                                                <input type="hidden" name="pending_transcript_segments_json" value="">
                                                <input type="hidden" name="pending_transcript_full" value="">
                                                <input type="hidden" name="pending_transcript_source" value="">
                                            </div>
                                            <div class="form-group" id="lesson_file_div">
                                                <label><?php echo $this->lang->line('lesson_file'); ?>
												</label><small class="req"> *</small>
                                                <input autofocus="" name="lesson_file" class="filestyle form-control" type="file" value="" />
                                                <span class="text-danger"><?php echo form_error('video_url'); ?></span>
                                            </div>
                                            <div class="form-group relative">
                                                <label><?php echo $this->lang->line('duration'); ?></label><small class="req"> *</small>
                                                <input autofocus="" name="lesson_duration" placeholder="HH:MM:SS" type="text" class="form-control timepicker">
                                                <span class="text-danger"><?php echo form_error('duration'); ?></span>
                                            </div>
                                        </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-12">
                                        <div class="form-group">
                                            <label for="pwd"><?php echo $this->lang->line('inline_preview_image'); ?> (700px X 400px)</label><small class="req"> *</small>
                                            <input type="file" id="thumbnail" autocomplete="off" class="filestyle form-control" name="add_lesson_thumbnail">
                                            <span id="thumbnail_error" class="text-danger"></span>
                                        </div>
                                    </div>
                                    <div class="col-sm-12">
                                        <div class="form-group">
                                            <label for="pwd"><?php echo $this->lang->line('summary'); ?></label>
                                            <textarea rows="5" id="title" autocomplete="off" class="form-control" name="summary"></textarea>
                                            <span id="title_error" class="text-danger"></span>
                                        </div>
                                    </div>
                                </div><!--./row-->
                        </div><!--./col-md-12-->
                    </div><!--./row-->
                        <div class="">
                            <div class="box-footer pr0">
                                <a id="save_lesson" class="btn btn-info pull-right" type="button"><span id="lesson_loader"></span> <?php echo $this->lang->line('save'); ?></a>
                            </div>
                        </div>
                    </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="edit_lesson_modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content modal-media-content">
                <div class="modal-header modal-media-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="box-title"> <?php echo $this->lang->line('edit_lesson'); ?>
                    </h4>
                </div>
				<div  class="scroll-area">
                <form id="edit_lesson_form" method="post" enctype="multipart/form-data" class="ptt10">
                    <div class="modal-body pt0 pb0">
                        <div class="row">
                            <div class="col-lg-12 col-md-12 col-sm-12">
                                    <input type="hidden" name="edit_lesson_course_id" id="edit_lesson_course_id">
                                    <input type="hidden" name="lesson_section_id" id="lesson_section_id">
                                    <input type="hidden" name="lessons_id" id="lessons_id">
                                    <div class="row">
                                        <div class="col-sm-12">
                                            <div class="form-group">
                                                <label for="pwd"><?php echo $this->lang->line('title'); ?></label><small class="req"> *</small>
                                                <input type="text" id="lesson_titleID" autocomplete="off" class="form-control" name="lesson_titleID">
                                                <span id="title_error" class="text-danger"></span>
                                            </div>
                                        </div>
                                        <div class="col-sm-12">
                                            <div class="form-group">
                                                <label for="pwd"><?php echo $this->lang->line('lesson_type'); ?></label><small class="req"> *</small>
                                                <select class="form-control" id="lesson_selectedID" name="lessons_type" onchange="geteditcontent(this.value)">
                                                    <option value=""><?php echo $this->lang->line('select') ?></option>
                                                    <?php foreach ($lesson_type as $key => $lvalue) {?>
                                                    <option value="<?php echo $key ?>"><?php echo $lvalue ?></option>
                                                <?php }?>
                                                </select>
                                                <span id="lesson_type_error" class="text-danger"></span>
                                            </div>
                                            <div class="form-group displaynone" id="editattachment">
                                            <label><?php echo $this->lang->line('attachment'); ?></label>
                                            <input id="lesson_attachment" name="lesson_attachment[]"  type="file" class="filestyle form-control" data-height="38"  value="<?php echo set_value('documents'); ?>" multiple=""/>                                          
                                            <input type="hidden" name="old_attachment_img" id="old_attachment_img_id">
                                            <span class="text-danger"><?php echo form_error('lesson_attachment'); ?></span>
                                        </div>
                                        <div id="editvideo_detail" class="displaynone">
                                            <div class="form-group">
                                            <label><?php echo $this->lang->line('video_provider'); ?></label>
                                            <select class="form-control" onclick="checkEditLessonProvider()" name="lesson_provider" id="lesson_provider_edit" >
                                            <?php
                                            $__lesson_edit_providers = isset($course_preview_providers) && is_array($course_preview_providers) ? $course_preview_providers : $course_provider;
                                            foreach ($__lesson_edit_providers as $key => $cpvalue) { ?>
                                            <option value="<?php echo $key ?>"><?php echo $cpvalue ?></option>
                                            <?php }?>
                                            </select>
                                            <span class="text-danger"><?php echo form_error('lesson_provider'); ?></span>
                                            </div>
                                            <div class="form-group" id="lesson_url_edit_div">
                                                <label><?php echo $this->lang->line('video_url'); ?></label><small class="req"> *</small>
                                                <input autofocus=""  name="lesson_url" id="lesson_urlID"  type="text" class="form-control " value="" />
                                                <span class="text-danger"><?php echo form_error('video_url'); ?></span>
                                            </div>
                                            <div class="form-group" id="edit_lesson_extract_subtitles_wrap">
                                                <button type="button"
                                                        id="edit_lesson_extract_subtitles_btn"
                                                        class="btn btn-default">
                                                    <i class="fa fa-closed-captioning"></i> Extract subtitles
                                                </button>
                                                <span id="edit_lesson_extract_subtitles_status"
                                                      class="ml8 hide"
                                                      style="color:#27ae60; font-weight:600; margin-left:10px;">
                                                    <i class="fa fa-check-circle"></i> subtitles extracted
                                                </span>
                                            </div>
                                            <div class="form-group" id="lesson_file_edit_div">
                                                    <label> <?php echo $this->lang->line('lesson_file'); ?></label>
                                                    <input autofocus="" name="lesson_file" class="filestyle form-control" type="file" value="" />
                                                    <span class="text-danger"><?php echo form_error('video_url'); ?></span>
                                                </div>
                                            <div class="form-group relative">
                                                <label><?php echo $this->lang->line('duration'); ?></label>
                                                <input autofocus=""  name="lesson_duration" id="lesson_durationID" placeholder="HH:MM:SS" type="text" class="form-control timepicker">
                                                <span class="text-danger"><?php echo form_error('duration'); ?></span>
                                            </div>
                                        </div>
                                        </div>
                                        <div class="col-sm-12">
                                            <div class="form-group">
                                                <label for="pwd"><?php echo $this->lang->line('inline_preview_image'); ?> (700px X 400px)</label>
                                                <input type="file" id="lesson_thumbnail" autocomplete="off" class="form-control filestyle" name="lesson_thumbnail">
                                                <input type="hidden" name="old_background" id="lesson_old_img_id">
                                                <span id="lesson_thumbnail_error" class="text-danger"></span>
                                            </div>
                                        </div>
                                        <div class="col-sm-12">
                                            <div class="form-group">
                                                <label for="pwd"><?php echo $this->lang->line('summary'); ?></label>
                                                <textarea rows="5" id="lessons_summaryID" autocomplete="off" class="form-control" name="lessons_summary"></textarea>
                                                <span id="lesson_summaryID_error" class="text-danger"></span>
                                            </div>
                                        </div>
                                    </div><!--./row-->
                               
                            </div><!--./col-md-12-->
                        </div><!--./row-->
                        <div class="">
                            <div class="box-footer pr0">
                                <a id="edit_lesson_btn" type="button" class="btn btn-info pull-right"><span id="lesson_loaders"></span> <?php echo $this->lang->line('save'); ?></a>
                            </div>
                        </div>
                    </div>
                     </form>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="download_attachment_modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog modal-lg " role="document">
            <div class="modal-content modal-media-content">
                <div class="modal-header modal-media-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="box-title"><?php echo $this->lang->line('download_attachments'); ?>
                    </h4>
                </div>
                <div  class="scroll-area">
                    <div class="modal-body pt0 pb0">
                        <div class="row pb10" id="set_attachments">
                           
                        </div>
                        <div class="">
                            <div class="box-footer pr0">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="add_quiz_modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content modal-media-content">
                <div class="modal-header modal-media-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="box-title"> <?php echo $this->lang->line('add_new_quiz'); ?>
                    </h4>
                </div>
                <form id="add_quiz_form" method="post" class="ptt10" enctype="multipart/form-data">
                <div class="modal-body pt0 pb0">
                    <div class="row">
                        <div class="col-lg-12 col-md-12 col-sm-12">
                            
                                <input type="hidden" name="quiz_courseid" id="quiz_courseid">
                                <input type="hidden" name="sectionId" id="sectionId">
                                <div class="row">
                                    <div class="col-sm-12">
                                        <div class="form-group">
                                            <label for="pwd"><?php echo $this->lang->line('quiz_title'); ?></label><small class="req"> *</small>
                                            <input type="text" id="quiz_title" autocomplete="off" class="form-control" name="quiz_title">
                                            <span id="title_error" class="text-danger"></span>
                                        </div>
                                    </div>
                                </div><!--./row-->
                                <div class="row">
                                    <div class="col-sm-12">
                                        <div class="form-group">
                                            <label for="pwd"><?php echo $this->lang->line('instruction'); ?></label>
                                            <textarea rows="5"  class="form-control" name="quiz_instruction" id="quiz_instruction"></textarea>
                                            <span id="quiz_instruction_error" class="text-danger"></span>
                                        </div>
                                    </div>
                                </div>
                            
                        </div><!--./col-md-12-->
                    </div><!--./row-->
                    <div class="">
                        <div class="box-footer pr0">
                            <a  id="add_quiz_btn" class="btn btn-info pull-right"><span id="quiz_loader"></span> <?php echo $this->lang->line('save'); ?></a>
                        </div>
                    </div>
                </div>
                </form>
            </div>
        </div>
    </div>

     <div class="modal fade" id="edit_quiz_modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content modal-media-content">
                <div class="modal-header modal-media-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="box-title"> <?php echo $this->lang->line('edit_quiz'); ?>
                    </h4>
                </div>
                <form id="edit_quiz_form" method="post" class="ptt10" enctype="multipart/form-data">
                    <div class="modal-body pt0 pb0">
                        <div class="row">
                            <div class="col-lg-12 col-md-12 col-sm-12">

                                    <input type="hidden" name="edit_quiz_course" id="edit_quiz_course">
                                    <input type="hidden" name="quizId" id="quizId">
                                    <input type="hidden" name="edit_sectionId" id="edit_sectionId">
                                    <div class="row">
                                        <div class="col-sm-12">
                                            <div class="form-group">
                                                <label for="pwd"><?php echo $this->lang->line('quiz_title'); ?></label><small class="req">* </small>
                                                <input type="text" id="edit_quiz_title" autocomplete="off" class="form-control" name="edit_quiz_title">
                                                <span id="title_error" class="text-danger"></span>
                                            </div>
                                        </div>
                                    </div><!--./row-->
                                    <div class="row">
                                        <div class="col-sm-12">
                                            <div class="form-group">
                                                <label for="pwd"><?php echo $this->lang->line('instruction'); ?></label>
                                                <textarea rows="5"  class="form-control" name="edit_quiz_instruction" id="edit_quiz_instruction"></textarea>
                                                <span id="quiz_instruction_error" class="text-danger"></span>
                                            </div>
                                        </div>
                                    </div>
                                
                            </div><!--./col-md-12-->
                        </div><!--./row-->
                        <div class="">
                            <div class="box-footer pr0">
                                <a  id="edit_quiz_btn" class="btn btn-info pull-right"> <span id="quiz_loaders"></span><?php echo $this->lang->line('save'); ?></a>
                            </div>
                        </div>
                    </div>
                </form>    
            </div>
        </div>
    </div>

 <div class="modal fade" id="question_modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content modal-media-content">
      <div class="modal-header modal-media-header">
        <div class="quizplusrighttop">
          <a id="add_new_question_btn" class="btn btn-info quizsavebtn-add"> <span id="question_loader"></span> <?php echo $this->lang->line('save'); ?></a>
          <button type="button" class="add-row plusgreenbtn addplus-add" data-toggle="tooltip"data-original-title="<?php echo $this->lang->line('add_question'); ?>"><i class='fa fa-plus'></i></button>
        </div>
        <button type="button" onclick="clear_question()" class="close" data-dismiss="modal">&times;</button>
        <h4 class="box-title"> <?php echo $this->lang->line('quiz_questions'); ?></h4><span id="total_question_" ></span>
      </div>
        <div class="scroll-area">
          <div class="modal-body pb5">
            <div class="row">
              <div class="col-lg-12 col-md-12 col-sm-12">
              <form id="add_new_question_form_ID" method="post">
                <input type="hidden" name="quiz_id" id="quiz_id">
                <input type="hidden" name="question_course_id" id="question_course_id">
                <table id="table_id" class="table tableinput">
                  <tbody>
                    <tr id="rowID0">
                      <td class="border0 pl0" width="75"><?php echo $this->lang->line('question'); ?><small class="req"> *</small></td>
                      <td class="pr0 border0 relative">
                        <input type='text' name='question_0' class="form-control pull-left">
                        <button type='button' data-toggle='tooltip' data-original-title='<?php echo $this->lang->line('delete_question'); ?>' data-placement="top" data-id='0' class='delete-row addclose quizplusright'><i class='fa fa-remove'></i></button>
                      </td>
                    </tr>
                    <tr class='options0'>
                      <td colspan="2" class="border0">
                        <div class="quizopationpad-left table-responsive">
                            <table width="100%" align="right">
                             <tr>
                              <td width="30">A <small class="req"> *</small></td>
                              <td>
                                <div class="input-group input-group-full-width">
                                <input type='text' name='question_0_options_0' class="form-control">
                                <span class="input-group-addon input-group-addon-bg"><input type='checkbox' value="option_1" name='question_0_result_0[]' title="<?php echo $this->lang->line('check_for_correct_option'); ?>"></span>
                                </div>
                              </td>
                            </tr>
                            <tr>
                              <td>B <small class="req"> *</small></td>
                              <td>
                                <div class="input-group input-group-full-width">
                                <input type='text' name='question_0_options_1' class="form-control">
                                <span class="input-group-addon input-group-addon-bg"><input type='checkbox' value="option_2" name='question_0_result_0[]' title="<?php echo $this->lang->line('check_for_correct_option'); ?>"></span>
                                </div>
                              </td>
                            </tr>
                            <tr>
                              <td>C</td>
                              <td>
                                <div class="input-group input-group-full-width">
                                <input type='text' name='question_0_options_2' class="form-control">
                                <span class="input-group-addon input-group-addon-bg"><input type='checkbox' value="option_3" name='question_0_result_0[]' title="<?php echo $this->lang->line('check_for_correct_option'); ?>"></span>
                                </div>
                              </td>
                            </tr>
                            <tr>
                              <td>D</td>
                              <td>
                                <div class="input-group input-group-full-width">
                                <input type='text' name='question_0_options_3' class="form-control">
                                <span class="input-group-addon input-group-addon-bg"><input type='checkbox' value="option_4" name='question_0_result_0[]' title="<?php echo $this->lang->line('check_for_correct_option'); ?>"></span>
                                </div>
                              </td>
                            </tr>
                            <tr>
                              <td>E</td>
                              <td>
                                <div class="input-group input-group-full-width">
                                <input type='text' name='question_0_options_4' class="form-control">
                                <span class="input-group-addon input-group-addon-bg"><input type='checkbox' value="option_5" name='question_0_result_0[]' title="<?php echo $this->lang->line('check_for_correct_option'); ?>"></span>
                                </div>
                              </td>
                            </tr>
                          </table>
                        </div>  
                      </td>
                    </tr>
                  </tbody>
                </table>
                <input type="hidden" id="question_count" name="question_count" value="0"/>
              </form>
            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="edit_question_modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content modal-media-content">
                <div class="modal-header modal-media-header">
          <div class="quizplusrighttop">
            <a id="edit_new_question_btn" class="btn btn-info quizsavebtn"> <span id="question_loaders"></span><?php echo $this->lang->line('save'); ?></a>
            <button type="button" class="edit-row plusgreenbtn addplus" data-toggle="tooltip"data-original-title="<?php echo $this->lang->line('add_question'); ?>"><i class='fa fa-plus'></i></button>
          </div>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="box-title"> <?php echo $this->lang->line('quiz_questions'); ?></h4>
                    <?php echo $this->lang->line('total_question'); ?> <span id="total_edit_question" ></span>
          </div>
        <div class="scroll-area">
          <div class="modal-body pb0">
            <div class="row">
              <div class="col-lg-12 col-md-12 col-sm-12">
                <form id="edit_question_form" method="post">
                  <input type="hidden" name="editquestion_course_id" id="editquestion_course_id">
                  <div id="edit_question"></div>
                </form>
              </div>
            </div>
          </div>
                </div>
            </div>
        </div>    
    </div>

    <div id="course_preview_modal" class="modal fade" role="dialog">
      <div class="modal-dialog modalwrapwidth">
        <div class="modal-content">
          <div class="modal-header"><button type="button" class="close" data-dismiss="modal" onclick="stopvideo()">&times;</button></div>
            <div class="scroll-area">
              <div class="modal-body paddbtop">
                  <div class="row">
                    <div id="course_preview">
                    </div>
                  </div><!--./row-->
              </div><!--./modal-body-->
          </div>
        </div>
      </div>
    </div><!--#/coursedetailmodal-->

    <!-- Subtitle extraction preview modal (shared by course + lesson flows) -->
    <div id="subtitle_preview_modal" class="modal fade" role="dialog" aria-labelledby="subtitlePreviewModalLabel" tabindex="-1">
      <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">&times;</button>
            <h4 class="modal-title" id="subtitlePreviewModalLabel">Subtitles preview</h4>
          </div>
          <div class="modal-body">
            <div id="subtitle_preview_meta" class="mb10" style="margin-bottom:10px;">
              <span id="subtitle_preview_segment_count" class="label label-info"></span>
              <span id="subtitle_preview_source" class="label label-default" style="margin-left:6px;"></span>
            </div>

            <ul class="nav nav-tabs" role="tablist">
              <li role="presentation" class="active">
                <a href="#subtitle_tab_segments" aria-controls="subtitle_tab_segments" role="tab" data-toggle="tab">Segments</a>
              </li>
              <li role="presentation">
                <a href="#subtitle_tab_full" aria-controls="subtitle_tab_full" role="tab" data-toggle="tab">Full transcript</a>
              </li>
            </ul>
            <div class="tab-content" style="margin-top:10px;">
              <div role="tabpanel" class="tab-pane active" id="subtitle_tab_segments">
                <div id="subtitle_preview_segments_wrap" style="max-height:360px; overflow:auto; border:1px solid #e5e5e5; padding:8px; border-radius:4px;">
                  <table class="table table-striped" id="subtitle_preview_segments_table">
                    <thead><tr><th style="width:80px;">Start</th><th style="width:80px;">End</th><th>Text</th></tr></thead>
                    <tbody></tbody>
                  </table>
                </div>
              </div>
              <div role="tabpanel" class="tab-pane" id="subtitle_tab_full">
                <textarea id="subtitle_preview_full_transcript"
                          class="form-control"
                          rows="12"
                          readonly
                          style="max-height:360px;"></textarea>
              </div>
            </div>

            <div id="subtitle_preview_save_hint" class="text-muted" style="margin-top:10px; display:none;">
              <small><i class="fa fa-info-circle"></i> Save the item first to persist these subtitles.</small>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            <button type="button" id="subtitle_preview_save_btn" class="btn btn-info">
              <span id="subtitle_preview_save_loader"></span> <?php echo $this->lang->line('save'); ?>
            </button>
          </div>
        </div>
      </div>
    </div><!--#/subtitle_preview_modal-->


<div class="modal fade" id="add_assignment_modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content modal-media-content">
            <div class="modal-header modal-media-header">
                <button type="button" class="close close_btn" data-dismiss="modal">&times;</button>
                <h4 class="modal-title box-title"><span id="heading"><?php echo $this->lang->line('add_assignment'); ?></span></h4>
            </div>
            <form id="add_assignment" method="post" class="ptt10" enctype="multipart/form-data">
                <div class="modal-body pt0 pb0">
                    <div class="row">
                        <div class="col-lg-12 col-md-12 col-sm-12">
                            <div class="row">
                                <input type="hidden" id="assignment_course_id" value="0" name="assignment_course_id">
                                <input type="hidden" id="assignment_section_id" value="0" name="assignment_section_id">
                                <input type="hidden" id="edit_assignment_id" value="0" name="edit_assignment_id">
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <label for="pwd"><?php echo $this->lang->line('title'); ?></label><small class="req"> *</small>
                                        <input type="text"  name="assignment_title" class="form-control" id="assignment_title" value="" >
                                        <span id="assignment_title" class="text-danger"></span>
                                    </div>
                                </div>
                                 <div class="col-sm-4">
                                    <div class="form-group">
                                        <label for="pwd"><?php echo $this->lang->line('assignment_date'); ?></label>
                                        <input type="text"  name="assignment_date" class="form-control date" id="assignment_date" value="<?php echo set_value('date', date($this->customlib->getSchoolDateFormat())); ?>" readonly="">
                                        <span id="date_add_error" class="text-danger"></span>
                                    </div>
                                </div>
                                <div class="col-sm-4">
                                    <div class="form-group">
                                        <label for="pwd"><?php echo $this->lang->line('submission_date'); ?></label>
                                        <input type="text" id="submit_date" name="submit_date" class="form-control date" value="<?php echo set_value('follow_up_date', date($this->customlib->getSchoolDateFormat())); ?>">
                                    </div>
                                </div>
                                <div class="col-sm-4">
                                    <div class="form-group">
                                        <label for="pwd"><?php echo $this->lang->line('max_marks'); ?></label>
                                        <!-- <small class="req"> *</small> -->
                                        <input type="text" id="marks" name="marks" class="form-control">
                                    </div>
                                </div>
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <label for="pwd"><?php echo $this->lang->line('attach_document'); ?></label>
                                        <input type="file" id="file" name="userfile" class="form-control filestyle">
                                    </div>
                                </div>
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <label for="email"><?php echo $this->lang->line('description'); ?></label><small class="req"> *</small>
                                        <textarea name="description" id="compose-textarea" class="form-control" ></textarea>
                                    </div>
                                </div>
                            </div><!--./row-->
                        </div><!--./col-md-12-->
                    </div><!--./row-->
                </div>
                <div class="modal-footer">
                    <div class="pull-right">
                        <button type="submit" class="btn btn-info pull-right" id="submit" data-loading-text="<i class='fa fa-spinner fa-spin '></i> <?php echo $this->lang->line('please_wait'); ?>"><?php echo $this->lang->line('save') ?></button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade pr0 pl0" id="evaluation" tabindex="-1" role="dialog" aria-labelledby="evaluation" >
    <div class="modal-dialog modal-full-width" role="document">
        <div class="modal-content modal-media-content h-100">
            <div class="modal-header modal-media-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><?php echo $this->lang->line("evalute_assignment"); ?></h4>
            </div>
            <div class="modal-body pt0 pb0" id="evaluation_details">
            </div>
        </div>
    </div>
</div>

<div id="myQuestionListModal" class="modal fade pl0">
    <div class="modal-dialog modal-full-width">
        <!-- Modal content-->
        <div class="modal-content h-100 rounded-0">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"></h4>
            </div>
            <div class="modal-body">
               <div class="question_list_result quescroll">
               </div>
           </div>
        </div>
        </div>
    </div>

 <div class="modal fade" id="mydeleteModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
    <form action="<?php echo site_url('onlinecourse/courseexam/deleteExamQuestions') ?>" id="delete_question" method="POST">
        <input type="hidden" value="0" id="question_id" name="question_id"/>
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="myModalLabel"><?php echo $this->lang->line('delete_question'); ?></h4>
      </div>
      <div class="modal-body">
        <?php echo $this->lang->line('delete_confirm'); ?>
      </div>
      <div class="modal-footer">
         <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo $this->lang->line('close') ?></button>
         <button type="submit" class="btn btn-primary pull-right" data-loading-text="<i class='fa fa-spinner fa-spin '></i><?php echo $this->lang->line('please_wait'); ?>" value=""><?php echo $this->lang->line('delete'); ?></button>
     </div>
    </form>
    </div>
    </div>
  </div>

 
<div class="modal fade pr-0" id="add_exam_modal" tabindex="-1" role="dialog" aria-labelledby="evaluation" style="padding-left: 0 !important">
     <div class="modal-dialog modal-lg" role="document">
        <!-- Modal content-->
        <div class="modal-content modal-media-content">
            <div class="modal-header modal-media-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                 <h4 class="modal-title box-title"><span id="exam_heading"><?php echo $this->lang->line('add_exam'); ?></span></h4>
            </div>            
			<form id="add_course_exam" method="post" class="ptt10" enctype="multipart/form-data">
                <div class="modal-body">
					<input type="hidden" id="exam_id" value="0" name="exam_id">
                    <input type="hidden" id="exam_course_id" value="0" name="exam_course_id">
                    <input type="hidden" id="exam_section_id" value="0" name="exam_section_id">
                <div class="row">
                    <div class="col-sm-12">
                    <div class="form-group">
                  <label class="checkbox-inline"><input type="checkbox" class="is_quiz" value="1" name="is_quiz" id="is_quiz"><?php echo $this->lang->line('quiz'); ?></label>
                  <span class="help-block"><?php echo $this->lang->line('check_on_quiz_message'); ?></span>
                 </div>
                     </div>
                    </div>
                    <div class="row">
                    <div class="col-sm-12">
                        <div class="form-group">
                            <label for="exam"><?php echo $this->lang->line('exam_title'); ?></label><small class="req"> *</small>
                            <input type="text" class="form-control" id="exam" name="exam">
                            <span class="text text-danger exam_error"></span>
                        </div>
                     </div>
                    </div>
                    <div class="row">
                <div class="col-sm-4">
                    <div class="form-group">
                        <label for="exam_from"><?php echo $this->lang->line('exam_from'); ?></label><small class="req"> *</small>
                            <div class="input-group">
                            <input class="form-control datetime_twelve_hour" name="exam_from" type="text" id="exam_from" >
                            <span class="input-group-addon" id="basic-addon2">
                                <i class="fa fa-calendar">
                                </i>
                            </span>
                        </div>
                        <span class="text text-danger exam_from_error"></span>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group">
                        <label for="exam_to"><?php echo $this->lang->line('exam_to'); ?></label>
                     <div class="input-group">
                            <input class="form-control datetime_twelve_hour" name="exam_to" type="text" id="exam_to" >
                            <span class="input-group-addon" id="basic-addon2">
                                <i class="fa fa-calendar">
                                </i>
                            </span>
                        </div>
                    </div>
                </div>
                    <div class="col-sm-4">
                    <div class="form-group">
                        <label for="exam_to"> <?php echo $this->lang->line('auto_result_publish_date') ?></label>
                          <div class="input-group">
                            <input class="form-control datetime_twelve_hour"  type="text" id="auto_publish_date" name="auto_publish_date">
                            <span class="input-group-addon" id="basic-addon2">
                                <i class="fa fa-calendar">
                                </i>
                            </span>
                        </div>
                    </div>
                </div>
                    </div>
                    <div class="row">
                <div class="col-sm-3">
                    <div class="form-group">
                        <label for="duration"><?php echo $this->lang->line('time_duration'); ?></label><small class="req"> *</small>
                        <input type="text" class="form-control timepicker" id="duration" name="duration">
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="form-group">
                        <label for="attempt"><?php echo $this->lang->line('attempt'); ?></label><small class="req"> *</small>
                        <input type="number" min="1" class="form-control" id="attempt" name="attempt" value="1">
                        <span class="text text-danger attempt_error"></span>
                    </div>
                </div>
                <div class="col-sm-3">
                      <div class="form-group">
                        <label for="attempt"><?php echo $this->lang->line('passing_percentage'); ?></label><small class="req"> *</small>
                        <input type="number" min="1" max="100" class="form-control" id="passing_percentage" name="passing_percentage">
                        <span class="text text-danger passing_percentage_error"></span>
                    </div>
                </div>
                    <div class="col-sm-3">
                      <div class="form-group">
                        <label for="attempt"><?php echo $this->lang->line('answer_word_limit'); ?></label><small class="req"> *</small>
                        <input type="number" min="-1" class="form-control" id="word_limit" value="-1" name="word_limit">
                        <span class="text text-danger"><?php echo $this->lang->line('set_minus_one_for_no_limit'); ?></span>
                    </div>
                </div>
                    </div>
                <div class="row">
                   <div class="form-group col-sm-12">
                    <label class="checkbox-inline">
                        <input type="checkbox" class="is_active" name="is_active" id="is_active" value="1">
                        <?php echo $this->lang->line('publish_exam'); ?>
                    </label>

                    <label class="checkbox-inline">
                        <input type="checkbox" class="publish_result" name="publish_result"  id="publish_result" value="1">
                        <?php echo $this->lang->line('publish_result'); ?>
                    </label>

                    <label class="checkbox-inline">
                        <input type="checkbox" class="is_neg_marking" name="is_neg_marking"  id="is_neg_marking" value="1">
                        <?php echo $this->lang->line('negative_marking') ?>
                    </label>

                    <label class="checkbox-inline">
                        <input type="checkbox" class="is_marks_display" name="is_marks_display"  id="is_marks_display" value="1">
                        <?php echo $this->lang->line('display_marks_in_exam'); ?>
                    </label>

                    <label class="checkbox-inline">
                        <input type="checkbox" class="is_random_question" name="is_random_question" id="is_random_question" value="1">
                        <?php echo $this->lang->line('random_question_order'); ?>
                    </label>
                </div>
                </div>
                <div class="row">
                    <div class="col-sm-12">
                        <div class="form-group" >
                            <label for="description"><?php echo $this->lang->line('description'); ?><small class="req"> *</small></label>
                            <textarea class="form-control" id="description" name="description"></textarea>
                            <span class="text text-danger description_error"></span>
                        </div>
                    </div>
                </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary" id="load" data-loading-text="<i class='fa fa-spinner fa-spin '></i> <?php echo $this->lang->line('saving') ?>"><?php echo $this->lang->line('save') ?></button>
                </div>
            </form>
           </div>
    </div>
</div>
	

<div id="assign_question_modal" class="modal fade pl0 pr0" role="dialog">
    <div class="modal-dialog modal-full-width">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <a  data-backdrop="static" data-keyboard="false" class="close" data-dismiss="modal">&times;</a>
                <h4 class="modal-title"><?php echo $this->lang->line('select_questions'); ?></h4>
            </div>
            <div class="modal-body">
                <input type="hidden" name="modal_exam_id" value="0" id="modal_exam_id">
                <input type="hidden" name="modal_is_quiz" value="0" id="modal_is_quiz">
                <form action="" method="POST" accept-charset="utf-8" id="form_search">
                    <div class="row">
                    <div class="col-md-2 col-sm-6">
                        <div class="form-group">
                        <label><?php echo $this->lang->line('question_tag'); ?></label>
                        <select class="form-control" name="question_tag" id="question_tag">
                        <option value=""><?php echo $this->lang->line('select'); ?></option>
                        <?php
                           foreach($gettags as $question_tag_key => $question_tag_value){   ?>
                           <option value="<?php echo $question_tag_value['id']; ?>"><?php echo $question_tag_value['tag_name']; ?></option>
                        <?php } ?>
                        </select>
                        </div>
                    </div>
                    <div class="col-md-2 col-sm-6">
                        <div class="form-group">
                            <label><?php echo $this->lang->line('seach_by_keyword'); ?></label>
                            <input type="text" class="form-control" name="keyword" id="keyword" >
                        </div>
                    </div>                    
                    <div class="col-md-2 col-sm-6">
                        <div class="form-group">
                        <label><?php echo $this->lang->line('question_type'); ?></label>
                        <select class="form-control" name="question_type" id="question_type">
                      <option value=""><?php echo $this->lang->line('select'); ?></option>
                      <?php
                      foreach($question_type as $question_type_key => $question_type_value){   ?>
                          <option value="<?php echo $question_type_key; ?>"><?php echo $question_type_value; ?></option>
                      <?php } ?>
                        </select>
                        </div>
                    </div>
                    <div class="col-md-2 col-sm-6">
                        <div class="form-group">
                        <label><?php echo $this->lang->line('question_level'); ?></label>
                        <select class="form-control" name="question_level" id="question_level">
                       <option value=""><?php echo $this->lang->line('select'); ?></option>
                        <?php
                        foreach ($question_level as $question_level_key => $question_level_value) {   ?>
                            <option value="<?php echo $question_level_key; ?>"><?php echo $question_level_value; ?></option>
                        <?php } ?>
                        </select>
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-6">
                        <label style="display: block; visibility:hidden;"><?php echo $this->lang->line('search'); ?></label>
                        <button type="button" class="btn btn-info btn-sm post_search_submit"><?php echo $this->lang->line('search'); ?></button>
                    </div>
                    </div>
                </form>
                <div class="search_box_result quescroll">
                </div>
                <div class="row">
                    <div class="col-sm-12 col-md-5">
                    <div class="pt20 font-weight-bold"><?php echo $this->lang->line('showing'); ?> <span class="row_from"></span> <?php echo $this->lang->line('to'); ?> <span class="row_to"></span> <?php echo $this->lang->line('of'); ?> <span class="row_count"></span> <?php echo $this->lang->line('search'); ?></div>
                    </div>
                    <div class="col-sm-12 col-md-7 search_box_pagination"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
	$('#assign_question_modal').on('show.bs.modal', function (e) {
            //get data-id attribute of the clicked element
            var course_id = $(e.relatedTarget).data('data-id');
            var exam_id = $(e.relatedTarget).data('exam-id');
            var is_quiz = $(e.relatedTarget).data('is_quiz');
            if(is_quiz == 1){
                  $("select#question_type option[value*='descriptive']").prop('disabled',true);
            }else{
                  $("select#question_type option[value*='descriptive']").prop('disabled',false);
            }
            $('#modal_exam_id').val(exam_id);
            $('#modal_is_quiz').val(is_quiz);
            //populate the textbox
            getQuestionByExam(1, exam_id,is_quiz);
	});
	
	function getQuestionByExam(page, exam_id,is_quiz) {     
        var keyword         = $('#form_search #keyword').val();
        var question_type   = $('#form_search #question_type').val();
        var question_level  = $('#form_search #question_level').val();
        var question_tag    = $('#form_search #question_tag').val();
         
        $.ajax({
            type: "POST",
            url: base_url + 'onlinecourse/courseexamquestion/search_question',
            data: {'page': page, 'exam_id': exam_id,'keyword':keyword,'question_type':question_type,'question_level': question_level,'is_quiz':is_quiz,'question_tag':question_tag}, // serializes the form's elements.
            dataType: "JSON", // serializes the form's elements.
            beforeSend: function () {
            },
            success: function (data){
                $('.search_box_result').html(data.content);
                $('.search_box_pagination').html(data.navigation);
                $('.row_from').html(data.show_from);
                $('.row_to').html(data.show_to);
                $('.row_count').html(data.total_display);
                if(data.show_to==0){
                    $('.search_box_result').html('<div class="alert alert-danger"><?php echo $this->lang->line("no_record_found"); ?></div>');
                }
            },
            error: function (xhr) { // if error occured
               alert("<?php echo $this->lang->line('error_occurred_please_try_again'); ?>");
            },
            complete: function () {

            }
        });
    }

    $(document).on('click', '.post_search_submit', function (e) {
        var _exam_id  = $('#modal_exam_id').val();
        var __is_quiz = $('#modal_is_quiz').val();
        getQuestionByExam(1, _exam_id,__is_quiz);
    });


</script>
	
<script>
  ( function ( $ ) {
    'use strict';
    $(document).ready(function() {
      emptyDatatable('course-list','data');
    });

  } ( jQuery ) );
</script>

<script>
    ( function ( $ ) {
		'use strict';
		$(document).ready(function () {
			initDatatable('course-list','onlinecourse/course/getcourselist',[],[],100);
		});
	} ( jQuery ) );
</script>

<script>
  $('a[data-toggle="tab"]').on('shown.bs.tab', function(e){
   $($.fn.dataTable.tables(true)).DataTable()
      .columns.adjust();
});
</script>

<script>
function clear_question()
{
  var total_question = $('#total_question').html();
  for (i = 1; i <= total_question; i++) {
    $('#rowID'+i).remove();
    $('.options'+i).remove();
    var new_count = $('#question_count').val();
    var count = 1;
    var new_count = new_count - count ;
    $('#question_count').val(new_count);
    var total_question = total_question - count;
    $('#total_question').html(total_question);
  }
}

function getcontent(type)
{
  if (type == 'video') {
    $("#video_detail").show();
    $("#attachment").hide();
    
  } else {
    $("#video_detail").hide();
    $("#attachment").show();
    $("#lesson_url").val('');   
  }
}

function geteditcontent(type){

  if (type == 'video' ) {
    $("#editvideo_detail").show();
    $("#editattachment").hide();
    
  } else {
    $("#editvideo_detail").hide();
    $("#editattachment").show();
    
  }
}

function add_outcomerow()
{
  var table = document.getElementById("outcometableID");
  var table_len = (table.rows.length);
  var id = parseInt(table_len + 1);
  var div = "<td><div class='form-group'><input type='text' name='outcomes[]' class='form-control'></div></td>";
  var row = table.insertRow(table_len).outerHTML = "<tr id='outcomerow" + id + "'>" + div + "<td valign='top'><button type='button' onclick='delete_outcomerow(" + id + ")' class='addclose'><i class='fa fa-remove'></i></button></td></tr>";
}

function delete_outcomerow(id)
{
  var table = document.getElementById("outcometableID");
  var rowCount = table.rows.length;
  $("#outcomerow" + id).remove();
}

function openCourse(evt, courseName) {

	if(courseName == 'course_card_tab'){
		$('#search_area').addClass('hide');
	}else{
		$('#search_area').removeClass('hide');
	}

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

 $('.course_preview_id').click(function(){
    var courseID = $(this).attr('data-id');
	$('#course_preview').html('');
    $.ajax({
     url  : "<?php echo base_url(); ?>onlinecourse/course/coursepreview",
     type : 'post',
     data : {courseID:courseID},
     beforeSend: function () {
      $('#course_preview').html('Loading...  <i class="fa fa-spinner fa-spin"></i>');
     },

     success : function(response){
       $('#course_preview').html(response);
     }
    });
  })

$(document).ready(function(){
    $('#course_detail_tab').show();
});

$('#add_course_btn').click(function(){	
  var formData = new FormData($('#add_course_form_ID')[0]);
  $.ajax({
        url: '<?php echo base_url(); ?>onlinecourse/course/create',
        type: 'post',
        data: formData,
        contentType: false,
        cache: false,
        processData: false,
        beforeSend: function () {
          $('#loader_btn').html('<i class="fa fa-spinner fa-spin"></i>');
        },

        success: function(data){
          var result = JSON.parse(data);
           if (result.status == "fail") {
              var message = "";
              $.each(result.error, function (index, value) {
                  message += value;
              });
              errorMsg(message);
          } else {
              successMsg(result.message);
              $('#add_course_modal').modal('hide');
              $('#course_detail_modal').modal('show');
              $('#add_course_form_ID')[0].reset();
              $(".dropify-clear").trigger("click");
              loadcoursedetail(result.course_id);
          }
        },

        error: function (xhr) { // if error occured
          $('#loader_btn').html('');
        },

        complete: function () {
          $('#loader_btn').html('');
        }
    });
});
})(jQuery);

function loadcoursedetail(courseID) {
	$('#course_detail').html('');
  $.ajax({
   url  : "<?php echo base_url(); ?>onlinecourse/course/coursedetail",
   type : 'post',
   data : {courseID:courseID},
   beforeSend: function () {
      $('#course_detail').html('Loading...  <i class="fa fa-spinner fa-spin"></i>');
    },
   success : function(response){
     $('#course_detail').html(response);
   }
 });
}

function publish_unpublish(courseID,status,title) {	
	if(status == 1){
		var confirmationBox = confirm("<?php echo $this->lang->line('are_you_sure_to_publish_course'); ?>");
	} else if (status == 0){
		var confirmationBox = confirm("<?php echo $this->lang->line('are_you_sure_to_unpublish_course'); ?>");	
	}	

	if (confirmationBox == true) {
	$.ajax({
		url  : "<?php echo base_url(); ?>onlinecourse/course/publish_unpublish",
		type : 'post',
		data : {courseID:courseID,status:status,title:title},  

		success : function(response){		
			successMsg('<?php echo $this->lang->line('update_message'); ?>');
			loadcoursedetail(courseID);
		}
	});
	}
}


(function ($) {
 "use strict";
$('.course_detail_id').click(function(){
 var courseID = $(this).attr('data-id');
  $.ajax({
    url  : "<?php echo base_url(); ?>onlinecourse/course/coursedetail",
    type : 'post',
    data : {courseID:courseID},
    beforeSend: function () {
      $('#course_detail').html('Loading...  <i class="fa fa-spinner fa-spin"></i>');
    },
    success : function(response){
      $('#course_detail').html(response);
   }
  });
});
})(jQuery);
</script>

<script type="text/javascript">
  (function ($) {
   "use strict";
    $(".sidebar-closebtn").on('click', function () {
      $(".fa-angle-right").toggleClass("rotate");
    });

    $("#menu-toggle").click(function (e) {
      e.preventDefault();
      $(".wrapper-modal").toggleClass("toggled");
    });
  })(jQuery);

</script>

<script>
(function ($) {
  "use strict";  

    // Select2 v4 + a hidden Bootstrap modal renders a broken dropdown (wrong
    // width, mispositioned popup) if we init while the modal is still
    // display:none. So we re-init every time the modal is actually visible
    // and always anchor the dropdown to the modal itself so it isn't clipped
    // or mispositioned.
    function __ss_destroySectionSelect() {
        var $sec = $('#section_id');
        if ($sec.length && $sec.hasClass('select2-hidden-accessible')) {
            try { $sec.select2('destroy'); } catch (e) {}
        }
    }

    function __ss_initSectionSelect() {
        if (!window.jQuery || !$.fn || !$.fn.select2) {
            return false;
        }
        var $sec = $('#section_id');
        if (!$sec.length) {
            return true;
        }
        var $modal = $sec.closest('.modal');
        __ss_destroySectionSelect();
        try {
            $sec.select2({
                placeholder: '<?php echo $this->lang->line('select'); ?>',
                width: '100%',
                dropdownParent: $modal.length ? $modal : $(document.body)
            });
        } catch (err) {
            if (window.console) { console.error('[section_id select2 init failed]', err); }
        }
        return true;
    }

    // Init when the Add Course modal is shown (the safe time to measure size
    // and position the dropdown).
    $(document).on('shown.bs.modal', '#add_course_modal', function () {
        __ss_initSectionSelect();
    });

    // Tear down when the modal hides so the next open always starts clean.
    $(document).on('hidden.bs.modal', '#add_course_modal', function () {
        __ss_destroySectionSelect();
    });

    function __ss_loadSectionsForClass(class_id) {
        var base_url = '<?php echo base_url() ?>';
        var $sec = $('#section_id');

        // Clear existing options (and the Select2 UI selection).
        $sec.empty();
        if ($sec.hasClass('select2-hidden-accessible')) {
            $sec.val(null).trigger('change');
        }

        if (!class_id) {
            return;
        }

        $.ajax({
            url: base_url + 'sections/getByClass',
            type: 'GET',
            data: { 'class_id': class_id },
            dataType: 'json',
            success: function (data) {
                if (window.console) { console.log('[section_id] loaded', data); }

                if (!data || !data.length) {
                    return;
                }

                var html = '';
                $.each(data, function (i, obj) {
                    var value = (typeof obj.id !== 'undefined' && obj.id !== null) ? obj.id : obj.section_id;
                    var label = obj.section;
                    html += '<option value="' + value + '">' + label + '</option>';
                });
                $sec.append(html);

                if ($sec.hasClass('select2-hidden-accessible')) {
                    $sec.trigger('change');
                }
            },
            error: function (xhr, status, err) {
                if (window.console) {
                    console.error('[section_id] load failed', status, err, xhr && xhr.responseText);
                }
            }
        });
    }

    $(document).on('change', '#class_id', function () {
        __ss_initSectionSelect();
        __ss_loadSectionsForClass($(this).val());
    });

})(jQuery);

function saveSection() {
  var courseid = $('#courseid').val();
  $.ajax({
    url: '<?php echo base_url(); ?>onlinecourse/coursesection/addsection/',
    type: 'POST',
    dataType: 'json',
    data: $("#formadd").serialize(),
    beforeSend: function () {
      $('#section_loader').html('<i class="fa fa-spinner fa-spin"></i>');
    },

    success: function (data) {
      if (data.status == "fail") {
        var message = "";

        $.each(data.error, function (index, value) {
          message += value;
        });
        errorMsg(message);
      } else {
        successMsg(data.message);
        $("#add_section_modal").modal('hide');
        coursedetail(courseid);
      }
    },

    error: function (xhr) { // if error occured
      $('#section_loader').html('');
    },

    complete: function () {
      $('#section_loader').html('');
    }
  });
}

function coursedetail(courseid) {
	$('#course_detail').html('');
  $.ajax({
    url  : "<?php echo base_url(); ?>onlinecourse/course/coursedetail",
    type : 'post',
    data : {courseID:courseid},
    success : function(response){
      $('#course_detail').html(response);
    }
  });
}

(function ($) {
  "use strict";
  $('#edit_section_btn').click(function(){
    var formData = new FormData($('#edit_section_form')[0]);
    var courseid = $('#courseid').val();
    $.ajax({
        url: '<?php echo base_url(); ?>onlinecourse/coursesection/editsection',
        type: 'post',
        data: formData,
        contentType: false,
        processData: false,
        beforeSend: function () {
          $('#section_loaders').html('<i class="fa fa-spinner fa-spin"></i>');
        },

        success: function(data){
            var result = JSON.parse(data);
            if (result.status == "fail") {
                var message = "";
                $.each(result.error, function (index, value) {
                    message += value;
                });
                errorMsg(message);
            } else {
                successMsg(result.message);
                $("#edit_section_modal").modal('hide');
                coursedetail(courseid);
            }
        },

        error: function (xhr) { // if error occured
          $('#section_loaders').html('');
        },
        complete: function () {
          $('#section_loaders').html('');
        }
    });
  });

  $('#add_quiz_btn').click(function(){
    var formData = new FormData($('#add_quiz_form')[0]);
    var courseid = $('#courseid').val();
    $.ajax({
        url: '<?php echo base_url(); ?>onlinecourse/coursequiz/add',
        type: 'post',
        data: formData,
        contentType: false,
        processData: false,
        beforeSend: function () {
          $('#quiz_loader').html('<i class="fa fa-spinner fa-spin"></i>');
        },

        success: function(data){
            var result = JSON.parse(data);
             if (result.status == "fail") {
                var message = "";
                $.each(result.error, function (index, value) {
                    message += value;
                });
                errorMsg(message);
            } else {

              successMsg(result.message);
              $("#add_quiz_modal").modal('hide');
              coursedetail(courseid);
            }
        },

        error: function (xhr) { // if error occured
          $('#quiz_loader').html('');
        },

        complete: function () {
          $('#quiz_loader').html('');
        }
    });
  });

  $('#edit_quiz_btn').click(function(){
    var formData = new FormData($('#edit_quiz_form')[0]);
    var courseid = $('#courseid').val();
    $.ajax({
        url: '<?php echo base_url(); ?>onlinecourse/coursequiz/edit',
        type: 'post',
        data: formData,
        contentType: false,
        processData: false,
        beforeSend: function () {
          $('#quiz_loaders').html('<i class="fa fa-spinner fa-spin"></i>');
        },

        success: function(data){
        var result = JSON.parse(data);
            if (result.status == "fail") {
                var message = "";
                $.each(result.error, function (index, value) {
                    message += value;
                });
                errorMsg(message);
            } else {
                successMsg(result.message);
                $("#edit_quiz_modal").modal('hide');
               coursedetail(courseid);
            }
        },

        error: function (xhr) { // if error occured
          $('#quiz_loaders').html('');
        },

        complete: function () {
          $('#quiz_loaders').html('');
        }
    });
  });

  $("#save_lesson").click(function(){
    var courseid = $('#courseid').val();
    var formData = new FormData($('#add_lesson_form')[0]);
    var files = $('#thumbnail')[0].files;
      $.ajax({
        url: '<?php echo base_url(); ?>onlinecourse/courselesson/addlesson',
        type: 'post',
        data: formData,
        contentType: false,
        processData: false,
        beforeSend: function () {
          $('#lesson_loader').html('<i class="fa fa-spinner fa-spin"></i>');
        },
        success: function(data){           
          var result = JSON.parse(data);
           if (result.status == "fail") {
              var message = "";
              $.each(result.error, function (index, value) {
                  message += value;
              });
              errorMsg(message);
          } else {
              successMsg(result.message);
              $("#add_lesson_modal").modal('hide');
              coursedetail(courseid);
          }
        },

        error: function (xhr) { // if error occured
          $('#lesson_loader').html('');
          $('#thumbnail_error').html("Please select image.");
        },

        complete: function () {
          $('#lesson_loader').html('');
        }
      });
  });

  $("#edit_lesson_btn").click(function(){
    var courseid = $('#courseid').val();
    var formData = new FormData($('#edit_lesson_form')[0]);
    var files = $('#lesson_thumbnail')[0].files;
      $.ajax({
        url: '<?php echo base_url(); ?>onlinecourse/courselesson/editlesson',
        type: 'post',
        data: formData,
        contentType: false,
        processData: false,
        beforeSend: function () {
          $('#lesson_loaders').html('<i class="fa fa-spinner fa-spin"></i>');
        },

        success: function(data){
          var result = JSON.parse(data);
           if (result.status == "fail") {
              var message = "";
              $.each(result.error, function (index, value) {
                  message += value;
              });

              errorMsg(message);

          } else {
              successMsg(result.message);
              $("#edit_lesson_modal").modal('hide');
              coursedetail(courseid);
          }
        },

        error: function (xhr) { // if error occured
          $('#lesson_loaders').html('');
          $('#lesson_thumbnail_error').html("Please select image.");
        },

        complete: function () {
          $('#lesson_loaders').html('');
        }
      });
  });

  $('#add_new_question_btn').click(function(){
    var courseid = $('#courseid').val();
    var formData = new FormData($('#add_new_question_form_ID')[0]);

      $.ajax({
        url: '<?php echo base_url(); ?>onlinecourse/coursequiz/addnewquestion',
        type: 'post',
        data: formData,
        contentType: false,
        processData: false,
        beforeSend: function () {
          $('#question_loader').html('<i class="fa fa-spinner fa-spin"></i>');
        },
        success: function(data){
          var result = JSON.parse(data);
           if (result.status == "fail") {
              var message = "";
              $.each(result.error, function (index, value) {
                  message += value;
              });
              errorMsg(message);
          } else {
              successMsg(result.message);
              $('#add_new_question_form_ID')[0].reset();
              $('#question_modal').modal('hide');
              window.location.reload(true);
          }
        },
        error: function (xhr) { // if error occured
            $('#question_loader').html('');
        },
        complete: function () {
            $('#question_loader').html('');
        }
      });
  });

  $('#edit_new_question_btn').click(function(){
    var courseid = $('#courseid').val();
    var formData = new FormData($('#edit_question_form')[0]);
      $.ajax({
        url: '<?php echo base_url(); ?>onlinecourse/coursequiz/editnewquestion',
        type: 'post',
        data: formData,
        contentType: false,
        processData: false,
        beforeSend: function () {
            $('#question_loaders').html('<i class="fa fa-spinner fa-spin"></i>');
        },
        success: function(data){
          var result = JSON.parse(data);
            if (result.status == "fail") {
                var message = "";
                $.each(result.error, function (index, value) {
                    errorMsg(value);                   
                });               
            } else {
                successMsg(result.message);   
                window.location.reload(true);        
            }
          },
        error: function (xhr) { // if error occured
            $('#question_loaders').html('');
        },
        complete: function () {
           $('#question_loaders').html('');
        }
      });
  });

  $('.close_btn').click(function(){
    var courseid = $('#courseid').val();
    $('#order_section_modal').modal('hide');
    coursedetail(courseid);
  });
})(jQuery);

</script>

<script>
(function ($) {
  "use strict";
  $(document).ready(function() {

    $(".add-row").click(function() {
    var i = $('#question_count').val();
    i++;
    $('#question_count').val(i);
    var totalquestion = 1 + i;
    $('#total_question').html(totalquestion);  
    var markup = "<tr id='rowID"+i+"'><td width='75' class='border0 pl0'><?php echo $this->lang->line('question'); ?> <small class='req'> *</small></td><td class='pr0 border0 relative'><input type='text' name='question_"+i+"' class='form-control pull-left'><button type='button' data-toggle='tooltip' data-placement='top' data-original-title='<?php echo $this->lang->line('delete_question'); ?>' data-id='"+i+"' class='delete-row addclose quizplusright'><i class='fa fa-remove'></i></button></td></tr><tr class='options"+i+"'><td colspan='2' class=' border0'><div class='quizopationpad-left'><table width='100%' align='right'><tr><td width='30'>A <small class='req'> *</small></td><td><div class='input-group input-group-full-width'><input type='text' name='question_"+i+"_options_0' class='form-control'><span class='input-group-addon input-group-addon-bg'><input type='checkbox' title='<?php echo $this->lang->line('check_for_correct_option'); ?>' value='option_1' name='question_"+i+"_result_"+i+"[]'></span></div></td></tr><tr><td>B <small class='req'> *</small></td><td><div class='input-group input-group-full-width'><input type='text' name='question_"+i+"_options_1' class='form-control'><span class='input-group-addon input-group-addon-bg'><input type='checkbox'  title='<?php echo $this->lang->line('check_for_correct_option'); ?>' value='option_2' name='question_"+i+"_result_"+i+"[]'></span></div></td></tr><tr><td>C</td><td><div class='input-group input-group-full-width'><input type='text' name='question_"+i+"_options_2' class='form-control'><span class='input-group-addon input-group-addon-bg'><input type='checkbox'  title='<?php echo $this->lang->line('check_for_correct_option'); ?>' value='option_3' name='question_"+i+"_result_"+i+"[]'></span></div></td></tr><tr><td>D</td><td><div class='input-group input-group-full-width'><input type='text' name='question_"+i+"_options_3' class='form-control'><span class='input-group-addon input-group-addon-bg'><input type='checkbox'  title='<?php echo $this->lang->line('check_for_correct_option'); ?>' value='option_4' name='question_"+i+"_result_"+i+"[]'></span></div></td></tr><tr><td>E</td><td><div class='input-group input-group-full-width'><input type='text' name='question_"+i+"_options_4' class='form-control'><span class='input-group-addon input-group-addon-bg'><input type='checkbox'  title='<?php echo $this->lang->line('check_for_correct_option'); ?>' value='option_5' name='question_"+i+"_result_"+i+"[]'></span></div></td></tr></table></div></td></tr></tr>";

    $("#table_id").append(markup);
    });

  $(document).on('click','.delete-row',(function() {
    var removeQuestionID = $(this).attr('data-id');
    $('#rowID'+removeQuestionID).remove();
    $('.options'+removeQuestionID).remove();
    var new_count = $('#question_count').val();
    var count = 1;
    var new_count = new_count - count ;
    $('#question_count').val(new_count);
    var total_question = $('#total_question').html() - count;
    $('#total_question').html(total_question);
    }));

  $(".edit-row").click(function() {
    var i = $('#questioncount').val();
    i++;

    $('#questioncount').val(i);
    $('#total_edit_question').html(i);
    var markup = "<tr id='rowIDedit"+i+"'><td width='75' class='border0 pl0'><?php echo $this->lang->line('question'); ?> <small class='req'> *</small></td><td class='pr0 border0 relative'><input type='text' name='question_"+i+"' class='form-control pull-left'><input type='hidden' name='question_id_"+i+"' value='0' class='form-control pull-left'><button type='button' data-toggle='tooltip' data-placement='top' data-original-title='<?php echo $this->lang->line('delete_question'); ?>' data-id='"+i+"' class='delete-edit-row addclose quizplusright'><i class='fa fa-remove'></i></button></td></tr><tr class='optionsedit"+i+"'><td colspan='2' class='pr0 border0 quizopationpad-left'><table width='100%' align='right'><tr><td width='30'>A <small class='req'> *</small></td><td><div class='input-group input-group-full-width'><input type='text' name='question_"+i+"_option_0' class='form-control'><span class='input-group-addon input-group-addon-bg'><input type='checkbox'  title='<?php echo $this->lang->line('check_for_correct_option'); ?>' value='option_1' name='question_"+i+"_result_"+i+"[]'></span></div></td></tr><tr><td>B <small class='req'> *</small></td><td><div class='input-group input-group-full-width'><input type='text' name='question_"+i+"_option_1' class='form-control'><span class='input-group-addon input-group-addon-bg'><input type='checkbox'  title='<?php echo $this->lang->line('check_for_correct_option'); ?>' value='option_2' name='question_"+i+"_result_"+i+"[]'></span></div></td></tr><tr><td>C</td><td><div class='input-group input-group-full-width'><input type='text' name='question_"+i+"_option_2' class='form-control'><span class='input-group-addon input-group-addon-bg'><input type='checkbox'  title='<?php echo $this->lang->line('check_for_correct_option'); ?>' value='option_3' name='question_"+i+"_result_"+i+"[]'></span></div></td></tr><tr><td>D</td><td><div class='input-group input-group-full-width'><input type='text' name='question_"+i+"_option_3' class='form-control'><span class='input-group-addon input-group-addon-bg'><input type='checkbox'  title='<?php echo $this->lang->line('check_for_correct_option'); ?>' value='option_4' name='question_"+i+"_result_"+i+"[]'></span></div></td></tr><tr><td>E</td><td><div class='input-group input-group-full-width'><input type='text' name='question_"+i+"_option_4' class='form-control'><span class='input-group-addon input-group-addon-bg'><input type='checkbox'  title='<?php echo $this->lang->line('check_for_correct_option'); ?>' value='option_5' name='question_"+i+"_result_"+i+"[]'></span></div></td></tr></table></td></tr><tr>";

    $("#edit_table_id").append(markup);
    });

  $(document).on('click','.delete-edit-row',(function() {
    var removeQuestionID = $(this).attr('data-id');
    var question_id = $('#question_id_'+removeQuestionID).val();
    $('#rowIDedit'+removeQuestionID).remove();
    $('.optionsedit'+removeQuestionID).remove();
    var new_count = $('#questioncount').val();
    var count = 1;

    var new_count = new_count - count ;
    $('#questioncount').val(new_count);
    var deleted = $('#deleted').val();

    if (question_id != undefined){
      $('#deleted').val(deleted+ ',' +question_id);
    }
    var total_edit_question = $('#total_edit_question').html() - count;
    $('#total_edit_question').html(total_edit_question);
  }));
  });
})(jQuery);

function deloption(qid,optionid){
  $("#options_rowID"+qid+"_"+optionid).remove();
}

(function ($) {
  "use strict";
    $('#add_section_modal, #add_lesson_modal, #add_quiz_modal,#add_assignment_modal').on('hidden.bs.modal', function () {
    $(this).find('form').trigger('reset');
    $(".select2").select2().select2("val", '');

  })

  var dateNow = new Date();
  $('.timepicker').datetimepicker({
      format: 'HH:mm:ss',
   defaultDate:moment(dateNow).hours(0).minutes(0).seconds(0).milliseconds(0)

  });
})(jQuery);

</script>

<script>
  checkCourseProvider();
  function checkCourseProvider(){
    course_provider = $("#course_provider").val();
    if(course_provider == "s3_bucket" || course_provider == "file"){
      $("#course_url_div").addClass("hide");
      $("#s3_file_div").removeClass("hide");
    }
    else{
      $("#course_url_div").removeClass("hide");
      $("#s3_file_div").addClass("hide");
    }
  }

  checkLessonProvider();
  function checkLessonProvider(){
    course_provider = $("#lesson_provider").val();
    if(course_provider == "s3_bucket" || course_provider == "file"){
      $("#lesson_url_div").addClass("hide");
      $("#lesson_file_div").removeClass("hide");
      $("#lesson_url").val("");
    }

    else{
      $("#lesson_url_div").removeClass("hide");
      $("#lesson_file_div").addClass("hide");
    }
  }

  checkEditLessonProvider();
  function checkEditLessonProvider(){
    course_provider = $("#lesson_provider_edit").val();
    if(course_provider == "s3_bucket" || course_provider == "file"){
      $("#lesson_url_edit_div").addClass("hide");
      $("#lesson_file_edit_div").removeClass("hide");
       
    }
    else{
      $("#lesson_url_edit_div").removeClass("hide");
      $("#lesson_file_edit_div").addClass("hide");
    }
  }
</script>

<script>
  // Stop any playing video/audio inside an element (works for both HTML5
  // <video>/<audio> elements and YouTube / Vimeo iframes which don't expose
  // a pause() method directly — blanking the src unloads the embed).
  function stopMediaIn($scope){
    if (!$scope || !$scope.length) { return; }
    $scope.find('video, audio').each(function(){
      try {
        this.pause();
        this.currentTime = 0;
        // Detach sources so the browser releases the media buffer.
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
    stopMediaIn($('#course_preview_modal'));
    $('#course_preview').html('');
    $('#course_preview_modal').modal('hide');
  }

  // Always stop media when the preview modal closes, no matter how it was
  // dismissed (X button, ESC, or backdrop click).
  $(document).on('hide.bs.modal', '#course_preview_modal', function(){
    stopMediaIn($(this));
  });
  $(document).on('hidden.bs.modal', '#course_preview_modal', function(){
    $('#course_preview').html('');
  });
</script>

<script>
 function editcourse(courseid){
    var formData = new FormData($('#edit_course_form_ID')[0]);
    $.ajax({
        url: '<?php echo base_url(); ?>onlinecourse/course/updatecourse',
        type: 'post',
        data: formData,
        contentType: false,
        cache: false,
        processData: false,
        beforeSend: function () {
           $('#loader_button').html('<i class="fa fa-spinner fa-spin"></i>');
        },
        success: function(data){
            var result = JSON.parse(data);
            if (result.status == "fail") {
                var message = "";
                $.each(result.error, function (index, value) {
                    message += value;
                });
                errorMsg(message);
            } else {
                successMsg(result.message);
                $('#edit_course_modal').modal('hide');
                $('#edit_course').html('');
				coursedetail(courseid);                				
            }
        },
        error: function (xhr) { // if error occured
           $('#loader_button').html('');
        },
        complete: function () {
          $('#loader_button').html('');
        }
	});
 } 
 
 (function ($) {
    "use strict";
    $('.modal').on('hidden.bs.modal', function () {
        $(this).find('form').trigger('reset'); 
      
    }); 
})(jQuery);
</script>


<script>   
    $(function () {
        $("#compose-textarea,#description,#desc-textarea").wysihtml5();
    });

    $("#add_assignment").on('submit', (function (e) {
        e.preventDefault();
        var $this = $(this).find("button[type=submit]:focus");
        var assignment_course_id=$("#assignment_course_id").val();
        $.ajax({
            url: "<?php echo site_url("onlinecourse/courseassignment/add_course_assignment") ?>",
            type: "POST",
            data: new FormData(this),
            dataType: 'json',
            contentType: false,
            cache: false,
            processData: false,
            beforeSend: function () {
                $this.button('loading');
            },
            success: function (res){ 
                if (res.status == "fail") {
                    var message = "";
                    $.each(res.error, function (index, value) {
                        message += value;
                    });
                    errorMsg(message);
                } else {                   
                    successMsg(res.message);
                    $('#add_assignment_modal').modal('hide');             
                    $this.button('reset'); 
                    coursedetail(assignment_course_id);
                }
            },
            error: function (xhr) { // if error occured
                alert("<?php echo $this->lang->line('error_occurred_please_try_again'); ?>");
                $this.button('reset');
            },
            complete: function () {
                $this.button('reset');
            }
        });
    }));
	
     function evaluation(id) {
        $('#evaluation').modal('show');     
        $.ajax({
            url: '<?php echo base_url(); ?>/onlinecourse/courseassignment/evaluation/' + id,
            type: "POST",
            success: function (data) {
                $('#evaluation_details').html(data);               
            },
            error: function () {
                alert("<?php echo $this->lang->line('fail'); ?>");
            }
        });
    }

    $(document).on('submit','form#evaluation_data',function(e){
    $("#hlist").find('option.active').attr("selected", "selected");
        e.preventDefault();
        var $this = $(this).find("button[type=submit]:focus");
        $.ajax({          
            url: '<?php echo base_url(); ?>onlinecourse/courseassignment/add_evaluation/',
            type: "POST",
            data: new FormData(this),
            dataType: 'json',
            contentType: false,
            cache: false,
            processData: false,
            beforeSend: function () {
                $this.button('loading');
            },
            success: function (res){                
                if (res.status == "fail") {
                    var message = "";
                    $.each(res.error, function (index, value) {
                        message += value;
                    });
                    errorMsg(message);
                } else {
                    successMsg(res.message);
                    $('#evaluation').modal('hide');                   
                }
            },
            error: function (xhr) { // if error occured
                alert("<?php echo $this->lang->line('error_occurred_please_try_again'); ?>");
                $this.button('reset');
            },
            complete: function () {
                $this.button('reset');
            }
        });
});
</script>

<script>

	$("#add_course_exam").on('submit', (function (e) {
        e.preventDefault();
        var $this = $(this).find("button[type=submit]:focus");
        var exam_course_id=$("#exam_course_id").val();
        $.ajax({
            url: "<?php echo site_url("onlinecourse/courseexam/add_course_exam") ?>",
            type: "POST",
            data: new FormData(this),
            dataType: 'json',
            contentType: false,
            cache: false,
            processData: false,
            beforeSend: function () {
                $this.button('loading');
            },
            success: function (res){ 

                if (res.status == "fail") {
                    var message = "";
                    $.each(res.error, function (index, value) {
                        message += value;
                    });
                    errorMsg(message);
                } else {                   
                                
                    successMsg(res.message);
                    $('#add_exam_modal').modal('hide');             
                    $this.button('reset'); 
                    coursedetail(exam_course_id);
                }
            },
            error: function (xhr) { // if error occured
                alert("<?php echo $this->lang->line('error_occurred_please_try_again'); ?>");
                $this.button('reset');
            },
            complete: function () {
                $this.button('reset');
            }
        });
    })); 

// save question marks in online question marks table
$(document).on('change', '.question_chk', function () {
    var _exam_id        =   $('#modal_exam_id').val();
    var ques_mark       =   $(this).closest('div.section-box').find("input[name='question_marks']").val();
    var ques_neg_mark   =   $(this).closest('div.section-box').find("input[name='question_neg_marks']").val();
    updateCheckbox($(this).val(), _exam_id,ques_mark,ques_neg_mark);
});

    function updateCheckbox(question_id, exam_id,ques_mark,ques_neg_mark) {
        $.ajax({
            type: 'POST',
            url: base_url + 'onlinecourse/courseexam/questionAdd',
            dataType: 'JSON',
            data: {'question_id': question_id, 'onlineexam_id': exam_id,'ques_mark':ques_mark,'ques_neg_mark':ques_neg_mark},
            beforeSend: function () {
            },
            success: function (data) {
                if (data.status) {
                    successMsg(data.message);
                }
            },
            error: function (xhr) { // if error occured
                alert("<?php echo $this->lang->line('error_occurred_please_try_again'); ?>");
            },
            complete: function () {
            },
        });
    }


    $(document).on('click', '.exam_ques_list', function () {
        var $this=$(this);
        var recordid = $(this).data('exam-id');
        $.ajax({
                type: 'POST',
                url: base_url + 'onlinecourse/courseexam/getExamQuestions',
                data: {'recordid': recordid},
                dataType: 'JSON',
                beforeSend: function () {
                    $this.button('loading');
                },
                success: function (data) {
                $('#myQuestionListModal').modal('show');
                $('#myQuestionListModal .modal-title').html(data.exam.exam);
                $('#myQuestionListModal .question_list_result').html(data.result);
                    $this.button('reset');
                },
                error: function (xhr) { // if error occured
                    alert("<?php echo $this->lang->line('error_occurred_please_try_again'); ?>");
                    $this.button('reset');
                },
                complete: function () {
                    $this.button('reset');
                }
            });
        });

    $('#mydeleteModal').on('shown.bs.modal', function (e) {
        var question_id = $(e.relatedTarget).data('onlineexamQuestionId');
        $("#mydeleteModal input[id='question_id']").val(question_id);
    });

    $(document).on('submit','#delete_question',function(e) {
        e.preventDefault();
        var form = $(this);
        var question_id=form.find("input[id='question_id']").val();
        var url = form.attr('action');
        var $this = form.find("button[type=submit]:focus");
        $this.button('loading');
        $.ajax({
            url: url,
            type: "POST",
            data: new FormData(this),
            dataType: 'json',
            contentType: false,
            cache: false,
            processData: false,
            beforeSend: function () {
                $this.button('loading');
            },
            success: function (res){
                $('.question_row_'+question_id).remove();
                $this.button('reset');
                if (res.status == 1) {
                    $('#mydeleteModal').modal('hide');
                    successMsg(res.message);
                }
            },
            error: function (xhr) { // if error occured
                alert("<?php echo $this->lang->line('error_occurred_please_try_again'); ?>");
                $this.button('reset');
            },
            complete: function () {
                $this.button('reset');
            }

        });
    });


     $(document).on('click', '.tag_pills li', function () {
            var $this=$(this);
            $this.addClass('active').siblings().removeClass('active');
            var subject_pill_selected=($this.find('a').data('subjectId'));
            if(subject_pill_selected != 0){
            $("div[class*='tag_div_']").css("display","none");
            $('.tag_div_'+subject_pill_selected).css("display","block");
            }else{
               $("div[class*='tag_div_']").css("display","block");
            }
        });

     $(document).on('click', '.download_attachment', function () {
        var $this      = $(this);
        var lesson_id  = $(this).data('lesson-id');
        var section_id = $(this).data('section-id');
        $.ajax({
                type: 'POST',
                url: base_url + 'onlinecourse/courselesson/get_lesson_attachment',
                data: {'lesson_id': lesson_id,'section_id':section_id},
                dataType: 'JSON',
                beforeSend: function () {
                    $this.button('loading');
                },
                success: function (data) {                    
                    if(data.status==1){  
                        $('#set_attachments').html(data.page);
                    }else if(data.status==0){
                    }
                    $this.button('reset');
                },
                error: function (xhr) { // if error occured
                    alert("<?php echo $this->lang->line('error_occurred_please_try_again'); ?>");
                    $this.button('reset');
                },
                complete: function () {
                    $this.button('reset');
                }
            });
        });

	
</script>

<!-- Subtitle extraction: extract preview + save ------------------------ -->
<script>
(function ($) {
    "use strict";

    // Holds the most recent preview payload so Save can post it unchanged.
    // save_mode = 'server'  -> POST to save_extracted_subtitles endpoint (edit flows)
    // save_mode = 'pending' -> stash into hidden form inputs; backend will
    //                          persist after the create form is saved.
    var SubtitlePreview = {
        entity_type: null,       // 'course' | 'lesson'
        entity_id: null,
        save_mode: 'server',
        pending_target_form: null,
        pending_status_el: null,
        video_url: '',
        video_provider: '',
        video_id: '',
        segments: [],
        full_transcript: '',
        source: ''
    };

    function escapeHtml(text) {
        return String(text == null ? '' : text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function formatTimestamp(seconds) {
        var n = Number(seconds);
        if (!isFinite(n) || n < 0) { return '0:00'; }
        var total = Math.floor(n);
        var mm = Math.floor(total / 60);
        var ss = total % 60;
        return mm + ':' + (ss < 10 ? '0' + ss : ss);
    }

    function renderPreview(data) {
        $('#subtitle_preview_segment_count').text((data.segment_count || 0) + ' segments');
        if (data.source) {
            $('#subtitle_preview_source').text('source: ' + data.source).removeClass('hide');
        } else {
            $('#subtitle_preview_source').text('').addClass('hide');
        }

        var $tbody = $('#subtitle_preview_segments_table tbody').empty();
        var segments = data.segments || [];
        for (var i = 0; i < segments.length; i++) {
            var seg = segments[i] || {};
            var row = '<tr>'
                + '<td>' + escapeHtml(formatTimestamp(seg.start)) + '</td>'
                + '<td>' + escapeHtml(formatTimestamp(seg.end)) + '</td>'
                + '<td>' + escapeHtml(seg.text || '') + '</td>'
                + '</tr>';
            $tbody.append(row);
        }

        $('#subtitle_preview_full_transcript').val(data.full_transcript || '');
    }

    function toggleSaveButtonForEntity() {
        // Save is always enabled now: on edit flows it persists immediately,
        // on create flows it stashes the transcript into the add-form hidden
        // inputs and the server stores it when the course/lesson is created.
        $('#subtitle_preview_save_btn').prop('disabled', false);
        $('#subtitle_preview_save_hint').hide();
    }

    function doExtractPreview(opts) {
        // opts: {
        //   url, endpoint, entity_type, entity_id, video_provider,
        //   $button, $file (optional File object), save_mode ('server'|'pending'),
        //   pending_target_form (selector for the create form),
        //   pending_status_el   (selector of the green "extracted" span)
        // }
        var apiType  = (String(opts.video_provider || '').toLowerCase() === 'file') ? 'file' : 'url';
        var videoUrl = $.trim(opts.url || '');
        var fileObj  = opts.file || null;

        if (apiType === 'file') {
            if (!fileObj && !opts.entity_id) {
                errorMsg('Please choose a video file before extracting subtitles.');
                return;
            }
        } else {
            if (!videoUrl) {
                errorMsg('Please enter a video URL before extracting subtitles.');
                return;
            }
        }

        var $btn = opts.$button;
        var originalHtml = $btn.html();
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Extracting...');

        var formData = new FormData();
        formData.append('video_url', videoUrl);
        formData.append('video_provider', opts.video_provider || '');
        if (opts.entity_type === 'course') {
            formData.append('course_id', opts.entity_id || 0);
        } else if (opts.entity_type === 'lesson') {
            formData.append('lesson_id', opts.entity_id || 0);
        }
        if (apiType === 'file' && fileObj) {
            formData.append('video_file', fileObj, fileObj.name || 'video');
        }

        $.ajax({
            url: opts.endpoint,
            type: 'post',
            dataType: 'json',
            data: formData,
            contentType: false,
            processData: false,
            success: function (resp) {
                if (!resp || resp.status !== 'success') {
                    errorMsg(resp && resp.message ? resp.message : 'Subtitle extraction failed.');
                    if (resp && resp.debug) {
                        try {
                            console.warn('[extract_subtitles_preview] debug payload from server:', resp.debug);
                        } catch (_) { /* ignore */ }
                    }
                    return;
                }
                SubtitlePreview.entity_type          = opts.entity_type;
                SubtitlePreview.entity_id            = opts.entity_id || null;
                SubtitlePreview.save_mode            = opts.save_mode || 'server';
                SubtitlePreview.pending_target_form  = opts.pending_target_form || null;
                SubtitlePreview.pending_status_el    = opts.pending_status_el || null;
                SubtitlePreview.video_url            = resp.data.video_url || videoUrl;
                SubtitlePreview.video_provider       = resp.data.video_provider || (opts.video_provider || '');
                SubtitlePreview.video_id             = opts.video_id || '';
                SubtitlePreview.segments             = resp.data.segments || [];
                SubtitlePreview.full_transcript      = resp.data.full_transcript || '';
                SubtitlePreview.source               = resp.data.source || '';

                renderPreview({
                    segment_count:   resp.data.segment_count,
                    source:          resp.data.source,
                    segments:        resp.data.segments,
                    full_transcript: resp.data.full_transcript
                });
                toggleSaveButtonForEntity();
                $('#subtitle_preview_modal').modal('show');
            },
            error: function (xhr) {
                var msg = 'Subtitle extraction failed.';
                if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                } else if (xhr && xhr.responseText) {
                    try {
                        var parsed = JSON.parse(xhr.responseText);
                        if (parsed && parsed.message) { msg = parsed.message; }
                    } catch (_) { /* ignore */ }
                }
                errorMsg(msg);
            },
            complete: function () {
                $btn.prop('disabled', false).html(originalHtml);
            }
        });
    }

    function doSavePreview(opts) {
        opts = opts || {};

        // Pending save mode (create flows): stash the transcript into hidden
        // inputs inside the target form. The server will pick them up when
        // the course/lesson create form is submitted.
        if (SubtitlePreview.save_mode === 'pending') {
            var $form = SubtitlePreview.pending_target_form
                ? $(SubtitlePreview.pending_target_form)
                : null;
            if (!$form || !$form.length) {
                errorMsg('Cannot locate the form to attach subtitles to.');
                return;
            }
            $form.find('input[name="pending_transcript_segments_json"]').val(JSON.stringify(SubtitlePreview.segments));
            $form.find('input[name="pending_transcript_full"]').val(SubtitlePreview.full_transcript || '');
            $form.find('input[name="pending_transcript_source"]').val(SubtitlePreview.source || '');
            if (SubtitlePreview.pending_status_el) {
                $(SubtitlePreview.pending_status_el).removeClass('hide');
            }
            successMsg('Subtitles ready. They will be saved when you save the form.');
            $('#subtitle_preview_modal').modal('hide');
            if (typeof opts.onSaved === 'function') { opts.onSaved(); }
            return;
        }

        if (!SubtitlePreview.entity_type || !SubtitlePreview.entity_id) {
            errorMsg('Missing entity reference, cannot save subtitles.');
            return;
        }
        var endpoint;
        var postData = {
            video_url:       SubtitlePreview.video_url,
            video_provider:  SubtitlePreview.video_provider,
            video_id:        SubtitlePreview.video_id,
            segments_json:   JSON.stringify(SubtitlePreview.segments),
            full_transcript: SubtitlePreview.full_transcript,
            source:          SubtitlePreview.source
        };
        if (SubtitlePreview.entity_type === 'course') {
            endpoint = '<?php echo base_url(); ?>onlinecourse/course/save_extracted_subtitles';
            postData.course_id = SubtitlePreview.entity_id;
        } else {
            endpoint = '<?php echo base_url(); ?>onlinecourse/courselesson/save_extracted_subtitles';
            postData.lesson_id = SubtitlePreview.entity_id;
        }

        var $btn = $('#subtitle_preview_save_btn');
        $btn.prop('disabled', true);
        $('#subtitle_preview_save_loader').html('<i class="fa fa-spinner fa-spin"></i>');

        $.ajax({
            url: endpoint,
            type: 'post',
            dataType: 'json',
            data: postData,
            success: function (resp) {
                if (!resp || resp.status !== 'success') {
                    errorMsg(resp && resp.message ? resp.message : 'Failed to save subtitles.');
                    return;
                }
                successMsg(resp.message || 'Subtitles saved.');
                $('#subtitle_preview_modal').modal('hide');
                if (typeof opts.onSaved === 'function') { opts.onSaved(); }
            },
            error: function () {
                errorMsg('Failed to save subtitles.');
            },
            complete: function () {
                $btn.prop('disabled', false);
                $('#subtitle_preview_save_loader').html('');
            }
        });
    }

    // ---- Lesson edit flow (always in DOM) -------------------------------
    $(document).on('click', '#edit_lesson_extract_subtitles_btn', function () {
        var lessonId = parseInt($('#lessons_id').val(), 10) || 0;
        var url      = $.trim($('#lesson_urlID').val());
        var provider = $('#lesson_provider_edit').val() || '';
        if (!lessonId) {
            errorMsg('Please save the lesson first before extracting subtitles.');
            return;
        }
        // If the user picked a new file in the lesson edit form, send that
        // binary to the extractor; otherwise rely on the URL already on the lesson.
        var fileObj = null;
        var fileInput = document.querySelector('#edit_lesson_form_ID input[name="lesson_file"]');
        if (fileInput && fileInput.files && fileInput.files.length > 0) {
            fileObj = fileInput.files[0];
        }
        doExtractPreview({
            url:            url,
            file:           fileObj,
            endpoint:       '<?php echo base_url(); ?>onlinecourse/courselesson/extract_subtitles_preview',
            entity_type:    'lesson',
            entity_id:      lessonId,
            video_provider: provider,
            save_mode:      'server',
            $button:        $(this)
        });
    });

    // ---- Course edit flow (courseedit.php is loaded into #edit_course via ajax)
    $(document).on('click', '#edit_course_extract_subtitles_btn', function () {
        var $btn = $(this);
        var courseId = parseInt($('#edit_course_id_hidden').val(), 10) || 0;
        if (!courseId) {
            // Fallback: try the course id rendered in the edit form modal if present.
            courseId = parseInt($btn.data('course-id'), 10) || 0;
        }
        var provider = $('#course_provider_edit').val() || '';
        var url      = $.trim($('#edit_course_form_ID #course_url').val());
        // For locally uploaded files, the URL text input is empty; use the
        // server-rendered public URL of the uploaded file instead.
        if (!url && provider === 'file') {
            url = $.trim($btn.data('file-url') || '');
        }
        if (!courseId) {
            errorMsg('Course id is missing.');
            return;
        }

        // If the user picked a new file in the edit form, send it as a binary
        // upload so the extractor sees the NEW file, not the previously saved one.
        var fileObj = null;
        if (provider === 'file') {
            var fileInput = document.getElementById('s3_file');
            if (fileInput && fileInput.files && fileInput.files.length > 0) {
                fileObj = fileInput.files[0];
            }
        }

        doExtractPreview({
            url:            url,
            file:           fileObj,
            endpoint:       '<?php echo base_url(); ?>onlinecourse/course/extract_subtitles_preview',
            entity_type:    'course',
            entity_id:      courseId,
            video_provider: provider,
            save_mode:      'server',
            $button:        $btn
        });
    });

    // ---- Course CREATE flow (Add Course modal) ---------------------------
    $(document).on('click', '#add_course_extract_subtitles_btn', function () {
        var $btn = $(this);
        var provider = $('#add_course_form_ID #course_provider').val() || '';
        var url      = $.trim($('#add_course_form_ID #course_url').val());
        var fileObj  = null;
        if (provider === 'file') {
            var fileInput = document.querySelector('#add_course_form_ID #s3_file');
            if (fileInput && fileInput.files && fileInput.files.length > 0) {
                fileObj = fileInput.files[0];
            } else {
                errorMsg('Please choose a video file first.');
                return;
            }
        } else if (!url) {
            errorMsg('Please enter a video URL first.');
            return;
        }
        doExtractPreview({
            url:                  url,
            file:                 fileObj,
            endpoint:             '<?php echo base_url(); ?>onlinecourse/course/extract_subtitles_preview',
            entity_type:          'course',
            entity_id:            0,
            video_provider:       provider,
            save_mode:            'pending',
            pending_target_form:  '#add_course_form_ID',
            pending_status_el:    '#add_course_extract_subtitles_status',
            $button:              $btn
        });
    });

    // When the Add Course modal closes, clear any pending transcript state
    // so a subsequent course doesn't accidentally carry over old data.
    $(document).on('hidden.bs.modal', '#coursefooter, #add_course_modal', function () {
        var $form = $('#add_course_form_ID');
        $form.find('input[name="pending_transcript_segments_json"]').val('');
        $form.find('input[name="pending_transcript_full"]').val('');
        $form.find('input[name="pending_transcript_source"]').val('');
        $('#add_course_extract_subtitles_status').addClass('hide');
    });

    // ---- Lesson CREATE flow (Add Lesson modal) ---------------------------
    $(document).on('click', '#add_lesson_extract_subtitles_btn', function () {
        var $btn = $(this);
        var provider = $('#add_lesson_form #lesson_provider').val() || '';
        var url      = $.trim($('#add_lesson_form #lesson_url').val());
        var fileObj  = null;
        if (provider === 'file' || provider === 'html5' || provider === 's3_bucket') {
            // Lesson forms use `lesson_file` for local uploads.
            var fileInput = document.querySelector('#add_lesson_form input[name="lesson_file"]');
            if (fileInput && fileInput.files && fileInput.files.length > 0) {
                fileObj = fileInput.files[0];
            }
            if (!fileObj && !url) {
                errorMsg('Please choose a video file or enter a URL first.');
                return;
            }
        } else if (!url) {
            errorMsg('Please enter a video URL first.');
            return;
        }
        doExtractPreview({
            url:                  url,
            file:                 fileObj,
            endpoint:             '<?php echo base_url(); ?>onlinecourse/courselesson/extract_subtitles_preview',
            entity_type:          'lesson',
            entity_id:            0,
            video_provider:       provider,
            save_mode:            'pending',
            pending_target_form:  '#add_lesson_form',
            pending_status_el:    '#add_lesson_extract_subtitles_status',
            $button:              $btn
        });
    });

    $(document).on('hidden.bs.modal', '#addsectionlesson', function () {
        var $form = $('#add_lesson_form');
        $form.find('input[name="pending_transcript_segments_json"]').val('');
        $form.find('input[name="pending_transcript_full"]').val('');
        $form.find('input[name="pending_transcript_source"]').val('');
        $('#add_lesson_extract_subtitles_status').addClass('hide');
    });

    $('#subtitle_preview_save_btn').on('click', function () {
        doSavePreview({
            onSaved: function () {
                if (SubtitlePreview.entity_type === 'lesson') {
                    $('#edit_lesson_extract_subtitles_status').removeClass('hide');
                } else if (SubtitlePreview.entity_type === 'course') {
                    $('#edit_course_extract_subtitles_status').removeClass('hide');
                }
            }
        });
    });

    // Fetch the transcript status for the currently opened lesson edit form
    // so the green "subtitles extracted" indicator is accurate on open.
    window.refreshLessonSubtitleStatus = function (lessonId) {
        if (!lessonId) { return; }
        var url      = $.trim($('#lesson_urlID').val());
        var provider = $('#lesson_provider_edit').val() || '';
        $.ajax({
            url: '<?php echo base_url(); ?>onlinecourse/courselesson/get_subtitle_status',
            type: 'post',
            dataType: 'json',
            data: { lesson_id: lessonId, video_provider: provider, video_url: url },
            success: function (resp) {
                if (resp && resp.has_transcript && resp.detail && resp.detail.fingerprint_match) {
                    $('#edit_lesson_extract_subtitles_status').removeClass('hide');
                } else {
                    $('#edit_lesson_extract_subtitles_status').addClass('hide');
                }
            }
        });
    };

    // Equivalent helper for course edit. Called from courseedit.php after load.
    window.refreshCourseSubtitleStatus = function (courseId) {
        if (!courseId) { return; }
        var provider = $('#course_provider_edit').val() || '';
        var url      = $.trim($('#edit_course_form_ID #course_url').val());
        if (!url && provider === 'file') {
            url = $.trim($('#edit_course_extract_subtitles_btn').data('file-url') || '');
        }
        $.ajax({
            url: '<?php echo base_url(); ?>onlinecourse/course/get_subtitle_status',
            type: 'post',
            dataType: 'json',
            data: { course_id: courseId, video_provider: provider, video_url: url },
            success: function (resp) {
                if (resp && resp.has_transcript && resp.detail && resp.detail.fingerprint_match) {
                    $('#edit_course_extract_subtitles_status').removeClass('hide');
                } else {
                    $('#edit_course_extract_subtitles_status').addClass('hide');
                }
            }
        });
    };

    // When the lesson edit modal populates `#lessons_id`, refresh status.
    $(document).on('input change', '#lessons_id', function () {
        var lessonId = parseInt($(this).val(), 10) || 0;
        if (lessonId > 0) {
            window.refreshLessonSubtitleStatus(lessonId);
        } else {
            $('#edit_lesson_extract_subtitles_status').addClass('hide');
        }
    });

    // Clear status indicator when edit lesson modal closes.
    $('#edit_lesson_modal').on('hidden.bs.modal', function () {
        $('#edit_lesson_extract_subtitles_status').addClass('hide');
    });

})(jQuery);
</script>

