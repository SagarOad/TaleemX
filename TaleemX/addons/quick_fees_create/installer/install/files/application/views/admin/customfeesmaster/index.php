<?php
$currency_symbol = $this->customlib->getSchoolCurrencyFormat();
?>
<div class="content-wrapper">   
    <section class="content-header">
        <h1>
            <i class="fa fa-money"></i> <?php echo $this->lang->line('fees_collection'); ?></h1>
    </section>
    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-search"></i> <?php echo $this->lang->line('quick_fees_master'); ?></h3>
                    </div>
					 <form role="form" id="addfees" action="<?php echo site_url('admin/customfeesmaster/savecustomfeesmaster') ?>"  method="post">
						<div class="box-body">                        
							<?php echo $this->customlib->getCSRF(); ?>
							<div class="row">	
								<div class="col-md-12">
									<div class="col-sm-3">
										<div class="form-group">
											<label><?php echo $this->lang->line('class'); ?></label>
											<select autofocus="" id="class_id" name="class_id" class="form-control" >
												<option value=""><?php echo $this->lang->line('select'); ?></option>
												<?php
												foreach ($classlist as $class) {
													?>
													<option value="<?php echo $class['id'] ?>" <?php if (set_value('class_id') == $class['id']) echo "selected=selected" ?>><?php echo $class['class'] ?></option>
													<?php
													$count++;
												}
												?>
											</select>
											<span class="text-danger"><?php echo form_error('class_id'); ?></span>
										</div>
									</div>    
									<div class="col-sm-3">
										<div class="form-group">   
											<label><?php echo $this->lang->line('section'); ?></label>
											<select  id="section_id" name="section_id" class="form-control" >
												<option value=""><?php echo $this->lang->line('select'); ?></option>
											</select>
											<span class="text-danger"><?php echo form_error('section_id'); ?></span>
										</div>   
									</div>    
									<div class="col-sm-3">
										<div class="form-group">   
											<label><?php echo $this->lang->line('student'); ?></label>
											<select  id="student_id" name="student_session_id" class="form-control" >
												
											</select>
											<span class="text-danger"><?php echo form_error('student_session_id'); ?></span>
										</div>   
									</div>                      
								</div>                      
							</div>                      
						</div>                   
                         
						<div id="datanotfount1">
						<div class="box-header ptbnull"></div>					
						<div class="box-body hide" id="installmentform"> 	
							<div class="row">	
								<div class="col-md-12">
									<div class="col-md-3">
										<div class="form-group">
											<label for="exampleInputEmail1"><?php echo $this->lang->line('total_fees'); ?></label> <small class="req"> *</small>
											<input autofocus="" id="total_fees" name="total_fees" type="text" class="form-control" value="<?php echo set_value('total_fees'); ?>"   />
											<span class="text-danger"><?php echo form_error('total_fees'); ?></span>
										</div>
									</div>
									<div class="col-md-3">
										<div class="form-group">
											<label for="exampleInputEmail1"><?php echo $this->lang->line('1st_installment'); ?></label> 
											<input autofocus="" id="down_payment" name="down_payment" type="text" class="form-control" value="<?php echo set_value('down_payment'); ?>"  />
											<span class="text-danger"><?php echo form_error('down_payment'); ?></span>
										</div>
									</div>
									<div class="col-md-3">
										<div class="form-group">
											<label for="exampleInputEmail1"><?php echo $this->lang->line('balance_fees'); ?></label> <small class="req"> *</small>
											<input autofocus="" id="balance_fees" name="balance_fees" type="text" class="form-control" value="<?php echo set_value('balance_fees'); ?>"  readonly />
											<span class="text-danger"><?php echo form_error('balance_fees'); ?></span>
										</div>
									</div>
									<div class="col-md-3">
										<div class="form-group">
											<label for="exampleInputEmail1"><?php echo $this->lang->line('no_of_installment'); ?></label> <small class="req"> *</small>
											<input autofocus="" id="no_of_installment" name="no_of_installment" type="text" class="form-control" value="<?php echo set_value('no_of_installment'); ?>"   />
											<span class="text-danger"><?php echo form_error('no_of_installment'); ?></span>
										</div>
									</div>
								</div>
								<div class="col-md-12">
									<div class="col-md-3">
										<div class="form-group">
											<label for="exampleInputEmail1"><?php echo $this->lang->line('monthly_day_for_due_date'); ?></label> 
											<select  id="day" name="day" class="form-control" onchange="resettable()">
												<option value="none"><?php echo $this->lang->line('none'); ?></option>
												<?php for($i = 1; $i <= 31; $i++){ ?>
												<option value="<?php echo $i ; ?>"><?php echo $i ; ?></option>
												<?php } ?>
											</select>
											<span class="text-danger"><?php echo form_error('day'); ?></span>
										</div>
									</div>								
									<div class="col-md-3 " id="finetypehideshow">
										<div class="form-group">
											<label for="exampleInputEmail1"><?php echo $this->lang->line("fine_type");?></label> 
											<select  id="fine_type" name="fine_type" class="form-control" onchange="resettable()">
												<option value="none"><?php echo $this->lang->line('none'); ?></option>												 
												<option value="fix"><?php echo $this->lang->line('fix_amount'); ?></option>
												<option value="percentage"><?php echo $this->lang->line('percentage'); ?></option>
												 
											</select>
											<span class="text-danger"><?php echo form_error('fine_type'); ?></span>
										</div>
									</div>
									<div class="col-md-3 " id="finetypevaluehideshow">
										<div class="form-group">
											<label for="exampleInputEmail1"><?php echo $this->lang->line("fine_type_value");?></label> <small class="req"> *</small>
											<input name="fine_type_value" id="fine_type_value" class="form-control" type="text"  />
											<span class="text-danger"><?php echo form_error('fine_type_value'); ?></span>
										</div>
									</div>								
									<div class="col-md-3">
										<div class="form-group">	
											<label class="displayblock opacity d-sm-none">&nbsp;</label>								 
											<a class="btn btn-primary smallbtn28 btn-sm create-installment "><?php echo $this->lang->line("view_installment");?></a>
										</div>
									</div>								
								</div>								
							</div>				
						</div>				
					
						<div class="box-body" >
							<div class="mailbox-messages">								
								<div id="replacedata">                                         
								</div>							 
							</div>
							<div class="col-sm-12 hide" id="assignFees">
								<div class="form-group"> 
									<?php if ($this->rbac->hasPrivilege('quick_fees', 'can_add')) { ?>
									<button type="submit" id="savebtn" class="btn btn-primary pull-right btn-sm checkbox-toggle "><?php echo $this->lang->line('save'); ?></button>
									<?php } ?>
								</div>
							</div>							
						</div>
						</div>
					</form>							
						
					<div id="datanotfount2">
					<form role="form" action="<?php echo site_url('admin/customfeesmaster/unassignfees') ?>" method="post" class="row">
						<div class="box-body" > 
							<div id="unassign" class="hide">
								<div id="replacedatafeesmaster">                                         
								</div>							 
								<div class="col-sm-12">
									<div class="form-group">  
										<?php if ($this->rbac->hasPrivilege('quick_fees', 'can_delete')) { ?>
										<button onclick='return confirm("<?php echo $this->lang->line('are_you_sure_you_want_to_unassign_fees_this_action_is_irreversible') ;?>")'  type="submit" class="btn btn-primary pull-right btn-sm checkbox-toggle " ><?php echo $this->lang->line('unassing_fees'); ?></button>
										<?php } ?>
									</div>
								</div>	
							</div>	
						</div>	
					</form>
					</div>			 
                </div>  
            </div>
        </div> 
    </section>
