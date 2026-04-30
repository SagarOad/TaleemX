<div class="row">
  <div class="col-md-12">
    <div class="mailbox-messages">
    <div class="download_label"><?php echo $this->lang->line('rating_details'); ?></div>
    <div class="table-responsive">           
      <table class="table table-striped table-bordered table-hover example">
        <thead>
          <tr>
            <th class="white-space-nowrap"><?php echo $this->lang->line('student_name'); ?></th>
            <th class="white-space-nowrap"><?php echo $this->lang->line('rating'); ?></th>
            <th class="white-space-nowrap"><?php echo $this->lang->line('review'); ?></th>
            <th class="text-right noExport"><?php echo $this->lang->line('action'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php if(!empty($courserating)){  
              foreach($courserating as $courserating_value){
           ?>
                <tr>
                  <td class="white-space-nowrap">
                      <?php 
                          echo $courserating_value['rating_provider_name'];
                          if($courserating_value['middlename'] != 'null'){
                              echo ' '.$courserating_value['middlename'];
                          }
                          if($courserating_value['lastname'] != 'null'){
                              echo ' '.$courserating_value['lastname'];
                          }
                      ?>
                      <?php  
                        if($courserating_value['guest_id'] > 0){ echo ' ('.$this->lang->line('guest').' - '.$courserating_value['admission_no'].')' ;}elseif($courserating_value['student_id'] > 0){  echo ' ('.$this->lang->line('student').' - '.$courserating_value['admission_no'].')' ; }                
                      ?>         
                  </td>
                  <td class="white-space-nowrap">
                    <?php for ($i = 1; $i <= 5; $i++) { ?>
                        <span class="fa fa-star" <?php if ($courserating_value['rating'] >= $i) { ?> style="color:orange;"<?php } ?>></span>
                    <?php } ?> 
                  </td>
                  <td><?php echo $courserating_value['review']; ?></td>
                  <td class="text-right">
                    <?php if ($this->rbac->hasPrivilege('course_rating_report', 'can_delete')) { ?>
                    <a href="#" class="btn btn-primary btn-xs delete_review" data-id="<?php echo $courserating_value['id']; ?>" data-toggle="tooltip" data-original-title="<?php echo $this->lang->line('delete') ?>"><i class="fa fa-trash"></i></a>
                    <?php } ?>
                  </td>
                 
                </tr>
        <?php } }else{ ?>
                <tr>
                  <th class="alert alert-danger text-left" colspan="4"><center><div class="box-body"><?php echo $this->lang->line('no_record_found'); ?></div></center></th>
                  
                </tr>
        <?php } ?>
        </tbody>
    </table>
</div>
  </div>
</div>
</div>