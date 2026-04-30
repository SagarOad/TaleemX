<table class="table table-striped table-hover">
	<tbody>	
		<tr>
			<td><?php echo $this->lang->line('name'); ?> <small class="req"> *</small></td>
			<td>
				<input type="text" id="name" name="name" class="form-control" value="<?php echo $guest_details[0]->guest_name; ?>" > 
			</td>
		</tr>	
		<tr>
			<td><?php echo $this->lang->line('email_id'); ?> <small class="req"> *</small></td>
			<td>
				<input disabled type="text" id="email" name="email" class="form-control" value="<?php echo $guest_details[0]->email; ?>" > 
			</td>
		</tr>	
		<tr>
			<td><?php echo $this->lang->line('mobile_number'); ?></td>
			<td>
				<input type="text" id="mobile_number" name="mobile_number" class="form-control" value="<?php echo $guest_details[0]->mobileno; ?>" >
				<input type="hidden" id="guest_id" name="guest_id" class="form-control" value="<?php echo $guest_details[0]->id; ?>" >
			</td>
		</tr>		
		<tr>
			<td><?php echo $this->lang->line('date_of_birth'); ?></td>
			<td>
				<?php                    
                    if($dob == '0000-00-00' && $dob == ''){
						$dob =  '';
                    }
                ?>
				<input type="text" id="dob" name="dob" class="form-control guestbirthdate" value="<?php echo $dob; ?>" > 
			</td>
		</tr>		
		<tr>
			<td><?php echo $this->lang->line('gender'); ?></td>
			<td>
				<select class="form-control" name="gender">
                    <option value=""><?php echo $this->lang->line('select'); ?></option>
                    <?php
                        foreach ($genderList as $key => $value) {
                    ?>
                    <option value="<?php echo $key; ?>" 
							<?php 
								if ($guest_details[0]->gender == $key){
                                    echo "selected";
                                }
                            ?>><?php echo $value; ?>
					</option>
                    <?php
                        }
                    ?>
                </select> 
			</td>
		</tr>	
		<tr>
			<td><?php echo $this->lang->line('address'); ?></td>
			<td>
				<textarea id="address" name="address" class="form-control" ><?php echo $guest_details[0]->address; ?></textarea> 
			</td>
		</tr>	
		<tr>
			<td class="ver"><?php echo $this->lang->line('photo'); ?></td>
			<td>
				 <input class="filestyle form-control" type='file' name='photo' id="photo" size='20' /> 
			</td>
		</tr>		
	</tbody>
</table>			
<script type="text/javascript">
  	$(document).ready(function(){
        // Basic
        $('.filestyle').dropify();
    });
</script> 