</div>

<script type="text/javascript">
//***save data***//
$(document).ready(function (e) {
     $('#addfees').on('submit', (function (e) {
        e.preventDefault();
        $.ajax({
            url: $(this).attr('action'),
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
                }else if(data.status==2){
                    errorMsg(data.error);
                } else {
                     var student_session_id = $('#student_id').val();
					 getStudentFeesMaster(student_session_id,1);	
                }
            },
            error: function () {
 
            }
         });
     }));
 }); 
//***save data***//
//precent form submit on enter
$(document).ready(function() {
  $(window).keydown(function(event){
    if(event.keyCode == 13) {
      event.preventDefault();
      return false;
    }
  });
});

const resettable=()=>{
	// $('#replacedata').html("");
	if($('#fine_type').val()=="none"){
		$('#fine_type_value').val("");
	}
}

	$(document).on('click','.create-installment',function(){
		createInstallment();
	});
	
	function createInstallment(){
		 
        var	total_fees			=	$('#total_fees').val();   
		var down_payment		=	$('#down_payment').val(); 
		var balance_fees		=	$('#balance_fees').val(); 
		var no_of_installment	=	$('#no_of_installment').val();		 
		var amount 				= 	balance_fees / no_of_installment;	 
		var day					=	$('#day').val();		 
		var fine_type			=	$('#fine_type').val(); 
		var fine_type_value		=	$('#fine_type_value').val(); 
		var student_name		=   $("#student_id option:selected").text();
		var student_id      	=   $("#student_id").val();
		 
        $.ajax({
			url: '<?php echo site_url("admin/customfeesmaster/createInstallment") ?>',
            type: 'post',
            dataType:"JSON",
            data: {'total_fees': total_fees,'down_payment': down_payment, 'balance_fees': balance_fees, 'no_of_installment': no_of_installment, 'amount': amount, 'day': day, 'fine_type': fine_type, 'fine_type_value': fine_type_value,'student_name':student_name,'student_id':student_id},			 
            success: function (response) {
				if (response.status === 1) {
					$('#assignFees').removeClass("hide");
					$('#viewinstallment').removeClass("hide");
					 
					$('#replacedata').html(response.page);
                } else if (response.status === 0) {
                      errorMsg(response.message);
                }              
            }
		});			
    }

   $("#fine_type").change(function(){
		var day		=	$('#fine_type').val();
		if(day != 'none'){
			$('#finetypevaluehideshow').removeClass("hide");
		}else{
			$('#fine_type_value').val("");
			$('#finetypevaluehideshow').addClass("hide");
		}
	});

