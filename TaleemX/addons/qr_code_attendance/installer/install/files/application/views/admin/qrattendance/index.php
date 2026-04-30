<script src="<?php echo base_url(); ?>backend/qrattendance/html5-qrcode.min.js"></script>
<link rel="stylesheet" href="<?php echo base_url(); ?>backend/qrattendance/style.css">
</link>
<div class="content-wrapper">

    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="box box-primary qrbox">
                    <div class="box-header ptbnull">
                        <h3 class="box-title titlefix pt6"><?php echo $this->lang->line("qr_code_attendance") ?> </h3>
                        <div class="float-lg-right d-flex align-items-center gap-1 qrcode-right-icon">
                    
						<?php 
						if (!empty($machine_type)){  
							if (!empty($machine_type) && in_array("gun_machine", $machine_type) && in_array("camera", $machine_type)){ ?>
														
							<div class="btn-group btn-toggle qrtabs">
								<a href="<?php echo site_url('admin/qrattendance/attendance/index') ?>" class="btn btn-sm  <?php echo ($this->uri->segment(4) == 'index' ? 'btn-primary' : 'btn-outline-primary'); ?>"><?php echo $this->lang->line("camera_based_device"); ?></a>
								<a href="<?php echo site_url('admin/qrattendance/attendance/scannergun') ?>" class="btn btn-sm <?php echo ($this->uri->segment(4) == 'scannergun' ? 'btn-primary' : 'btn-outline-primary'); ?>"><?php echo $this->lang->line("sensor_based_device"); ?></a>
							</div>

						<?php } } ?>
                        <div class="full-screen-icon" data-toggle="tooltip" title="<?php echo $this->lang->line("full_screen") ?>" data-placement="bottom">
                            <i class="fa fa-arrows-alt cursor-pointer gr-icon-bs-text"></i>
                        </div>
                        <div class="exit-screen-icon displaynone" data-toggle="tooltip" title="<?php echo $this->lang->line("full_screen") ?>" data-placement="bottom">
                            <i class="fa fa-arrows-alt gr-icon-bs-text cursor-pointer"></i>
                        </div>
                        
                      </div>  
                    </div>
                    <div class="box-body">
                        <div class="row qrcodediv row is-flex ptt10 pb10">


                            <div class="col-md-offset-2 col-lg-4 col-md-4 col-sm-12 mb-md">
                                <div class="isbox">
                                    <div class="text text-center mb10 displaynone" id="qrbutton">
                                        <button class="btn btn-primary"><i class="fa fa-qrcode" aria-hidden="true"></i> <?php echo $this->lang->line("rescan_qr_code_barcode") ?> </button>
                                        <div>
                                            <img src="<?php echo base_url('backend/qrattendance/attendance_card.png'); ?>" alt="ID CARD IMAGE" class="w-100" />
                                        </div>
                                    </div>
                                    <div class="form-group box mt-md scanner displayblock">
                                        <audio id="successAudio">
                                            <source src="<?php echo base_url(); ?>backend/qrattendance/success.mp3" type="audio/ogg">
                                        </audio>
                                        <div class="">
                                            <span class="title"><?php echo $this->lang->line("scan_your_id_card_qr_code_barcode") ?></span>

                                            <div class="text text-center text-danger" id="reader" style="width: 100%;padding: 1rem;"></div>

                                        </div>

                                        <div id="reader-results"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4 col-md-4 col-sm-12 mb-md">

                                <div class="isbox" style="position: relative; min-height: 430px;">

                                    <div class="modal-inner-loader" style="display: none;"></div>
                                    <div class="saving_loader" style="display: none;"><i class="fa fa-spinner fa-pulse fa-3x fa-fw"></i> <?php echo $this->lang->line('saving') ?></div>

                                    <div id="profile_data">
                                        <img src="<?php echo base_url('backend/qrattendance/attendance_card.png'); ?>" class="qrscansvg" alt="ID CARD IMAGE" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
    var obj = jQuery.parseJSON('<?php echo $setting; ?>');
    var camera_type = obj.camera_type;
    var auto_attendance = obj.auto_attendance;
    var statusMatched = "<?php echo $this->lang->line("matched") ?>";
    var statusScanning = "<?php echo $this->lang->line("scanning") ?>";

    var x = document.getElementById("successAudio");
    var lastResult, modalOpen = 0;
    const html5QrCode = new Html5Qrcode("reader");
    const qrCodeSuccessCallback = (decodedText, decodedResult) => {

        if (decodedText !== lastResult && modalOpen == 0) {
            x.play();
            lastResult = decodedText;
            modalOpen = 1;

            $('#attendanceRemark').val('');
            $('#chkAttendance').prop('checked', false);
            getProfileDetails(decodedText);
            $("#qr_status").html(statusMatched);
            html5QrCode.clear();
        }
    };

    let getProfileDetails = (decodedText) => {
        $.ajax({
            type: "POST",
            url: base_url + "admin/qrattendance/attendance/getProfileDetail",
            data: {
                'text': decodedText
            },
            dataType: "json",
            beforeSend: function() {
                $('#profile_data').html("");
                $('.modal-inner-loader').css("display", "block");
            },
            success: function(response) {
                if (response.attendance_range) {
                    if (response.status == 1) {
                        $('#profile_data').html(response.page);

                        setTimeout(function() {
                            check_auto_submit();
                        }, 3000);


                    } else if (response.status == 2) {
                        $('#profile_data').html(response.page);
                        setTimeout(function() {
                            resetQrCodeScanner();
                        }, 5000);


                    } else {
                        $('#profile_data').html(response.page);
                        setTimeout(function() {
                            resetQrCodeScanner();
                        }, 5000);

                    }
                } else {
                    errorMsg(response.msg);
                    setTimeout(function() {
                        resetQrCodeScanner();
                    }, 5000);
                }

                $('.modal-inner-loader').fadeOut(400);

            },
            complete: function() {
                $('.modal-inner-loader').fadeOut(400);
            },
            complete: function() {
                modalOpen = 0;
                $('.scanner').removeClass('displayblock').addClass('displaynone');
                $('#qrbutton').removeClass('displaynone').addClass('displayblock');
                $('.modal-inner-loader').fadeOut(400);

            }
        });
    }

    const formatsToSupport = [
        Html5QrcodeSupportedFormats.QR_CODE,
        Html5QrcodeSupportedFormats.CODE_128
    ];



    var config = {
        fps: 50,
        qrbox: {
            width: 350,
            height: 250
        }
    };
    if ($(window).width() <= '400') {
        config = {
            fps: 50,
            qrbox: {
                width: 300,
                height: 250
            }
        };
    }
    if ($(window).width() <= '370') {
        config = {
            fps: 50,
            qrbox: {
                width: 200,
                height: 170
            }
        };
    }

    // if you want to prefer front camera
    html5QrCode.start({
        facingMode: camera_type
    }, config, qrCodeSuccessCallback).catch((err) => {
        //$("#qr_status").css("background", "red");
        // $("#reader").addClass("camera-preview").html("camera not available wait until someone else in this home stops viewing this camera and try again.");
        $("#reader").addClass("camera-preview").html("<?php echo $this->lang->line('camera_not_found'); ?>");

        $("#qr_status").html(`Error: ${err}`);
        alert("<?php echo $this->lang->line('camera_not_found'); ?>");
        console.log(`Unable to start scanning, error: ${err}`);
    });

    function onScanFailure(error) {
        console.warn(`Code scan error = ${error}`);
    }


    $(document).on('click', '#qrbutton', function(e) {

        resetQrCodeScanner();

    });

    $(document).on('submit', 'form#add_attendance', function(e) {

        e.preventDefault(); // avoid to execute the actual submit of the form.

        let form = $(this);
        var _btn = form.find("button[type=submit]:focus");
        var attendance_for = _btn.data('attendance_for');
        var record_id = _btn.data('record_id');
        var prev_attendance = _btn.data('prev_attendance');
        var url = base_url + "admin/qrattendance/attendance/saveAttendance";
        var attendence_type_id = $("input[name='attendance_type']:checked").val();
        var prev_attendance_id = $("input[name='prev_attendance']").val();
        $.ajax({
            type: "POST",
            url: url,
            dataType: 'JSON',
            data: {
                'attendance_for': attendance_for,
                'record_id': record_id,
                'attendence_type_id': attendence_type_id,
                'prev_attendance_id': prev_attendance_id
            }, // serializes the _btn's elements.
            beforeSend: function() {
                _btn.button('loading');
            },
            success: function(response) {
                if (response.status == 1) {

                    successimg_message();
                    successMsg(response.msg);

                } else if (response.status == 0) {

                    errorMsg(response.msg);

                    // setTimeout(function() {
                    //     resetQrCodeScanner();
                    // }, 5000);

                }
                _btn.button('reset');
            },
            error: function(xhr) { // if error occured

                alert("<?php echo $this->lang->line('error_occurred_please_try_again'); ?>");
                _btn.button('reset');
            },
            complete: function() {


                _btn.button('reset');
            }
        });
    });

    function isEmpty(value) {
        return (value == null || value.length === 0);
    }


    let check_auto_submit = () => {
        console.log("sdfsdfsdfsdf");
        let form = $('form#add_attendance');
        let prev_attendance = form.find("input[name=prev_attendance]").val();
        let in_time = form.find("input[name=in_time]").val();
        let out_time = form.find("input[name=out_time]").val();
        var prev_attendance_id = $("input[name='prev_attendance']").val();


        if ((auto_attendance == 1 && prev_attendance == 0) ||
            (auto_attendance == 1 && prev_attendance > 0 && (isEmpty(out_time) || isEmpty(in_time)))
        ) {

            var attendance_for = form.find("input[name=attendance_for]").val();
            var record_id = form.find("input[name=record_id]").val();
            var url = base_url + "admin/qrattendance/attendance/saveAttendance";

            $.ajax({
                type: "POST",
                url: url,
                dataType: 'JSON',
                data: {
                    'attendance_for': attendance_for,
                    'record_id': record_id,
                    'attendence_type_id': 1,
                    'prev_attendance_id': prev_attendance_id
                }, // serializes the _btn's elements.
                beforeSend: function() {
                    $('.saving_loader').css("display", "block");
                },
                success: function(response) {
                    if (response.status == 1) {

                        successimg_message();
                        successMsg(response.msg);

                    } else if (response.status == 0) {
                        errorMsg(response.msg);
                        setTimeout(function() {
                            resetQrCodeScanner();
                        }, 5000);
                    }

                    $('.saving_loader').fadeOut(400);
                },
                error: function(xhr) { // if error occured

                    alert("<?php echo $this->lang->line('error_occurred_please_try_again'); ?>");
                    $('.saving_loader').fadeOut(400);
                },
                complete: function() {

                    $('.saving_loader').fadeOut(400);
                }
            });
        }
    }

    let resetQrCodeScanner = () => {
        //============
        var img = $('<img />', {
            class: 'qrscansvg',
            src: base_url + 'backend/qrattendance/attendance_card.png',
            alt: 'ID CARD IMAGE'
        });
        modalOpen = 0;
        lastResult = "";
        $('#profile_data').html("").html(img);
        $("#qr_status").html(statusScanning);
        $('#qrbutton').removeClass('displayblock').addClass('displaynone');
        $('.scanner').removeClass('displaynone').addClass('displayblock');
        //============
    }

    let successimg_message = () => {
        var success_image = '<div class="text text-center qrarround20">';
        success_image += '<svg version="1.0" xmlns="http://www.w3.org/2000/svg"  width="200.000000pt" height="200.000000pt" viewBox="0 0 512.000000 512.000000" preserveAspectRatio="xMidYMid meet">';
        success_image += '<g transform="translate(0.000000,512.000000) scale(0.100000,-0.100000)" fill="#0a8000" stroke="none">';
        success_image += '<path d="M1635 4880 c-93 -10 -160 -37 -274 -111 -161 -103 -286 -248 -347 -398 -46 -114 -55 -311 -23 -523 11 -73 10 -77 -12 -108 -21 -28 -24 -42 -23 -123 2 -104 32 -231 65 -278 11 -16 31 -72 44 -123 81 -328 321 -626 587 -728 83 -32 205 -35 303 -9 292 80 573 401 660 755 9 38 27 85 41 105 55 85 89 336 52 393 -14 22 -14 31 4 108 27 119 29 319 4 420 -49 197 -170 346 -361 443 -133 67 -470 169 -595 180 -30 3 -86 2 -125 -3z"/>';
        success_image += '<path d="M1213 2613 c-65 -65 -268 -167 -483 -242 -300 -105 -394 -172 -477 -343 -28 -57 -63 -139 -78 -183 -122 -371 -208 -1041 -152 -1186 25 -65 47 -82 177 -133 340 -132 880 -241 1424 -286 158 -13 256 -12 461 6 297 27 784 102 809 125 12 10 -70 111 -132 163 -112 94 -334 218 -537 301 -49 20 -186 63 -304 96 -118 32 -215 60 -216 61 -3 3 710 1457 718 1465 9 8 374 -226 507 -326 130 -97 276 -220 391 -329 l67 -63 41 48 c22 27 41 51 41 56 0 17 -71 182 -104 241 -76 137 -189 201 -661 376 -125 46 -223 101 -282 158 l-40 39 -6 -96 c-23 -346 -200 -533 -502 -532 -168 0 -291 48 -396 152 -92 92 -173 263 -198 418 -6 35 -12 64 -13 65 -2 0 -27 -23 -55 -51z"/>';
        success_image += '<path d="M5003 2645 c-155 -60 -397 -201 -573 -335 -298 -227 -678 -597 -969 -943 -41 -48 -77 -84 -81 -80 -91 111 -131 156 -219 246 -155 159 -336 312 -534 451 l-68 47 -189 -386 c-105 -213 -190 -390 -190 -394 0 -4 46 -24 103 -45 148 -54 425 -193 547 -274 156 -103 239 -187 415 -418 87 -113 160 -202 164 -197 4 4 38 76 76 158 277 607 611 1174 921 1565 186 233 470 503 627 594 74 44 64 47 -30 11z"/>';
        success_image += '</g>';
        success_image += '</svg>';
        success_image += '<br/>';
        success_image += '<h4 class="ptt10 qrattendance_success"><?php echo $this->lang->line('attendance_submitted_successfully'); ?>.</h4>';
        success_image += '</div>';

        $("#profile_data").fadeOut('2000', function() {
            $(this).html(success_image)
        }).fadeIn(2000);

        $("#profile_data").fadeOut('2000', function() {
            resetQrCodeScanner()
        }).fadeIn(2000);

    };
