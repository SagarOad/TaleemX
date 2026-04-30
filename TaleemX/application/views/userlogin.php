<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#442F24" />
        <title>Login : <?php echo $name; ?></title>
        <link rel="icon" type="image/svg+xml" href="<?php echo base_url('backend/images/front_theme/TaalimX%20Favicon.svg'); ?>">
        <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto:400,100,300,500">
        <link rel="stylesheet" href="<?php echo base_url(); ?>backend/usertemplate/assets/bootstrap/css/bootstrap.min.css">
        <link rel="stylesheet" href="<?php echo base_url(); ?>backend/usertemplate/assets/font-awesome/css/font-awesome.min.css">
        <link rel="stylesheet" href="<?php echo base_url(); ?>backend/usertemplate/assets/css/jquery.mCustomScrollbar.min.css">
        <!-- ss-theme: shared design foundation (tokens + reusable components) -->
        <link rel="stylesheet" href="<?php echo base_url(); ?>backend/dist/css/ss-theme.css">
    </head>

    <body class="ss-no-bg">
        <div class="ss-auth">
            <!-- LEFT: Brand + form panel -->
            <section class="ss-auth__panel">
                <div class="ss-auth__panel-inner">
                    <div class="ss-auth-brand">
                        <img class="ss-auth-brand__logo"
                             src="<?php echo base_url(); ?>uploads/school_content/admin_logo/<?php echo $this->setting_model->getAdminlogo(); ?>"
                             alt="<?php echo html_escape($name); ?>">
                        <p class="ss-auth-brand__tagline">AI-Powered Education Platform</p>
                    </div>

                    <h3 class="ss-auth-title">Login To Continue</h3>

                    <?php if (isset($error_message)) { ?>
                        <div class="ss-auth-alert ss-auth-alert--danger"><?php echo $error_message; ?></div>
                    <?php } ?>
                    <?php if ($this->session->flashdata('message')) { ?>
                        <div class="ss-auth-alert ss-auth-alert--success"><?php echo $this->session->flashdata('message'); ?></div>
                    <?php } ?>

                    <form action="<?php echo site_url('site/userlogin') ?>" method="post" class="ss-auth-form login-form">
                        <?php echo $this->customlib->getCSRF(); ?>

                        <div class="ss-auth-field">
                            <label class="sr-only" for="email"><?php echo $this->lang->line('username'); ?></label>
                            <div class="ss-auth-input">
                                <span class="ss-auth-input__icon" aria-hidden="true">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path opacity="0.4" d="M17 20.5H7C4 20.5 2 19 2 15.5V8.5C2 5 4 3.5 7 3.5H17C20 3.5 22 5 22 8.5V15.5C22 19 20 20.5 17 20.5Z" fill="#BF8546"/>
                                        <path d="M12 12.87C11.16 12.87 10.31 12.61 9.66003 12.08L6.53002 9.57997C6.21002 9.31997 6.15003 8.84997 6.41003 8.52997C6.67003 8.20997 7.14003 8.14997 7.46003 8.40997L10.59 10.91C11.35 11.52 12.64 11.52 13.4 10.91L16.53 8.40997C16.85 8.14997 17.33 8.19997 17.58 8.52997C17.84 8.84997 17.79 9.32997 17.46 9.57997L14.33 12.08C13.69 12.61 12.84 12.87 12 12.87Z" fill="#BF8546"/>
                                    </svg>
                                </span>
                                <input type="text"
                                       id="email"
                                       name="username"
                                       class="ss-auth-input__control form-username form-control"
                                       value="<?php echo set_value('username'); ?>"
                                       placeholder="Username"
                                       autocomplete="username">
                            </div>
                            <?php if (form_error('username')) { ?>
                                <span class="ss-auth-field__error"><?php echo form_error('username'); ?></span>
                            <?php } ?>
                        </div>

                        <div class="ss-auth-field">
                            <label class="sr-only" for="password"><?php echo $this->lang->line('password'); ?></label>
                            <div class="ss-auth-input">
                                <span class="ss-auth-input__icon" aria-hidden="true">
                                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path opacity="0.4" d="M9.99994 14.4583C10.7501 14.4583 11.3583 13.8502 11.3583 13.1C11.3583 12.3498 10.7501 11.7416 9.99994 11.7416C9.24975 11.7416 8.6416 12.3498 8.6416 13.1C8.6416 13.8502 9.24975 14.4583 9.99994 14.4583Z" fill="#BF8546"/>
                                        <path d="M13.8751 7.86664H6.12508C2.70841 7.86664 1.66675 8.90831 1.66675 12.325V13.875C1.66675 17.2916 2.70841 18.3333 6.12508 18.3333H13.8751C17.2917 18.3333 18.3334 17.2916 18.3334 13.875V12.325C18.3334 8.90831 17.2917 7.86664 13.8751 7.86664ZM10.0001 15.6166C8.60841 15.6166 7.48341 14.4833 7.48341 13.1C7.48341 11.7166 8.60841 10.5833 10.0001 10.5833C11.3917 10.5833 12.5167 11.7166 12.5167 13.1C12.5167 14.4833 11.3917 15.6166 10.0001 15.6166Z" fill="#BF8546"/>
                                        <path opacity="0.4" d="M5.93327 7.87502V6.90002C5.93327 4.45835 6.62494 2.83335 9.99994 2.83335C13.3749 2.83335 14.0666 4.45835 14.0666 6.90002V7.87502C14.4916 7.88335 14.8749 7.90002 15.2333 7.95002V6.90002C15.2333 4.65002 14.6916 1.66669 9.99994 1.66669C5.30827 1.66669 4.7666 4.65002 4.7666 6.90002V7.94169C5.1166 7.90002 5.50827 7.87502 5.93327 7.87502Z" fill="#BF8546"/>
                                    </svg>
                                </span>
                                <input type="password"
                                       id="password"
                                       name="password"
                                       class="ss-auth-input__control form-password form-control"
                                       value="<?php echo set_value('password'); ?>"
                                       placeholder="Password"
                                       autocomplete="current-password">
                                <button type="button"
                                        class="ss-auth-input__toggle"
                                        data-ss-toggle-password="password"
                                        aria-label="Show password">
                                    <i class="fa fa-eye" aria-hidden="true"></i>
                                </button>
                            </div>
                            <?php if (form_error('password')) { ?>
                                <span class="ss-auth-field__error"><?php echo form_error('password'); ?></span>
                            <?php } ?>
                        </div>

                        <?php if ($is_captcha) { ?>
                        <div class="ss-auth-field">
                            <div class="ss-auth-captcha">
                                <div class="ss-auth-captcha__img">
                                    <span id="captcha_image"><?php echo $captcha_image; ?></span>
                                    <span class="fa fa-refresh ss-auth-captcha__refresh"
                                          title="<?php echo $this->lang->line('refresh_captcha') ?>"
                                          onclick="refreshCaptcha()"></span>
                                </div>
                                <div class="ss-auth-captcha__input">
                                    <div class="ss-auth-input">
                                        <input type="text"
                                               id="captcha"
                                               name="captcha"
                                               class="ss-auth-input__control form-control"
                                               placeholder="<?php echo $this->lang->line('captcha'); ?>"
                                               autocomplete="off">
                                    </div>
                                </div>
                            </div>
                            <?php if (form_error('captcha')) { ?>
                                <span class="ss-auth-field__error"><?php echo form_error('captcha'); ?></span>
                            <?php } ?>
                        </div>
                        <?php } ?>

                        <button type="submit" class="ss-auth-submit">
                            Login
                        </button>

                        <div class="ss-auth-link__row">
                            <a href="<?php echo site_url('site/ufpassword') ?>" class="ss-auth-link forgot">
                                <i class="fa fa-key"></i>
                                <?php echo $this->lang->line('forgot_password'); ?>?
                            </a>
                        </div>
                    </form>
                </div>
            </section>

            <!-- RIGHT: Hero visual -->
            <aside class="ss-auth__visual"
                   style="background-image: url('<?php echo base_url(); ?>uploads/school_content/login_image/<?php echo $school['user_login_page_background']; ?>');">
                <span class="ss-auth__visual-overlay" aria-hidden="true"></span>
            </aside>
        </div>

        <script src="<?php echo base_url(); ?>backend/usertemplate/assets/js/jquery-1.11.1.min.js"></script>
        <script src="<?php echo base_url(); ?>backend/usertemplate/assets/bootstrap/js/bootstrap.min.js"></script>
        <script src="<?php echo base_url(); ?>backend/usertemplate/assets/js/jquery.mCustomScrollbar.min.js"></script>
        <script src="<?php echo base_url(); ?>backend/usertemplate/assets/js/jquery.mousewheel.min.js"></script>
        <script type="text/javascript">
            // Password show/hide toggle (reusable across auth pages)
            document.addEventListener('click', function (e) {
                var btn = e.target.closest('[data-ss-toggle-password]');
                if (!btn) return;
                var targetId = btn.getAttribute('data-ss-toggle-password');
                var input = document.getElementById(targetId);
                if (!input) return;
                var icon = btn.querySelector('i');
                if (input.type === 'password') {
                    input.type = 'text';
                    if (icon) { icon.classList.remove('fa-eye'); icon.classList.add('fa-eye-slash'); }
                    btn.setAttribute('aria-label', 'Hide password');
                } else {
                    input.type = 'password';
                    if (icon) { icon.classList.remove('fa-eye-slash'); icon.classList.add('fa-eye'); }
                    btn.setAttribute('aria-label', 'Show password');
                }
            });

            function refreshCaptcha(){
                $.ajax({
                    type: "POST",
                    url: "<?php echo base_url('site/refreshCaptcha'); ?>",
                    data: {},
                    success: function(captcha){
                        $("#captcha_image").html(captcha);
                    }
                });
            }

            function copy(email, password){
                document.getElementById("email").value = email;
                document.getElementById("password").value = password;
            }
        </script>
    </body>
</html>