</script>
<script type="text/javascript">	
	$("#down_payment, #total_fees").on("keyup",function(event) {
		var total_fees		=	$('#total_fees').val();
		var  down_payment	=	$('#down_payment').val();       
		if(down_payment != ''){
			balance_fees	=	(parseFloat(total_fees)-parseFloat(down_payment));
		} else{
			balance_fees	=	(parseFloat(total_fees));
		}		
		$('#balance_fees').val(balance_fees.toFixed(2));
	});
	
</script>

<script type="text/javascript">	
	
	$('#section_id').change(function(){
       var class_id   = $('#class_id').val();
       var section_id = $('#section_id').val();
       studentbysection(class_id,section_id,'');
    });

    function studentbysection(class_id,section_id){
        var div_data = '<option value=""><?php echo $this->lang->line('select'); ?></option>';   
		$('#student_id').html('');  
		$.ajax({
            type: "post",
            url: "<?php echo base_url(); ?>admin/visitors/getstudent",
            data: {class_id: class_id,section_id:section_id},
            dataType: "json",
            success: function (data) {
                $.each(data.studentlist, function (i, obj)
                { 
                    selected = '';
                   
                    if( obj.middlename == null){
                     obj.middlename="";
                    }
					if( obj.lastname == null){
                     obj.lastname="";
                    }

                    div_data += "<option value=" + obj.id + ">" + obj.firstname +' '+ obj.middlename +' '+ obj.lastname +" ("+obj.admission_no+")"+ selected +"</option>";
                });
                $('#student_id').append(div_data);                         
            }
        });
    }
</script>

