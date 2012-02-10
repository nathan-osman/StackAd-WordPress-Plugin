/*
StackAd - Making it easy to display community ads on your blog.
Copyright (C) 2012  Nathan Osman

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

// This little shortcut allows us to use '$' within the context
// of this function while keeping it from polluting anything.
(function($) {
    
    $(document).ready(function() {
        
        $('.widget_stackad a').each(function() {
            
            // Create the popup <div>
            var popup_div = $('<div class="popup"><div>' + this.dataset['score'] + ' votes | <a href="' + this.dataset['link'] + '">vote</a><a href="' +  + '">question</a></div></div>');
            
            // Have it slide up
            $(this).hover(function() { popup_div.stop().animate({ 'top': (250 - popup_div.height()) + 'px' }, 'fast'); },
                          function() { popup_div.animate({ 'top': '250px' }, 'fast'); });
            
            // Insert it after the img
            $(this).append(popup_div);
            
        });
        
    });
    
})(jQuery);