/**
 * @file
 * Attaches the behaviors for the Field UI module.
 */

(function ($) {

    "use strict";

    Drupal.behaviors.responsiveImageMappingPreview = {
        attach: function (context) {
            $('.responsive-image-mapping-breakpoint input[name$="[size]"]', context).each(function () {
                var $this = $(this);
                $this.on('change', function () {
                    var $trigger = $(this);
                    var $img = $trigger.parent().parent().find('img');
                    $img.width($trigger.val() + '%');
                });
            });
        }
    };

})(jQuery);

