                    <div class="row">
                        <div class="col-md-6">

                            <div class="form-group col-xs-12 col-sm-12 col-md-12 col-lg-12">
                                <label for="zoom_api_key"><?php echo $this->lang->line('zoom_api_key') ?><small class="req"> *</small></label>
                                <input type="text" class="form-control" id="zoom_api_key" name="zoom_api_key" value="<?php echo $zoom_api_key?>">
                                <span class="text text-danger" id="title_error"></span>
                            </div>
                            <div class="form-group col-xs-12 col-sm-12 col-md-12 col-lg-12">
                                <label for="zoom_api_secret"><?php echo $this->lang->line('zoom_api_secret'); ?><small class="req"> *</small></label>
                                <input type="text" class="form-control" id="zoom_api_secret" name="zoom_api_secret" value="<?php echo $zoom_api_secret?>">
                                <span class="text text-danger" id="title_error"></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb10"><img src="<?php echo base_url(); ?>backend/images/zoom-icon.png" /></div>
                                    <p><?php echo $this->lang->line('to_set_zoom_api'); ?> <a class="display-inline" href="https://marketplace.zoom.us/"> <?php echo $this->lang->line('click_here'); ?></a></p>
                                    <p class="pb0 mb0"><?php echo $this->lang->line('set_zoom_redirect_url'); ?>:</p> 
                                    <p  class="word-break-all"><?php echo  site_url('admin/conference/stafftoken'); ?></p>
                                    <?php 
                                    if($oAuthURL != ""){
                                    ?>
                                     <a href='<?php echo $oAuthURL;?>' class="btn btn-primary"> <?php echo $this->lang->line('get_access_token'); ?> </a>
                                    <?php 

                                    }
                                    
                                    ?>
                                   
                        </div>
                    </div>
            
                    <div class="modal-footer pb0">
                    <button type="submit" class="btn btn-primary" value="reset" id="submit-btn-credential" data-loading-text="<i class='fa fa-spinner fa-spin '></i> Updating..."><?php echo $this->lang->line('reset') ?></button>
                    <button type="submit" class="btn btn-primary" value="save" id="submit-btn-credential" data-loading-text="<i class='fa fa-spinner fa-spin '></i> <?php echo $this->lang->line('saving') ?>"><?php echo $this->lang->line('save') ?></button>
                </div>