<div class="scroll-area-fullheight-video">
<?php
if (!empty($lesson)) {
    if ($lesson['lesson_type'] == "video") {
        if ($lesson['video_provider'] == "html5") {?>
    <div id="player-overlay">
        <video id="videoPlayer" controls crossorigin="anonymous">
            <source src="<?php echo $lesson['video_url']; ?>" type="video/mp4">
        </video>
    </div>
<?php
} elseif ($lesson['video_provider'] == "youtube") {?>
    <div class='embed-container'>
        <iframe id="videoPlayer" width="560" height="315" src="//www.youtube.com/embed/<?php echo $lesson['video_id']; ?>?rel=0&version=3&modestbranding=1&autoplay=1&controls=1&showinfo=0&loop=1&enablejsapi=1" frameborder="0" allowfullscreen></iframe>
    </div>
<?php
} elseif ($lesson['video_provider'] == "vimeo") {?>
    <div class='embed-container'>
        <iframe id="videoPlayer" src="https://player.vimeo.com/video/<?php echo $lesson['video_id']; ?>" width="640" height="1164" frameborder="0" allow="autoplay; fullscreen" allowfullscreen></iframe>
    </div>
<?php }elseif ($lesson['video_provider'] == "s3_bucket") { ?>
    <div class='embed-container'>
        <video id="videoPlayer" controls width="100%" crossorigin="anonymous">
            <source src="<?php echo $lesson['s3_url'] ?>">
        </video>
    </div>
    <?php }
        // Transcript panel for the lesson video (only renders when subtitles exist).
        $sub_entity_type = 'lesson';
        $sub_entity_id   = isset($lesson['id']) ? (int) $lesson['id'] : 0;
        $sub_provider    = isset($lesson['video_provider']) ? $lesson['video_provider'] : '';
        $sub_video_el_id = 'videoPlayer';
        include APPPATH . 'views/onlinecourse/course/_video_subtitles_panel.php';
    } elseif ($lesson['lesson_type'] == "pdf" || $lesson['lesson_type'] == "text" || $lesson['lesson_type'] == "document") {?>
        <div class="downloadlession">
            <div class="videopdfdownload">
                <a href="#" id="downloadfile">
                    <div class="lession-text" id="lessontext"><?php echo $lesson['summary']; ?></div>
                </a>              
                <center>
                <?php
                if(isset($lesson['attachment']) && $lesson['attachment']!=null && $lesson['attachment']!=""){ ?>
                    <a  class="pull-left" href="<?php echo base_url()."user/studentcourse/download_lesson_single_attachment/".$sectionid."/".$lesson['id']; ?>">
                        <i class="fa fa-download"></i><div class="" id="lessontext"><small><?php echo $lesson['attachment'];?></small></div>
                    </a> 
                <?php }else if(!empty($lesson_attachments)){
                    foreach($lesson_attachments as $key=>$value){ ?>
                    <a  class="pull-left" href="<?php echo base_url()."user/studentcourse/download_lesson_attachment/".$sectionid."/".$value['lesson_id']."/".$value['id']; ?>">
                    <i class="fa fa-download"></i><div class="" id="lessontext"><small><?php echo $value['attachment_name'];?></small></div>
                    </a> 
                    <?php }  
                } ?>
                </center>
            </div>
        </div>
<?php }}?></div>