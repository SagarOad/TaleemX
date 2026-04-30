<html>
<head>
  <style>
    .container{
      display: flex;
      justify-content: center; /* horizontal */
      align-items: center;    /* vertical 
      /*height: 100vh;          /* full height of viewport */*/
    }

  </style>

  <style>
    .submit-button{
  padding: 12px 15px;
  margin: 10px;
  background-color: #2d32d5;
  border-radius: 5px;
  color: #fff;
  text-decoration: none;
  border: none;
  cursor: pointer;

  }
 
</style>
</head>

<body>

<div class="container">
  <div class="this-template" style="position: relative;"  width="960px" height="720px">
    <?php  echo  $certificate_templatedat; ?>
  </div>
</div>

<a href='<?php echo base_url("course/downloadcertificatepdf/$certificate_id/$student_id/$course_id");?>' class="btn btn-info submit-button"  align="center"><?php echo $this->lang->line('download');?></a>

</body>
</html>
