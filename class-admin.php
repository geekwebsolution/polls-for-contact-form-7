<?php
if (!class_exists('cf7p_settings')) {
    class cf7p_settings
    {
        public function __construct()
        {
            include_once(ABSPATH . 'wp-admin/includes/plugin.php');
            if ((is_plugin_active('contact-form-7/wp-contact-form-7.php'))) {
                add_action('wpcf7_editor_panels', array('cf7p_settings', 'polls_settings'));
                wpcf7_add_form_tag('cf7p_view_result', array('cf7p_settings', 'cf7p_view_result_btn'));
                add_action('wpcf7_after_save', array('cf7p_settings', 'cf7p_save_settings_call'));
            }
        }

        static function cf7p_view_result_btn($tag)
        {
            $contact_form   = WPCF7_ContactForm::get_current();
            $form_id        = $contact_form->id();
            $cf7p_option    = get_option('cf7p_' . $form_id);
            if(isset($cf7p_option) && !empty($cf7p_option)) {
                $cf7p_view_result = $cf7p_option['cf7p_view_result'];
                $cf7p_status    = $cf7p_option['cf7p_status'];
                $html='';
                if ($cf7p_view_result && $cf7p_view_result==1 && !empty($cf7p_option["cf7p_name"]) && !empty($cf7p_status)) {
                    $html .= '<a href="javascript:void(0);" class="cf7p_result_btn" data-value="' . $form_id . '">View Result</a>';
                    return $html;
                }
            }
        }

        static function polls_settings($panels)
        {
            $panels['cf7p-polls'] = array(
                'title'     => __('Polls Settings', 'cf7p-setting'),
                'callback'  => array('cf7p_settings', 'polls_settings_callback'),
            );
            $panels['cf7p-polls-result'] = array(
                'title'     => __('Polls Result', 'cf7p-setting'),
                'callback'  => array('cf7p_settings', 'polls_result_callback'),
            );
            return $panels;
        }
        static function polls_settings_callback($post)
        {
            global $wpdb;
            $db_table_name  =   $wpdb->prefix . 'cf7p_options';
            $cf7p_nonce     =   wp_create_nonce('cf7p_option_nonce');
            $form_id        =   $post->id();
            $option         =   get_option('cf7p_' . $form_id);
            $cf7p_status = $cf7p_view_result = $cf7p_name = $cf7p_percentage = $cf7p_votes = $cf7p_color = $cf7p_backcolor = $cf7p_set_limit = $cf7p_limit_per_mail="";
            if(isset($option["cf7p_status"]))       $cf7p_status = $option["cf7p_status"];
            if(isset($option["cf7p_view_result"]))  $cf7p_view_result = $option["cf7p_view_result"];
            if(isset($option["cf7p_name"]))         $cf7p_name = $option["cf7p_name"];
            if(isset($option["cf7p_percentage"]))   $cf7p_percentage = $option["cf7p_percentage"];
            if(isset($option["cf7p_votes"]))        $cf7p_votes = $option["cf7p_votes"];
            if(isset($option["cf7p_color"]))        $cf7p_color = $option["cf7p_color"];
            if(isset($option["cf7p_backcolor"]))    $cf7p_backcolor = $option["cf7p_backcolor"];
            if(isset($option["cf7p_set_limit"]))  $cf7p_set_limit = $option["cf7p_set_limit"];
            if(isset($option["cf7p_limit_per_mail"]))  $cf7p_limit_per_mail = $option["cf7p_limit_per_mail"];
            if(isset($option["cf7p_email_field"]))  $cf7p_email_field = $option["cf7p_email_field"];
            $cf7p_limit = (isset($option['cf7p_limit']) ) ? $option['cf7p_limit'] : 1;
            ?>
            <div class="contact-form-polls-box" id="cf7p_polls">
                <?php
                if ($post->id()) { ?>
                    <h2><?php _e('Polls Settings'); ?></h2>
                    <fieldset>
                        <legend>
                            <?php _e('You can use shortcodes to print polls.'); ?>
                        </legend>
                        <div>
                            <input type="text" onfocus="this.select();" readonly="readonly" value="[<?php esc_html_e('cf7p id=' . $form_id, 'polls-for-contact-form-7'); ?>]" class="large-text code shortcode">
                        </div>
                        <table class="form-table">
                            <tbody>
                                <tr>
                                    <th scope="row">
                                        <label for="cf7p_status"><?php esc_html_e('Status', 'polls-for-contact-form-7'); ?></label>
                                    </th>
                                    <td>
                                        <label class="cf7p-switch">
                                            <input type="checkbox" class="cf7p-checkbox" name="cf7p_status" value="on" <?php if ($cf7p_status == 'on' && !empty($cf7p_status)) esc_attr_e('checked'); ?>>
                                            <span class="cf7p-slider"></span>
                                        </label>
                                        <p class="cf7p-note"><i>Note : <?php esc_html_e('Enable to show polls result.', 'polls-for-contact-form-7'); ?></i></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label><?php esc_html_e('All Polls', 'polls-for-contact-form-7'); ?></label>
                                    </th>
                                    <td>
                                        <table class="cf7p_polls_table">
                                            <thead>
                                                <th>
                                                    <span><?php esc_html_e('Title', 'polls-for-contact-form-7'); ?></span>
                                                </th>
                                                <th>
                                                    <span><?php esc_html_e('Select Field', 'polls-for-contact-form-7'); ?></span>
                                                </th>
                                            </thead>
                                            <tbody id="cf7p_all_polls">
                                                <?php
                                                $ContactForm = WPCF7_ContactForm::get_instance($form_id);
                                                $form_fields = $ContactForm->scan_form_tags();
                                                $input_type = array('select', 'checkbox', 'radio');                                                
                                                $all_fields = [];
                                                if(isset($form_fields) && $form_fields){
                                                    foreach ($form_fields as $key => $value) {
                                                        if ((in_array($value->basetype, $input_type))) {
                                                            array_push($all_fields,$value->name);
                                                        }
                                                        
                                                    }
                                                }
                                                if (isset($cf7p_name) && !empty($cf7p_name)) {
                                                    $title_arr = explode(",", $option['cf7p_title']);
                                                    $name_arr = explode(",", $cf7p_name);
                                                    $status = 0;
                                                    if(isset($form_fields) && $form_fields){
                                                        foreach ($form_fields as $key => $value) {
                                                            if (!(in_array($value->basetype, $input_type))) {
                                                                $status++;
                                                            }
                                                            
                                                        }
                                                    }
                                                    if ($status > 0) {
                                                        if(!empty($name_arr) && !empty($title_arr)){
                                                            for ($i = 0; $i < count($name_arr); $i++) {
                                                                $title = (!empty($title_arr[$i])) ? wp_unslash($title_arr[$i]) : ''; ?>
                                                                <tr class="cf7p-field-row" datafield=<?php echo $name_arr[$i] ?>>
                                                                    <td>
                                                                        <input type="text" name="cf7p-title[]" id="cf7p-title" value="<?php esc_html_e($title); ?>" />
                                                                    </td>

                                                                    <td>
                                                                        <select name="cf7p-names[]" id="cf7p-name">
                                                                            <?php
                                                                            if(isset($form_fields) && !empty($form_fields)){
                                                                                foreach ($form_fields as $key => $value) {
                                                                                    if (in_array($value->basetype, $input_type)) { 
                                                                                            ?>
                                                                                        <option value="<?php esc_attr_e($value->name, 'polls-for-contact-form-7');  ?>" <?php esc_attr_e(($value->name == $name_arr[$i]) ? 'selected="selected"' : ''); ?>>
                                                                                            <?php esc_attr_e($value->name, 'polls-for-contact-form-7');  ?>
                                                                                        </option>
                                                                                        <?php
                                                                                    }
                                                                                }
                                                                            } ?>
                                                                        </select>
                                                                    </td>

                                                                    <td>
                                                                        <button type="button" class="cf7p_remove_field" value="<?php esc_attr_e($form_id); ?>" data-name="<?php esc_attr_e($name_arr[$i]); ?>">
                                                                            <svg width="18" height="18" x="0" y="0" viewBox="0 0 1024 1024">
                                                                                <g>
                                                                                    <path xmlns="http://www.w3.org/2000/svg" d="m724.9 952.2h-423c-22.1 0-40.4-17.1-41.9-39.2l-36.3-539.6c-1.6-24.3 17.6-44.8 41.9-44.8h495.6c24.3 0 43.5 20.6 41.9 44.8l-36.3 539.6c-1.5 22.1-19.8 39.2-41.9 39.2zm119.6-702.3h-657c-.6 0-1-.4-1-1v-114.9c0-.6.4-1 1-1h657c.6 0 1 .4 1 1v114.8c0 .6-.4 1.1-1 1.1z" fill="#000000" data-original="#000000" class=""></path>
                                                                                    <path xmlns="http://www.w3.org/2000/svg" d="m690.9 189.5h-351.1c-.6 0-1-.4-1-1v-130.6c0-.6.4-1 1-1h351.1c.6 0 1 .4 1 1v130.6c0 .5-.4 1-1 1z" fill="#000000" data-original="#000000" class=""></path>
                                                                                </g>
                                                                            </svg>
                                                                        </button>
                                                                    </td>
                                                                </tr>
                                                                <?php
                                                            }
                                                        }
                                                    }
                                                } ?>
                                            </tbody>
                                        </table>
                                        <div class="cf7p_polls_btn">
                                            <button type="button" id="cf7p_add" class="btn btn-success cf7p-loader" data-all-fields="<?php esc_attr_e(implode(",",array_filter($all_fields))) ?>" data-selected_fields="<?php  esc_attr_e($cf7p_name) ?>">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><g id="plus"><line class="cls-1" x1="16" x2="16" y1="7" y2="25"/><line class="cls-1" x1="7" x2="25" y1="16" y2="16"/></g></svg>                                                
                                                <span class="cf7p_btn_text"><?php esc_html_e('Add Poll', 'polls-for-contact-form-7') ?></span>
                                                <span class="loader"></span>
                                            </button>
                                            <button type="button" id="cf7p_remove_all" class="btn" style="<?php esc_html_e(empty($cf7p_name) ? 'display:none;' :''); ?>">
                                                <svg width="18" height="18" x="0" y="0" viewBox="0 0 1024 1024">
                                                        <g>
                                                            <path xmlns="http://www.w3.org/2000/svg" d="m724.9 952.2h-423c-22.1 0-40.4-17.1-41.9-39.2l-36.3-539.6c-1.6-24.3 17.6-44.8 41.9-44.8h495.6c24.3 0 43.5 20.6 41.9 44.8l-36.3 539.6c-1.5 22.1-19.8 39.2-41.9 39.2zm119.6-702.3h-657c-.6 0-1-.4-1-1v-114.9c0-.6.4-1 1-1h657c.6 0 1 .4 1 1v114.8c0 .6-.4 1.1-1 1.1z" fill="#000000" data-original="#000000" class=""></path>
                                                        </g>
                                                    </svg>                                                
                                                <span class="cf7p_btn_text"><?php esc_html_e('Remove All', 'polls-for-contact-form-7') ?></span>
                                            </button>
                                        </div>
                                        <p class="cf7p-note cf7p-poll-note"><?php esc_html_e('Here you can manage polls. Selected field types can only be', 'polls-for-contact-form-7'); ?> <strong><?php esc_html_e('checkboxes', 'polls-for-contact-form-7'); ?></strong>, <strong><?php esc_html_e('radio button', 'polls-for-contact-form-7'); ?></strong> and <strong><?php esc_html_e('dropdown menu', 'polls-for-contact-form-7'); ?></strong></p>
                                    </td>
                                </tr>
                                <tr class="cf7p-row">
                                    <th>
                                        <label for="cf7p-percentage"><?php esc_html_e('Percentage', 'polls-for-contact-form-7'); ?></label>
                                    </th>
                                    <td>
                                        <input type="radio" name="cf7p-percentage" value="1" id="cf7p-percentage-show" <?php esc_attr_e(($cf7p_percentage == '1') ? 'checked' : 'checked' ); ?>><label for="cf7p-percentage-show"><?php esc_html_e('Show', 'polls-for-contact-form-7'); ?></label>
                                        <input type="radio" name="cf7p-percentage" value="0" id="cf7p-percentage-hide" <?php esc_attr_e(($cf7p_percentage == '0') ? 'checked' : '');?>><label for="cf7p-percentage-hide"><?php esc_html_e('Hide', 'polls-for-contact-form-7'); ?></label>
                                        <p class="cf7p-note"><?php esc_html_e('Select for display votes in percentage format.', 'polls-for-contact-form-7'); ?></p>
                                    </td>
                                </tr>
                                <tr class="cf7p-row">
                                    <th>
                                        <label for="cf7p-votes"><?php esc_html_e('Number of Votes', 'polls-for-contact-form-7'); ?></label>
                                    </th>
                                    <td>
                                        <input type="radio" name="cf7p-votes" value="1" id="cf7p-votes-show" <?php esc_attr_e(($cf7p_votes == '1') ? 'checked' : 'checked'); ?>><label for="cf7p-votes-show"><?php esc_html_e('Show', 'polls-for-contact-form-7'); ?></label>
                                        <input type="radio" name="cf7p-votes" value="0" id="cf7p-votes-hide" <?php esc_attr_e(($cf7p_votes == '0') ? 'checked' : ''); ?>><label for="cf7p-votes-hide"><?php esc_html_e('Hide', 'polls-for-contact-form-7'); ?></label>
                                        <p class="cf7p-note"><?php esc_html_e('Select for display number of votes.', 'polls-for-contact-form-7'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        <label for="cf7p-view-result"><?php esc_html_e('View Result Button', 'polls-for-contact-form-7'); ?></label>
                                    </th>
                                    <td>
                                        <input type="checkbox" class="cf7p-view-result" id="cf7p-view-result" name="cf7p-view-result" value="1" <?php esc_attr_e(($cf7p_view_result == 1) ? 'checked="checked"' : ''); ?>><label for="cf7p-view-result"><?php esc_html_e('Enable', 'polls-for-contact-form-7'); ?></label>
                                        <p class="cf7p-note"><?php esc_html_e('Enable to apply view result button.', 'polls-for-contact-form-7'); ?> <a href="javascript:void(0);" class="cf7p-view-poll-result"><?php  esc_html_e("View Result","polls-for-contact-form-7"); ?></a>
                                        </p>
                                    </td>
                                </tr>

                                <tr class="<?php esc_attr_e(($cf7p_view_result != 1) ? 'cf7p-hide-fields' : ''); ?>">
                                    <th></th>
                                    <td class="cf7p_result_btn_shortcode">
                                        <input type="text" onfocus="this.select();" readonly="readonly" value="[<?php esc_html_e('cf7p_view_result', 'polls-for-contact-form-7'); ?>]" class="large-text code shortcode">
                                        <p class="cf7p-note"><?php esc_html_e('Add shortcode in your form to display View Result button.', 'polls-for-contact-form-7'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="cf7p-set-limit"><?php esc_html_e('Limit', 'polls-for-contact-form-7'); ?></label></th>
                                    <td>
                                    <input type="checkbox" class="cf7p-set-limit" id="cf7p-set-limit" name="cf7p-set-limit" value="1" <?php esc_attr_e(($cf7p_set_limit == 1) ? 'checked="checked"' : ''); ?>><label for="cf7p-set-limit"><?php esc_html_e('Enable', 'polls-for-contact-form-7'); ?></label>
                                    </td>
                                </tr>

                                <tr class="<?php esc_attr_e(($cf7p_set_limit != 1) ? 'cf7p-hide-fields' : ''); ?>">
                                    <th></th>
                                    <td class="cf7p_set_limit">
                                    <input type="number" name="cf7p_limit" min="0" value="<?php esc_attr_e($cf7p_limit);  ?>">
                                    <?php 
                                    if (isset($cf7p_limit) && !empty($cf7p_limit)) {
                                        if($wpdb->get_var( "show tables like '$db_table_name'" ) == $db_table_name ){
                                            $results =  $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $db_table_name WHERE form_id = %d", $form_id ) );
                                        }
                                        else{
                                            $results = 0;
                                        }
                                        $results = (!empty($results)) ?count($results) : 0 ;
                                        $cnt=$cf7p_limit-$results;
                                        $cf7p_remaining_vote=(isset($cnt) ? $cnt : $cf7p_limit); ?>
                                        <span class="<?php esc_html_e((empty($cf7p_limit) ? 'cf7p-hide-fields' :'')); esc_html_e($cf7p_remaining_vote<=0 && !empty($option['cf7p_status']) ? 'cf7p-error-msg' : ''); ?>">
                                            <?php  (($cf7p_remaining_vote<=0 && !empty($option['cf7p_status'])) ? _e('<i>Your Limit Is Over. <b style="color:#000">(Note: </b><b style="color:#000;font-weight:inherit">Enable reset polls data option to reset polls.</b><b style="color:#000">)</b></i>') :  esc_html_e('Remaining Vote ') . esc_attr_e($cf7p_remaining_vote)); ?> </span>
                                            
                                        <?php 
                                    } ?>
                                        <p class="cf7p-note"><?php esc_html_e('Set max vote limit for form submission.', 'polls-for-contact-form-7'); ?></p>
                                        <p class="cf7p-note"><?php esc_html_e('Note:', 'polls-for-contact-form-7'); ?> <i><?php esc_html_e('Value must be greater than or equal to 1.','polls-for-contact-form-7'); ?></i></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="cf7p_status"><?php esc_html_e('Limit per Mail', 'polls-for-contact-form-7'); ?></label>
                                    </th>
                                    <td>
                                        <label class="cf7p-switch">
                                            <input type="checkbox" class="cf7p-checkbox cf7p_limit_per_mail" name="cf7p_limit_per_mail" value="on" <?php if (( !empty($cf7p_limit_per_mail) && $cf7p_limit_per_mail == 'on' && !empty($cf7p_email_field))) esc_attr_e('checked'); ?>>
                                            <span class="cf7p-slider"></span>
                                        </label>
                                        <p class="cf7p-note"><?php esc_html_e('Enable to set limit one per Email.', 'polls-for-contact-form-7'); ?></p>
                                    </td>
                                </tr>
                                <tr class="cf7p_mail <?php esc_attr_e(($cf7p_limit_per_mail != 'on') ? 'cf7p-hide-fields' : ''); ?>">
                                    <th>Select Email Field</th>
                                    <td class="cf7p_result_btn_shortcode">
                                    <?php
                                        $ContactForm = WPCF7_ContactForm::get_instance($form_id);
                                        $form_fields = $ContactForm->scan_form_tags();
                                        $found_mail_field = false;
                                        $email_fields = [];
                                        if(isset($form_fields) && $form_fields){
                                            foreach ($form_fields as $key => $value) {
                                                if ($value->basetype == 'email') {
                                                    array_push($email_fields,$value->name);
                                                    $found_mail_field = true;
                                                }
                                            }
                                        }
                                        if ($found_mail_field == true) { ?>
                                            <select name="cf7p_email_field">
                                                <?php
                                                    foreach ($email_fields as $key => $value) { ?>
                                                        <option value="<?php  esc_attr_e($value);  ?>" <?php esc_attr_e(((!empty($cf7p_email_field) && $value == $cf7p_email_field) || count($email_fields)==1) ? 'selected="selected"' : ''); ?>>
                                                            <?php esc_attr_e($value, 'polls-for-contact-form-7');  ?>
                                                        </option>                                                        
                                                    <?php
                                                    }
                                                ?>
                                            </select>
                                            <?php
                                        }
                                        else{ ?>
                                            <p><?php esc_html_e('There is no email field found.','polls-for-contact-form-7'); ?></p>
                                            <?php
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="cf7p_color"><?php esc_html_e('ProgressBar Color', 'polls-for-contact-form-7'); ?></label></th>
                                    <td>
                                        <input type="text" name="cf7p_color" id="cf7p_color" class="cf7p-color-field" value="<?php esc_attr_e($cf7p_color); ?>">
                                        <p class="cf7p-note"><?php esc_html_e('Here you can change ProgressBar color.', 'polls-for-contact-form-7'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="cf7p_backcolor"><?php esc_html_e('ProgressBar Background Color', 'polls-for-contact-form-7'); ?></label></th>
                                    <td>
                                        <input type="text" name="cf7p_backcolor" id="cf7p_backcolor" class="cf7p-color-field" value="<?php esc_attr_e($cf7p_backcolor); ?>">
                                        <p class="cf7p-note"><?php esc_html_e('Here you can change ProgressBar background color.', 'polls-for-contact-form-7'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="cf7p-reset-polls-data"><?php esc_html_e('Reset Polls Data', 'polls-for-contact-form-7'); ?></label></th>
                                    <td>
                                    <input type="checkbox" class="cf7p-reset-polls-data" id="cf7p-reset-polls-data" name="cf7p-reset-polls-data" value="1" ><label for="cf7p-reset-polls-data"><?php esc_html_e('Reset', 'polls-for-contact-form-7'); ?></label>
                                    <p class="cf7p-note"><i><?php esc_html_e('Here you can Reset polls data.', 'polls-for-contact-form-7'); ?></i></p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </fieldset>
                <?php
                } else { ?>
                    <h3><?php _e('Please save your form', 'polls-for-contact-form-7'); ?></h3>
                <?php
                } ?>
                <input type="hidden" name="cf7p-form-id" value="<?php esc_attr_e($form_id, 'polls-for-contact-form-7'); ?>">
                <input type="hidden" name="cf7p-nonce" value="<?php esc_attr_e($cf7p_nonce, 'polls-for-contact-form-7');  ?>">
            </div>
            <?php
        }

        static function polls_result_callback($post){
            $form_id=$post->id(); 
            if ($form_id) {
                $option     =   get_option('cf7p_' . $form_id);
                global $wpdb;
                $db_table_name  = $wpdb->prefix . 'cf7p_options';
                $cf7p_limit     = (isset($option['cf7p_limit']) ) ? $option['cf7p_limit'] : 0;
                $cf7p_status    = (isset($option['cf7p_status']) ) ? $option['cf7p_status'] : '';
                $cf7p_set_limit = (isset($option['cf7p_set_limit']) ) ? $option['cf7p_set_limit'] : 0;

                if ($cf7p_set_limit == 1 && !empty($cf7p_status)) {
                    $results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $db_table_name WHERE form_id = %d", $form_id ) );
                    if(!empty($results))    $cnt = $cf7p_limit - count($results);
                    $cf7p_remaining_vote    =   (isset($cnt) ? $cnt : $cf7p_limit); ?>
                    <div class="contact-form-polls-box" id="cf7p_polls">
                        <table class="form-table">
                            <tr>
                                <th><?php  esc_html_e('Limit','polls-for-contact-form-7'); ?></th>
                                <td class="cf7p_set_limit"><?php esc_attr_e($cf7p_limit);  ?></td>
                            </tr>
                            <tr>
                                <?php
                                    if ($cf7p_remaining_vote<=0) { ?>
                                        <th class="cf7p-error-msg"><?php esc_html_e('Your Limit Is Over','polls-for-contact-form-7'); ?></th>
                                    <?php }
                                    else{ ?>
                                        <th><?php esc_html_e('Remaining Vote','polls-for-contact-form-7'); ?></th>
                                        <td><?php  esc_attr_e($cf7p_remaining_vote); ?></td>
                                    <?php }
                                ?>
                            </tr>
                        </table>
                    </div>
                <?php
                } 
                echo esc_html(do_shortcode('[cf7p id='.$form_id.']'));
            }
            else{ ?>
                <h3><?php _e('Please save your form','polls-for-contact-form-7'); ?></h3>
            <?php }
        }

        static function cf7p_save_settings_call($ContactForm)
        {
            global $wpdb;
            $cf7p_table  = $wpdb->prefix . 'cf7p_options';
            $form_id = $ContactForm->id();
            $cf7p_nonce = isset($_POST['cf7p-nonce']) ? sanitize_text_field($_POST['cf7p-nonce']) : '';

            $titles = isset( $_POST['cf7p-title'] ) ? array_map( 'sanitize_text_field', $_POST['cf7p-title'] ) : array();
            $names  = isset( $_POST['cf7p-names'] ) ? array_map( 'sanitize_text_field', $_POST['cf7p-names'] ) : array();
            $mail     =   (isset($_POST['cf7p_email_field']) && !empty($_POST['cf7p_email_field']) && !empty($_POST['cf7p_limit_per_mail'])) ? sanitize_text_field($_POST['cf7p_email_field']) : '';
            $enable_mail_limit = ((isset($_POST['cf7p_limit_per_mail'])) && !empty($_POST['cf7p_email_field']) ) ? sanitize_text_field($_POST['cf7p_limit_per_mail']) : '';
            $cf7p_status = isset($_POST['cf7p_status']) ? sanitize_text_field($_POST['cf7p_status']) : '';
            $ContactForm = WPCF7_ContactForm::get_instance($form_id);
            $form_fields = $ContactForm->scan_form_tags();
            $input_type  = array('select', 'checkbox', 'radio');

            $arr = $cf7p_names = $cf7p_titles =  $email_fields = $fields = [];
            if (isset($names)) {
                foreach ($names as $key => $value) {
                    $arr[$value]=$titles[$key];
                }
            }

            
            if(isset($form_fields) && $form_fields){
                foreach ($form_fields as $key => $value) {
                    // Remove names from option 
                    if (in_array($value->basetype, $input_type)){
                        array_push($fields,$value->name);
                        if(in_array($value->name,$names)){ 
                            $cf7p_names[] = $value->name;
                        }
                    }
                    // Get All Email Fields
                    if ($value->basetype == 'email') {
                        array_push($email_fields,$value->name);
                        $found_mail_field = true;
                    }
                }
            }
            
            if(isset($names) && $names){
                foreach ($names as $key => $value) {
                    if(!in_array($value,$fields)){ 
                        if (($key = array_search($value, $names)) !== false) {
                            unset($names[$key]);
                        }
                    }
                }

            }
            // die;
            // Reset All Polls Data
            if(isset($_POST['cf7p-reset-polls-data'])) {
                $wpdb->query( $wpdb->prepare( "DELETE FROM $cf7p_table WHERE form_id = %d", $form_id ) );
            }
            // Remove Mail Field From Option
            if (isset($_POST['cf7p_email_field'])) {
                if (!in_array($_POST['cf7p_email_field'],$email_fields) && !empty($enable_mail_limit)) {
                    $mail = $email_fields[0];
                    $enable_mail_limit = '';
                }
            }

            $results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $cf7p_table WHERE form_id = %d", $form_id ) );
            // Delete data from table when form field remove
            if (isset($results)) {
                foreach ($results as $key => $value) {
                    $row_id = $value->id;
                    $get_data = unserialize($value->inputs);
                    if (isset($get_data)) {
                        foreach ($get_data as $data_key => $data_value) {
                            if (!in_array($data_key,$cf7p_names)) {
                                unset($get_data[$data_key]);
                            }
                        }
                    }
                    $serialize_data =   serialize($get_data);     
                    $wpdb->query( $wpdb->prepare( "UPDATE $cf7p_table SET inputs = %s WHERE form_id = %d AND id = %d", $serialize_data, $form_id, $row_id ) );
                }
            }
            // Remove title from option when form field remove
            if (isset($arr) && !empty($arr)) {
                foreach($arr as $key => $val){
                    if(in_array($key,$cf7p_names)){
                        $cf7p_titles[] = $val;
                    }
                }
            }
            if(isset($cf7p_titles)) $cf7p_titles = implode(",",$cf7p_titles);
            if(isset($cf7p_names))  $cf7p_names = implode(",",array_unique($names));
            
            // Remove all data from table when no any relavent field found
            if(empty($cf7p_names)){
                $cf7p_status='';
                $wpdb->query( $wpdb->prepare( "DELETE FROM $cf7p_table WHERE form_id = %d", $form_id ) );
            }

            $cf7p['form_id']            =   $form_id;
            $cf7p['cf7p_name']          =   $cf7p_names;
            $cf7p['cf7p_title']         =   $cf7p_titles;
            $cf7p['cf7p_status']        =   sanitize_text_field($cf7p_status);
            $cf7p['cf7p_percentage']    =   isset($_POST['cf7p-percentage']) ? sanitize_text_field($_POST['cf7p-percentage']) : '';
            $cf7p['cf7p_votes']         =   isset($_POST['cf7p-votes']) ? sanitize_text_field($_POST['cf7p-votes']) : '';
            $cf7p['cf7p_view_result']   =   isset($_POST['cf7p-view-result']) ? sanitize_text_field($_POST['cf7p-view-result']) : '';
            $cf7p['cf7p_set_limit']     =   isset($_POST['cf7p-set-limit']) ? sanitize_text_field($_POST['cf7p-set-limit']) : '';
            $cf7p['cf7p_limit']         =   (isset($_POST['cf7p-set-limit']) && !empty($_POST['cf7p-set-limit']) && $_POST['cf7p_limit'] > 0) ? sanitize_text_field($_POST['cf7p_limit']): 1;
            $cf7p['cf7p_limit_per_mail']     =  sanitize_text_field($enable_mail_limit);
            $cf7p['cf7p_email_field']   = sanitize_text_field($mail);
            $cf7p['cf7p_color']         =   (isset($_POST['cf7p_color']) && !empty($_POST['cf7p_color'])) ? sanitize_text_field($_POST['cf7p_color']) : '#2196F3';
            $cf7p['cf7p_backcolor']     =   (isset($_POST['cf7p_backcolor']) && !empty($_POST['cf7p_backcolor'])) ? sanitize_text_field($_POST['cf7p_backcolor']) : '#2196f343';
            
            if (wp_verify_nonce($cf7p_nonce, 'cf7p_option_nonce')) {
                update_option('cf7p_' . $form_id, $cf7p);
            }
        }
    }
    new cf7p_settings();
}