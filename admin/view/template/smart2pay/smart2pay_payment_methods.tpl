<?php

    /** @var Loader $this */
    /** @var ModelSmart2payHelper $model_smart2pay_helper */
    /** @property Registry $registry */
    $this->model( 'smart2pay/helper' );
    $model_smart2pay_helper = $this->registry->get( 'model_smart2pay_helper' );

    echo $header;
    echo $column_left;
?>

<div id="content">
    <div class="page-header">
        <div class="container-fluid">
            <div class="pull-right">
                <button type="submit" form="form" data-toggle="tooltip" title="<?php echo $btn_text_save; ?>" class="btn btn-primary"><i class="fa fa-save"></i></button>
                <a href="<?php echo $cancel; ?>" data-toggle="tooltip" title="<?php echo $btn_text_cancel; ?>" class="btn btn-default"><i class="fa fa-reply"></i></a>
            </div>
            <h1><?php echo $heading_title; ?></h1>
            <ul class="breadcrumb">
                <?php foreach ($breadcrumbs as $breadcrumb) { ?>
                <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
                <?php } ?>
            </ul>
        </div>
    </div>

    <div class="container-fluid">
        <?php if ($error_warning) { ?>
        <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo $error_warning; ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
        <?php } ?>
        <?php if ($success) { ?>
            <div class="alert alert-success"><i class="fa fa-check-circle"></i> <?php echo $success; ?>
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
        <?php } ?>
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa fa-pencil"></i> <?php echo $text_edit; ?></h3>
            </div>
            <?php
                $tabs_arr = array();
                $tabs_arr['current_tab'] = 'payment_methods';

                echo $model_smart2pay_helper->render_main_plugin_tabs( $tabs_arr );
            ?>
            <div class="panel-body">
            <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form" class="form-horizontal">
            <?php
            if( !empty( $form_elements ) and is_array( $form_elements ) )
            {
                ?>
                <div class="table-responsive"><table class="table table-bordered table-hover">
                <thead>
                <tr>
                    <td class="text-center" style="width:1px;">
                        Active?<br/>
                        <input type="checkbox" onclick="$('input[name$=\'\_status\]\'][disabled!=\'disabled\']').prop('checked', this.checked);">
                    </td>
                    <td class="text-left" style="width:1px;">Installed?</td>
                    <td class="text-left" colspan="2">Method</td>
                    <td class="text-left">Details</td>
                </tr>
                </thead>
                <tbody>
                <?php
                foreach( $form_elements as $method_slug => $method_arr )
                {
                    //echo $model_smart2pay_helper->render_module_fields( $method_arr['module_fields'], $error );
                    ?>
                    <tr>
                        <td class="text-center">
                            <input type="checkbox" name="settings[<?php echo $method_slug?>][<?php echo $method_slug?>_status]" value="1"
                                <?php echo (!empty( $method_arr['module_fields'][$method_slug.'_status']['value'] )?'checked="checked"':'')?>
                                <?php echo (empty( $method_arr['installed'] )?'disabled="disabled" readonly="readonly"':'')?>
                                />
                        </td>
                        <td class="text-center"><?php
                            if( !empty( $method_arr['installed'] ) )
                                echo 'Yes';
                            else
                            {
                                ?><a href="<?php echo $method_arr['install_link']?>" title="<?php echo 'Install '.$method_arr['db_details']['display_name']?>" data-toggle="tooltip" class="btn btn-success"><i class="fa fa-plus-circle"></i> Install</a><?php
                            }
                        ?></td>
                        <td class="text-center" style="width:165px;"><img src="view/image/payment/smart2pay/methods/<?php echo $method_arr['db_details']['logo_url']?>" style="max-width: 150px; margin: 0 5px 5px 0;border: 1px solid #EEEEEE;padding:1px;" /></td>
                        <td>
                            <?php
                            if( !empty( $error ) and !empty( $error[$method_slug] ) )
                            {
                                ?><div class="text-danger"><?php echo $error[$method_slug]?></div><?php
                            }
                            ?>
                            <strong><?php echo $method_arr['db_details']['display_name']?></strong><br/>
                            <div id="s2p_meth_countries_<?php echo $method_slug?>" style="height: 30px; overflow: hidden;text-overflow: ellipsis;display:inline-block;">
                            Available in following countries
                                (<a href="javascript:void(0);" style="text-decoration: underline;" onclick="$('#s2p_meth_countries_<?php echo $method_slug?>').css('overflow','visible').css('height','auto');">show all</a>): <?php
                            if( empty( $method_arr['countries'] ) or !is_array( $method_arr['countries'] ) )
                                echo 'N/A';

                            else
                            {
                                $first_country = true;
                                foreach( $method_arr['countries'] as $country_id => $country_arr )
                                {
                                    if( empty( $first_country ) )
                                        echo ', ';

                                    echo $country_arr['name'];
                                    $first_country = false;
                                }
                                echo '.';
                            }
                            ?>
                            </div>
                        </td>
                        <td></td>
                    </tr>
                    <?php
                }
                ?></tbody></table></div><?php
            }
            ?>
            </form>
            </div>
        </div>
    </div>
</div>

<?php echo $footer; ?>
