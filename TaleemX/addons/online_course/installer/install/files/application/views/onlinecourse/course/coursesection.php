<?php foreach ($sectionlist as $value) {
    ?>
     <option value="<?php echo $value['id']; ?>" <?php if (in_array($value['id'], $multipalsection)) {echo "selected";}?>><?php echo $value['section']; ?></option>
<?php }?>