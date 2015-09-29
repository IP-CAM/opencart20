<?php

/**
 * Class ModelSmart2payHelper
 *
 * This is actually a helper class, not a model
 * 
 * @property DB $db
 * @property Config $config
 * @property Loader $load
 * @property Url $url
 * @property Log $log
 * @property Request $request
 * @property Response $response
 * @property Session $session
 * @property Language $language
 * @property Document $document
 * @property Customer $customer
 * @property Currency $currency
 * @property Cart $cart
 * @property Event $event
 * @property User $user
 * @property ModelSettingSetting $model_setting_setting
 * @property ModelLocalisationOrderStatus $model_localisation_order_status
 * @property ModelExtensionExtension $model_extension_extension
 * @property ModelSmart2payPaymentExtension $model_smart2pay_payment_extension
 */
class ModelSmart2payHelper extends Model
{
    const MODULE_VERSION = '1.0.6';

    const ENV_DEMO = 1, ENV_TEST = 2, ENV_LIVE = 3;
    const S2P_STATUS_OPEN = 1, S2P_STATUS_SUCCESS = 2, S2P_STATUS_CANCELLED = 3, S2P_STATUS_FAILED = 4, S2P_STATUS_EXPIRED = 5, S2P_STATUS_PROCESSING = 7;
    const PAYMENT_METHOD_BT = 1, PAYMENT_METHOD_SIBS = 20;
    const CONFIRM_ORDER_PAID = 0, CONFIRM_ORDER_FINAL_STATUS = 1, CONFIRM_ORDER_REDIRECT = 2, CONFIRM_ORDER_INITIATE = 3;

    private static $last_instance = false;

    public function __construct( $registry )
    {
        parent::__construct( $registry );

        self::$last_instance = $this;
    }

    static function get_last_instance()
    {
        return self::$last_instance;
    }

    static function valid_environment( $env )
    {
        $env = intval( $env );
        if( !in_array( $env, array( self::ENV_DEMO, self::ENV_TEST, self::ENV_LIVE ) ) )
            return false;

        return true;
    }
    /**
     * Get logs
     *
     * @return array
     */
    public function get_logs()
    {
        if( !($query = $this->db->query( 'SELECT * FROM ' . DB_PREFIX . 'smart2pay_log ORDER BY log_created DESC' )) )
            return array();

        $logs = array();
        foreach( $query->rows as $method )
            $logs[] = $method;

        return $logs;
    }

