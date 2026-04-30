<style type="text/css">
    .list-group-item.active, .list-group-item.active:focus, .list-group-item.active:hover {
        z-index: 2;
        color: #444;
        background-color: #fff;
        border-color: #ddd;
    }

    /* a:link {
      color: black;
      background-color: transparent;
    }      */
</style>

<div class="row row-eq h-85vh h-100vh-m">
    <?php
    $admin = $this->customlib->getLoggedInUserData();
    ?>               

    <div class="col-lg-9 col-md-9 col-sm-9 paddlr">
        <form id="evaluation_data" method="post">           
            <div class="row">
                <div class="scroll-area">
                    <div class="test">                         
                        <div class="">
                        <?php if(!empty($studentlist)){ ?>
                            <ul multiple="" class="list-group mb0" id="slist">
                              <div class="table-responsive">
                                <table class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th class="white-space-nowrap"><?php echo $this->lang->line('student_name') ?><small class="req"> *</small>
                                                <input type="hidden" id="assignment_id" name="assignment_id" value="<?php echo $getassignment->id;?>">
                                            </th>
                                            <th><?php echo $this->lang->line('type') ?></th>
                                            <th><?php echo $this->lang->line('message') ?></th>
                                            <th><?php echo $this->lang->line('uploaded_documents'); ?></th>
                                        <?php if($getassignment->marks !=0){ ?>
                                            <th><?php echo $this->lang->line('marks') ?> (<?php echo $getassignment->marks; ?>)</th>
                                        <?php } ?>
                                        </tr>
                                </thead>
                                <?php  
                                $sno=0;
                                foreach ($studentlist as $key => $value) { $sno++;
                                    $active_status = false;
                                    if ($value["assignment_evaluation_id"] != 0) {
                                        $active_status = true;
                                    }

                                    if($value['student_id']!=0){
                                        $student_or_quest_id=$value['student_id'];
                                        $student_or_quest_name=$this->customlib->getFullName($value['firstname'],$value['middlename'],$value['lastname'],$sch_setting->middlename,$sch_setting->lastname) . " (" . $value['admission_no'] . ")";
                                        $user_type="student";
                                    }else if($value['guest_id']!=0){
                                        $student_or_quest_id=$value['guest_id'];
                                        $student_or_quest_name=$value['guest_name']." (".$value['guest_unique_id'].")";
                                        $user_type="guest";
                                    }                             
                                    ?>  
                                        <tr>
                                        <li class="<?php echo ($active_status) ? "active" : "" ?>">
                                         <tr>
                                            <td class="white-space-nowrap">
                                                <div class="checkbox mt0">
                                                <label>
                                                <input type="checkBox" <?php echo ($active_status) ? "checked='checked'":"" ?> name="student_id[<?php echo $sno;?>]" value="<?php echo $student_or_quest_id; ?>"><?php echo $student_or_quest_name ; ?>
                                                <input type="hidden" name="student_list[<?php echo $sno;?>]"  value="<?php echo $student_or_quest_id ?>">
                                                <input type="hidden" name="evaluation_id[<?php echo $sno;?>]"  value="<?php echo $value["assignment_evaluation_id"] ?>">
                                                <input type="hidden" name="user_type[<?php echo $sno;?>]" value="<?php echo $user_type;?>">
                                                </label>
                                                </div>
                                                </td>
                                                <td class=""><?php echo $this->lang->line("$user_type"); ?></td>
                                                <td class="overflow-wrap-anywhere"><?php echo $value['message']; ?></td>
                                                <td class="">
                                                <?php if($value['docs'] !=''){ ?>                           
                                                    <?php echo $this->media_storage->fileview($value['docs']) ?>                                                  
                                                    <a href="<?php echo base_url(); ?>./onlinecourse/courseassignment/download_submit_assignment/<?php echo $value['id']; ?>"  download data-toggle="tooltip" data-placement="right" data-original-title="<?php echo $this->lang->line('download'); ?>"> <i class="fa fa-download text-info"></i></a>
                                            <?php } ?>
                                                </td>
                                            <?php if($getassignment->marks !=0){ ?>
                                                <td class="w-150"><input type="text" name="marks[<?php echo $sno;?>]"  value="<?php echo $value['evaluate_marks'] ?>" class="form-control w-150"></td>
                                            <?php } ?>
                                                </tr>
                                                <tr>
                                                    <td></td>
                                                    <td></td>
                                                    <td colspan="3">
                                                    <textarea class="form-control" name="note[<?php echo $sno;?>]" rows="2" placeholder="<?php echo $this->lang->line('note'); ?>"><?php echo $value["note"]; ?></textarea>
                                                    </td>
                                                </tr>                                           
                                            </li>
                                        </tr>
                                    <?php
                               }
                                ?>
                                </table>
                              </div>  
                            </ul>
                            <?php } ?>
                        </div>
                    </div>
                </div>

                <div class="sticky-footer"> 
                    <div class="col-lg-12 col-md-12 col-sm-12">                   
                        <div class="row">   
                            <div class="col-md-2 col-sm-2 col-xs-12">  
                                <div class="form-group">
                                    <label class="pt5"><?php echo $this->lang->line('evaluation_date'); ?> <small class="req"> *</small></label>
                                </div>
                            </div> 
                            <div class="col-md-8 col-sm-8 col-xs-12">
                                <div class="form-group">
                                    <?php
                                    $evl_date = $this->customlib->dateformat(date('Y-m-d'));
                                    if (!IsNullOrEmptyString($getassignment->evaluation_date)) {
                                        $evl_date = $this->customlib->dateformat($getassignment->evaluation_date);
                                    }
                                    ?>
                                    <input type="text" id="evaluation_date" name="evaluation_date" class="form-control modalddate97 date" value="<?php echo $evl_date; ?>" readonly="">
                                    <input type="hidden" name="homework_id" value="<?php echo $getassignment->id ?>">                                    
                                    <span class="text-danger" id="date_error"></span>
                                </div>
                            </div>   
                            <div class="col-md-2 col-sm-2 col-xs-12"> 
                                    <div class="form-group">
										<?php if($this->rbac->hasPrivilege('online_course_evalute_assignment', 'can_add')) { ?>
                                        <button type="submit" class="btn btn-info pull-right" id="submit" data-loading-text="<i class='fa fa-spinner fa-spin '></i> <?php echo $this->lang->line('please_wait'); ?>"><?php echo $this->lang->line('save') ?></button>
										<?php } ?>
                                    </div>
                            </div> 
                        </div>  
                    </div>    
                </div>    
            </div>
        </form>
    </div>
    <div class="col-lg-3 col-md-3 col-sm-3 col-eq">
        <div class="taskside scroll-area">
            <h4 class="mt0"><?php echo $this->lang->line('summary'); ?></h4>
            <hr class="taskseparator mt12" />
            <div class="task-info task-single-inline-wrap task-info-start-date">
                <h5><i class="fa task-info-icon fa-fw fa-lg fa-calendar-plus-o pull-left fa-margin"></i>
                    <span><?php echo $this->lang->line('assignment_date'); ?></span>:<?php echo $this->customlib->dateformat($getassignment->assignment_date); ?> 
                </h5>
            </div>
            <div class="task-info task-single-inline-wrap task-info-start-date">
                <h5><i class="fa task-info-icon fa-fw fa-lg fa-calendar-plus-o pull-left fa-margin"></i>
                    <span><?php echo $this->lang->line('submission_date'); ?></span>:<?php echo $this->customlib->dateformat($getassignment->submit_date); ?>
                </h5>
            </div>
            <div class="task-info task-single-inline-wrap task-info-start-date">
                <h5><i class="fa task-info-icon fa-fw fa-lg fa-calendar-plus-o pull-left fa-margin"></i>
                    <span>
                    <?php echo $this->lang->line('evaluation_date'); ?></span>:
                    <?php
                    $evl_date = "";
                    if (!IsNullOrEmptyString($getassignment->evaluation_date)) {
                        echo $this->customlib->dateformat($getassignment->evaluation_date);
                    }
                    ?>
                </h5>
            </div>
            <div class="task-info task-single-inline-wrap ptt10">
                <label><span><?php echo $this->lang->line('created_by'); ?></span>: <?php echo $created_by; ?></label>
                <label><span><?php echo $this->lang->line('evaluated_by'); ?></span>: <?php echo $evaluated_by; ?></label> 
                <label><span><?php echo $this->lang->line('total_marks') ?></span>: <?php if($getassignment->marks != '0.00' || $getassignment->marks !='0'){echo $getassignment->marks; } ?></label>                
                <?php if (!empty($getassignment->document)) { ?>
                    <label><span><?php echo $this->lang->line('document') ?></span>:<br> 
                    <?php echo $this->media_storage->fileview($getassignment->document) ?>
                    <a class="" data-toggle="tooltip" title="<?php echo $this->lang->line('download'); ?>" download href="<?php echo site_url("./uploads/course_content/online_course_assignment/" . $getassignment->document) ?>"><i class="fa fa-download"></i></a></label>
                    <?php
                } ?>
                <label><span><?php echo $this->lang->line('description'); ?></span>: <br/><?php echo $getassignment->description; ?></label>
            </div> 
        </div>
    </div>  
</div>
