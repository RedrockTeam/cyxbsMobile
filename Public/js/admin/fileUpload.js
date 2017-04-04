var FormFileUpload = function () {
    return {
        //main function to initiate the module
        init: function (form) {
           
            var Form = $(form);

             // Initialize the jQuery File Upload widget:
            Form.fileupload({
                disableImageResize: false,
                autoUpload: false,
                disableImageResize: /Android(?!.*Chrome)|Opera/.test(window.navigator.userAgent),
                maxFileSize: 5000000,
                acceptFileTypes: /(\.|\/)(gif|jpe?g|png)$/i,
                // Uncomment the following to send cross-domain cookies:
                //xhrFields: {withCredentials: true},
                previewMaxWidth:50,
            });

            // Enable iframe cross-domain access via redirect option:
            Form.fileupload(
                'option',
                'redirect',
                window.location.href.replace(
                    /\/[^\/]*$/,
                    getUrl()+'Public/plugins/jquery-file-upload/cors/result.html?%s'
                )
            );

            // Upload server status check for browsers with CORS support:
            if ($.support.cors) {
                $.ajax({
                    type: 'HEAD'
                }).fail(function () {
                    $('<div class="alert alert-danger"/>')
                        .text('Upload server currently unavailable - ' +
                                new Date())
                        .appendTo(form);
                });
            }

            // Load & display existing files:
            Form.addClass('fileupload-processing');
            $.ajax({
                // Uncomment the following to send cross-domain cookies:
                //xhrFields: {withCredentials: true},
                url: Form.attr("action"),
                dataType: 'json',
                context: Form[0],
            }).always(function () {
                $(this).removeClass('fileupload-processing');
            }).done(function (result) {
                $(this).fileupload('option', 'done')
                .call(this, $.Event('done'), {result: result});
            });
        }

    };

}();

// jQuery(document).ready(function() {
//     FormFileUpload.init();
// });