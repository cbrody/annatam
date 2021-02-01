jQuery(document).ready(function( $ ) {

    // page restriction conditional fields.
    var ps_selected = $('#passster-protection-type').find('option:selected').val();
    ps_show_selected(ps_selected);

    $('#passster-protection-type').on('change', function(){
        value = $(this).find('option:selected').val();
        ps_show_selected(value);
  
    });

    function ps_show_selected( value ) {
        if ('password' === value ) {
            $('#passster-password').parent().show();
        } else {
            $('#passster-password').parent().hide();
        }

        if ('passwords' === value ) {
            $('#passster-passwords').parent().show();
        } else {
            $('#passster-passwords').parent().hide();
        }

        if ('password_list' === value ) {
            $('#passster-password-list').parent().show();
        } else {
            $('#passster-password-list').parent().hide();
        }

        if ('api' === value ) {
            $('#passster-api').parent().show();
        } else {
            $('#passster-api').parent().hide();
        }
    }

	/* premium indicator */
    $("input.premium").attr('disabled', 'disabled');
    let td = $("input.premium").parent();
    $(td).append('<span class="pro">PRO</span>');
    
    // toggle admin notice if clicked.
    $( '.passster-notice button.notice-dismiss' ).on( 'click', function() {
        var data = { 'action': 'passster_dismiss_notice' };

        $.post( ps_admin_ajax.ajax_url, data, function( response ) {
        });
    });
});

