<?php

    echo $header;
    echo $column_left;

    function renderSmart2PayFormElements( $elements, $errors )
    {
        if( empty( $elements ) or !is_array( $elements ) )
            return;

        foreach( $elements as $name => $element )
        {
            ?>
            <div class="form-group <?php echo ( !empty( $element['required'] ) ? 'required' : '' )?>">
                <label class="col-sm-2 control-label" for=""><?php echo $element['label']?></label>
                <div class="col-sm-10">
                <?php
                switch( $element['type'] )
                {
                    case 'text':
                        ?><input class="form-control" style="width: 300px;<?php echo $element['extra_css']?>" type="text" name="<?php echo $name?>" value="<?php echo $element['value']?>" /><?php
                    break;

                    case 'textarea':
                        ?><textarea class="form-control" style="width: 300px;<?php echo $element['extra_css']?>" name="<?php echo $name?>"><?php echo $element['value']?></textarea><?php
                    break;

                    case 'select':
                        ?><select class="form-control" <?php ( !empty( $element['multiple'] )? 'multiple' : '')?> name="<?php echo $name?>" style="<?php echo $element['extra_css']?>"><?php

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
                        if( strstr( $name, 'smart2pay_active_methods' ) )
                        {
                            $indexedOptionsKeys = array_keys( $element['options'] );
                            $columns = 5;
                            $methodsCount = count($element['options']);
                            $methodsPerColumn = round($methodsCount / $columns);

                            while( ($methodsCount % $columns) != 0 )
                            {
                                $methodsCount++;
                                $methodsPerColumn = $methodsCount / $columns;
                            }

                            ?><table>
                              <tr>
                                  <?php
                                  for( $i = 1; $i < $columns + 1; $i++ )
                                  {
                                      ?><td><?php
                                      for( $m = ($i - 1) * $methodsPerColumn; $m < ($i * $methodsPerColumn); $m++ )
                                      {
                                          if( isset( $indexedOptionsKeys[$m] ) )
                                          {
                                              ?>
                                              <input id="<?php echo $name.$m?>" type="checkbox" value="<?php echo $indexedOptionsKeys[$m]?>"
                                                       name="<?php echo $name . (count($element['options']) > 1 ? '[]' : '')?>" style="<?php echo $element['extra_css']?>"
                                                      <?php echo (in_array( $indexedOptionsKeys[$m], (array)$element['value'] ) ? 'checked="checked"' : '')?>">
                                              <label for="<?php echo $name.$m?>"><?php echo $element['options'][$indexedOptionsKeys[$m]]?></label><br />
                                              <?php
                                          }
                                      }
                                      ?></td><?php
                                  }
                                  ?>
                                </tr>
                              </table>
                            <?php
                        } else
                        {
                            foreach( $element['options'] as $key => $label )
                            {
                                ?>
                                <input id="<?php echo $name.$key?>" type="checkbox" value="<?php echo $key?>" name="<?php echo $name.(count($element['options']) > 1 ? '[]' : '')?>" <?php echo (in_array($key, (array) $element['value']) ? 'checked="checked"' : '')?> style="<?php echo $element['extra_css']?>">
                                <label for="<?php echo $name.$key?>"><?php echo $label?></label>
                                <?php
                            }
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
    }
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

        <?php
        if( $error_warning )
        {
            ?>
            <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo $error_warning; ?>
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
            <?php
        }
        ?>

        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa fa-pencil"></i> <?php echo $text_edit; ?></h3>
            </div>
            <div class="panel-body">
                <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form" class="form-horizontal">
                <?php
                renderSmart2PayFormElements( $form_elements, $error );
                ?>
                </form>
            </div>
        </div>
    </div>

</div>

<?php echo $footer; ?> 
