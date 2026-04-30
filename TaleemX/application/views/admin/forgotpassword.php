<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#424242" />
        <title><?php $app_name = $this->setting_model->get();
echo $app_name[0]['name'];
?></title>
        <!--favican-->
        <link rel="icon" type="image/svg+xml" href="<?php echo base_url('backend/images/front_theme/TaalimX%20Favicon.svg'); ?>">
        <!-- CSS -->
        <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto:400,100,300,500">
        <link rel="stylesheet" href="<?php echo base_url(); ?>backend/usertemplate/assets/bootstrap/css/bootstrap.min.css">
        <link rel="stylesheet" href="<?php echo base_url(); ?>backend/usertemplate/assets/font-awesome/css/font-awesome.min.css">
        <link rel="stylesheet" href="<?php echo base_url(); ?>backend/usertemplate/assets/css/form-elements.css">
        <link rel="stylesheet" href="<?php echo base_url(); ?>backend/usertemplate/assets/css/style.css">
        <!-- ss-theme: shared design foundation (tokens + reusable components) -->
        <link rel="stylesheet" href="<?php echo base_url(); ?>backend/dist/css/ss-theme.css">
        <style type="text/css">
            .nopadding {border-right: 0px solid #ddd;}
        </style>
    </head>
    <body class="ss-no-bg">
        <div class="ss-auth">
            <section class="ss-auth__panel">
                <div class="ss-auth__panel-inner">
                    <div class="ss-auth-brand">
                        <img class="ss-auth-brand__logo"
                             src="<?php echo base_url(); ?>uploads/school_content/admin_logo/<?php echo $this->setting_model->getAdminlogo(); ?>"
                             alt="<?php echo html_escape($school['name']); ?>">
                        <p class="ss-auth-brand__tagline">AI-Powered Education Platform</p>
                    </div>

                    <h3 class="ss-auth-title"><?php echo $this->lang->line('forgot_password'); ?></h3>

                    <?php if (isset($error_message)) { ?>
                        <div class="ss-auth-alert ss-auth-alert--danger"><?php echo $error_message; ?></div>
                    <?php } ?>

                    <form action="<?php echo site_url('site/forgotpassword') ?>" method="post" class="ss-auth-form login-form">
                        <?php echo $this->customlib->getCSRF(); ?>
                        <div class="ss-auth-field">
                            <label class="sr-only" for="form-email"><?php echo $this->lang->line('email'); ?></label>
                            <div class="ss-auth-input">
                                <span class="ss-auth-input__icon" aria-hidden="true">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path opacity="0.4" d="M17 20.5H7C4 20.5 2 19 2 15.5V8.5C2 5 4 3.5 7 3.5H17C20 3.5 22 5 22 8.5V15.5C22 19 20 20.5 17 20.5Z" fill="#BF8546"/>
                                        <path d="M12 12.87C11.16 12.87 10.31 12.61 9.66003 12.08L6.53002 9.57997C6.21002 9.31997 6.15003 8.84997 6.41003 8.52997C6.67003 8.20997 7.14003 8.14997 7.46003 8.40997L10.59 10.91C11.35 11.52 12.64 11.52 13.4 10.91L16.53 8.40997C16.85 8.14997 17.33 8.19997 17.58 8.52997C17.84 8.84997 17.79 9.32997 17.46 9.57997L14.33 12.08C13.69 12.61 12.84 12.87 12 12.87Z" fill="#BF8546"/>
                                    </svg>
                                </span>
                                <input type="text"
                                       id="form-email"
                                       name="email"
                                       class="ss-auth-input__control form-control"
                                       placeholder="<?php echo $this->lang->line('email'); ?>"
                                       autocomplete="email">
                            </div>
                            <?php if (form_error('email')) { ?>
                                <span class="ss-auth-field__error"><?php echo form_error('email'); ?></span>
                            <?php } ?>
                        </div>
                        <button type="submit" class="ss-auth-submit"><?php echo $this->lang->line('submit'); ?></button>
                    </form>

                    <div class="ss-auth-link__row">
                        <a href="<?php echo site_url('site/login') ?>" class="ss-auth-link forgot">
                            <i class="fa fa-key"></i> <?php echo $this->lang->line('admin_login'); ?>
                        </a>
                    </div>
                </div>
            </section>

            <aside class="ss-auth__visual"
                   style="background-image: url('<?php echo base_url(); ?>uploads/school_content/login_image/<?php echo $school['admin_login_page_background']; ?>');">
                <span class="ss-auth__visual-overlay" aria-hidden="true"></span>
            </aside>
        </div>
        <!-- Javascript -->
        <script src="<?php echo base_url(); ?>backend/usertemplate/assets/js/jquery-1.11.1.min.js"></script>
        <script src="<?php echo base_url(); ?>backend/usertemplate/assets/bootstrap/js/bootstrap.min.js"></script>
        <script src="<?php echo base_url(); ?>backend/usertemplate/assets/js/jquery.mCustomScrollbar.min.js"></script>
        <script src="<?php echo base_url(); ?>backend/usertemplate/assets/js/jquery.mousewheel.min.js"></script>
    </body>
</html>