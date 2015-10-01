<?php

if( @file_exists( DIR_APPLICATION . 'model/smart2pay/smart2pay_model_abstract.php' ) )
    include_once( DIR_APPLICATION . 'model/smart2pay/smart2pay_model_abstract.php' );


class ModelPaymentSmart2payAmexthailand extends ModelPaymentSmart2payAbstract
{
    /**
     * Returns ID of method
     * @return int
     */
    public function get_method_id()
    {
        return 1035;
    }

    /**
     * Returns a lowercase filename of payment method without smart2pay_ prefix and file extension
     * eg. onlinebankingthailand for smart2pay_onlinebankingthailand.php file
     * @return string
     */
    public function get_method_short_name()
    {
        return 'amexthailand';
    }

    /**
     * Returns user friendly method name
     * @return string
     */
    public function get_method_name()
    {
        return 'AMEXThailand';
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
}
