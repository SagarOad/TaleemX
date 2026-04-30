<style type="text/css">
  .draggable{
  border: 2px dashed #8d8d8d;
  padding: 0px 5px;
  cursor: move;
  background-color: #15b57e33;
  top: 0;
  max-width: 500px;
  }
  .submit-button{
  margin: 10px;
  cursor: pointer;
  }
  .back-button{
  padding: 12px 15px;
  background-color: #848484;
  border-radius: 5px;
  color: #fff;
  text-decoration: none;
  border: none;
  cursor: pointer;
  }
  .hidden-position{
  background-color: #ffd3d3 !important;
  }
</style>
<?php foreach ($get_data as $key => $value) {
  // code...
}?>
<div class="content-wrapper">
  <section class="content-header">
    <h1>
      <i class="fa fa-mortar-board"></i><?php echo $this->lang->line('add_tag'); ?>
    </h1>
  </section>
  <section class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="box box-primary">
          <div class="box-header ptbnull">
            <h3 class="box-title titlefix"><?php echo ('certificate--r'); ?></h3>
            <div class="box-tools pull-right">
            </div>
          </div>
          <div class="box-body">
            <div class="row">
            <div class="col-md-8">
            <input type="hidden" id="edit_id" name="edit_id" value="<?php echo $value['id'];?>">
            <div style="width: 750px; position: relative; text-align: center;">
              <!-- ================== -->
              <div class="certificate-text-position">
                
                 <img width="960px" height="720px"  id="preview"  src="<?php echo base_url('/uploads/course_content/online_course_certificate/'.$value['background_image']);?>">
                  <div class="draggable course_level" style="position: absolute; font-size: 16px; top: 388.844px; left: 658.844px;">{course_level}</div>
                  <div class="draggable course_language" style="position: absolute; font-size: 16px; top: 233.812px; left: 135.844px;">{course_language}</div>
                  <div class="draggable student_name" style="position: absolute; font-size: 40px; top: 427.891px; left: 359.891px;">{student}</div>
                  <div class="draggable duration_name" style="position: absolute; font-size: 16px; top: 376.812px; left: 390.797px;">{total_duration}</div>
                  <div class="draggable lesson_name" style="position: absolute; font-size: 16px; top: 379.875px; left: 177.844px;">{total_lesson}</div>
                  <div class="draggable course_completion_date" style="position: absolute; font-size: 20px; top: 253.922px; left: 741.891px;">{date}</div>
                 <div class="draggable certificate_text" style="position: absolute; width: 500px; text-align: center; font-size: 28px; top: 297.938px; font-family: &quot;Pinyon Script&quot;; left: 211.891px;">{certificate_text}</div>

                  <div class="draggable qrCode" style="position: absolute; width: 65px; height: 65px; text-align: center; font-size: 20px; top: 176.891px; left: 741.922px;">
                    <p style="text-align: center; padding: 4px 0px;">{qr_code}</p>
                  </div>
                            
              </div>
              <!-- ================== -->
              <button class="btn btn-info submit-button" onclick="save_text_position();">Update</button>
            </div>
            </div>
              <div class="col-md-4">
                <div style="padding: 10px;">
              <h3 style="padding-left: 20px;">Attention !</h3>
              <ul>
                <li>You can change the text positions by drag and drop--r</li>
                <li>Drag out of the certificate layout to keep an object hidden--r</li>
                <li>After changing your text positions, click the save button to save the parts--r</li>
              </ul>
            </div>
              </div>
            </div>
            <script>
              $(document).ready(function() {
                  $('.certificate_text').html("<?php echo $value['certificate_text']; ?>");
                  $('.hidden-position').show();
                  $(".draggable").draggableTouch();
                  //$(".draggable").draggableTouch("disable");
                  $(".draggable").on("dragstart", function(e, pos) {
                      //console.log(pos.left + "," + pos.top);
                  }).on("dragend", function(e, pos) {
                      console.log("dragend:", this, pos.left + "," + pos.top);
                      // if(pos.left <= 720 && pos.top <= 520){
                      if(pos.left <= 960 && pos.top <= 720){
                          if($(this).hasClass('hidden-position')){
                              $(this).removeClass('hidden-position');
                          }
                      }else{
                          if(!$(this).hasClass('hidden-position')){
                              $(this).addClass('hidden-position');
                          }
                      }
                  });
              });
              
              function save_text_position(){
                  $('.hidden-position').hide();
                  var btnText = $('.submit-button').html();
                  $('.submit-button').html('Please wait...');
                  var positionHtml = $('.certificate-text-position').html();
                  var edit_id = $('#edit_id').val();
                  $.ajax({
                      type: 'post',
                      url: base_url+'onlinecourse/coursecertificate/edit_certificate_position',
                      data: {'id':edit_id,'text_positions' : positionHtml},
                      success: function(result){
                        console.log(result)
                          $('.submit-button').html(btnText);
                          $('.hidden-position').show();
                          // successMsg("successfully saved");
                          // location.reload();
                          window.location.replace(base_url+'onlinecourse/coursecertificate/createcertificate');
                      }
                  });
              }
            </script>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>