    /**
     * Get all method settings in a single array
     *
     * @return array
     */
    public function get_all_method_settings( $params = false )
    {
        static $all_methods_settings = array();

        if( empty( $params ) or !is_array( $params ) )
            $params = array();

        if( !isset( $params['skip_cache'] ) )
            $params['skip_cache'] = false;

        if( empty( $params['skip_cache'] )
        and !empty( $all_methods_settings ) )
            return $all_methods_settings;

        $this->load->model( 'extension/extension' );
        $this->load->model( 'smart2pay/helper' );

        $installed_extensions = $this->model_extension_extension->getInstalled( 'payment' );

        $sql_str = 'SELECT '.DB_PREFIX.'smart2pay_method.*, '.
                   ' '.DB_PREFIX.'smart2pay_country.country_id AS country_id, '.DB_PREFIX.'smart2pay_country.code AS country_code, '.DB_PREFIX.'smart2pay_country.name AS country_name, '.
                   ' '.DB_PREFIX.'smart2pay_method_files.file_slug AS file_slug '.
                   ' FROM '.DB_PREFIX.'smart2pay_method '.
                   ' LEFT JOIN '.DB_PREFIX.'smart2pay_method_files ON '.DB_PREFIX.'smart2pay_method.method_id = '.DB_PREFIX.'smart2pay_method_files.method_id '.
                   ' LEFT JOIN '.DB_PREFIX.'smart2pay_country_method ON '.DB_PREFIX.'smart2pay_method.method_id = '.DB_PREFIX.'smart2pay_country_method.method_id '.
                   ' LEFT JOIN '.DB_PREFIX.'smart2pay_country ON '.DB_PREFIX.'smart2pay_country.country_id = '.DB_PREFIX.'smart2pay_country_method.country_id '.
                   ' ORDER BY '.DB_PREFIX.'smart2pay_method.display_name ASC, '.DB_PREFIX.'smart2pay_country.name ASC';

        if( !($query = $this->db->query( $sql_str ))
            or !is_object( $query ) or empty( $query->rows ) )
            return array();

        $methods = array();
        $methods['file_slug_to_id'] = array();
        foreach( $query->rows as $method_arr )
        {
            if( empty( $method_arr['method_id'] ) or empty( $method_arr['file_slug'] ) )
                continue;

            if( empty( $methods[$method_arr['method_id']] ) )
            {
                $module_file = DIR_APPLICATION . 'controller/payment/smart2pay_' . $method_arr['file_slug'] . '.php';

                if( !@file_exists( $module_file ) )
                    continue;

                $simple_method_arr = $method_arr;
                if( array_key_exists( 'country_id', $simple_method_arr ) )
                    unset( $simple_method_arr['country_id'] );
                if( array_key_exists( 'country_code', $simple_method_arr ) )
                    unset( $simple_method_arr['country_code'] );
                if( array_key_exists( 'country_name', $simple_method_arr ) )
                    unset( $simple_method_arr['country_name'] );
                if( array_key_exists( 'file_slug', $simple_method_arr ) )
                    unset( $simple_method_arr['file_slug'] );

                $methods[$method_arr['method_id']]['file_slug'] = 'smart2pay_'.$method_arr['file_slug'];
                $methods[$method_arr['method_id']]['installed'] = in_array( $methods[$method_arr['method_id']]['file_slug'], $installed_extensions );
                $methods[$method_arr['method_id']]['db_details'] = $simple_method_arr;
                $methods[$method_arr['method_id']]['settings'] = $this->model_smart2pay_helper->get_module_settings( $method_arr['provider_value'] );
                $methods[$method_arr['method_id']]['countries'] = array();

                $methods['file_slug_to_id'][$method_arr['file_slug']] = $method_arr['method_id'];
            }

            if( !empty( $method_arr['country_id'] ) )
            {
                $methods[$method_arr['method_id']]['countries'][$method_arr['country_id']] = array(
                    'code' => $method_arr['country_code'],
                    'name' => $method_arr['country_name'],
                );
            }
        }

        $all_methods_settings = $methods;

        return $methods;
    }

    public function get_method_language_array( $params = false )
    {
        if( empty( $params ) or !is_array( $params ) )
            $params = array();

        if( empty( $params['method_id'] ) )
            $params['method_id'] = 0;
        else
            $params['method_id'] = intval( $params['method_id'] );
        if( empty( $params['file_slug'] ) )
            $params['file_slug'] = '';
        else
            $params['file_slug'] = trim( $params['file_slug'] );

        $all_method_settings = $this->get_all_method_settings();

        if( empty( $params['method_id'] ) and empty( $params['file_slug'] ) )
            return array();

        $method_details = false;
        if( !empty( $params['method_id'] )
        and !empty( $all_method_settings[$params['method_id']] ) )
            $method_details = $all_method_settings[$params['method_id']];
        if( !empty( $params['file_slug'] )
        and !empty( $all_method_settings['file_slug_to_id'][$params['file_slug']] )
        and !empty( $all_method_settings[$all_method_settings['file_slug_to_id'][$params['file_slug']]] ) )
            $method_details = $all_method_settings[$all_method_settings['file_slug_to_id'][$params['file_slug']]];

        if( empty( $method_details ) )
            return array();

        $lang_arr = array();
        $lang_arr['heading_title'] = 'Smart2Pay '.$method_details['db_details']['display_name'];
        $lang_arr['text_'.$method_details['file_slug']] = '<a href="http://www.smart2pay.com" target="_blank"><img style="border: 1px solid #EEEEEE; padding:1px; max-height: 38px;" alt="'.$lang_arr['heading_title'].'" title="'.$lang_arr['heading_title'].'" src="view/image/payment/smart2pay/methods/'.$method_details['db_details']['logo_url'].'" /></a>';

        return $lang_arr;
    }

