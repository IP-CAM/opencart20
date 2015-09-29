<?php

/**
 * Class ControllerPaymentSmart2payAbstract
 *
 * This class is actually an abstract controller (which should not be instantiated normally with $this->load->controller())
 * The purpose of it being in model folder is that we don't want to OpenCart to show it in payment modules list
 * This should "manually" be included in each payment method
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
 * @property ModelSmart2payHelper $model_smart2pay_helper
 */
abstract class ControllerPaymentSmart2payAbstract extends Controller
{
    protected $error = array();

    protected $method_name = '';

    /**
     * Returns ID of method
     * @return int
     */
    abstract public function get_method_id();

    /**
     * Returns a lowercase filename of payment method without smart2pay_ prefix and file extension
     * eg. onlinebankingthailand for smart2pay_onlinebankingthailand.php file
     * @return string
     */
    abstract public function get_method_short_name();

    /**
     * Returns user friendly method name
     * @return string
     */
    abstract public function get_method_name();

    /**
     * When saving payment method settings, this method will do any special validations.
     * Method will set errors to $this->error array
     *
     * @param array $settings_arr Array with all values to be validated (from post or other source)
     * @param array $validated_arr Array with common keys already validated by abstract class
     *
     * @return array|false Returns final validated array with settings or false on any error
     */
    abstract public function method_validate( $settings_arr, $validated_arr );

    /**
     * If any special fields are required for this payment method, this method will return array of fields. If no special fields return false.
     * @return array|false
     */
    abstract public function method_settings();

    /**
     * Main entry point (renders payment method settings)
     * @param array|false $data Data array if we want to override something in child class
     */
    public function index( $data = false )
    {
        $this->load->language( 'payment/smart2pay' );
        $this->load->model( 'smart2pay/helper' );

        if( $data === false or !is_array( $data ) )
            $data = array();

        if( !($method_name = $this->get_method_name())
         or !($method_short_name = $this->get_method_short_name()))
        {
            $this->response->redirect( $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL' ) );
            exit;
        }

        if( empty( $data['error'] ) )
            $data['error'] = array();

        if( empty( $data['heading_title'] ) )
            $data['heading_title'] = $this->language->get( 'heading_title');

        if( empty( $data['text_edit'] ) )
            $data['text_edit'] = sprintf( $this->language->get( 'text_edit' ), ModelSmart2payHelper::MODULE_VERSION ) . ' (' . $method_name . ')';

        if( empty( $data['btn_text_save'] ) )
            $data['btn_text_save'] = $this->language->get('btn_text_save');

        if( empty( $data['btn_text_cancel'] ) )
            $data['btn_text_cancel'] = $this->language->get('btn_text_cancel');

        $data['go_to_payment_methods_tab'] = $this->language->get('text_go_to_payment_methods');
        $data['go_to_payment_methods_link'] = $this->url->link( 'payment/smart2pay/view_payment_methods', 'token=' . $this->session->data['token'], 'SSL');

        $this->document->setTitle( $data['text_edit'] );

        /*
         * Save POST data if valid
         */
        if( $this->request->server['REQUEST_METHOD'] == 'POST'
        and ($posted_values = $this->validate_settings( $this->request->post )) )
        {
            if( $this->save_settings( $posted_values ) )
                $this->session->data['success'] = 'Success: You have modified Smart2Pay ' . $method_name . ' settings!';

            $this->response->redirect($this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL'));
        }

        /*
         * Set form elements
         */
        $form_elements = $this->get_settings_fields();
        $saved_settings = $this->get_settings();

        if( !empty( $saved_settings ) and is_array( $saved_settings ) )
        {
            foreach( $saved_settings as $key => $val )
            {
                if( isset( $form_elements[$key] ) )
                    $form_elements[$key]['value'] = $val;
            }
        }

        $data['form_elements'] = $form_elements;

        /*
         * Set links
         */
        $data['cancel'] = $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL');
        $data['action'] = $this->url->link('payment/smart2pay_' . $method_short_name, 'token=' . $this->session->data['token'], 'SSL');

        /*
         * Set validation errors and warnings
         */
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        /*
         * Set breadcrumbs
         */
        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text'      => $this->language->get('text_home'),
            'href'      => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
            'separator' => false
        );

        $data['breadcrumbs'][] = array(
            'text'      => $this->language->get('text_payment'),
            'href'      => $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL'),
            'separator' => ' :: '
        );

        $data['breadcrumbs'][] = array(
            'text'      => $this->language->get('heading_title'),
            'href'      => $this->url->link('payment/smart2pay', 'token=' . $this->session->data['token'], 'SSL'),
            'separator' => ' :: '
        );

        $data['breadcrumbs'][] = array(
            'text'      => $method_name,
            'href'      => 'javascript:void(0);',
            'separator' => ' :: '
        );

        $data['header'] = $this->load->controller( 'common/header' );
        $data['column_left'] = $this->load->controller( 'common/column_left' );
        $data['footer'] = $this->load->controller( 'common/footer' );

        /*
         * Render
         */
        $data['error'] = $this->error;

        $this->response->setOutput( $this->load->view( 'smart2pay/smart2pay_payment_method.tpl', $data ) );
	}

    public function get_settings_fields()
    {
        if( !($method_short_name = $this->get_method_short_name()) )
            return array();

        $this->load->model( 'smart2pay/helper' );

        if( !($particular_fields = $this->method_settings())
         or !is_array( $particular_fields ) )
            $particular_fields = array();

        $common_fields = array(
            'status' => array(
                'label'   => 'Enabled',
                'type'    => 'select',
                'options' =>
                    array(
                        0 => 'No',
                        1 => 'Yes'
                    ),
                'value' => 0,
            ),
        );

        $final_fields = array_merge( $common_fields, $particular_fields );

        if( !($final_fields = $this->model_smart2pay_helper->validate_settings_fields( $final_fields, $method_short_name )) )
            return array();

        return $final_fields;
    }

    public function get_settings()
    {
        if( !($method_short_name = $this->get_method_short_name()) )
            return false;

        $this->load->model( 'smart2pay/helper' );

        return $this->model_smart2pay_helper->get_module_settings( $method_short_name );
    }

    protected function save_settings( $settings_arr )
    {
        if( !($method_short_name = $this->get_method_short_name()) )
            return false;

        $this->load->model( 'smart2pay/helper' );

        $this->model_smart2pay_helper->save_module_settings( $settings_arr, $method_short_name );

        return true;
    }

    /**
     * Validate post data
     *
     * @return bool
     */
    private function validate_settings( $settings_arr )
    {
        if( empty( $settings_arr ) or !is_array( $settings_arr )
         or !($method_short_name = $this->get_method_short_name())
         or !($settings_fields = $this->get_settings_fields()) )
            return false;

		if( !$this->user->hasPermission( 'modify', 'payment/smart2pay_' . $method_short_name ) )
        {
			$this->error['warning'] = $this->language->get( 'error_permission' );
            return false;
		}

        $validated_arr = array();
        foreach( $settings_fields as $key => $value )
        {
            if( !array_key_exists( $key, $settings_arr ) )
                continue;

            $validated_arr[$key] = $settings_arr[$key];
        }

        if( !($validated_arr = $this->method_validate( $settings_arr, $validated_arr )) )
            return false;

        $this->error = array();

        return $validated_arr;
	}

    /**
     * Install extension
     */
    public function install()
    {
        $this->response->redirect( $this->url->link( 'payment/smart2pay', 'token=' . $this->session->data['token'].'&installed='.$this->get_method_short_name(), 'SSL' ) );
    }

    /**
     * Uninstall extension
     */
    public function uninstall(){}
}
