(function($) {
    var WPG = function(args) {

        var self = this;

        self.element = null;
        self.content_element = null;
        self.close_button = null;

        self.timeout_object = null;
        self.close_timeout = 5000;
        self.show_timeout = 5000;
        self.limit = 1;
        self.current_tick = 0;

        self.ajax_url = args.ajax_url;
        self.product_id = args.product_id;

        self.init();
    }

    WPG.prototype = {

        init : function() 
        {
            var self = this;

            // build growl dom-object & append to <body>
            self.element = $('<div>').attr('id', 'wpg').css({'display' : 'block', 'bottom' : '-100px'});
            self.content_element = $('<span>');
            self.close_button = $('<a>').attr({'id' : 'wpg-close', 'href' : '#'}).html('x');

            $(self.element).append(self.close_button).append(self.content_element);
            $(self.close_button).click(function(e) {
                self.hide();
                e.preventDefault();
            })
            $('body').append(self.element);


            self.tick();
        },

        tick : function()
        {
            var self = this;

            if (self.current_tick < self.limit) {
                self.timeout_object = setTimeout(function() {
                    self.show();
                    setTimeout(function() {
                        self.hide();
                    }, self.show_timeout);
                    clearTimeout(self.timeout_object);
                    self.tick();
                }, self.close_timeout);

                self.current_tick++;
            }
        },

        show : function(type)
        {
            var self = this;
            
            $.get(self.ajax_url, {'action' : 'get_view_count', 'product_id' : self.product_id}, function(data) {
                if (data.status) {

                    var message = data.number_of_views + ' person';
                    if (data.number_of_views > 1) {
                        message += 'er';
                    }

                    message += ' tittade på denna produkt de senaste dagarna';

                    $(self.content_element).html(message);
                    $(self.element).show().animate({
                        'bottom' : '20px'
                    }, 'fast');
                }
            }, 'json');

        },

        hide : function()
        {
            var self = this;

            $(self.element).animate({
                'bottom' : '-100px'
            }, 'fast', function() {
                $(self.element).hide();
            });
        }
    };

    window.WPG = WPG;

}(jQuery));

jQuery(document).ready(function() {
    var wpg = new WPG(wpg_ajax_data);
})