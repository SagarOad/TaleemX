<?php
$lang = $this->setting_model->get(); 
$session = $this->session->userdata('admin');

$id          = $session['id'];
$defoultlang = $this->setting_model->get_stafflang($id);

if (!empty($defoultlang)) {
    if ($defoultlang['lang_id'] != 0) {
        $defoult = $defoultlang['lang_id'];
    } else {
        $defoult = $lang[0]['lang_id'];
    }
}
$json_languages = json_decode($lang[0]['languages']);

if (!is_array($json_languages)) {
    $json_languages = [];
}
$arabic_row = $this->db->select('id')->from('languages')->where('language', 'Arabic')->get()->row_array();
if (!empty($arabic_row)) {
    $arabic_id = (int) $arabic_row['id'];
    if (!in_array($arabic_id, array_map('intval', $json_languages), true)) {
        $json_languages[] = $arabic_id;
    }
}

foreach ($json_languages as $value) {
    $result = $this->db->select('languages.language,`languages`.`country_code`')->from('languages')->where('id', $value)->get()->row_array();
    ?>
    <option data-content='<span class="flag-icon flag-icon-<?php echo $result['country_code']; ?>"></span> <?php echo $result['language']; ?>' value="<?php echo $value; ?>" <?php
if ($defoult == $value) {
        echo "Selected";
    }
    ?>></option>
    <?php
}
?>