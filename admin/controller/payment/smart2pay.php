<?php

/**
 * Class ControllerPaymentSmart2pay
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
 * @property ModelSmart2payPaymentExtension $model_smart2pay_payment_extension
 * @property ModelSmart2payHelper $model_smart2pay_helper
 */
class ControllerPaymentSmart2pay extends Controller
{
    private $error = array();

	public function index()
    {
        $this->load->language( 'payment/smart2pay' );
        $this->load->model( 'setting/setting' );
        $this->load->model( 'smart2pay/helper' );

        $this->error = array();

        $data = array();

        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_edit'] = sprintf( $this->language->get('text_edit'), ModelSmart2payHelper::MODULE_VERSION );
        $data['btn_text_save'] = $this->language->get('btn_text_save');
        $data['btn_text_cancel'] = $this->language->get('btn_text_cancel');

        $this->document->setTitle( $data['text_edit'] );

        /*
         * Save POST data if valid
         */
        if( $this->request->server['REQUEST_METHOD'] == 'POST'
        and $this->validate_settings( $this->request->post ) )
        {
            if( $this->save_settings( $this->request->post ) )
            {
                $this->session->data['success'] = 'Success: You have modified Smart2Pay settings!';
                $this->response->redirect( $this->url->link( 'payment/smart2pay', 'token=' . $this->session->data['token'], 'SSL' ) );
            }

            if( empty( $this->error['warning'] ) )
                $this->error['warning'] = 'Couldn\'t save plugin settings. Please try again.';
        }

        /*
         * Set form elements
         */
        $form_elements = $this->model_smart2pay_helper->get_main_module_fields();
        $saved_settings = $this->model_smart2pay_helper->get_module_settings();

        // Check if database version is older than script version and if there are things to change in database...
        if( ($new_settings_arr = $this->model_smart2pay_helper->check_for_updates( $saved_settings )) )
            $saved_settings = $new_settings_arr;

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
        $data['action'] = $this->url->link('payment/smart2pay', 'token=' . $this->session->data['token'], 'SSL');

        /*
         * Set validation errors and warnings
         */
        if( isset( $this->error['warning'] ) )
            $data['error_warning'] = $this->error['warning'];
        else
            $data['error_warning'] = '';

        if( isset( $this->session->data['success'] ) )
        {
            $data['success'] = $this->session->data['success'];
            unset( $this->session->data['success'] );
        } else
            $data['success'] = '';

        /*
         * Set breadcrumbs
         */
        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], 'SSL')
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_payment'),
            'href' => $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL')
        );
        $data['breadcrumbs'][] = array(
            'text'      => $this->language->get('heading_title').' (v'.ModelSmart2payHelper::MODULE_VERSION.')',
            'href'      => $this->url->link('payment/smart2pay', 'token=' . $this->session->data['token'], 'SSL')
        );


        /*
         * Prepare templates
         */
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        /*
         * Render
         */
        $data['error'] = $this->error;
        $this->response->setOutput( $this->load->view( 'smart2pay/smart2pay.tpl', $data ) );
	}

	public function view_payment_methods()
    {
        $this->load->language( 'payment/smart2pay' );
        $this->load->model( 'smart2pay/helper' );

        $this->error = array();

        $data = array();

        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_edit'] = sprintf( $this->language->get('text_view_payment_methods'), ModelSmart2payHelper::MODULE_VERSION );
        $data['btn_text_save'] = $this->language->get('btn_text_save');
        $data['btn_text_cancel'] = $this->language->get('btn_text_cancel');

        $this->document->setTitle( $data['text_edit'] );

        var_dump( $this->model_smart2pay_helper->get_all_method_settings() );
        exit;

        /*
         * Save POST data if valid
         */
        if( $this->request->server['REQUEST_METHOD'] == 'POST'
        and ($posted_values = $this->validate_settings( $this->request->post )) )
        {
            if( $this->save_settings( $posted_values ) )
            {
                $this->session->data['success'] = 'Success: You have modified Smart2Pay settings!';
                $this->response->redirect( $this->url->link( 'payment/smart2pay/view_payment_methods', 'token=' . $this->session->data['token'], 'SSL' ) );
            }

            if( empty( $this->error['warning'] ) )
                $this->error['warning'] = 'Couldn\'t save plugin settings. Please try again.';
        }

        /*
         * Set form elements
         */
        $form_elements = $this->model_smart2pay_helper->get_main_module_fields();
        $saved_settings = $this->model_smart2pay_helper->get_module_settings();

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
        $data['action'] = $this->url->link('payment/smart2pay', 'token=' . $this->session->data['token'], 'SSL');

        /*
         * Set validation errors and warnings
         */
        if( isset( $this->error['warning'] ) )
            $data['error_warning'] = $this->error['warning'];
        else
            $data['error_warning'] = '';

        if( isset( $this->session->data['success'] ) )
        {
            $data['success'] = $this->session->data['success'];
            unset( $this->session->data['success'] );
        } else
            $data['success'] = '';

        /*
         * Set breadcrumbs
         */
        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], 'SSL')
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_payment'),
            'href' => $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL')
        );
        $data['breadcrumbs'][] = array(
            'text'      => $this->language->get('heading_title').' (v'.ModelSmart2payHelper::MODULE_VERSION.')',
            'href'      => $this->url->link('payment/smart2pay', 'token=' . $this->session->data['token'], 'SSL')
        );


        /*
         * Prepare templates
         */
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        /*
         * Render
         */
        $data['error'] = $this->error;
        $this->response->setOutput( $this->load->view( 'smart2pay/smart2pay.tpl', $data ) );
	}

	public function view_logs()
    {
        $this->load->language( 'payment/smart2pay' );
        $this->load->model( 'smart2pay/helper' );

        $this->error = array();

        $data = array();

        $data['heading_title'] = $this->language->get( 'heading_title' );
        $data['text_edit'] = sprintf( $this->language->get( 'text_view_logs' ), ModelSmart2payHelper::MODULE_VERSION );
        $data['btn_text_cancel'] = $this->language->get('btn_text_cancel');

        $this->document->setTitle( $data['text_edit'] );

        /*
         * Set logs
         */
        $data['logs'] = $this->model_smart2pay_helper->get_logs();

        /*
         * Set links
         */
        $data['cancel'] = $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL');

        /*
         * Set validation errors and warnings
         */
        if( isset( $this->error['warning'] ) )
            $data['error_warning'] = $this->error['warning'];
        else
            $data['error_warning'] = '';

        if( isset( $this->session->data['success'] ) )
        {
            $data['success'] = $this->session->data['success'];
            unset( $this->session->data['success'] );
        } else
            $data['success'] = '';

        /*
         * Set breadcrumbs
         */
        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], 'SSL')
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_payment'),
            'href' => $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL')
        );
        $data['breadcrumbs'][] = array(
            'text'      => $this->language->get('heading_title').' (v'.ModelSmart2payHelper::MODULE_VERSION.')',
            'href'      => $this->url->link('payment/smart2pay', 'token=' . $this->session->data['token'], 'SSL')
        );

        /*
         * Prepare templates
         */
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        /*
         * Render
         */
        $data['error'] = $this->error;
        $this->response->setOutput( $this->load->view( 'smart2pay/smart2pay_logs.tpl', $data ) );
	}

    /**
     * Validate post data
     *
     * @return bool
     */
    private function validate_settings( $post_arr )
    {
        if( empty( $post_arr ) or !is_array( $post_arr ) )
            return false;

        $this->load->model( 'smart2pay/helper' );

		if( !$this->user->hasPermission( 'modify', 'payment/pp_standard' ) )
        {
			$this->error['warning'] = $this->language->get( 'error_permission' );
            return false;
		}

        // Validate values if plugin is active...
        if( !empty( $post_arr['smart2pay_status'] ) )
        {
            switch( $post_arr['smart2pay_env'] )
            {
                case ModelSmart2payHelper::ENV_DEMO:
                break;
                
                case ModelSmart2payHelper::ENV_TEST:
                    if( empty( $post_arr['smart2pay_post_url_test'] )
                     or !filter_var( $this->request->post['smart2pay_post_url_test'], FILTER_VALIDATE_URL ) )
                        $this->error['smart2pay_post_url_test'] = 'Invalid Post URL';

                    if( empty( $post_arr['smart2pay_signature_test'] ) )
                        $this->error['smart2pay_signature_test'] = 'Invalid Signature';

                    if( empty( $post_arr['smart2pay_mid_test'] ) )
                        $this->error['smart2pay_mid_test'] = 'Invalid MID';
                break;

                case ModelSmart2payHelper::ENV_LIVE:
                    if( empty( $post_arr['smart2pay_post_url_live'] )
                     or !filter_var( $this->request->post['smart2pay_post_url_live'], FILTER_VALIDATE_URL ) )
                        $this->error['smart2pay_post_url_test'] = 'Invalid Post URL';

                    if( empty( $post_arr['smart2pay_signature_live'] ) )
                        $this->error['smart2pay_signature_test'] = 'Invalid Signature';

                    if( empty( $post_arr['smart2pay_mid_live'] ) )
                        $this->error['smart2pay_mid_test'] = 'Invalid MID';
                break;
            }

            if( empty( $post_arr['smart2pay_return_url'] )
             or !filter_var( $post_arr['smart2pay_return_url'], FILTER_VALIDATE_URL ) )
                $this->error['smart2pay_return_url'] = 'Invalid Return URL';
        }

		if( empty( $this->error ) )
			return true;

        if( empty( $this->error['warning'] ) )
            $this->error['warning'] = 'There have been some problems saving your settings. Please check the form!';
        
        return false;
	}

    public function save_settings( $posted_values )
    {
        if( empty( $posted_values ) or !is_array( $posted_values ) )
            return false;

        $this->load->model( 'smart2pay/helper' );

        if( !$this->model_smart2pay_helper->save_module_settings( $posted_values ) )
            $this->error['warning'] = (!empty( $this->error['warning'] )?'<br/>':'').'Error saving module settings. Please retry.';

        return true;
    }

    public function save_payment_methods_settings( $posted_values )
    {
        if( empty( $posted_values ) or !is_array( $posted_values ) )
            return false;

        $this->load->model( 'smart2pay/helper' );

        if( !$this->model_smart2pay_helper->save_methods_settings( $posted_values['methods'] ) )
            $this->error['warning'] = (!empty( $this->error['warning'] )?'<br/>':'').'Error saving module settings. Please retry.';

        return true;
    }

    /**
     * Install extension
     */
    public function install()
    {
        $this->load->model('smart2pay/payment_extension');
        $this->model_smart2pay_payment_extension->install();
    }

    /**
     * Uninstall extension
     */
    public function uninstall()
    {
        $this->load->model('smart2pay/payment_extension');
        $this->model_smart2pay_payment_extension->uninstall();
    }
}
