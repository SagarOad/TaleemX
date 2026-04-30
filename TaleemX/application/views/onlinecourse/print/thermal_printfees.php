<?php $currency_symbol = $this->customlib->getSchoolCurrencyFormat();?>

<!DOCTYPE html>
<html lang="en">
<meta name="viewport" content="width=device-width, initial-scale=1">
<head>
    <style>
        .page-break { display: block; page-break-before: always; }
        
        *{padding: 0; margin: 0;}
        body {
            margin: 0;
            padding: 0;
            font-family: 'arial', sans-serif;
            font-size: 11pt;
        }

         @page {
            size: 2.9in 11in;
            margin-top: 0cm;
            margin-left: 0cm;
            margin-right: 0cm;
            margin-bottom: 0cm;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }
 
        table th, table td{padding-top: 5px; padding-bottom: 5px;font-size: 9pt;vertical-align: top;}
        p{margin-bottom: 5px;}
        h1 {
            margin: 0;
            padding: 0;
            font-size: 16pt; font-weight:bold
        }
       
.title-around-span {
    position: relative;
    text-align: center;
    padding: 0;
} 

.title-around-span:before {
    content: "";
    display: block;
    width: 100%;
    position: absolute;
    left: 0;
    top: 50%;
    border-top: 2px #000 dashed;
}

.title-around-span span {
    position: relative;
    z-index: 1;
    padding: 0 5px;
    color: #000;
    font-weight: bold;
}

.title-around-span span:before{
    content: "";
    display: block;
    width: 100%;
    z-index: -1;
    position: absolute;
    left: 0;
    top: 50%;
    border-top: 10px #fff solid;
}

    </style>
</head>
<body>
<?php 
$print_copy=explode(',', $sch_setting->is_duplicate_fees_invoice);
for($i=0;$i<count($print_copy);$i++){   ?>
	<?php if($sch_setting->single_page_print==0 && $i > 0) {  ?>
    <div class="page-break"></div>
	<?php } ?>
	
   <div style="margin: 0 auto;padding: 0;">
        <h1 style="text-align: center;padding-bottom: 5px;"><?php echo $thermal_print['school_name'];?></h1>
        <p style="text-align:center; font-weight:bold;"><?php echo $thermal_print['address'];?></p>
        <h2 style="border-top: 1px solid #000; border-bottom: 1px solid #000; text-align:center;font-size: 12pt;padding-top: 5px; padding-bottom: 5px; margin-top: 8px; margin-bottom:5px;"><?php echo $this->lang->line('receipt_number'); ?>: <?php echo $courselist['receipt_number']; ?></h2>
        <table width="100%"   cellpadding="0" cellspacing="0" style="line-height: 11px;padding-top: 2px;">   
            <tbody>
                <tr>
                    <td align="left" colspan="2" style="padding-top:0; padding-bottom:0; font-weight: bold"><?php echo $this->lang->line('name'); ?>: <?php echo $courselist['firstname'].' '.$courselist['lastname'].' ('.$courselist['admission_no'].')'; ?></td>
				</tr>
				<tr>
                    <td align="left" colspan="2" style="padding-top:2px;font-weight: bold; padding-bottom:2px;"><?php echo $this->lang->line('class'); ?>: <?php echo $courselist['class']." (".$courselist['section'].")"; ?></td>					
                </tr>
            </tbody>
        </table> 
       <?php
        if (!empty($courselist)) { ?>
            <table width="100%" cellpadding="0" cellspacing="0" style="line-height: 11px;padding-top: 2px;">
                <tr><td class="title-around-span"  colspan="3"><span><?php echo $this->lang->line('course'); ?></span></td></tr>
                <tr><td colspan="3" align="center" style="font-weight: bold;"><?php echo $courselist['title']; ?></td></tr>              
            </table>
         <table width="100%" cellpadding="0" cellspacing="0" style="border-top: 2px #000 dashed;line-height: 11px;padding-top: 2px;">
                <tr>
                    <th  width="25%"><?php echo $this->lang->line('payment_type'); ?></th>
                    <th  width="25%"><?php echo $this->lang->line('payment_mode'); ?></th>                   
                    <th  width="20%" class="text-right"><?php echo $this->lang->line('price').' ('.$currency_symbol.')'; ?></th>
                </tr>
                <tbody>
                <tr>
                    <td><?php echo $this->lang->line(strtolower($courselist['payment_type'])); ?></td>
                    <td><?php echo $this->lang->line($courselist['payment_mode']); ?></td>
                    <td class="text-right"><?php echo amountFormat($courselist['paid_amount']); ?></td>
                </tr>
                </tbody>
            </table>
        <?php } ?>
         <p style="padding-top:3px; line-height:normal; font-size:8pt; padding-bottom: 5px;border-top: 1px #000 solid; font-style: italic;"><?php echo $thermal_print['footer_text'];?> </p>
    </div> 
	<?php
}
?>
</body>
</html>