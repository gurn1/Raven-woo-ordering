/**
 * ajax functions for Raven Prestashop to WooCommerce Migration Tool
 *
 * @since 1.0.0
 */


/**
 * Get categories as selected. 
 */
function get_categories($) {
    var container   = '#' + rvoObject.order_panel_name,
        $container  = $(container),
        orderTable  = $container.find('table.ordering-table'),
        siblingsid  = [];

    var loader = '<img class="loader rvo-inline-icon" src="' + rvoObject.loaderURL + '">',
        successIcon = '<span class="success-icon rvo-inline-icon dashicons dashicons-saved"></span>';
        errorIcon = '<span class="error-icon rvo-inline-icon dashicons dashicons-no-alt"></span>';

    window.$_GET    = new URLSearchParams(location.search);
    var postID      = $_GET.get('post');

    $('input[name^="tax_input"]').on('change', function() {
        // Set parent cateogories to checked
        $(this).parents('.children').each(function(){
            $(this).siblings('.selectit').find('input[name^="tax_input"]').prop('checked', true);
        });

        var siblings   = $('input[name^="tax_input"]').filter(':checked');

        // Overlay a loading screen
        if( ! $('.rvo-overlay')[0] ) {
            $container.append('<div class="rvo-overlay">'+loader+'</div>');
        }

        // Get ids
        $.each(siblings, function() {
            siblingsid.push( $(this).val() );
        });
    
        // Process data via ajax
        $.ajax( {
            type: 'POST',
            url: rvoObject.ajaxurl,
            data: {
                action: 'ajax_get_terms',
                siblings: siblingsid,
                post_id: postID
            },
            success: function(data) {
                
                // remove message
                if( $('.no-order-categories')[0] ){
                    $('.no-order-categories').remove();
                }

                // remove loader and add success notification
                $container.find('.loader').remove();
                $container.find('.rvo-overlay').append(successIcon);

                timer = setTimeout(function () {
                    //update data
                    orderTable.html(data);
                    // remove overlay
                    $container.find('.rvo-overlay').remove();
                }, 900);

            },
            error: function(jqXHR, exception) {
                error_report(jqXHR, exception);
            }
        } );

        // clear ids
        siblingsid = [];
    } );

}

/**
 *  Edit order on list page
 * 
 * @sicne 1.0.0
 */
 function inline_edit_buttons($) {

    var tableContainer  = $('.wp-list-table'),
        loader = '<img class="loader rvo-inline-icon" src="' + rvoObject.loaderURL + '">',
        successIcon = '<span class="success-icon rvo-inline-icon dashicons dashicons-saved"></span>';
        errorIcon = '<span class="error-icon rvo-inline-icon dashicons dashicons-no-alt"></span>';

    tableContainer.on('click', '.rvo-edit-post-order', function(e) {
        e.preventDefault();

        var column = $(this).closest('.column-rvo_multi_order');
            
        column.find(".rvo-result, .rvo-row-actions").hide();
        column.find(".rvo-edit-result, .rvo-inline-edit-save").show();
    } );

    tableContainer.on('click', '.rvo-cancel', function(e) {
        e.preventDefault();

        var column = $(this).closest('.column-rvo_multi_order');

        column.find(".rvo-result, .rvo-row-actions").show();
        column.find(".rvo-edit-result, .rvo-inline-edit-save").hide();
    });

    tableContainer.on('click', '.rvo-save', function(e) {
        e.preventDefault();

        var post_id = $(this).closest('tr').attr('id'),
            post_id = post_id.replace(/\D/g, ""),
            nonce = $(this).data('nonce');

        if( ! isNaN(post_id) ) {
            var values = $(this).closest('.column-rvo_multi_order').find("input[name^=_rvo-product-order]").map(function(idx, elem) {
                return {
                    id: $(elem).data('id'),
                    result: $(elem).val(),
                }
            }).get();
            
            var column = $(this).closest('.column-rvo_multi_order');

            column.append('<div class="rvo-overlay">'+loader+'</div>');
            
            $.ajax( {
                type: 'POST',
                url: rvoObject.ajaxurl,
                data: {
                    action: 'ajax_update_ordering_meta',
                    nonce: nonce,
                    post_id: post_id,
                    values: values,
                },
                success: function(data) {
                    column.find('.loader').remove();
                
                    if( data == true ) {
                        column.find('.rvo-overlay').append(successIcon);

                        $.each(values, function(key, value) {
                            column.find('.result-'+value.id).html(value.result);
                        });

                        timer = setTimeout(function () {
                            column.find('.rvo-overlay').remove();
                            column.find(".rvo-result, .rvo-row-actions").show();
                            column.find(".rvo-edit-result, .rvo-inline-edit-save").hide();
                        }, 900);
                    } else {
                        console.log(data);
                        column.find('.rvo-overlay').append(errorIcon);

                        timer = setTimeout(function () {
                            column.find('.rvo-overlay').remove();
                            column.find(".rvo-result, .rvo-row-actions").show();
                            column.find(".rvo-edit-result, .rvo-inline-edit-save").hide();
                        }, 1800);
                    }
                },
                error: function(jqXHR, exception) {
                    column.find('.rvo-overlay').append(errorIcon);

                    timer = setTimeout(function () {
                        column.find('.rvo-overlay').remove();
                        column.find(".rvo-result, .rvo-row-actions").show();
                        column.find(".rvo-edit-result, .rvo-inline-edit-save").hide();
                    }, 1800);

                    error_report(jqXHR, exception);
                    
                }
            } );
        }

    });
}

/**
 * Error tracking
 * 
 * @since 1.0.0
 */
function error_report(jqXHR, exception) {
    if (jqXHR.status === 0) {
        alert('Not connect.\n Verify Network.');
    } else if (jqXHR.status == 404) {
        alert('Requested page not found. [404]');
    } else if (jqXHR.status == 500) {
        alert('Internal Server Error [500].');
    } else if (exception === 'parsererror') {
        alert('Requested JSON parse failed.');
    } else if (exception === 'timeout') {
        alert('Time out error.');
    } else if (exception === 'abort') {
        alert('Ajax request aborted.');
    } else {
        alert('Uncaught Error.\n' + jqXHR.responseText);
    }
}

(function($) {
	
	$(document).ready(function() {

        get_categories($);

        inline_edit_buttons($);

    } );

} )( jQuery );