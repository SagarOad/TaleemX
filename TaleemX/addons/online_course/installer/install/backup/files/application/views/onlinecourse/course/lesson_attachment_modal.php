<?php 
foreach($attachments as $key=>$value){
$document_extions  = array("doc", "docx");
$txt_extions       = array('txt');
$pdf_extions       = array("pdf");
$excel_extions     = array("xlsx", "xlsm");
$ppt_extions       = array("pptx", "pptm", "ppt",);
$extension         = explode(".",$value['attachment']);
$thumb_file        = "";

if (in_array(strtolower($extension[1]), $document_extions)) {
     $thumb_file = $this->media_storage->getImageURL('backend/images/wordicon.png');
}
if (in_array(strtolower($extension[1]), $txt_extions)) {
       $thumb_file = $this->media_storage->getImageURL('backend/images/txticon.png');
}
if (in_array(strtolower($extension[1]), $pdf_extions)) {
       $thumb_file = $this->media_storage->getImageURL('backend/images/pdficon.png');
}
if (in_array(strtolower($extension[1]), $excel_extions)) {
       $thumb_file = $this->media_storage->getImageURL('backend/images/excelicon.png');
}
if (in_array(strtolower($extension[1]), $ppt_extions)) {
       $thumb_file = $this->media_storage->getImageURL('backend/images/pptxicon.png');
} ?>
     	<div class='col-lg-2 col-sm-4 col-md-3 col-xs-6 img_div_modal image_div' >      
       <div class='fadeoverlay'>
     	<div class='fadeheight'>      
       <a href="<?php echo base_url()."onlinecourse/courselesson/download_lesson_attachments/$section_id/$lesson_id/".$value['id']; ?>" class="btn btn-xs pull-right" data-toggle="tooltip" title="<?php echo $this->lang->line('download'); ?>">
       <img class='' data-fid='' data-content_type='' data-content_name='' data-is_image='' data-vid_url='' data-img=''  data-thumb_img='' 
              src='<?php echo $thumb_file;?>'>
       </a>   
        </div>   
       </div>
        <p class='fadeoverlay-para'><?php echo $value['attachment_name'];?></p>
       </div>
<?php } ?>