</script>
<script type="text/javascript">
    $(document).ready(function() {
        var s = "";

        $(document).on('click', ".full-screen-icon", function() {


            if (!document.fullscreenElement && // alternative standard method
                !document.mozFullScreenElement && !document.webkitFullscreenElement && !document.msFullscreenElement) { // current working methods
                if (document.documentElement.requestFullscreen) {
                    document.documentElement.requestFullscreen();
                } else if (document.documentElement.msRequestFullscreen) {
                    document.documentElement.msRequestFullscreen();
                } else if (document.documentElement.mozRequestFullScreen) {
                    document.documentElement.mozRequestFullScreen();
                } else if (document.documentElement.webkitRequestFullscreen) {
                    document.documentElement.webkitRequestFullscreen(Element.ALLOW_KEYBOARD_INPUT);
                }

            }
            s = $('.content-wrapper').attr('style');
            $("header, .main-sidebar, .main-footer, .topaleart").addClass('displaynone');

            // $(".content-wrapper").css('cssText', ' background-color: rgb(255, 255, 255); margin-left: 0px; padding-top: 0px; margin-right:0px !important');
             $(".content-wrapper").css('cssText', ' margin-left: 0px; padding-top: 0px; margin-right:0px !important');

            $(".content").css({
                "padding-top": "0px",
                "padding-left": "10px",
                "padding-right": "10px"
            });

            $(".wrapper").css({
                "box-shadow": "none"
            });
            $(".qrbox").css({
                "box-shadow": "none"
            });
            $('.full-screen-icon').fadeOut('1000');
            $('.exit-screen-icon').fadeIn('1000');
        });

        $(document).on('click', ".exit-screen-icon", function() {

            if (document.exitFullscreen) {
                document.exitFullscreen();
            } else if (document.msExitFullscreen) {
                document.msExitFullscreen();
            } else if (document.mozCancelFullScreen) {
                document.mozCancelFullScreen();
            } else if (document.webkitExitFullscreen) {
                document.webkitExitFullscreen();
            }

            $('.full-screen-icon').fadeIn('1000');
            $('.exit-screen-icon').fadeOut('1000');

            $("header, .main-sidebar, .main-footer, .topaleart").removeClass('displaynone');
            $(".content-wrapper").attr('style', "").attr('style', s);
            $(".content").css({
                "padding": "10px"
            });
            $(".content-wrapper").css({
                "background-color": "transparent"
            });

            $(".wrapper").css({
                "box-shadow": ""
            });
            $(".qrbox").css({
                "box-shadow": ""
            });
        });

        $(".full-screen-icons").toggle(
            function() {

                s = $('.content-wrapper').attr('style');

                $("header, .main-sidebar, .main-footer, .topaleart").addClass('displaynone');

                $(".content-wrapper").css({
                    "margin-left": "0px",
                    "background-color": "#fff",
                    "padding-top": "0px"
                });
                $(".content").css({
                    "padding-top": "0px",
                    "padding-left": "10px",
                    "padding-right": "10px"
                });

                $(".wrapper").css({
                    "box-shadow": "none"
                });
                $(".qrbox").css({
                    "box-shadow": "none"
                });

            },
            function() {
                $("header, .main-sidebar, .main-footer, .topaleart").removeClass('displaynone');
                $(".content-wrapper").attr('style', "").attr('style', s);

                $(".content").css({
                    "padding": "10px"
                });

                $(".content-wrapper").css({
                    "background-color": "#f3f3f3"
                });

                $(".wrapper").css({
                    "box-shadow": ""
                });

                $(".qrbox").css({
                    "box-shadow": ""
                });

            });
    });
</script>