<?php
if ($status) {
?>
  <form action="<?php echo site_url('admin/qrattendance/attendance/saveAttendance'); ?>" method="POST" id="add_attendance">
    <input type="hidden" name="prev_attendance" value="<?php echo $student->attendance_id; ?>" id="prev_attendance">
    <input type="hidden" name="in_time" value="<?php echo $student->in_time; ?>" id="in_time">
    <input type="hidden" name="out_time" value="<?php echo $student->out_time; ?>" id="out_time">

    <?php

    if ($profile_type == "student") {
    ?>
      <input type="hidden" name="attendance_for" value="student">
      <input type="hidden" name="record_id" value="<?php echo $student->id; ?>">
      <h4 class="text text-primary text-center qrtitle"><?php echo $this->lang->line('student'); ?></h4>
      <div class="row gutters-sm">
        <div class="col-md-12 mb-3">
          <div class="card shadow-none">
            <div class="card-body">
              <div class="d-flex flex-column align-items-center text-center">
                <?php
                if (!empty($student->student_image)) {
                  $image_url = $this->media_storage->getImageURL($student->student_image);
                } else {

                  if ($student->gender == 'Female') {
                    $image_url = $this->media_storage->getImageURL("uploads/student_images/default_female.jpg");
                  } else {
                    $image_url = $this->media_storage->getImageURL("uploads/student_images/default_male.jpg");
                  }
                }
                ?>
                <img src="<?php echo $image_url; ?>" alt="<?php echo $this->customlib->getFullName($student->name, $student->middlename, $student->surname, $sch_setting->middlename, $sch_setting->lastname); ?>" class="rounded-circle" width="150">
                <div class="mt-3">
                  <h3><?php echo $this->customlib->getFullName($student->name, $student->middlename, $student->surname, $sch_setting->middlename, $sch_setting->lastname); ?> (<?php echo $student->admission_no; ?>)</h3>

                  <p class="text-muted font-size-sm"><?php echo $student->class . " (" . $student->section . ")" ?></p>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>
      <div class="row">
        <?php

        if ((($student->attendance_id) <= 0  && IsNullOrEmptyString($student->in_time) && IsNullOrEmptyString($student->out_time)) ||  ($student->attendance_id) > 0  && (IsNullOrEmptyString($student->in_time) || IsNullOrEmptyString($student->out_time))) {

          if (!$setting->auto_attendance) {

            if (!$attendance_range) {
        ?>
              <div class="alert alert-danger">Attendance setting is not configured for <?php echo date('d-m-Y H:i:s') ?> , please contact to admin.</div>
            <?php
            }
            ?>
            <div class="col-md-12">
              <div class="row">


                <div class="col-md-12">
                  <?php

                  if (($student->attendance_id) > 0  && !IsNullOrEmptyString($student->in_time)) {

                  ?>
                    <div> 
						<b><?php echo $this->lang->line('your_in_time_attendance'); ?>: </b><?php echo $student->in_time; ?>
						<p><?php echo $this->lang->line('please_save_attendance_as_out_attendance'); ?></p>
                    </div>
                  <?php

                  } else {
                  ?>
                    <div class="form-group">
                      <label for="input-type"><?php echo $this->lang->line('mark_attendance_as'); ?></label>
                      <div id="input-type" class="row">
                        <?php
                        foreach ($attendencetypeslist as $attendance_key => $attendance_value) {
                        ?>
                          <div class="col-sm-4">
                            <label class="radio-inline radio-sm-block">
                              <input name="attendance_type" class="attendance " id="attendance<?php echo $attendance_value['id'] ?>" value="<?php echo $attendance_value['id'] ?>" type="radio" autocomplete="off" <?php echo ($attendance_value['id'] == $defalt_selected_option) ? "checked='checked'" : "" ?>>
                              <b class="vertical-middle"><?php echo $this->lang->line(strtolower($attendance_value['long_lang_name'])); ?></b> </label>
                          </div>
                        <?php
                        }
                        ?>

                      </div>
                    </div>

                  <?php
                  }
                  ?>



                </div>
                <div class="col-md-12">
                  <button class="btn btn-sm btn-info add_attendance" type="submit" data-attendance_for="student" data-record_id="<?php echo $student->id; ?>" title="<?php echo $this->lang->line('save'); ?>" data-loading-text="<i class='fa fa-spinner fa-spin '></i> <?php echo $this->lang->line('submit_attendance') ?>"> <?php echo $this->lang->line('submit_attendance') ?></button>
                </div>
              </div>
            </div>
          <?php
          }
        } else {
          ?>
          <div class="alert alert-info">
            <?php echo $this->lang->line(strtolower($student->long_lang_name)); ?> : <?php echo $this->lang->line('attendance_has_been_already_submitted') ?>
          </div>
        <?php
        }
        ?>
      </div>
    <?php
    } else {
    ?>
      <input type="hidden" name="attendance_for" value="staff">
      <input type="hidden" name="record_id" value="<?php echo $student->id; ?>">
      <h4 class="text text-primary text-center qrtitle"><?php echo $this->lang->line('staff'); ?></h4>
      <div class="row gutters-sm">
        <div class="col-md-12 mb-3">
          <div class="card shadow-none">
            <div class="card-body">
              <div class="d-flex flex-column align-items-center text-center">
                <?php

                if (!empty($student->student_image)) {
                  $image_url = $this->media_storage->getImageURL("uploads/staff_images/" . $student->student_image);
                } else {

                  if ($student->gender == 'Female') {
                    $image_url = $this->media_storage->getImageURL("uploads/staff_images/default_female.jpg");
                  } else {
                    $image_url = $this->media_storage->getImageURL("uploads/staff_images/default_male.jpg");
                  }
                }
                ?>
                <img src="<?php echo $image_url; ?>" alt="<?php echo $student->name . " " . $student->surname; ?>" class="rounded-circle" width="150">
                <div class="mt-3">
                  <h3><?php echo $student->name . " " . $student->surname; ?> (<?php echo $student->employee_id; ?>)</h3>

                  <p class="text-muted font-size-sm"><?php echo $student->role; ?></p>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>
      <div class="row">

        <?php
        if ((($student->attendance_id) <= 0  && IsNullOrEmptyString($student->in_time) && IsNullOrEmptyString($student->out_time)) ||
          ($student->attendance_id) > 0  && (IsNullOrEmptyString($student->in_time) || IsNullOrEmptyString($student->out_time))
        ) {
          if (!$setting->auto_attendance) {
            if (!$attendance_range) {
        ?>
              <div class="alert alert-danger">Attendance setting is not configured for <?php echo date('d-m-Y H:i:s') ?> , please contact to admin.</div>
            <?php
            }
            ?>
            <div class="col-md-12">
              <div class="row">


                <div class="col-md-12">

                  <?php

                  if (($student->attendance_id) > 0  && !IsNullOrEmptyString($student->in_time)) {

                  ?>
                    <div>
						<b><?php echo $this->lang->line('your_in_time_attendance'); ?>: </b><?php echo $student->in_time; ?>
						<p><?php echo $this->lang->line('please_save_attendance_as_out_attendance'); ?></p>
                    </div>
				<?php

                  } else {

                  ?>
                    <div class="form-group">
                      <label for="input-type"><?php echo $this->lang->line('mark_attendance_as'); ?></label>
                      <div id="input-type" class="row">

                        <?php

                        foreach ($attendencetypeslist as $attendance_key => $attendance_value) {
                        ?>
                          <div class="col-sm-4">
                            <label class="radio-inline">
                              <input name="attendance_type" class="attendance" id="attendance<?php echo $attendance_value['id'] ?>" value="<?php echo $attendance_value['id'] ?>" type="radio" autocomplete="off" <?php echo ($attendance_value['id'] == $defalt_selected_option) ? "checked='checked'" : "" ?>>
                              <?php echo $this->lang->line(strtolower($attendance_value['long_lang_name'])); ?> </label>
                          </div>
                        <?php
                        }
                        ?>

                      </div>
                    </div>
                  <?php

                  }
                  ?>


                </div>
                <div class="col-md-12">

                  <button class="btn btn-sm btn-info add_attendance" type="submit" data-attendance_for="staff" data-record_id="<?php echo $student->id; ?>" title="<?php echo $this->lang->line('save'); ?>" data-loading-text="<i class='fa fa-spinner fa-spin '></i> <?php echo $this->lang->line('submit_attendance') ?>"> <?php echo $this->lang->line('submit_attendance') ?></button>

                </div>

              </div>
            </div>
          <?php
          }
        } else {
          ?>
          <div class="alert alert-info">
            <?php echo $this->lang->line(strtolower($student->long_lang_name)); ?> : <?php echo $this->lang->line('attendance_has_been_already_submitted') ?>
          </div>
        <?php
        }

        ?>
      </div>
    <?php

    }
    ?>

  </form>
<?php
} else {
?>
  <div class="alert alert-danger">
    <?php echo $this->lang->line('invalid_qr_code_barcode_please_try_again_or_contact_to_admin') ?>
  </div>
<?php

}

?>