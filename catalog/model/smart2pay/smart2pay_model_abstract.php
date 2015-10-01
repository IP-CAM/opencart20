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
abstract class ModelPaymentSmart2payAbstract extends Model
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
     * Tells if method supports recurring payments
     * @return bool
     */
    public function recurringPayments()
    {
        return false;
    }

    /**
     * This method is called in checkout page and should return an array of values for available payment methods for current address and total amount
     *
     * @param $address
     * @param $total
     *
     * @return array
     */
    public function getMethod( $address, $total )
    {
        $this->load->model( 'payment/smart2pay' );
        $this->load->model( 'smart2pay/helper' );

        $settings = $this->model_smart2pay_helper->get_module_settings();

        if( empty( $address ) or !is_array( $address )
         or empty( $address['iso_code_2'] )
         or empty( $settings['smart2pay_status'] )
         or !$this->available_for_country( $address['iso_code_2'] ) )
            return false;

        $title = 'Smart2Pay '.$this->get_method_name();
        $code  = 'smart2pay_' . $this->get_method_short_name();

        $method_data = array(
            'code'       => $code,
            'title'      =>  $title,
            'terms'      => false,
            'sort_order' => (!empty( $settings['smart2pay_sort_order'] )?$settings['smart2pay_sort_order']:0),
        );

        return $method_data;
    }

    public function available_for_country( $iso_code_2 )
    {
        $this->load->model( 'smart2pay/helper' );

        return $this->model_smart2pay_helper->method_available_for_country( $this->get_method_id(), $iso_code_2 );
    }

}
