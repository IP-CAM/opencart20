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
 * @property ModelSettingSetting $model_setting_setting
 * @property ModelAccountAddress $model_account_address
 * @property ModelCheckoutOrder $model_checkout_order
 * @property ModelPaymentSmart2pay $model_payment_smart2pay
 * @property ModelSmart2payHelper $model_smart2pay_helper
 */
class ControllerPaymentSmart2pay extends Controller
{
    const DEMO_POST_URL = 'https://apitest.smart2pay.com', DEMO_MID = 1045, DEMO_SITE_ID = 30291, DEMO_SIGNATURE = '9fc01939-71b3';

    /**
     * Index action
     *  Used within checkout flow
     */
    protected function index()
    {
        $this->load->model( 'payment/smart2pay' );
        $this->load->model( 'account/address' );

        /*
         * Get address
         */
        if( $this->customer->isLogged() && isset( $this->session->data['payment_address_id'] ) )
            $payment_address = $this->model_account_address->getAddress( $this->session->data['payment_address_id'] );
        elseif( isset( $this->session->data['guest'] ) )
            $payment_address = $this->session->data['guest']['payment'];

        if( empty( $data ) )
            $data = array();

        /*
         * Set template data
         */
        $data['trans'] = $this->load->language( 'payment/smart2pay' );
        //$language = new Language(DIR_LANGUAGE);
        //$translations = $language->load('payment/smart2pay');
        //$data['trans'] = $translations;

        $data['methods']  = $this->model_payment_smart2pay->getActiveMethods( $payment_address, true );

        /*
         * Set checkout method id
         *   - this might be set by s2p checkout helper in checkout step before last one
         */
        $data['checkout_method_id'] = (isset( $this->session->data['smart2pay_checkout_method_id'] ) ? $this->session->data['smart2pay_checkout_method_id'] : null);

        /*
         * Set base URL
         */
         
        $server_base = null;
    	if( !isset( $this->request->server['HTTPS'] )
         or $this->request->server['HTTPS'] != 'on' )
            $server_base = HTTP_SERVER;
		else
            $server_base = HTTPS_SERVER;

        if( !is_dir( DIR_TEMPLATE . $this->config->get( 'config_template' ) . '/image/payment/smart2pay' ) )
        {
            $this->template = $this->config->get( 'config_template' ) . '/template/payment/smart2pay.tpl';
            $data['base_img_url'] = $server_base . 'catalog/view/theme/' . $this->config->get( 'config_template' ) . '/image/payment/smart2pay/methods/';
        } else
            $data['base_img_url'] = $server_base . 'catalog/view/theme/default/image/payment/smart2pay/methods/';

        /*
         * Prepare template
         */
        if( !file_exists( DIR_TEMPLATE . $this->config->get( 'config_template' ) . '/template/payment/smart2pay.tpl' ) )
            $this->template = $this->config->get( 'config_template' ) . '/template/payment/smart2pay.tpl';
        else
            $this->template = 'default/template/payment/smart2pay.tpl';

        return $this->load->view( $this->template, $data );
	}

