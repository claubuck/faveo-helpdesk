<script src="{{asset("lb-faveo/plugins/select2/select2.full.min.js")}}" type="text/javascript"></script>
<script type="text/javascript">
    // Define addSelectlist as a jQuery plugin that wraps select2
    (function($) {
        $.fn.addSelectlist = function(options) {
            return this.each(function() {
                var $this = $(this);
                // Initialize select2 with the provided options
                $this.select2(options || {});
                return $this;
            });
        };
    })(jQuery);
</script>