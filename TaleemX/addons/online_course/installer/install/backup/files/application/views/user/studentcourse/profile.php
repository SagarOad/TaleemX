<?php $this->load->view('layout/course_css.php'); ?>
<?php
$currency_symbol = $this->customlib->getSchoolCurrencyFormat();
?>
<div class="content-wrapper">    
    <section class="content">     
        <div class="row">         
            <div class="col-lg-12 col-md-12 col-sm-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title titlefix"> <?php echo $this->lang->line('profile'); ?> </h3>  
                        <div class="box-tools pull-right">
                            <button type="button"class="btn btn-sm btn-primary pull-right edit_modal" title="<?php echo $this->lang->line('edit'); ?>"><i class="fa fa-pencil"></i> <?php echo $this->lang->line('edit'); ?></button>
                        </div>    
                    </div>
                    <div class="box-body">
                        <div class="box box-widget widget-user-2 mb0">
                            <div class="widget-user-header bg-gray-light overflow-hidden">
                                <div class="widget-user-image">
                                     <img class="profile-user-img img-responsive img-rounded border0" src="<?php
                                        if (!empty($guest_details[0]->guest_image)) {
                                          echo base_url() . "uploads/guest_images/" .$guest_details[0]->guest_image;
                                        } else {
               
                                            if ($guest_details[0]->gender == 'Female') {
                                                echo base_url() . "uploads/student_images/default_female.jpg";
                                            } elseif ($guest_details[0]->gender == 'Male') {
                                                echo base_url() . "uploads/student_images/default_male.jpg";
                                            } else    {
                                                echo base_url() . "uploads/student_images/no_image.png";
                                            }
                                        }
                                       ?>" alt="User Image">
                                </div>
                                   <h3 class="widget-user-username"><?php echo $guest_details[0]->guest_name; ?></h3>
                                   <p class="widget-user-desc"><?php if($guest_details[0]->gender){ $gender    =    strtolower($guest_details[0]->gender); echo $this->lang->line($gender) ; } ?></p>
                            </div>    
                        </div>
                        <div class="row pb5 ptt10">
                            <div class="col-sm-3 col-lg-2 col-md-2 col-xs-5 bmedium">
                                <?php echo $this->lang->line('user_id'); ?>
                            </div>
                            <div class="col-sm-9 col-lg-10 col-md-10 col-xs-7">
                                <?php echo $guest_details[0]->guest_unique_id; ?>
                            </div>
                        </div>
                        <div class="row pb5">
                            <div class="col-sm-3 col-lg-2 col-md-2 col-xs-5 bmedium">
                                <?php echo $this->lang->line('date_of_joining'); ?>
                            </div>
                            <div class="col-sm-9 col-lg-10 col-md-10 col-xs-7">
                                <?php
                                    if (!empty($guest_details[0]->created_at)) {
                                        echo date($this->customlib->getSchoolDateFormat(), $this->customlib->dateyyyymmddTodateformat($guest_details[0]->created_at));
                                    }
                                ?> 
                            </div>
                        </div>           
                        <div class="row pb5">
                            <div class="col-sm-3 col-lg-2 col-md-2 col-xs-5 bmedium"><?php echo $this->lang->line('email_id'); ?></div>
                            <div class="col-sm-9 col-lg-10 col-md-10 col-xs-7"><?php echo $guest_details[0]->email; ?></div>
                        </div>
                        <div class="row pb5">
                            <div class="col-sm-3 col-lg-2 col-md-2 col-xs-5 bmedium"><?php echo $this->lang->line('mobile_number'); ?></div>
                            <div class="col-sm-9 col-lg-10 col-md-10 col-xs-7"><?php echo $guest_details[0]->mobileno; ?></div>
                        </div>
                        <div class="row pb5">
                            <div class="col-sm-3 col-lg-2 col-md-2 col-xs-5 bmedium"><?php echo $this->lang->line('date_of_birth'); ?></div>
                            <div class="col-sm-9 col-lg-10 col-md-10 col-xs-7">
                                <?php
                                    if (!empty($guest_details[0]->dob)) {
                                        echo date($this->customlib->getSchoolDateFormat(), $this->customlib->dateyyyymmddTodateformat($guest_details[0]->dob));
                                    }
                                ?>
                            </div>
                        </div>                                
                        <div class="row pb5">
                            <div class="col-sm-3 col-lg-2 col-md-2 col-xs-5 bmedium"><?php echo $this->lang->line('address'); ?></div>
                            <div class="col-sm-9 col-lg-10 col-md-10 col-xs-7"><?php echo $guest_details[0]->address; ?></div>
                        </div>       
                    </div><!--./box-body-->
                </div>
            </div>
        </div>               
    </section>
</div> 

<div id="scheduleModal" class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                <h4 class="modal-title_logindetail"><?php echo $this->lang->line('update'); ?></h4>
            </div>
            <form id="formedit" method="post" class="ptt10" enctype="multipart/form-data">
                <div class="modal-body pt0 pb0">
                    <div class="row">
                        <div class="col-lg-12 col-md-12 col-sm-12">
                            <div class="replace_data">
                            </div>
                        </div><!--./col-md-12-->
                    </div><!--./row-->
                </div>
                <div class="box-footer">
                    <div class="pull-right paddA10">
                        <button type="submit" class="btn btn-info pull-right" id="submit" data-loading-text="<i class='fa fa-spinner fa-spin '></i> Please wait"><?php echo $this->lang->line('save') ?></button>
                    </div>
                </div>
            </form>             
        </div>
    </div>
</div>

<script>
   $('.edit_modal').on('click', function () {      
        var base_url = '<?php echo base_url() ?>';      
        var student_id = '<?php echo $guest_details[0]->id; ?>';    

        $.ajax({
            type: "post",
            url: base_url + "user/studentcourse/editguestmodel",
            data: {'student_id': student_id},             
            success: function (data) {       
               $('.replace_data').html(data);
               $("#scheduleModal").modal('show');
            }
        });        
   });
   
   $(document).ready(function (e) {
        $("#formedit").on('submit', (function (e) {
            e.preventDefault();          
            
            $("#submit").prop("disabled", true);            
            $.ajax({
                url: "<?php echo site_url("user/studentcourse/updateguestdata") ?>",
                type: "POST",
                data: new FormData(this),
                dataType: 'json',
                contentType: false,
                cache: false,
                processData: false,
                success: function (res)
                {

                    if (res.status == "fail") {
                        var message = "";
                        $.each(res.error, function (index, value) {
                            message += value;
                        });
                        errorMsg(message);
                        $("#submit").prop("disabled", false);
                    } else {
                        successMsg(res.message);
                        window.location.reload(true);
                    }
                }
            });
        }));
    });
</script>