    public function get_countries_for_method( $method_id )
    {
        $method_id = intval( $method_id );
        if( empty( $method_id ) )
            return array();

        if( !($query = $this->db->query( 'SELECT '.DB_PREFIX.'smart2pay_country.* FROM '.DB_PREFIX.'smart2pay_country_method '.
                                         ' LEFT JOIN '.DB_PREFIX.'smart2pay_country ON '.DB_PREFIX.'smart2pay_country.country_id = '.DB_PREFIX.'smart2pay_country_method.country_id '.
                                         ' WHERE '.DB_PREFIX.'smart2pay_country_method.method_id = \''.$method_id.'\' '.
                                         ' ORDER BY '.DB_PREFIX.'smart2pay_country.name' ))
            or !is_object( $query ) or empty( $query->rows ) )
            return array();

        $return_arr = array();
        foreach( $query->rows as $country_arr )
            $return_arr[$country_arr['country_id']] = $country_arr;

        return $return_arr;

    }

    public function get_module_settings( $module_name = '' )
    {
        $this->load->model( 'setting/setting' );

        return $this->model_setting_setting->getSetting( 'smart2pay' . ($module_name!=''?'_':'').$module_name );
    }

    public function save_module_settings( $settings_arr, $module_name = '' )
    {
        // If accessing in front-end we don't have localisation/order_status model
        if( !defined( 'DIR_CATALOG' ) )
            return false;

        $this->load->model( 'smart2pay/payment_extension' );
        $this->load->model( 'setting/setting' );

        if( !($saved_settings = $this->get_module_settings( $module_name )) )
            $saved_settings = array();

        $new_settings = array_merge( $saved_settings, $settings_arr );

        if( $module_name == '' )
        {
            if( ($new_settings_arr = $this->check_for_updates( $new_settings )) )
                $new_settings = $new_settings_arr;
        }

        $this->model_setting_setting->editSetting( 'smart2pay' . ($module_name!=''?'_':'').$module_name, $new_settings );

        return true;
    }

    public function check_for_updates( $settings_arr = false )
    {
        $this->load->model( 'smart2pay/payment_extension' );

        if( $settings_arr === false )
            $settings_arr = $this->get_module_settings();

        if( empty( $settings_arr['smart2pay_db_version'] ) )
            $settings_arr['smart2pay_db_version'] = '1.0.0';

        if( version_compare( $settings_arr['smart2pay_db_version'], self::MODULE_VERSION, '<' ) )
        {
            if( $this->model_smart2pay_payment_extension->update( $settings_arr['smart2pay_db_version'] ) )
                $settings_arr['smart2pay_db_version'] = self::MODULE_VERSION;
        }

        return $settings_arr;
    }

    public function save_methods_settings( $methods_settings_arr )
    {
        // If accessing in front-end we don't have localisation/order_status model
        if( !defined( 'DIR_CATALOG' )
         or empty( $methods_settings_arr ) or !is_array( $methods_settings_arr ) )
            return false;

        foreach( $methods_settings_arr as $method_name => $method_settings )
        {
            if( !$this->save_module_settings( $method_settings ) )
                $saved_settings = array();
        }

        return true;
    }

    /**
     * Returns default keys for a field array that is to be displayed in settings form
     * @return array
     */
    protected function default_field_values()
    {
        return array(
            'label'   => '',
            'type'    => '',
            'options' => array(),
            'value' => '',
            'required' => false,
            'multiple' => false,
            'extra_css' => '',
        );
    }

    /**
     * @param array $fields_arr Array of settings fields to be completed with all keys from default_field_values() method
     *
     * @return array|false Validated fields array
     */
    public function validate_settings_fields( $fields_arr, $module_name = '' )
    {
        if( empty( $fields_arr ) or !is_array( $fields_arr ) )
            return false;

        $key_prefix = 'smart2pay'.($module_name!=''?'_':'').$module_name.'_';
        $key_prefix_len = strlen( $key_prefix );

        $default_field_values = $this->default_field_values();
        $new_fields_arr = array();
        foreach( $fields_arr as $key => $field_arr )
        {
            if( empty( $field_arr ) or !is_array( $field_arr ) )
                continue;

            foreach( $default_field_values as $prop_key => $prop_value )
            {
                if( !array_key_exists( $prop_key, $field_arr ) )
                    $field_arr[$prop_key] = $prop_value;
            }

            if( substr( $key, 0, $key_prefix_len ) != $key_prefix )
                $key = $key_prefix.$key;

            $new_fields_arr[$key] = $field_arr;
        }

        return $new_fields_arr;
    }

