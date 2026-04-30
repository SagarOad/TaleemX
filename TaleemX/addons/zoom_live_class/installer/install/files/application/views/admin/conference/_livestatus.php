<?php

$url = "";
$extracls = "";
if ($live->purpose == "meeting") {
    $name = ($live->create_by_surname == "") ? $live->create_by_name . " (" . $live->create_by_empid . ")" : $live->create_by_name . " " . $live->create_by_surname . " (" . $live->create_by_empid . ")";
} else {
    $name = ($live->create_for_surname == "") ? $live->create_for_name . " (" . $live->for_create_empid . ")" : $live->create_for_name . " " . $live->create_for_surname . " (" . $live->for_create_empid . ")";
}
?>

<div class="modal-header ">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
    <h4 class="modal-title"><?php echo $live->title; ?></h4>
</div>

<div class="modal-body zoom_details">
<div class="row">
    <div class="col-lg-4 col-md-4">
        <label>
            <span class="labalblock"> <?php echo $this->lang->line('host'); ?></span> <span class="text-dark robo-normal"><?php echo $name; ?></span>
        </label>
    </div>

    <div class="col-lg-4 col-md-4">
        <label>
            <span class="labalblock"> <?php echo $this->lang->line('date_time'); ?></span> <span class="text-dark robo-normal"><?php echo $this->customlib->dateyyyymmddToDateTimeformat($live->date); ?></span>
        </label>
    </div>

    <div class="col-lg-4 col-md-4">
        <label>
            <span class="labalblock"> <?php echo $this->lang->line('duration_minutes'); ?></span> <span class="text-dark robo-normal"><?php echo $live->duration; ?></span>
        </label>
    </div>

</div>




<?php

if ($live->purpose == "class") {

    $url = $live_url->start_url;
} elseif ($live->purpose == "meeting") {
    if ($live->created_id == $logged_staff_id) {
        $url = $live_url->start_url;
    } else {

        $url = $live_url->join_url;
    }
}

?>
Join With URL :<br><a href="<?php echo ($url); ?>" target="_blank"><?php echo $url; ?></a>

</div>
<?php

$st_label = "label label-success";


if ($live->created_id == $logged_staff_id && ($live->purpose == "meeting")) {
    $label_display = $this->lang->line('start') . " " . $this->lang->line('now');
    $label_type = 'label-success';
}
if ($live->created_id != $logged_staff_id && ($live->purpose == "meeting")) {
    $label_display = $this->lang->line('join') . " " . $this->lang->line('now');
    $label_type = 'label-success';
}
if (($live->purpose == "class") && ($live->created_id != $logged_staff_id)) {
    $label_display = $this->lang->line('join') . " " . $this->lang->line('now');
    $label_type = 'label-success';
}
if (($live->purpose == "class") && ($live->created_id == $logged_staff_id)) {
    $label_display = $this->lang->line('start') . " " . $this->lang->line('now');
    $label_type = 'label-success';
}

$target = "";
if ($conference_setting->use_zoom_app) {
    $target = "_blank";
    if ($live->purpose == "class") {
        $url = $live_url->start_url;
    } elseif ($live->purpose == "meeting") {
        if ($live->created_id == $logged_staff_id) {
            $url = $live_url->start_url;
        } else {

            $url = $live_url->join_url;
        }
    }
} else {

    if ($live->purpose == "class") {

        $url = site_url('admin/conference/join/class' . '/' . $live->id);
    } elseif ($live->purpose == "meeting") {

        $url = site_url('admin/conference/join/meeting' . '/' . $live->id);
    }
}



if (($live->created_id == $logged_staff_id) && $live->purpose == "meeting") {

?>
<div class="modal-footer">
        <a data-placement="left" href="<?php echo $url; ?>" class="btn btn-default btn-sm pull-right" target="<?php echo $target; ?>">
            <i class="fa fa-video-camera"></i> <?php echo $label_display; ?>
        </a>
    </div>
<?php

} elseif (($live->created_id != $logged_staff_id) && $live->purpose == "meeting") {
?>
<div class="modal-footer">
        <a data-placement="left" href="<?php echo $url; ?>" class="btn btn-default btn-sm pull-right <?php echo $extracls; ?>" data-id="<?php echo $live->id; ?>" target="<?php echo $target; ?>">
            <i class="fa fa-video-camera"></i> <?php echo $label_display; ?>
        </a>
    </div>
<?php
} else if ($live->purpose == "class") {

?>
<div class="modal-footer">
        <a data-placement="left" href="<?php echo $url; ?>" class="btn btn-default btn-sm pull-right" target="<?php echo $target; ?>">
            <i class="fa fa-video-camera"></i> <?php echo $label_display; ?>
        </a>
    </div>
<?php
}

?>

