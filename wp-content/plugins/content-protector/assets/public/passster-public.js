jQuery(document).ready(function( $ ) {

    // Passwords
    $('.passster-submit').on('click', function(e){
        e.preventDefault();

        ps_id      = $(this).attr('data-psid');
        form       = $( "#" + ps_id );
        password   = $( "#" + ps_id + ' .passster-password').attr('data-password');
        type       = $( "#" + ps_id + ' .passster-password').attr('data-protection-type');
        list       = $( "#" + ps_id + ' .passster-password').attr('data-list');
        partly     = $( "#" + ps_id + ' .passster-password').attr('data-partly');
        protection = $( "#" + ps_id + ' .passster-password').attr('data-protection');
        input      = $( "#" + ps_id + ' .passster-password').val();

        $.ajax({
            type: "post",
            dataType: "json",
            url: ps_ajax.ajax_url,
            data: { 'action': 'validate_input','nonce' : ps_ajax.nonce, 'input' : input, 'password' : password, 'post_id' : ps_ajax.post_id, 'type' : type, 'list' : list, 'partly' : partly, 'protection' : protection, 'elementor_content' : $(this).attr('data-elementor-content') },
            beforeSend: function() {
                form.find(".ps-loader").css('display', 'block');
            },
            success: function(response){
                form.find(".ps-loader").css('display', 'none');
                if ( true === response.success ) {
                      // if no ajax.
                      if ( 'on' === ps_ajax.no_ajax ) {
                        Cookies.set('passster', input, { expires: parseInt(  ps_ajax.days ) });
                        window.location.reload();
                    } else {
                        // set cookie if activated.
                        if ( 'on' === ps_ajax.use_cookie ) {
                            Cookies.set('passster', input, { expires: parseInt(  ps_ajax.days ) });
                        }
                        form.find('.passster-error').hide();

                        // replace shortcodes.
                        let content = response.content;

                        $.each(ps_ajax.shortcodes, function(key, value) {
                            content = content.replace( key, value );
                        });

                        $( "#" + ps_id ).replaceWith( content );
                    }
                } else {
                    form.find('.passster-error').text(response.error);
                    form.find('.passster-error').show();
                }
            }
        });
    });

    // Recaptcha v2
    if ( $('.recaptcha-form-v2').length > 0 ) {
        grecaptcha.ready(function() {
            grecaptcha.render('ps-recaptcha-v2', {
                'sitekey' : ps_ajax.recaptcha_key,
                'callback' : function( token ) {
                    ps_id      = $('.recaptcha-v2-submit').attr('data-psid');
                    form       = $( "#" + ps_id );
                    protection = $('.recaptcha-v2-submit').attr('data-protection');

                    $.ajax({
                        type: "post",
                        dataType: "json",
                        url: ps_ajax.ajax_url,
                        data: { 'action': 'validate_input','nonce' : ps_ajax.nonce, 'token' : token, 'post_id' : ps_ajax.post_id, 'type' : 'recaptcha', 'protection' : protection, 'elementor_content' : $('.recaptcha-v2-submit').attr('data-elementor-content'), 'captcha_id' : ps_id },
                        success: function(response){
                            // todo: set cookie if activated.
                            if ( true === response.success ) {
                                // if no ajax.
                                if ( 'on' === ps_ajax.no_ajax ) {
                                    Cookies.set('passster', 'recaptcha', { expires: parseInt(  ps_ajax.days ) });
                                    window.location.reload();
                                } else {
                                    // set cookie if activated.
                                    if ( 'on' === ps_ajax.use_cookie ) {
                                        Cookies.set('passster', 'recaptcha', { expires: parseInt(  ps_ajax.days ) });
                                    }
                                    form.find('.passster-error').hide();
                                    
                                    // replace shortcodes.
                                    let content = response.content;

                                    $.each(ps_ajax.shortcodes, function(key, value) {
                                        content = content.replace( key, value );
                                    });

                                    ( "#" + ps_id ).replaceWith( content );
                                }
                            } else {
                                form.find('.passster-error').text(response.error);
                                form.find('.passster-error').show();
                            }
                        }
                    });
                }
            });
        });
    }

    // ReCaptcha v3
    $('.recaptcha-form').submit(function(event) {
        event.preventDefault();

        ps_id      = $(this).find('.passster-submit-recaptcha').attr('data-psid');
        form       = $( "#" + ps_id );
        protection = $(this).find('.passster-submit-recaptcha').attr('data-protection');

        grecaptcha.ready(function() {
            grecaptcha.execute(ps_ajax.recaptcha_key, {action: 'validate_input'}).then(function(token) {
   
                form.prepend('<input type="hidden" name="token" value="' + token + '">');
                form.prepend('<input type="hidden" name="action" value="validate_input">');

                $.ajax({
                    type: "post",
                    dataType: "json",
                    url: ps_ajax.ajax_url,
                    data: { 'action': 'validate_input','nonce' : ps_ajax.nonce, 'token' : token, 'post_id' : ps_ajax.post_id, 'type' : 'recaptcha', 'protection' : protection,  'elementor_content' : form.find('.passster-submit-recaptcha').attr('data-elementor-content'), 'captcha_id' : ps_id },
                    success: function(response){
                        // todo: set cookie if activated.
                        if ( true === response.success ) {
                            // if no ajax.
                            if ( 'on' === ps_ajax.no_ajax ) {
                                Cookies.set('passster', 'recaptcha', { expires: parseInt(  ps_ajax.days ) });
                                window.location.reload();
                            } else {
                                // set cookie if activated.
                                if ( 'on' === ps_ajax.use_cookie ) {
                                    Cookies.set('passster', 'recaptcha', { expires: parseInt(  ps_ajax.days ) });
                                }
                                form.find('.passster-error').hide();
                                // replace shortcodes.
                                let content = response.content;

                                $.each(ps_ajax.shortcodes, function(key, value) {
                                    content = content.replace( key, value );
                                });

                                form.replaceWith( content );
                            }
                        } else {
                            form.find('.passster-error').text(response.error);
                            form.find('.passster-error').show();
                        }
                    }
                });
            });
        });
    });

     // Captcha
     if ( $('.passster-captcha-input').length > 0 ) {
        var captcha = new jCaptcha({
            el: '.passster-captcha-input',
            canvasClass: 'jCaptchaCanvas',
            canvasStyle: {
                // properties for captcha stylings
                width: 100,
                height: 25,
                textBaseline: 'top',
                font: '22px Arial',
                textAlign: 'left',
                fillStyle: '#000',
            },
            'requiredValue' : '',
            callback: function(response ) {
                if (response == 'success') {
                    ps_id      = $('.passster-submit-captcha').attr('data-psid');
                    form       = $( "#" + ps_id );
                    protection = $('.passster-submit-captcha').attr('data-protection');

                    $.ajax({
                        type: "post",
                        dataType: "json",
                        url: ps_ajax.ajax_url,
                        data: { 'action': 'validate_input','nonce' : ps_ajax.nonce, 'captcha' : 'success', 'post_id' : ps_ajax.post_id, 'type' : 'captcha', 'protection' : protection, 'elementor_content' : $('.passster-submit-captcha').attr('data-elementor-content'), 'captcha_id' : ps_id },
                        success: function(response){
                            if ( true === response.success ) {
                                // if no ajax.
                                if ( 'on' === ps_ajax.no_ajax ) {
                                    Cookies.set('passster', 'captcha', { expires: parseInt(  ps_ajax.days ) });
                                    window.location.reload();
                                } else {
                                    // set cookie if activated.
                                    if ( 'on' === ps_ajax.use_cookie ) {
                                        Cookies.set('passster', 'captcha', { expires: parseInt(  ps_ajax.days ) });
                                    }
                                    form.find('.passster-error').hide();
                                    // replace shortcodes.
                                    let content = response.content;

                                    $.each(ps_ajax.shortcodes, function(key, value) {
                                        content = content.replace( key, value );
                                    });

                                    $( "#" + ps_id ).replaceWith( content );
                                }
                            } else {
                                form.find('.passster-error').text(response.error);
                                form.find('.passster-error').show();
                            }
                        }
                    });
                }

                if (response == 'error') {
                    form.find('.passster-error').text(ps_ajax.captcha_error);
                    form.find('.passster-error').show();
                }
        } 
        });

        document.querySelector('.captcha-form').addEventListener('submit', function(e){
            e.preventDefault();
            captcha.validate()
        });
    }
});