    function render_module_fields( $elements, $errors )
    {
        if( empty( $elements ) or !is_array( $elements ) )
            return '';

        ob_start();
        foreach( $elements as $name => $element )
        {
            ?>
            <div class="form-group <?php echo ( !empty( $element['required'] ) ? 'required' : '' )?>">
                <label class="col-sm-2 control-label" for=""><?php echo $element['label']?></label>
                <div class="col-sm-10"><?php

                        switch( $element['type'] )
                        {
                            case 'text':
                                ?><input class="form-control" style="width: 300px;" type="text" name="<?php echo $name?>" value="<?php echo $element['value']?>" /><?php
                                break;

                            case 'textarea':
                                ?><textarea class="form-control" style="width: 300px;" name="<?php echo $name?>"><?php echo $element['value']?></textarea><?php
                                break;

                            case 'select':
                                ?><select class="form-control" <?php ( !empty( $element['multiple'] )? 'multiple' : '')?> name="<?php echo $name?>"><?php

                                if( !empty( $element['options'] ) and is_array( $element['options'] ) )
                                {
                                    foreach( $element['options'] as $key => $label )
                                    {
                                        ?><option <?php echo (in_array( $key, (array)$element['value'] ) ? 'selected="selected"' : '' )?> value="<?php echo $key?>"><?php echo $label?></option><?php
                                    }
                                }
                                ?></select><?php
                                break;

                            case 'checkbox':
                                foreach( $element['options'] as $key => $label )
                                {
                                    ?>
                                    <input id="<?php echo $name.$key?>" type="checkbox" value="<?php echo $key?>" name="<?php echo $name.(count($element['options']) > 1 ? '[]' : '')?>" <?php echo (in_array($key, (array) $element['value']) ? 'checked="checked"' : '')?>">
                                    <label for="<?php echo $name.$key?>"><?php echo $label?></label>
                                    <?php
                                }
                                break;
                        }

                        if( isset( $errors[$name] ) )
                        {
                            ?><div class="text-danger"><?php echo $errors[$name]?></div><?php
                        }

                    ?></div>
            </div>
            <?php
        }
        $buf = ob_get_clean();

        return $buf;
    }

    public function render_logs( $logs )
    {
        ob_start();
        if( empty( $logs ) or !is_array( $logs ) )
            echo "There are no logs, yet.";

        else
        {
            usort( $logs, function( $a, $b )
            {
                if( $a['log_id'] == $b['log_id'] )
                    return 0;

                return ($a['log_id'] < $b['log_id'] ? 1 : -1);
            });

            foreach( $logs as $log )
            {
                echo $log['log_created'].' ['.str_pad( $log['log_type'], 15, ' ', STR_PAD_LEFT ) . ']' . ' ' . $log['log_data'] . "\r\n";
            }
        }
        $buf = ob_get_clean();

        return $buf;
    }

    public function render_main_plugin_tabs( $params = false )
    {
        if( empty( $params ) or !is_array( $params ) )
            $params = array();

        if( empty( $params['current_tab'] ) )
            $params['current_tab'] = 'module_settings';

        $tabs_arr = array(
            'module_settings' => array(
                'title' => 'Module Settings',
                'link' => $this->url->link( 'payment/smart2pay', 'token=' . $this->session->data['token'], 'SSL' ),
            ),
            'payment_methods' => array(
                'title' => 'Payment Methods',
                'link' => $this->url->link( 'payment/smart2pay/view_payment_methods', 'token=' . $this->session->data['token'], 'SSL' ),
            ),
            'logs' => array(
                'title' => 'View Logs',
                'link' => $this->url->link( 'payment/smart2pay/view_logs', 'token=' . $this->session->data['token'], 'SSL' ),
            ),
        );

        ob_start();
        ?><div style="padding: 10px;"><?php
        $first_tab = true;
        foreach( $tabs_arr as $tab_name => $tab_arr )
        {
            if( empty( $first_tab ) )
                echo ' | ';

            $title_str = $tab_arr['title'];
            if( $tab_name == $params['current_tab'] )
                $title_str = '<strong>'.$title_str.'</strong>';

            ?><a href="<?php echo $tab_arr['link']?>"><?php echo $title_str?></a><?php

            $first_tab = false;
        }
        ?></div><?php

        $buf = ob_get_clean();

        return $buf;
    }