    /**
     * Pay action
     *  It handles the flow after checkout is finished
     */
    public function pay()
    {
        $this->load->model( 'payment/smart2pay' );
        $this->load->model( 'account/address' );
        $this->load->model( 'checkout/order' );
        $this->load->model( 'smart2pay/helper' );

        $this->load->language( 'payment/smart2pay' );

        if( !($template_search = $this->model_smart2pay_helper->get_template_file_location( 'template/smart2pay/smart2pay_send_form.tpl' ))
            or !is_array( $template_search ) )
        {
            trigger_error( $this->language->get( 'err_template_file' ) );
            exit();
        } else
        {
            if( !empty( $template_search['path'] ) )
                $template_file = $template_search['path'];
            else
                $template_file = $template_search['default_path'];
        }

        $data = array();

        $data['header'] = $this->load->controller( 'common/header' );
        $data['footer'] = $this->load->controller( 'common/footer' );
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');

        $error_arr = array();
        $error_arr['error'] = '';

        $method_id = 0;
        if( !empty( $this->request->get['method'] ) )
            $method_id = intval( $this->request->get['method'] );

        if( empty( $method_id ) )
            $error_arr['error'] .= (!empty( $error_arr['error'] )?'<br/>':'').$this->language->get( 'err_payment_method' );

        elseif( empty( $this->session->data['order_id'] )
         or !($order = $this->model_checkout_order->getOrder( $this->session->data['order_id'] )) )
            $error_arr['error'] .= (!empty( $error_arr['error'] )?'<br/>':'').$this->language->get( 'err_order_not_found' );

        elseif( empty( $order['payment_iso_code_2'] ) )
            $error_arr['error'] .= (!empty( $error_arr['error'] )?'<br/>':'').$this->language->get( 'err_country_details' );

        elseif( !$this->model_smart2pay_helper->method_available_for_country( $method_id, $order['payment_iso_code_2'] ))
            $error_arr['error'] .= (!empty( $error_arr['error'] )?'<br/>':'').$this->language->get( 'err_country_details' );

        $data['error_warning'] = $error_arr['error'];

        if( !empty( $error_arr['error'] ) )
        {
            $this->response->setOutput( $this->load->view( $template_file, $data ) );
            return;
        }

        /*
         * Set data
         */
        //$settings = $this->model_setting_setting->getSetting('smart2pay');

        $settings = $this->model_smart2pay_helper->get_module_settings();

        switch( $settings['smart2pay_env'] )
        {
            case ModelSmart2payHelper::ENV_DEMO:
                $settings['smart2pay_post_url']  = self::DEMO_POST_URL;
                $settings['smart2pay_mid']       = self::DEMO_MID;
                $settings['smart2pay_site_id']   = self::DEMO_SITE_ID;
                $settings['smart2pay_signature'] = self::DEMO_SIGNATURE;
            break;
            case ModelSmart2payHelper::ENV_TEST:
                $settings['smart2pay_post_url']  = $settings['smart2pay_post_url_test'];
                $settings['smart2pay_mid']       = $settings['smart2pay_mid_test'];
                $settings['smart2pay_signature'] = $settings['smart2pay_signature_test'];
            break;
            case ModelSmart2payHelper::ENV_LIVE:
                $settings['smart2pay_post_url']  = $settings['smart2pay_post_url_live'];
                $settings['smart2pay_mid']       = $settings['smart2pay_mid_live'];
                $settings['smart2pay_signature'] = $settings['smart2pay_signature_live'];
            break;
        }

        if( $settings['smart2pay_send_order_number_as_product_description'] )
            $settings['smart2pay_product_description'] = 'Ref. no: ' . $this->session->data['order_id'];
        else
            $settings['smart2pay_product_description'] = $settings['smart2pay_custom_product_description'];

        $data['settings'] = $settings;

        $orderTotal = round( $order['total'] * $order['currency_value'] * 100 );

        $skipHpp = $settings['smart2pay_skip_payment_page'];

        if( $this->request->get['method'] === ModelSmart2payHelper::PAYMENT_METHOD_BT
         or $this->request->get['method'] === ModelSmart2payHelper::PAYMENT_METHOD_SIBS )
            $skipHpp = 0;

        $data['payment_data'] = array(
            'MerchantID'       => $settings['smart2pay_mid'],
            'MerchantTransactionID' => ($settings['smart2pay_env']==ModelSmart2payHelper::ENV_DEMO?'DEMO_'.str_replace('.','',microtime(true)).'_':'').$this->session->data['order_id'],
            'Amount'           => $orderTotal,
            'Currency'         => $order['currency_code'],
            'ReturnURL'        => $settings['smart2pay_return_url'],
            'IncludeMethodIDs' => $method_id,
            'CustomerName'     => $order['payment_firstname'] . ' ' . $order['payment_lastname'],
            'CustomerFirstName'=> $order['payment_firstname'],
            'CustomerLastName' => $order['payment_lastname'],
            'CustomerEmail'    => $order['email'],
            'Country'          => $order['payment_iso_code_2'],
            'MethodID'         => $method_id,
            'Description'      => $settings['smart2pay_product_description'],
            'SkipHPP'          => $skipHpp,
            'RedirectInIframe' => $settings['smart2pay_redirect_in_iframe'],
            'SkinID'           => $settings['smart2pay_skin_id'],
			'SiteID'           => $settings['smart2pay_site_id'],
        );

        foreach( $data['payment_data'] as $key => $value )
        {
            if( !$value )
                unset( $data['payment_data'][$key] );
        }

        $stringToHash = $this->model_payment_smart2pay->createStringToHash( $data['payment_data'] );

        $data['string_to_hash'] = $stringToHash;

        $data['payment_data']['Hash'] = $this->model_payment_smart2pay->computeHash( $stringToHash, $settings['smart2pay_signature'] );

        
      	//if order is unconfirmed we confirm it depending on the smart2pay_order_confirm flag
		//status is new for now
		if( $settings['smart2pay_order_confirm'] == ModelSmart2payHelper::CONFIRM_ORDER_INITIATE )
        {
            $this->model_payment_smart2pay->log( 'Confirming order on initiate payment', 'info' );
            $this->model_checkout_order->addOrderHistory( $this->session->data['order_id'], $settings['smart2pay_order_status_new'] );
        }

        $this->response->setOutput( $this->load->view( $template_file, $data ) );
    }

