<?php
    echo $header;
    echo $column_left;
    echo $column_right;

function form_esc( $str )
{
    return str_replace( '"', '&quot;', $str );
}
?>
<div id="content">
<?php
if( !empty( $error_warning ) )
{
    ?>
    <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo $error_warning; ?>
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
    <?php
} else
{
?>
    <div style="min-height: 800px;">

        <?php echo $content_top; ?>

        <form id="s2pform" method="post" action="<?php echo $settings['smart2pay_post_url'] ?>" <?php echo( !empty( $settings['smart2pay_redirect_in_iframe'] ) ? 'target="merchantIframe"' : '' ) ?>>
        <?php
        if( !empty( $settings['smart2pay_debug_form'] ) )
        {
            ?>
            <p><b>Message to hash</b>: <?php echo $string_to_hash; ?></p>
            <p><b>Hash</b>: <?php echo $payment_data['Hash']; ?></p>
            <table>
                <?php
                    if( !empty( $payment_data ) and is_array( $payment_data ) )
                    {
                        foreach( $payment_data as $key => $val )
                        {
                            ?>
                            <tr>
                                <td><?php echo $key ?></td>
                                <td>
                                    <input type="text" name="<?php echo form_esc( $key ) ?>" value="<?php echo form_esc( $val ) ?>" style="width: 400px"/>
                                </td>
                            </tr>
                            <?php
                        }
                    }
                ?>
                <tr>
                    <td colspan="2">
                        <input type="submit" value="Submit"/>
                    </td>
                </tr>
            </table>
            <?php
        }
        else
        {
            if( !empty( $payment_data ) and is_array( $payment_data ) )
            {
                foreach( $payment_data as $key => $val )
                {
                    ?><input type="hidden" name="<?php echo form_esc( $key ) ?>" value="<?php echo form_esc( $val ) ?>" /><?php
                }
            }
        }
        ?>
        </form>

    </div>


    <div id="iframe-container" style="display: none; position: absolute; top: 0px; left: 0px; width: 100%; height: 100%; z-index: 1000;">
        <div style="position: relative; width: 100%; height: 100%;">
            <div style="position: absolute; top: 0px; left: 0px; width: 100%; height: 100%; background: #333; opacity: 0.5; filter:alpha(opacity=50)"></div>
            <div style="position: absolute; top: 0px; left: 0px; width: 100%; height: 100%;">
                <div style="display: table; margin: 0px auto; margin-top: 50px;">
                <?php
                if( !empty( $settings['smart2pay_redirect_in_iframe'] )
                and !empty( $settings['smart2pay_skip_payment_page'] )
                and in_array( $payment_data['MethodID'], array( 1001, 1002 ) ) )
                {
                    ?><iframe style='border: none; margin: 0px auto; background-color: #ffffff;' id="merchantIframe" name="merchantIframe" src="" width="780" height="500"></iframe><?php
                }
                else
                {
                    ?><iframe style='border: none; margin: 0px auto; background-color: transparent;' id="merchantIframe" name="merchantIframe" src="" width="900" height="800"></iframe><?php
                }
                ?>
                </div>
            </div>
        </div>
    </div>

    <script type="text/javascript">

    function modalIframe()
    {
        var container_obj = jQuery( "#iframe-container" );
        if( container_obj )
        {
            container_obj.css( { height: jQuery( 'body' ).height() } );
            container_obj.appendTo( 'body' );
            container_obj.show();
        }
    }

    jQuery( document ).ready( function()
    {
        jQuery( '#s2pform' ).submit( function() {
             modalIframe();
        });

        // autosend form if needed
        <?php
        if( !empty( $settings['smart2pay_debug_form'] ) )
        {
            ?>jQuery( "#s2pform" ).submit();<?php
        }
        ?>

        // get/parse smart2pay message
        var onmessage = function( e )
        {
            if( console )
                console.log( e );

            if( e.data == 'close_HPP' )
                setTimeout( function() { jQuery( 'iframe#merchantIframe' ).remove() }, 300 );

            else if( e.data.substring( 0, 7 ) == "height=" )
            {
                var iframe_height = e.data.substring( 7 );
                jQuery( 'iframe#merchantIframe' ).attr( 'height', parseInt( iframe_height ) + 300 );
                if( console )
                    console.log( "jQuery('iframe#merchantIframe').attr('height'," + (parseInt( iframe_height ) + 300) + ");" );
            } else if( e.data.substring( 0, 6 ) == "width=" )
            {
                var iframe_width = e.data.substring( 6 );
                jQuery( 'iframe#merchantIframe' ).attr( 'width', parseInt( iframe_width ) + 100 );
                if( console )
                    console.log( "jQuery('iframe#merchantIframe').attr('width'," + (parseInt( iframe_width ) + 100) + ");" );
            } else if( e.data.substring( 0, 12 ) == "redirectURL=" )
            {
                window.location = e.data.substring( 12 );
            }
        };

        // set event listener for smart2pay
        if( typeof window.addEventListener != 'undefined' )
            window.addEventListener( 'message', onmessage, false );
        else if( typeof window.attachEvent != 'undefined' )
            window.attachEvent( 'onmessage', onmessage );
    } );
    </script>
    <div>
<?php
}

    echo $footer;
