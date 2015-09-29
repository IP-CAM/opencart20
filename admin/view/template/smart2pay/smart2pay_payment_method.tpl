<?php

    echo $header;
    echo $column_left;
?>
<div id="content">
    <div class="page-header">
        <div class="container-fluid">

            <h1><?php echo $heading_title; ?></h1>

            <ul class="breadcrumb">
                <?php foreach ($breadcrumbs as $breadcrumb) { ?>
                <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
                <?php } ?>
            </ul>
        </div>
    </div>

    <div class="container-fluid">

        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa fa-pencil"></i> <?php echo $text_edit; ?></h3>
            </div>
            <div class="panel-body">

                <p style="text-align: center;">In order to be easier to edit settings for all Smart2Pay payment methods we unified all payment method settings in a single interface.</p>
                <p style="text-align: center;"><a href="<?php echo $go_to_payment_methods_link?>" class="btn btn-primary"><i class="fa fa-cog"></i> <?php echo $go_to_payment_methods_tab?> </a></p>

            </div>
        </div>
    </div>

</div>

<?php echo $footer; ?> 