    /**
     * Feedback action
     *  Default return url after payment
     */
    public function feedback()
    {
        $this->load->model( 'payment/smart2pay' );
        $this->load->model( 'checkout/order' );
        $this->load->model( 'smart2pay/helper' );

        $data = array();

        $error_arr = array();
        $error_arr['error'] = '';

        $data['lang'] = $this->load->language( 'payment/smart2pay' );

        $order_id = 0;
        if( !empty( $this->request->get['MerchantTransactionID'] ) )
            $order_id = intval( $this->request->get['MerchantTransactionID'] );
        $status_id = 0;
        if( !empty( $this->request->get['data'] ) )
            $status_id = intval( $this->request->get['data'] );

        if( empty( $order_id )
         or !($order = $this->model_checkout_order->getOrder( $order_id )) )
            $error_arr['error'] .= (!empty( $error_arr['error'] )?'<br/>':'').$this->language->get( 'err_feedback_order_not_found' );

        if( empty( $status_id )
         or !$this->model_smart2pay_helper->valid_status( $status_id ) )
            $error_arr['error'] .= (!empty( $error_arr['error'] )?'<br/>':'').$this->language->get( 'err_feedback_invalid_status' );

        $data['error_warning'] = $error_arr['error'];

        $this->model_payment_smart2pay->log( '>>> START FEEDBACK'.(!empty( $order_id )?' for order #'.$order_id:'').(!empty( $error_arr['error'] )?' (with error)':''), 'info' );

        if( !empty( $error_arr['error'] ) )
        {
            $data['header'] = $this->load->controller( 'common/header' );
            $data['footer'] = $this->load->controller( 'common/footer' );
            $data['column_left'] = $this->load->controller( 'common/column_left' );
            $data['column_right'] = $this->load->controller( 'common/column_right' );
            $data['content_top'] = $this->load->controller( 'common/content_top' );
            $data['content_bottom'] = $this->load->controller( 'common/content_bottom' );

            if( !($template_search = $this->model_smart2pay_helper->get_template_file_location( 'template/smart2pay/smart2pay_feedback.tpl' ))
             or !is_array( $template_search ) )
            {
                trigger_error( $this->language->get( 'err_template_file' ) );
                exit();
            } else
            {
                if( !empty( $template_search['path'] ) )
                    $template_file = $template_search['path'];
                else
                    $template_file = $template_search['default_path'];
            }

            $this->response->setOutput( $this->load->view( $template_file, $data ) );
            return;
        }

        $settings = $this->model_smart2pay_helper->get_module_settings();

        $status_id_to_string = array(
            ModelSmart2payHelper::S2P_STATUS_OPEN => 'new',
            ModelSmart2payHelper::S2P_STATUS_SUCCESS => 'success',
            ModelSmart2payHelper::S2P_STATUS_CANCELLED => 'canceled',
            ModelSmart2payHelper::S2P_STATUS_FAILED => 'failed',
            ModelSmart2payHelper::S2P_STATUS_EXPIRED => 'expired',
            ModelSmart2payHelper::S2P_STATUS_PENDING_CUSTOMER => 'new',
            ModelSmart2payHelper::S2P_STATUS_PENDING_PROVIDER => 'new',
            ModelSmart2payHelper::S2P_STATUS_SUBMITTED => 'new',
            ModelSmart2payHelper::S2P_STATUS_PROCESSING => 'new',
            ModelSmart2payHelper::S2P_STATUS_AUTHORIZED => 'new',
            ModelSmart2payHelper::S2P_STATUS_APPROVED => 'new',
            ModelSmart2payHelper::S2P_STATUS_CAPTURED => 'new',
            ModelSmart2payHelper::S2P_STATUS_REJECTED => 'failed',
            ModelSmart2payHelper::S2P_STATUS_PENDING_CAPTURE => 'new',
            ModelSmart2payHelper::S2P_STATUS_EXCEPTION => 'new',
            ModelSmart2payHelper::S2P_STATUS_PENDING_CANCEL => 'new',
            ModelSmart2payHelper::S2P_STATUS_REVERSED => 'new',
            ModelSmart2payHelper::S2P_STATUS_COMPLETED => 'success',
            ModelSmart2payHelper::S2P_STATUS_PROCESSING => 'new',
            ModelSmart2payHelper::S2P_STATUS_DISPUTED => 'new',
            ModelSmart2payHelper::S2P_STATUS_CHARGEBACK => 'new',
        );

        if( !empty( $status_id_to_string[$status_id] ) )
            $status_string = $status_id_to_string[$status_id];
        else
            $status_string = 'new';

        $db_status = 0;
        if( !empty( $settings['smart2pay_order_status_' . $status_string] ) )
            $db_status = $settings['smart2pay_order_status_' . $status_string];

        if( $settings['smart2pay_order_confirm'] == ModelSmart2payHelper::CONFIRM_ORDER_REDIRECT )
        {
            $this->model_payment_smart2pay->log( '>>> Return URL: Order #'. $order_id . ' updated with status ' . $status_string, 'info' );
            $this->model_checkout_order->addOrderHistory( $order_id, $db_status );
        }

        if( isset( $data['lang']['info_payment_feedback_' . $status_string] ) )
            $data['feedback'] = $data['lang']['info_payment_feedback_' . $status_string];
        else
            $data['feedback'] = $this->language->get( 'info_payment_feedback_failed' );

        if( !in_array( $status_string, array( 'new', 'success' ) ) )
            $redirect = $this->url->link( 'checkout/failure', (!empty( $this->session->data['token'] )?'token=' . $this->session->data['token']:''), 'SSL' );
        else
            $redirect = $this->url->link( 'checkout/success', (!empty( $this->session->data['token'] )?'token=' . $this->session->data['token']:''), 'SSL' );

        $data['redirect'] = $redirect;

		$this->model_payment_smart2pay->log( '>>>END FEEDBACK', 'info' );

        $data['header'] = $this->load->controller( 'common/header' );
        $data['footer'] = $this->load->controller( 'common/footer' );
        $data['column_left'] = $this->load->controller( 'common/column_left' );
        $data['column_right'] = $this->load->controller( 'common/column_right' );
        $data['content_top'] = $this->load->controller( 'common/content_top' );
        $data['content_bottom'] = $this->load->controller( 'common/content_bottom' );

        if( !($template_search = $this->model_smart2pay_helper->get_template_file_location( 'template/smart2pay/smart2pay_feedback.tpl' ))
         or !is_array( $template_search ) )
        {
            trigger_error( $this->language->get( 'err_template_file' ) );
            exit();
        } else
        {
            if( !empty( $template_search['path'] ) )
                $template_file = $template_search['path'];
            else
                $template_file = $template_search['default_path'];
        }

        $this->response->setOutput( $this->load->view( $template_file, $data ) );
    }

