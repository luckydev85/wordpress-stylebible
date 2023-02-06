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
            url: ajaxUrl,
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
		init: function() {
			if( !isLoggedIn ) {
				jQuery("#access_guide").attr("href", "javascript:CityGuide.openMenu();");
			}
		},
		openMenu: function() {
			jQuery(".scroll-to-top-button").click();
			jQuery("#header-dropdown-toggle").click();
			CityGuide.signUpForm();
		},
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
            jQuery(".site-header .primary-menu-container").removeClass("display-none");
            jQuery(".site-header .signup-form").addClass("display-none");
        },
        signUpForm: function() {
            jQuery(".site-header .primary-menu-container").addClass("display-none");
            jQuery(".site-header .signup-form").removeClass("display-none");
        }
    }
}();

jQuery(document).ready(function(){
	CityGuide.init();
});