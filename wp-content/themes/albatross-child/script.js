var CityGuide = function() {
    var filters = {
        city: 0,
        category: 0,
        sort: 0
    }

    var pagination = {
        limit: 6,
        current: 1
    }

    var ajaxRequest = function() {
        jQuery.ajax({
            method: 'POST',
            url: 'http://wilsonairlines.com/wp-admin/admin-ajax.php',
            data: {
                action:     'the_list',
                filters,
                pagination
            },
            success: function(res) {
                jQuery(".item-list").html(res);
            }, 
            error: function(err) {
                console.log(err);
                alert('Error is occuered!');
            }
        });
    }

    return {
        setFilter: function( key, id, value ) {
            if( key == 'city' ) {
                jQuery("h4.city-name").text( value );
            }
            jQuery('.filter.' + key + ' li').removeClass('active');
            jQuery('.filter.' + key + ' li.' + key + id).addClass('active');
            filters[key] = id;
            CityGuide.goPage( 1 );
        },
        goPage: function( pageNum ) {
            pagination['current'] = pageNum;
            ajaxRequest( filters );
        },
        backToNav: function() {
            jQuery(".site-header .primary-menu-container").css("display", "block");
            jQuery(".site-header .signup-form").css("display", "none");
        },
        changeUrlToSignUpForm: function() {
            //jQuery("#menu-item-2545 a").attr("href", "javascript:CityGuide.signUpForm();");
        },
        signUpForm: function() {
            jQuery(".site-header .primary-menu-container").css("display", "none");
            jQuery(".site-header .signup-form").css("display", "block");
        }
    }
}();

jQuery(function() {
    if( !isLoggedIn ) {
        CityGuide.changeUrlToSignUpForm();
    }
});