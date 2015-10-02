<?php
    echo $header;
    echo $column_left;
    echo $column_right;
?>

<div id="content">
    <?php echo $content_top; ?>
    <div class="text-center" style="margin-top: 20px; margin-bottom: 20px;">
        <?php
        if( !empty( $error_warning ) )
        {
            ?>
            <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo $error_warning; ?>
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
            <?php
        }

        if( !empty( $feedback ) )
            echo $feedback;
        ?>
        <div class="buttons text-center" style="text-align: center;">
            <p>&nbsp;</p>
            <p class="text-center"><?php echo sprintf( $lang['redirect_secs'], 5, $redirect )?></p>
            <p>&nbsp;</p>
            <a class="btn btn-primary" href="<?php echo $redirect; ?>"><?php echo $lang['continue']?></a>
        </div>
    </div>
</div>
<script type="text/javascript">
jQuery(document ).ready(function(){
    setTimeout( function(){ document.location = '<?php echo $redirect; ?>'; }, 5000 );
});
</script>
<?php
    echo $footer;
