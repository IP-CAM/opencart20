<?php

if( @file_exists( DIR_APPLICATION . 'model/smart2pay/smart2pay_model_abstract.php' ) )
    include_once( DIR_APPLICATION . 'model/smart2pay/smart2pay_model_abstract.php' );


class ModelPaymentSmart2payDebito extends ModelPaymentSmart2payAbstract
{
    /**
     * Returns ID of method
     * @return int
     */
    public function get_method_id()
    {
        return 1001;
    }

    /**
     * Returns a lowercase filename of payment method without smart2pay_ prefix and file extension
     * eg. onlinebankingthailand for smart2pay_onlinebankingthailand.php file
     * @return string
     */
    public function get_method_short_name()
    {
        return 'debito';
    }

    /**
     * Returns user friendly method name
     * @return string
     */
    public function get_method_name()
    {
        return 'Débito Bradesco';
    }
}