<script type="text/javascript">

	$('#student_id').change(function(){
        var student_session_id = $('#student_id').val();
		getStudentFeesMaster(student_session_id,0);	
	});

	$('#student_id,#section_id,#class_id').change(function(){
		//reset input fields and content 	
		var class_id 		= $('#class_id').val();
		if(class_id==""){
			$('#student_id').html("<option value=''><?php echo $this->lang->line('select'); ?></option>");
		}
		$('#replacedata').html("");	  
		$('#installmentform').find('input').val("");	  
		$('#installmentform').find('select').val("none");	  
		$('#finetypevaluehideshow').addClass("hide"); //fine value
		$('#viewinstallment').addClass("hide");
		$('#installmentform').addClass("hide");
		$('#replacedatafeesmaster').html("");
		$('#datanotfount2').removeClass("hide"); 
		$('#datanotfount1').addClass("hide");
		//reset input fields and content 
    });

	function getStudentFeesMaster(student_session_id,type) {        
            var base_url = '<?php echo base_url() ?>';			
            $.ajax({
                type: "POST",
                url: base_url + "admin/customfeesmaster/getStudentFeesMaster",
                data: {'student_session_id': student_session_id},
                dataType: "json",
                success: function (data) {  
					if(student_session_id != ''){
					if (data.status === 1) {	
						$('#viewinstallment').addClass("hide");
						$('#installmentform').addClass("hide");
						$('#unassign').removeClass("hide");
						$('#replacedatafeesmaster').html(data.page);
						$('#datanotfount2').removeClass("hide"); 
						$('#datanotfount1').addClass("hide"); 
					} else {
						$('#installmentform').removeClass("hide");
						$('#viewinstallment').removeClass("hide");
						$('#unassign').addClass("hide");
						$('#datanotfount1').removeClass("hide"); 
						$('#datanotfount2').addClass("hide"); 
					} 
					} else{						
						$('#datanotfount1').addClass("hide");
						$('#datanotfount2').addClass("hide");
					}
					if(type==1){
						 $("#setmsg").html("<?php echo $this->lang->line('fee_assigned_successfully'); ?>");
					}
				
                }
            });
    }
</script>

<script type="text/javascript">
    function getSectionByClass(class_id, section_id) {
        if (class_id != "" && section_id != "") {
            $('#section_id').html("");
            var base_url = '<?php echo base_url() ?>';
            var div_data = '<option value=""><?php echo $this->lang->line('select'); ?></option>';
            $.ajax({
                type: "GET",
                url: base_url + "sections/getByClass",
                data: {'class_id': class_id},
                dataType: "json",
                success: function (data) {
                    $.each(data, function (i, obj)
                    {
                        var sel = "";
                        if (section_id == obj.section_id) {
                            sel = "selected";
                        }
                        div_data += "<option value=" + obj.section_id + " " + sel + ">" + obj.section + "</option>";
                    });
                    $('#section_id').append(div_data);
                }
            });
        }
    }

    $(document).ready(function () {
        var class_id = $('#class_id').val();
        var section_id = '<?php echo set_value('section_id') ?>';
        getSectionByClass(class_id, section_id);
        $(document).on('change', '#class_id', function (e) {
            $('#section_id').html("");
            var class_id = $(this).val();
            var base_url = '<?php echo base_url() ?>';
            var div_data = '<option value=""><?php echo $this->lang->line('select'); ?></option>';
            $.ajax({
                type: "GET",
                url: base_url + "sections/getByClass",
                data: {'class_id': class_id},
                dataType: "json",
                success: function (data) {
                    $.each(data, function (i, obj)
                    {
                        div_data += "<option value=" + obj.section_id + ">" + obj.section + "</option>";
                    });
                    $('#section_id').append(div_data);

                    var section_id=$('#section_id').val();
                    if(section_id==""){
						$('#student_id').html("<option value=''><?php echo $this->lang->line('select'); ?></option>");
					}


                }
            });
        });
    });	
 
</script>

<script type="text/javascript">
	document.getElementById("print").style.display = "block";
    document.getElementById("btnExport").style.display = "block";

    function printDiv() {      
        $(".displayinline").addClass("hide");
        document.getElementById("print").style.display = "none";
        document.getElementById("btnExport").style.display = "none";
        var divElements = document.getElementById('viewinstallment').innerHTML;
        var oldPage = document.body.innerHTML;
        document.body.innerHTML =
                "<html><head><title></title></head><body>" +
                divElements + "</body>";
        window.print();
        document.body.innerHTML = oldPage;
        location.reload(true);
    }
</script>