        /**
     * Get module settings
     *
     * @return array
     */
    public function get_main_module_fields()
    {
        // If accessing in front-end we don't have localisation/order_status model
        if( !defined( 'DIR_CATALOG' ) )
            return array();

        if( !isset( $this->request->server['HTTPS'] )
         or $this->request->server['HTTPS'] != 'on' )
            $server_base = HTTP_CATALOG;
        else
            $server_base = HTTPS_CATALOG;

        $this->load->model( 'smart2pay/helper' );
        $this->load->model( 'localisation/order_status' );

        $moduleSettings = array(
            'smart2pay_status' =>
                array(
                    'label'   => 'Enabled',
                    'type'    => 'select',
                    'options' =>
                        array(
                            0 => 'No',
                            1 => 'Yes'
                        ),
                    'value' => 0
                ),
            'smart2pay_env' =>
                array(
                    'label'     => 'Environment',
                    'type'      => 'select',
                    'options'   =>
                        array(
                            self::ENV_DEMO => 'Demo',
                            self::ENV_TEST => 'Test',
                            self::ENV_LIVE => 'Live'
                        ),
                    'value' => 0
                ),
            'smart2pay_post_url_live' =>
                array(
                    'label' => 'Post URL Live',
                    'type'  => 'text',
                    'value' => 'https://api.smart2pay.com'
                ),
            'smart2pay_post_url_test' =>
                array(
                    'label' => 'Post URL Test',
                    'type'  => 'text',
                    'value' => 'https://apitest.smart2pay.com'
                ),
            'smart2pay_signature_live' =>
                array(
                    'label' => 'Signature Live',
                    'type'  => 'text',
                    'value' => '',
                ),
            'smart2pay_signature_test' =>
                array(
                    'label' => 'Signature Test',
                    'type'  => 'text',
                    'value' => '',
                ),
            'smart2pay_mid_live' =>
                array(
                    'label' => 'MID Live',
                    'type'  => 'text',
                    'value' => '',
                ),
            'smart2pay_mid_test' =>
                array(
                    'label' => 'MID Test',
                    'type'  => 'text',
                    'value' => '',
                ),
            'smart2pay_site_id' =>
                array(
                    'label' => 'Site ID',
                    'type'  => 'text',
                    'value' => '',
                ),
            'smart2pay_skin_id' =>
                array(
                    'label' => 'Skin ID',
                    'type'  => 'text',
                    'value' => '',
                ),
            'smart2pay_return_url' =>
                array(
                    'label' => 'Return URL',
                    'type'  => 'text',
                    'value' => $server_base . 'index.php?route=payment/smart2pay/feedback'
                ),
            'smart2pay_send_order_number_as_product_description' =>
                array(
                    'label'   => 'Send order number as product description',
                    'type'    => 'select',
                    'options' =>
                        array(
                            0 => 'No',
                            1 => 'Yes'
                        ),
                    'value' => 0
                ),
            'smart2pay_custom_product_description' =>
                array(
                    'label' => 'Custom product description',
                    'type'  => 'textarea',
                    'value' => null
                ),
            'smart2pay_notify_customer_by_email' =>
                array(
                    'label'   => 'Notify customer by email',
                    'type'    => 'select',
                    'options' =>
                        array(
                            0 => 'No',
                            1 => 'Yes'
                        ),
                    'value' => 0
                ),
            //'smart2pay_create_invoice_on_success' =>
            //    array(
            //        'label'   => 'Create invoice on success',
            //        'type'    => 'select',
            //        'options' =>
            //            array(
            //                0 => 'No',
            //                1 => 'Yes'
            //            ),
            //        'value' => 0
            //    ),
            //'smart2pay_automate_shipping' =>
            //    array(
            //        'label'   => 'Automate shipping',
            //        'type'    => 'select',
            //        'options' =>
            //            array(
            //                0 => 'No',
            //                1 => 'Yes'
            //            ),
            //        'value' => 0
            //    ),
            'smart2pay_order_confirm' =>
                array(
                    'label'   => 'Confirm order',
                    'type'    => 'select',
                    'options' =>
                        array(
                            self::CONFIRM_ORDER_PAID => 'Only when paid',
                            self::CONFIRM_ORDER_FINAL_STATUS => 'On final status',
                            self::CONFIRM_ORDER_REDIRECT => 'On redirect',
                            self::CONFIRM_ORDER_INITIATE => 'On initiate',
                        ),
                    'value' => self::CONFIRM_ORDER_PAID,
                ),
            'smart2pay_order_status_new' =>
                array(
                    'label'   => 'Order status when NEW',
                    'type'    => 'select',
                    'options' =>
                        array(
                            0 => 'Status 1',
                            1 => 'Status 2'
                        ),
                    'value' => 1
                ),
            'smart2pay_order_status_success' =>
                array(
                    'label'   => 'Order status when SUCCESS',
                    'type'    => 'select',
                    'options' =>
                        array(
                            0 => 'Status 1',
                            1 => 'Status 2'
                        ),
                    'value' => 1
                ),
            'smart2pay_order_status_canceled' =>
                array(
                    'label'   => 'Order status when CANCEL',
                    'type'    => 'select',
                    'options' =>
                        array(
                            0 => 'Status 1',
                            1 => 'Status 2'
                        ),
                    'value' => 1
                ),
            'smart2pay_order_status_failed' =>
                array(
                    'label'   => 'Order status when FAIL',
                    'type'    => 'select',
                    'options' =>
                        array(
                            0 => 'Status 1',
                            1 => 'Status 2'
                        ),
                    'value' => 1
                ),
            'smart2pay_order_status_expired' =>
                array(
                    'label'   => 'Order status on EXPIRED',
                    'type'    => 'select',
                    'options' =>
                        array(
                            0 => 'Status 1',
                            1 => 'Status 2'
                        ),
                    'value' => 1
                ),
            'smart2pay_skip_payment_page' =>
                array(
                    'label'   => 'Skip Payment Page',
                    'type'    => 'select',
                    'options' =>
                        array(
                            0 => 'No',
                            1 => 'Yes'
                        ),
                    'value' => 0
                ),
            'smart2pay_redirect_in_iframe' =>
                array(
                    'label'   => 'Redirect In IFrame',
                    'type'    => 'select',
                    'options' =>
                        array(
                            0 => 'No',
                            1 => 'Yes'
                        ),
                    'value' => 0
                ),
           'smart2pay_debug_form' =>
                array(
                    'label'   => '[Debug Form]',
                    'type'    => 'select',
                    'options' =>
                        array(
                            0 => 'No',
                            1 => 'Yes'
                        ),
                    'value' => 0
                ),
            'smart2pay_sort_order' =>
                array(
                    'label'   => 'Sort Order',
                    'type'    => 'text',
                    'value'   => ''
                ),
        );

        /*
         * Get order statuses
         */
        $orderStatuses = $this->model_localisation_order_status->getOrderStatuses();
        $orderStatusesIndexed = array();
        foreach( $orderStatuses as $status )
            $orderStatusesIndexed[$status['order_status_id']] = $status['name'];

        $moduleSettings['smart2pay_order_status_new']['options']     = $orderStatusesIndexed;
        $moduleSettings['smart2pay_order_status_success']['options'] = $orderStatusesIndexed;
        $moduleSettings['smart2pay_order_status_canceled']['options']  = $orderStatusesIndexed;
        $moduleSettings['smart2pay_order_status_failed']['options']    = $orderStatusesIndexed;
        $moduleSettings['smart2pay_order_status_expired']['options'] = $orderStatusesIndexed;

        if( !($moduleSettings = $this->validate_settings_fields( $moduleSettings, '' )) )
            return array();

        return $moduleSettings;
    }

    /**
     * Get logs
     *
     * @return array
     */
    public function getLogs()
    {
        $logs = array();

        $query = $this->db->query( 'SELECT * FROM ' . DB_PREFIX . 'smart2pay_log ORDER BY log_created DESC' );

        foreach( $query->rows as $method )
            $logs[] = $method;

        return $logs;
    }
}