    /**
     * Callback action
     *  Handle payment gateway response
     */
    public function callback()
    {
        $this->load->model( 'payment/smart2pay' );
        $this->load->model( 'setting/setting' );
        $this->load->model( 'checkout/order' );
        $this->load->model( 'smart2pay/helper' );

        $this->model_payment_smart2pay->log( '>>> START CALLBACK', 'info' );

        //$settings = $this->model_setting_setting->getSetting('smart2pay');
        $settings = $this->model_smart2pay_helper->get_module_settings();

        switch( $settings['smart2pay_env'] )
        {
            case ModelSmart2payHelper::ENV_DEMO:
                $settings['smart2pay_post_url']  = self::DEMO_POST_URL;
                $settings['smart2pay_mid']       = self::DEMO_MID;
                $settings['smart2pay_site_id']   = self::DEMO_SITE_ID;
                $settings['smart2pay_signature'] = self::DEMO_SIGNATURE;
            break;
            case ModelSmart2payHelper::ENV_TEST:
                $settings['smart2pay_post_url']  = $settings['smart2pay_post_url_test'];
                $settings['smart2pay_mid']       = $settings['smart2pay_mid_test'];
                $settings['smart2pay_signature'] = $settings['smart2pay_signature_test'];
            break;
            case ModelSmart2payHelper::ENV_LIVE:
                $settings['smart2pay_post_url']  = $settings['smart2pay_post_url_live'];
                $settings['smart2pay_mid']       = $settings['smart2pay_mid_live'];
                $settings['smart2pay_signature'] = $settings['smart2pay_signature_live'];
            break;
        }

        try
        {
            $response = null;
            if( ($data = file_get_contents( 'php://input' )) )
                parse_str( $data, $response );

            if( empty( $data )
             or empty( $response ) or !is_array( $response ) )
            {
                $this->model_payment_smart2pay->log( 'No data provided', 'info' );
                exit;
            }

            if( empty( $response['StatusID'] ) )
                $response['StatusID'] = 0;
            if( empty( $response['MerchantTransactionID'] ) )
                $response['MerchantTransactionID'] = 0;
            if( empty( $response['NotificationType'] ) )
                $response['NotificationType'] = '';
            if( empty( $response['PaymentID'] ) )
                $response['PaymentID'] = 0;

			$this->model_payment_smart2pay->log( 'Notification from Smart2Pay: ' . $data, 'info' );

			$this->model_payment_smart2pay->log( 'StatusID = ' . $response['StatusID'], 'info' );
            $this->model_payment_smart2pay->log( 'MerchantTransactionID = ' . $response['MerchantTransactionID'], 'info' );

            $vars = array();
			$recomposedHash = '';
            if( ($pairs = explode( '&', $data ))
            and is_array( $pairs ) )
            {
                foreach( $pairs as $pair )
                {
                    $nv = explode( '=', $pair, 2 );
                    $name = $nv[0];
                    $vars[$name] = (isset( $nv[1] )?$nv[1]:'');

                    if( strtolower( $name ) != 'hash' )
                        $recomposedHash .= $name . $vars[$name];
                }
            }
			
            $recomposedHash = $this->model_payment_smart2pay->computeHash( $recomposedHash, $settings['smart2pay_signature'] );

            $order_id = $response['MerchantTransactionID'];

            // Message is intact
            if( empty( $response['Hash'] ) or $recomposedHash != $response['Hash'] )
            {
                $this->model_payment_smart2pay->log('Hashes do not match (received:' . $response['Hash'] . ') (recomposed:' . $recomposedHash . ')', 'warning');
                echo 'OpenCart Plugin: Hashes did not match (received:' . $response['Hash'] . ') (recomposed:' . $recomposedHash . ')';
            } elseif( empty( $order_id )
                   or !($order = $this->model_checkout_order->getOrder( $order_id )) )
            {
                $this->model_payment_smart2pay->log( 'Couldn\'t find order with ID ['.$order_id.']', 'warning');
                echo 'OpenCart Plugin: Hashes did not match (received:' . $response['Hash'] . ') (recomposed:' . $recomposedHash . ')';
            } else
            {
                $this->model_payment_smart2pay->log( 'Hashes match', 'info' );

                // Leave order in pending if notification status is open
                if( $order['order_status_id'] == 0
                and $response['StatusID'] != ModelSmart2payHelper::S2P_STATUS_OPEN )
                {
					// If order is unconfirmed we confirm it depending on the smart2pay_order_confirm flag
					// status is new for now
					if( $settings['smart2pay_order_confirm'] == ModelSmart2payHelper::CONFIRM_ORDER_FINAL_STATUS )
                    {
						$this->model_payment_smart2pay->log('Confirming order..', 'info');
						$this->model_checkout_order->addOrderHistory( $order_id, $settings['smart2pay_order_status_new'] );
					}
				}

                $order = $this->model_checkout_order->getOrder( $order_id );

                $this->model_payment_smart2pay->log( 'Order status is ' . $order['order_status_id'], 'info' );

                if( empty( $order['payment_method'] ) )
                    $order['payment_method'] = 'Smart2Pay';

				$this->model_payment_smart2pay->log( '> DEBUG: Payment method used was ' . $order['payment_method'], 'info' );
               
                /**
                 * Check status ID
                 */
                switch( $response['StatusID'] )
                {
                    case ModelSmart2payHelper::S2P_STATUS_OPEN:
                        $this->model_payment_smart2pay->log( 'Payment state is open', 'info' );
                    break;

                    case ModelSmart2payHelper::S2P_STATUS_SUCCESS:
                        $this->model_payment_smart2pay->log( 'Order #'.$order_id.': Payment success', 'info' );

                        // cheking amount  and currency
                        $orderAmount = round( $order['total'] * $order['currency_value'] * 100 );
                        $orderCurrency = $order['currency_code'];

                        if( (int) $orderAmount !== (int) $response['Amount']
                         or $orderCurrency != $response['Currency'] )
                            $this->model_payment_smart2pay->log(
                                'Amount or currency do NOT match (' . $orderAmount . '/' . $response['Amount'] . ', ' . $orderCurrency . '/' . $response['Currency'] . ')',
                                'info'
                            );

                        else
                        {
                            $this->model_payment_smart2pay->log( 'Amount and currency match', 'info' );

                            if( $order['order_status_id'] == 0 )
                            {
                                $this->model_payment_smart2pay->log( 'Confirming order..', 'info' );
                                $this->model_checkout_order->addOrderHistory( $order_id, $settings['smart2pay_order_status_new'] );
                            }

                            $this->model_payment_smart2pay->log( 'Updating order - setting received notification to history.', 'info' );

                            $this->model_checkout_order->addOrderHistory(
                                $order_id,
                                $settings['smart2pay_order_status_success'],
                                '[' . date('Y-m-d H:i:s') . '] Smart2Pay :: order has been paid. [Method: ' . $order['payment_method'] . ']'
                            );

                            if( !empty( $settings['smart2pay_notify_customer_by_email'] ) )
                            {
                                try {
                                    // Inform customer
                                    $this->model_payment_smart2pay->log('Informing customer via email', 'info');
                                    $this->informCustomer( $order );
                                } catch (Exception $e) {
                                    $this->model_payment_smart2pay->log('Could not send e-mail: ' . $e->getMessage(), 'exception');
                                }
                            }
                        }
                    break;

                    case ModelSmart2payHelper::S2P_STATUS_CANCELLED:
                        $this->model_payment_smart2pay->log( 'Payment state is cancelled', 'info' );

						if( $order['order_status_id'] )
                        {
							$this->model_payment_smart2pay->log( 'Updating order..', 'info' );
							$this->model_checkout_order->addOrderHistory(
								$order_id,
								$settings['smart2pay_order_status_canceled'],
								'[' . date('Y-m-d H:i:s') . '] Smart2Pay :: order payment has been canceled. [Method: ' . $order['payment_method'] . ']'
							);
						}
                    break;

                    case ModelSmart2payHelper::S2P_STATUS_FAILED:
                        $this->model_payment_smart2pay->log('Payment state is failed', 'info');

						if( $order['order_status_id'] )
                        {
							$this->model_checkout_order->addOrderHistory(
								$order_id,
								$settings['smart2pay_order_status_failed'],
								'[' . date('Y-m-d H:i:s') . '] Smart2Pay :: order payment has failed. [Method: ' . $order['payment_method'] . ']'
							);
						}
                    break;

                    // Status = expired
                    case ModelSmart2payHelper::S2P_STATUS_EXPIRED:
                        $this->model_payment_smart2pay->log( 'Payment state is expired', 'info' );

						if( $order['order_status_id'] )
                        {
							$this->model_checkout_order->addOrderHistory(
								$order_id,
								$settings['smart2pay_order_status_expired'],
								'[' . date('Y-m-d H:i:s') . '] Smart2Pay :: order payment has expired. [Method: ' . $order['payment_method'] . ']'
							);
						}
                    break;

                    default:
                        $this->model_payment_smart2pay->log( 'Payment state is unknown ('.$response['StatusID'].'). [Method: ' . $order['payment_method'] . ']', 'info');
                    break;
                }

                //if notification was processed OK, we respond
                // NotificationType IS payment
                if( strtolower( $response['NotificationType'] ) == 'payment' )
                {
                    // prepare string for the hash
                    $responseHashString = 'notificationTypePaymentPaymentId' . $response['PaymentID'];
                    $recomposedHash = $this->model_payment_smart2pay->computeHash( $responseHashString, $settings['smart2pay_signature'] );

                    // prepare response data
                    $responseData = array(
                        'NotificationType' => 'Payment',
                        'PaymentID' => $response['PaymentID'],
                        'Hash' => $recomposedHash,
                    );

                    // output response
                    echo 'NotificationType=payment&PaymentID=' . $responseData['PaymentID'] . '&Hash=' . $responseData['Hash'];
                }
            }
        } catch( Exception $e )
        {
            $this->model_payment_smart2pay->log( $e->getMessage(), 'exception' );
        }

        $this->model_payment_smart2pay->log('END CALLBACK <<<', 'info');

        exit;
	}

