window.onload = function() {
function deselect(e) {
    jQuery('.pop').slideFadeToggle(function() {
        e.removeClass('selected');
    });
}

jQuery(function() {
    jQuery('#contact').on('click', function() {
        if(jQuery(this).hasClass('selected')) {
            deselect(jQuery(this));
        } else {
            jQuery(this).addClass('selected');
            jQuery('.pop').slideFadeToggle();
        }
        return false;
    });

    jQuery('.close').on('click', function() {
        deselect(jQuery('#contact'));
        return false;
    });

    jQuery('#contact').on('blur', function() {
        deselect(jQuery(this));
    })
});

jQuery.fn.slideFadeToggle = function(easing, callback) {
    return this.animate({ opacity: 'toggle', height: 'toggle' }, 'fast', easing, callback);
};
}
