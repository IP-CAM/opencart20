<?php

/** @var Language $this */
/** @var Loader $loader */
/** @var ModelSmart2payHelper $model_smart2pay_helper */
global $loader;

$loader->model( 'smart2pay/helper' );
$model_smart2pay_helper = ModelSmart2payHelper::get_last_instance();

if( empty( $_ ) )
    $_ = array();

if( ($lang_arr = $model_smart2pay_helper->get_method_language_array( array( 'file_slug' => 'onlinebankingthailand' ) ))
and is_array( $lang_arr ) )
    $_ = array_merge( $_, $lang_arr );