    private function informCustomer( $order )
    {
        if( defined( 'HTTP_IMAGE' ) )
            $logo_path = HTTP_IMAGE . $this->config->get( 'config_logo' );
        else
            $logo_path = HTTP_SERVER . 'image/' .$this->config->get( 'config_logo' );

        $data = array();

        $data['logo'] = $logo_path;
        $data['store_name'] = $order['store_name'];
        $data['store_url'] = $order['store_url'];
        $data['order_id'] = $order['order_id'];
        $data['order_date'] = date('d F Y', strtotime($order['date_added']));
        $data['order_total'] = number_format($order['total'], 2);
        $data['order_currency'] = $order['currency_code'];
        $data['customer_name'] = $order['firstname'] . ' ' . $order['lastname'];

        $data['suport_email'] = $this->config->get( 'config_email' );

        if( @file_exists( DIR_TEMPLATE . $this->config->get( 'config_template' ) . '/template/smart2pay/email/smart2pay_payment_confirmation.tpl' ) )
            $template = $this->config->get( 'config_template' ) . '/template/smart2pay/email/smart2pay_payment_confirmation.tpl';
        else
            $template = 'default/template/smart2pay/email/smart2pay_payment_confirmation.tpl';

        $subject = 'Payment Confirmation';

        $mail = new Mail();
        $mail->protocol = $this->config->get('config_mail_protocol');
        $mail->parameter = $this->config->get('config_mail_parameter');
        $mail->hostname = $this->config->get('config_smtp_host');
        $mail->username = $this->config->get('config_smtp_username');
        $mail->password = $this->config->get('config_smtp_password');
        $mail->port = $this->config->get('config_smtp_port');
        $mail->timeout = $this->config->get('config_smtp_timeout');

        $mail->setTo( $order['email'] );
        $mail->setFrom( $this->config->get( 'config_email' ) );
        $mail->setSender( $order['store_name'] );
        $mail->setSubject( html_entity_decode( $subject, ENT_QUOTES, 'UTF-8' ) );
        $mail->setHtml( $this->load->view( $template, $data ) );
        //$mail->setText(html_entity_decode($text, ENT_QUOTES, 'UTF-8'));
        $mail->send();

        $this->model_payment_smart2pay->log( 'Informed customer via email (mail sent)', 'info' );
    }
}
