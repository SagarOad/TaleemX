<!DOCTYPE html>
<html <?php echo $this->customlib->getRTL(); ?>>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?php echo $this->customlib->getAppName(); ?></title>
    <meta name="format-detection" content="telephone=no">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta http-equiv="Cache-control" content="no-cache">
    <meta name="theme-color" content="#424242" />
    <link href="<?php echo base_url(); ?>uploads/school_content/admin_small_logo/<?php $this->setting_model->getAdminsmalllogo(); ?>" rel="shortcut icon" type="image/x-icon">
    <link type="text/css" rel="stylesheet" href="https://source.zoom.us/3.11.2/css/bootstrap.css" />
</head>

<body oncontextmenu="return false;">
<style type="text/css">
        body {
            padding-top: 0px;
        }
        #zmmtg-root {
    width: 100%;
    height: 100%;
   
    top: 0px;
    left: 0;
    background-color: #000;
}
        .navbar-inverse {
            z-index: 1;
            padding: 0;
            background-color: #040404;
        }

        .navbar-header h4 {
            margin: 0;
            /* padding: 15px 15px; */
            color: #c4c2c2;
        }

        .navbar-right h5 {
            margin: 0;
            padding: 9px 5px;
            color: #c4c2c2;
        }

        .navbar-inverse .navbar-collapse,
        .navbar-inverse .navbar-form {
            border-color: transparent;
        }
    </style>

    <nav id="nav-tool" class="navbar navbar-inverse navbar-fixed-top">
        <div class="container">
            <div class="navbar-header">
                <h4><i class="fab fa-chromecast"></i> Live Class Title : <?php echo $title; ?></h4>
            </div>
            <div class="navbar-form navbar-right">
                <h5><i class="far fa-user-circle" style=""></i> Host By : <?php echo $host; ?></h5>
            </div>
        </div>
    </nav>
    <script src="https://source.zoom.us/3.11.2/lib/vendor/react.min.js"></script>
    <script src="https://source.zoom.us/3.11.2/lib/vendor/react-dom.min.js"></script>
    <script src="https://source.zoom.us/3.11.2/lib/vendor/redux.min.js"></script>
    <script src="https://source.zoom.us/3.11.2/lib/vendor/redux-thunk.min.js"></script>
    <script src="https://source.zoom.us/3.11.2/lib/vendor/lodash.min.js"></script>
    <script src="https://source.zoom.us/3.11.2/zoom-meeting-3.11.2.min.js"></script>

    <script type="text/javascript">
        // document.onkeydown = function(e) {
        //     if (event.keyCode == 123) {
        //         return false;
        //     }
        //     if (e.ctrlKey && e.shiftKey && e.keyCode == 'I'.charCodeAt(0)) {
        //         return false;
        //     }
        //     if (e.ctrlKey && e.shiftKey && e.keyCode == 'C'.charCodeAt(0)) {
        //         return false;
        //     }
        //     if (e.ctrlKey && e.shiftKey && e.keyCode == 'J'.charCodeAt(0)) {
        //         return false;
        //     }
        //     if (e.ctrlKey && e.keyCode == 'U'.charCodeAt(0)) {
        //         return false;
        //     }
        // }

        window.addEventListener('DOMContentLoaded', function(event) {
            console.log('DOM fully loaded and parsed');
            websdkready();
        });


  


        function websdkready() {
    
            ZoomMtg.setZoomJSLib("https://source.zoom.us/3.11.2/lib", "/av"); // CDN version defaul
 
       
  ZoomMtg.preLoadWasm();
  ZoomMtg.prepareWebSDK();

     var meetingConfig = {
         sdkKey: "<?php echo $zoom_api_key; ?>",
         meetingNumber: "<?php echo $meetingID; ?>",
         userName: "<?php echo $name; ?>",
         passWord: "<?php echo $meeting_password ?>",
         leaveUrl: "<?php echo site_url($leaveUrl); ?>",
         role: 0,
         userEmail: "",
         lang:"en-US",
         china: false,
         sdkSecret: "<?php echo $zoom_api_secret; ?>",
         webEndpoint:'zoom.us'
     };
     console.log(meetingConfig);
     // var signature = ZoomMtg.generateSignature({
     //     meetingNumber: meetingConfig.meetingNumber,
     //     apiKey: meetingConfig.apiKey,
     //     apiSecret: meetingConfig.apiSecret,
     //     role: meetingConfig.role,
     //     success: function(res) {
     //         console.log(res.result);
     //     }
     // });
     
     var signature = ZoomMtg.generateSDKSignature({
         meetingNumber: meetingConfig.meetingNumber,
         sdkKey: meetingConfig.sdkKey,
         sdkSecret: meetingConfig.sdkSecret,
         role: meetingConfig.role,
         success: function(res) {
             console.log(res.result);
         },
     });
     
     
     function beginJoin(signature) {
      
         ZoomMtg.init({
            
             leaveUrl: meetingConfig.leaveUrl,
             webEndpoint: meetingConfig.webEndpoint,
             disableCORP: !window.crossOriginIsolated, // default true
             // disablePreview: false, // default false
             externalLinkPage: './externalLinkPage.html',
             success: function() {
                 console.log(meetingConfig);
                 console.log("signature", signature);
                 ZoomMtg.i18n.load(meetingConfig.lang);
                 ZoomMtg.i18n.reload(meetingConfig.lang);
                 ZoomMtg.join({
                     meetingNumber: meetingConfig.meetingNumber,
                     userName: meetingConfig.userName,
                     signature: signature,
                     sdkKey: meetingConfig.sdkKey,
                     userEmail: meetingConfig.userEmail,
                     passWord: meetingConfig.passWord,
                     success: function(res) {
                         console.log("join meeting success");
                         console.log("get attendeelist");
                         ZoomMtg.getAttendeeslist({});
                         ZoomMtg.getCurrentUser({
                             success: function(res) {
                                 console.log("success getCurrentUser", res.result.currentUser);
                             },
                         });
                     },
                     error: function(res) {
                         console.log(res);
                     },
                 });
             },
             error: function(res) {
                 console.log(res);
             },
         });
     
         ZoomMtg.inMeetingServiceListener('onUserJoin', function(data) {
             console.log('inMeetingServiceListener onUserJoin', data);
         });
     
         ZoomMtg.inMeetingServiceListener('onUserLeave', function(data) {
             console.log('inMeetingServiceListener onUserLeave', data);
         });
     
         ZoomMtg.inMeetingServiceListener('onUserIsInWaitingRoom', function(data) {
             console.log('inMeetingServiceListener onUserIsInWaitingRoom', data);
         });
     
         ZoomMtg.inMeetingServiceListener('onMeetingStatus', function(data) {
             console.log('inMeetingServiceListener onMeetingStatus', data);
         });
     }
     
     // ZoomMtg.init({
     //     leaveUrl: meetingConfig.leaveUrl,
     //     isSupportAV: true,
     //     success: function() {
     //         ZoomMtg.join({
     //             meetingNumber: meetingConfig.meetingNumber,
     //             userName: meetingConfig.userName,
     //             signature: signature,
     //             sdkKey: meetingConfig.apiKey,
     //             passWord: meetingConfig.passWord,
     //             success: function(res) {
     //                 $('#nav-tool').hide();
     //             },
     //             error: function(res) {
     //                 console.log(res);
     //             }
     //         });
     //     },
     //     error: function(res) {
     //         console.log(res);
     //     }
     // });
 
 
     beginJoin(signature);
 
 }
    </script>
</body>

</html>