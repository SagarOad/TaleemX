<?php $this->load->view('layout/course_css.php'); ?>
<div class="content-wrapper">   
    
    <!-- Main content -->
    <section class="content">
        <?php $this->load->view('onlinecourse/report/_coursereport'); ?>
        <div class="row">
            <div class="col-md-12">
                <div class="box removeboxmius">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-search"></i> <?php echo  $this->lang->line("course_assignment_report"); ?></h3>
                    </div>
                    <div class="box-body"> 
                        <div class="table-responsive">
                            <table class="table table-switch table-striped table-bordered table-hover all-list"  cellspacing="0" data-export-title="<?php echo $this->lang->line('course_assignment_report'); ?>">
                                <thead>
                                    <tr>                                 
                                        <th><?php echo  $this->lang->line("course_name"); ?></th>
                                        <th><?php echo  $this->lang->line("assignment_title"); ?></th>
                                        <th><?php echo  $this->lang->line("assignment_date"); ?></th>
                                        <th><?php echo  $this->lang->line("submit_date"); ?></th>
                                        <th><?php echo  $this->lang->line("created_by"); ?></th>
                                        <th><?php echo  $this->lang->line("evaluated_by"); ?></th>
                                        <th><?php echo  $this->lang->line("total_student"); ?></th>
                                        <th><?php echo  $this->lang->line("total_submit"); ?></th>
                                        <th><?php echo  $this->lang->line("total_evaluate"); ?></th>
                                        <th><?php echo  $this->lang->line("total_guest"); ?></th>
                                        <th><?php echo  $this->lang->line("total_submit"); ?></th>
                                        <th><?php echo  $this->lang->line("total_evaluate"); ?></th>
                                        <th><?php echo  $this->lang->line("total_student_guest"); ?></th>                        
                                </thead>
                                <tbody>
                                </tbody>
                            </table> 
                        </div>                       
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
<div id="studentModal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-lg">
        <!-- Modal content-->
        <div class="modal-content">
           
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <div class="pull-right box-tools pt3 d-flex gap-0-5 pr5" id="export_buttons">
                    <button type="button" title="<?php echo $this->lang->line('download_excel'); ?>" onclick="fnExcelReport();" class="btn btn-primary btn-xs dt-button buttons-excel buttons-html5" autocomplete="off"><i class="fa fa-file-excel-o"></i></button>
                    <button type="button" title="<?php echo $this->lang->line('print'); ?>" onclick="print_table('myTable');" class="btn btn-primary btn-xs dt-button buttons-print"><i class="fa fa-print"></i></button>
                </div>
                <h4 class="modal-title"><?php echo $this->lang->line('student_list'); ?></h4>
                 
            </div>
            <div class="modal-body scroll-area">
            </div>
        </div>
    </div>
</div>
<script>
( function ( $ ) {
    'use strict';
    $(document).ready(function () {
        initDatatable('all-list','onlinecourse/coursereport/get_course_assignment_report',[],[],100);
    });
} ( jQuery ) )

function view_student_list(courseid,assignment_id,free_course){
    $.ajax({
        type: 'POST',
        url: base_url + "onlinecourse/coursereport/getstudents",
        data: {'courseid':courseid,'free_course':free_course,'assignment_id':assignment_id},
        dataType: 'JSON',
        beforeSend: function () {
        },
        success: function (data) {
            $('#studentModal .modal-body').html(data.page);
            $('#studentModal').modal('show');
            
            if(data.status){
                $("#export_buttons").show();
            }else{
                $("#export_buttons").hide();
            }
        },
        error: function (xhr) { 
            // if error occured
        },
        complete: function () {

        }
        });
}
</script>
<script type="text/javascript">
    function print_table(divID) {
        var oldPage = document.body.innerHTML;
        $("#table-heading").html("");  
        var divElements = document.getElementById(divID).innerHTML;
        document.body.innerHTML =
          "<html><head><title></title></head><body>" +
          divElements + "</body>";
        window.print();
        document.body.innerHTML = oldPage;
    }

    function fnExcelReport()
    {
        var tab_text = "<table border='2px'><tr >";
        var textRange;
        var j = 0;
        tab = document.getElementById('headerTable'); // id of table

        for (j = 0; j < tab.rows.length; j++)
        {
            tab_text = tab_text + tab.rows[j].innerHTML + "</tr>";           
        }

        tab_text = tab_text + "</table>";
        tab_text = tab_text.replace(/<A[^>]*>|<\/A>/g, "");//remove if u want links in your table
        tab_text = tab_text.replace(/<img[^>]*>/gi, ""); // remove if u want images in your table
        tab_text = tab_text.replace(/<input[^>]*>|<\/input>/gi, ""); // reomves input params
        var ua = window.navigator.userAgent;
        var msie = ua.indexOf("MSIE ");
        if (msie > 0 || !!navigator.userAgent.match(/Trident.*rv\:11\./))      // If Internet Explorer
        {
            txtArea1.document.open("txt/html", "replace");
            txtArea1.document.write(tab_text);
            txtArea1.document.close();
            txtArea1.focus();
            sa = txtArea1.document.execCommand("SaveAs", true, "Say Thanks to Sumit.xls");
        } else                 //other browser not tested on IE 11
            sa = window.open('data:application/vnd.ms-excel,' + encodeURIComponent(tab_text));
        return (sa);
    }
</script>

