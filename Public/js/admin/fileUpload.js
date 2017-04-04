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
                uploadTemplateId: null,
                downloadTemplateId: null,
                previewMaxWidth:50,
                uploadTemplate:' {% for (var i=0, file; file=o.files[i]; i++) { %}\
                  <tr class="template-upload fade">\
                      <td>\
                          <span class="preview"></span>\
                      </td>\
                      <td>\
                          <p class="name">{%=file.name.substr(0,8)%}</p>\
                          <strong class="error text-danger"></strong>\
                      </td>\
                      <td>\
                          <p class="size">加载中</p>\
                          <div class="progress progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><div class="progress-bar progress-bar-success" style="width:0%;"></div></div>\
                      </td>\
                      <td>\
                          {% if (!i && !o.options.autoUpload) { %}\
                              <button class="btn btn-primary start" disabled>\
                                  <i class="glyphicon glyphicon-upload"></i>\
                                  <span>开始</span>\
                              </button>\
                          {% } %}\
                          {% if (!i) { %}\
                              <button class="btn btn-warning cancel">\
                                  <i class="glyphicon glyphicon-ban-circle"></i>\
                                  <span>取消</span>\
                              </button>\
                          {% } %}\
                      </td>\
                  </tr>\
              {% } %}',
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