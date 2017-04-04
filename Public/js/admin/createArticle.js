var createArticle = function() {
	

	return {
		init: function() {
	   var templets = {
      title: '<div class="form-group"><div class="input-icon"><i class="fa fa-file-o font-green"></i><input type="text" class="form-control" placeholder="标题" name="title"></div></div>',
      content: '<div class="form-group"><div class="input-icon><i class="fa fa-comment-o font-green"></i><textarea class="form-control" placeholder="想写点啥。。" name="content"></textarea></div></div>',
      keyword: '<div class="form-group"><div class="input-icon"><i class="fa fa-bullhorn font-green"></i><input type="text" class="form-control" placeholder="话题" name="keyword"></div></div>',
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
              if (has_photo) {
                // templet += templets.photo;
                var lastInput = that.closest(".form-group").nextAll().last();
                lastInput.after(templets.photo);
                var form = lastInput.next();
                var FormAttrbution = {
                  id: 'upload',
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
