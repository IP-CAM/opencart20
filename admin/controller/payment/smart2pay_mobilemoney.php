<?php

if( @file_exists( DIR_APPLICATION . 'model/smart2pay/smart2pay_abstract.php' ) )
    include_once( DIR_APPLICATION . 'model/smart2pay/smart2pay_abstract.php' );


class ControllerPaymentSmart2payMobilemoney extends ControllerPaymentSmart2payAbstract
{
    /**
     * Returns ID of method
     * @return int
     */
    public function get_method_id()
    {
        return 1016;
    }

    /**
     * Returns a lowercase filename of payment method without smart2pay_ prefix and file extension
     * eg. onlinebankingthailand for smart2pay_onlinebankingthailand.php file
     * @return string
     */
    public function get_method_short_name()
    {
        return 'mobilemoney';
    }

    /**
     * Returns user friendly method name
     * @return string
     */
    public function get_method_name()
    {
        return 'Mobile Money';
    }

    /**
     * When saving payment method settings, this method will do any special validations.
     * Method will set errors to $this->error array
     *
     * @param array $settings_arr Array with all values to be validated (from post or other source)
     * @param array $validated_arr Array with common keys already validated by abstract class
     *
     * @return array|false Returns final validated array with settings or false on any error
     */
    public function method_validate( $settings_arr, $validated_arr )
    {
        return $validated_arr;
    }

    /**
     * If any special fields are required for this payment method, this method will return array of fields. If no special fields return false.
     * @return array|false
     */
    public function method_settings()
    {
        return false;
    }

    public function index( $data = false )
    {
        parent::index();
	}
}
