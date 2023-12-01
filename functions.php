<?php
if(!class_exists('cf7p_functions')){
    class cf7p_functions
    {
        public function __construct() { 
            include_once(ABSPATH . 'wp-admin/includes/plugin.php');
            if (is_plugin_active('contact-form-7/wp-contact-form-7.php')) {
                add_action( 'wpcf7_mail_sent', array('cf7p_functions','cf7p_after_mail_sent'));
                add_shortcode( 'cf7p', array('cf7p_functions','shortcode_callback') );
            }
        }
      
        static function shortcode_callback($attr, $content = null){
            // Get All Contact Forms And Check Whether Contact form Exists Or Not
            $args = array (
                'post_type'              => 'wpcf7_contact_form',
                'post_status'            => 'publish',
            );
            // The Query
            $query = new WP_Query( $args );
            $forms = $query->posts;
            $id = [];
            foreach ($forms as $key => $value) {
                array_push($id,$value->ID);
            }

            if (isset($attr['id']) && !empty($attr['id']) && in_array($attr['id'],$id)) {
                $form_id = $attr['id'];
                global $wpdb;
                $db_table_name = $wpdb->prefix . 'cf7p_options';
                $cf7p_option = get_option('cf7p_'.$form_id.'');
                if($wpdb->get_var( "show tables like '$db_table_name'" ) == $db_table_name ){
                    $results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $db_table_name WHERE form_id = %d", $form_id ) );
                }
                else{
                    $results = [];
                }
                $final_arr = $max_cnt = $single_field_count = [];
                if (isset($results)) {
                    foreach ($results as $key => $value) {
                        $data = unserialize($value->inputs);
                        if (!empty($data)) {
                            foreach ($data as $data_key => $data_value) {
                                $max_cnt[$data_key][]=$data_value;  
                                if( strpos($data_value, ',') !== false ) {
                                    $multiple_values = explode(",",$data_value);
                                    if (isset($multiple_values)) {
                                        foreach ($multiple_values as $keys => $data_value) {
                                            $final_arr[$data_key][]=$data_value;
                                        }
                                    }
                                }else{
                                    if (isset($data_value) && !empty($data_value)) {
                                        $final_arr[$data_key][]  =   $data_value;
                                    }
                                }                    
                            }
                        }
                        else{
                            $delete = $wpdb->query( $wpdb->prepare( "DELETE FROM $db_table_name WHERE id = %d", $value->id ) );
                        }
                    }
                }
               
                $titles         = (isset($cf7p_option['cf7p_title'])) ? explode(',',$cf7p_option['cf7p_title']) : array();
                $names          = (isset($cf7p_option['cf7p_name']) && (!empty($cf7p_option['cf7p_name']))) ? explode(',',$cf7p_option['cf7p_name']) : array();
                $cf7p_status    = (isset($cf7p_option['cf7p_status'])) ? $cf7p_option['cf7p_status'] : ''; 
                $cf7p_votes     = (isset($cf7p_option['cf7p_votes'])) ? $cf7p_option['cf7p_votes'] : '';
                $cf7p_percentage = (isset($cf7p_option['cf7p_percentage'])) ? $cf7p_option['cf7p_percentage'] : '';
                $progreessbar_color     = ((isset($cf7p_option['cf7p_color'])) ? sanitize_text_field($cf7p_option['cf7p_color']) : '');
                $progreessbar_bg_color  = ((isset($cf7p_option['cf7p_backcolor'])) ? sanitize_text_field($cf7p_option['cf7p_backcolor']) : '');
                $contact_form   = WPCF7_ContactForm::get_instance( $form_id );
                $form_fields    = $contact_form->scan_form_tags();
                if (!empty($cf7p_option) && !empty($cf7p_status)){ ?>
                    <div class="cf7p-div cf7p-main-box-<?php echo esc_attr($form_id); ?>">
                        <?php if(empty($names)){ ?>
                            <h2><?php esc_html_e('Poll Not Added', 'polls-for-contact-form-7'); ?></h2>
                            <?php 
                        }else{
                            
                            if(!empty($progreessbar_color) || $progreessbar_bg_color){ ?>
                                <style>
                                    <?php    
                                    if(!empty($progreessbar_color)) { 
                                        
                                        ?>
                                        .cf7p-main-box-<?php echo esc_attr($form_id); ?> .cf7p-poll-bar{
                                            background: <?php echo esc_attr($progreessbar_color); ?> !important;
                                        }
                                        <?php
                                    }
                                    if(!empty($progreessbar_bg_color)) { ?>
                                        .cf7p-main-box-<?php echo esc_attr($form_id); ?> .cf7p-poll-bg{
                                            background: <?php echo esc_attr($progreessbar_bg_color); ?> !important;
                                        }
                                    <?php
                                    } ?>
                                </style>
                                <?php
                            } ?>
                            
                            <ul>
                                <?php 
                                for ($i=0; $i <count($names) ; $i++) {
                                    $title = (!empty($titles[$i])) ? wp_unslash($titles[$i]) : ''; ?>
                                    <li>
                                    <?php
                                        if (!empty($title)) { ?>
                                            <h3><?php esc_html_e($title)?></h3>
                                        <?php }
                                        if(isset($form_fields) && !empty($form_fields)){
                                            foreach ($form_fields as $key => $field) {
                                                if ($names[$i]==$field->name) { 
                                                    // All Field Count
                                                    $max_count = ((isset($max_cnt[$names[$i]]) && !empty($max_cnt[$names[$i]])) ? count($max_cnt[$names[$i]]) : 0);  ?>
                                                    <ul>  
                                                        <?php 
                                                        if (!empty($field->name)) {      
                                                            $field_name_chk = $field->name;
                                                            $cf7p_cnt[$field_name_chk] = (isset($final_arr[$field_name_chk])) ? array_count_values($final_arr[$field_name_chk]) : [];
                                                            $cf7p_field_values = $field->values;
                                                            $cf7p_sorted_arr = [];
                                                            if(isset($cf7p_field_values) && !empty($cf7p_field_values)) {
                                                                foreach ($cf7p_field_values as $field_key => $field_value) { 
                                                                    $cf7p_sorted_arr[$field_value] = (array_key_exists($field_value, $cf7p_cnt[$field_name_chk] )) ? $cf7p_cnt[$field_name_chk][$field_value] : 0 ;
                                                                }
                                                            }
                                                            arsort($cf7p_sorted_arr);
                                                            foreach ($cf7p_sorted_arr as $field_key => $field_value) { 
                                                                $count = (isset($cf7p_cnt[$field_name_chk][$field_key]) ? $field_value : 0);
                                                                $percentage = ((isset($max_count) && !empty($max_count)) ? ($count/$max_count)*100 : 0);
                                                                $cf7p_votes_elem = ($cf7p_votes == 1 || $cf7p_percentage == 1 ) ? true : false; 
                                                                ?>
                                                            <li>
                                                                <div class="cf7p-poll-name"><?php esc_attr_e($field_key); ?></div>
                                                                <div class="cf7p-choice-poll <?php if($cf7p_votes == 0 || $cf7p_percentage == 0 ) { echo esc_attr('cf7p-poll-only'); } ?>">
                                                                    <div class="cf7p-poll-bg">
                                                                        <div class="cf7p-poll-bar" style="width: <?php esc_attr_e(round($percentage, 2)); ?>%;"></div>
                                                                    </div> 
                                                                    <?php if($cf7p_votes_elem){ ?>
                                                                        <div class="cf7p-poll-votes">
                                                                            <?php if($cf7p_votes == 1){ ?>
                                                                                <span><?php esc_attr_e($count); ?> Vote<?php esc_html_e((($count>1)?'s':'') );?> </span>
                                                                            <?php } ?>
                                                                            <?php if($cf7p_percentage == 1){ ?>
                                                                                <span>(<?php esc_attr_e(round($percentage, 2)); ?>%)</span>
                                                                            <?php } ?>
                                                                        </div>
                                                                        <?php
                                                                    } ?>
                                                                </div>

                                                            </li>
                                                                <?php
                                                            }
                                                        }
                                                        ?> 
                                                    </ul>
                                                <?php }
                                            }
                                        } ?>
                                    </li>
                                <?php } ?>  
                            </ul>
                        <?php } ?>
                    </div>
                    <?php
                }else{ ?>
                        <h3><?php esc_html_e('Poll is Disabled', 'polls-for-contact-form-7'); ?></h3>    
                    <?php
                }
            }
        }

        static function cf7p_after_mail_sent($contact_form){
            global $wpdb;
            $form_id    = $contact_form->id();
            $option = get_option('cf7p_'.$form_id);
            $db_table_name  = $wpdb->prefix . 'cf7p_options';
            $cf7p_set_limit = (isset($option['cf7p_set_limit']) && !empty($option['cf7p_set_limit'])) ? $option['cf7p_set_limit'] : '';
            $cf7p_limit_per_mail = (isset($option['cf7p_limit_per_mail']) && !empty($option['cf7p_limit_per_mail'])) ? $option['cf7p_limit_per_mail'] : '';
            $cf7p_email_field = (isset($option['cf7p_email_field']) && !empty($option['cf7p_email_field'])) ? $option['cf7p_email_field'] : '';
            $cf7p_limit     = (isset($option['cf7p_limit']) && !empty($option['cf7p_limit'])) ? $option['cf7p_limit'] : 0;
            $cf7p_status    = (isset($option['cf7p_status']) && !empty($option['cf7p_status'])) ? $option['cf7p_status'] : '';
            $cf7p_name      = (isset($option['cf7p_name']) && !empty($option['cf7p_name'])) ? $option['cf7p_name'] : '';
            $cf7p_remaining_vote = 1;

            if (!empty($cf7p_set_limit) && isset($cf7p_limit) && !empty($cf7p_limit)) {
                $results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $db_table_name WHERE form_id = %d", $form_id ) );
                if(!empty($results)) $cnt = $cf7p_limit - count($results);
                $cf7p_remaining_vote = (isset($cnt) ? max($cnt,0) : $cf7p_limit);
            }elseif(!empty($cf7p_set_limit) && empty($cf7p_limit)){
                $cf7p_remaining_vote = 0;
            }

            if (isset($option) && !empty($option) && ($cf7p_status=='on') && !empty($cf7p_name) ) {
                $submission = WPCF7_Submission::get_instance();
                $posted_data = $submission->get_posted_data();
                if ( $submission ) {
                    $cf7p_name = explode(",",$cf7p_name);
                    $cf7p_data = [];
                    $i = 0;
                    if (isset($cf7p_name)) {
                        foreach ($cf7p_name as $key => $value) {
                            if (isset($_POST[$value]) && !empty($_POST[$value])) {
                                if (is_array($_POST[$value])) {
                                    $inputs = array_map( 'sanitize_text_field', $_POST[$value] );
                                    $str = implode(",",$inputs);
                                    $data[$cf7p_name[$i]] = $str;
                                }else{
                                    $inputs = sanitize_text_field($_POST[$value]);
                                    $data[$cf7p_name[$i]] = $inputs;
                                }
                                $cf7p_data = $data;
                            }
                            $i++;
                        }
                    }
                    $cf7p_serialize_data = serialize($cf7p_data);
                    
                    if($cf7p_remaining_vote > 0 || empty($cf7p_set_limit) ){
                        // Store Email Based Data
                        if (!empty($cf7p_limit_per_mail)) {
                            $column = $wpdb->get_results($wpdb->prepare(
                                "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s ",
                                $wpdb->dbname,
                                $db_table_name,
                                'email_data'
                            ));
                            if(empty($column)){
                                $wpdb->query("ALTER TABLE $db_table_name ADD email_data varchar(900) NOT NULL");
                            }                            
                            $email = [];
                            $email[$cf7p_email_field] = $posted_data[$cf7p_email_field];
                            $cf7p_email_serialize_data = serialize($email);

                            $mail_based_data = $wpdb->get_results( $wpdb->prepare( "SELECT email_data FROM $db_table_name WHERE form_id = %d AND email_data = %s", $form_id,$cf7p_email_serialize_data ) );
                            if (empty($mail_based_data) && !empty($posted_data[$cf7p_email_field])) {
                                $inputs = array('form_id' => $form_id,'inputs'=>$cf7p_serialize_data,'email_data'=>$cf7p_email_serialize_data );
                                $wpdb->insert($db_table_name,$inputs);
                            }
                        }

                        else{
                            $inputs = array('form_id' => $form_id,'inputs'=>$cf7p_serialize_data);
                            $wpdb->insert($db_table_name,$inputs);
                        }
                    }
                    
                }
            } 
        }
    }
    new cf7p_functions();
}