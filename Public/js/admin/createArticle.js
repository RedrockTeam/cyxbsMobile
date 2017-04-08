var createArticle = function() {
	

	return {
		init: function() {
	   var templets = {
      title: '<div class="form-group"><div class="input-icon"><i class="fa fa-file-o font-green"></i><input type="text" class="form-control" placeholder="标题" name="title"></div></div>',
      content: '<div class="form-group"><div class="input-icon><i class="fa fa-comment-o font-green"></i><input class="form-control" placeholder="想写点啥。。" name="content"></div></div>',
      keyword: '<div class="form-group"><div class="input-icon"><i class="fa fa-bullhorn font-green"></i><input type="text" class="form-control" placeholder="话题" name="keyword"></div></div>',
      official: '<div class="form-group"> <input type="checkbox" class="make-switch"  name="is_official" data-on-text="官方" data-off-text="个人" data-on-color="warning" data-off-color="danger"></div>',
      photo:    '<form>' 
                  + '<div class="form-group">'
                  +'<div class="row fileupload-buttonbar" style="margin-left: 0px;">'
                      +'<span class="btn green fileinput-button btn-file">'
                           +'<i class="fa fa-plus"></i>'
                           +'<span> 添加 </span>'
                           +'<input type="file" name="fold[]" multiple=""> </span>'
                       +'<button type="submit" class="btn blue start">'
                           +'<i class="fa fa-upload"></i>'
                           +'<span> 开始上传 </span>'
                       +'</button>'
                       +'<button type="reset" class="btn warning cancel">'
                           +'<i class="fa fa-ban-circle"></i>'
                           +'<span> 停止上传 </span>'
                       +'</button>'
                       +'<button type="button" class="btn red delete">'
                           +'<i class="fa fa-trash"></i>'
                           +'<span> 删除 </span>'
                       +'</button>'
                       // +'<input type="checkbox" class="toggle">'
                       // +'<span class="fileupload-process"> </span>'
                   +'</div>'
                   +'<div class="col-lg-5 fileupload-progress fade">'
                       +'<div class="progress progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100">'
                           +'<div class="progress-bar progress-bar-success" style="width:0%;"> </div>'
                       +'</div>'
                       +'<div class="progress-extended"> &nbsp; </div>'
                   +'</div></div>\
                  <table role="presentation" class="table table-striped"><tbody class="files"></tbody></table>\
                   </form>'
      +'<script id="template-upload" type="text/x-tmpl">\
              {% for (var i=0, file; file=o.files[i]; i++) { %}\
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
              {% } %}\
              </script>\
              <!-- The template to display files available for download -->\
              <script id="template-download" type="text/x-tmpl">\
              {% for (var i=0, file; file=o.files[i]; i++) { %}\
                  <tr class="template-download fade" style="width:490px;" >\
                      <td>\
                          <span class="preview">\
                              {% if (file.thumbnailUrl) { %}\
                                  <a href="{%=file.url%}" title="{%=file.name%}" download="{%=file.name%}" data-gallery><img src="{%=file.thumbnailUrl%}"></a>\
                              {% } %}\
                          </span>\
                      </td>\
                      <td>\
                          <p class="name">\
                              {% if (file.url) { %}\
                                  <a href="{%=file.url%}" title="{%=file.name%}" download="{%=file.name%}" {%=file.thumbnailUrl?\'data-gallery\':\'\'%}>{%=file.name.substr(0,8)%}</a>\
                              {% } else { %}\
                                  <span>{%=file.name.substr(0,8)%}</span>\
                              {% } %}\
                          </p>\
                          {% if (file.error) { %}\
                              <div><span class="label label-danger">Error</span> {%=file.error%}</div>\
                          {% } %}\
                      </td>\
                      <td>\
                          <span class="size">{%=o.formatFileSize(file.size)%}</span>\
                      </td>\
                      <td>\
                          {% if (file.deleteUrl) { %}\
                              <button class="btn btn-danger delete" data-type="{%=file.deleteType%}" data-url="{%=file.deleteUrl%}"{% if (file.deleteWithCredentials) { %} data-xhr-fields=\'{"withCredentials":true}\'{% } %}>\
                                  <i class="glyphicon glyphicon-trash"></i>\
                                  <span>删除</span>\
                              </button>\
                          {% } else { %}\
                              <button class="btn btn-warning cancel">\
                                  <i class="glyphicon glyphicon-ban-circle"></i>\
                                  <span>取消</span>\
                              </button>\
                          {% } %}\
                      </td>\
                      \
                  </tr>\
              {% } %}\
        </script>'

     };
   		$("[name='article_type']").on('change', function(){
        var that = $(this);
   			$.ajax({
   				url: getUrl()+'/Admin/Article/getWriteTemplet',
   				type: 'get',
   				data: connection.encode({type_id: this.value}),
   				dataType: 'text',
   				success: function(data) {
   					data = connection.decode(data);
   					if (data.status === 200) {
   						var templet = '';
   						var has_photo = false;
   						$.each(data.data, function(key, value){
                  if (value === 'photo')
                    has_photo = true;
                  else
                    templet += templets[value];
              });            
              
              
              that.closest('.form-group').nextAll().remove();
              that.closest('.form-group').after(templet);
              $('.make-switch').bootstrapSwitch();
              if (has_photo) {
                // templet += templets.photo;
                var lastInput = that.closest(".form-group").nextAll().last();
                lastInput.after(templets.photo);
                var form = lastInput.next();
                var FormAttrbution = {
                  id: 'uploadArticlePhoto',
                  action: getUrl()+'/Home/Photo/multipleUploadArticle',
                  method: 'post',
                };
                form.attr(FormAttrbution);
                FormFileUpload.init('#'+ form.attr('id'));
              }               
              
   					}
   				},
   			});
      });

	}
}
}();
