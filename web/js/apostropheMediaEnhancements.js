if (!window.apostrophe)
{
    window.apostrophe = new aConstructor();
}
appendMediaEnhancements(window.apostrophe);

function appendMediaEnhancements(apostrophe)
{
    //
    // File upload enhancements
    //
    apostrophe.fileUploader = function(defaults) {
        var defaults = $.extend({
            'fileUploaderSelector': 'a-file-uploader',
            'fileListSelector': 'a-file-upload-list'
        }, defaults);


        var $selector = (defaults.selector)? $(defaults.selector) : $('.' + defaults.fileUploaderSelector);
        var $uploadList = (defaults.uploadListSelector)? $(defauls.uploadListSelector) : $('.' + defaults.fileListSelector);

        function combine(fn1, fn2, e, file)
        {
            if (typeof(fn2) == 'function') {
                if (typeof(fn1) == 'function') {
                    return function(e, file) {fn1(e, file); fn2(e, file);};
                }
                return fn2;
            }
            return fn1;
        }

        function findThumb(file)
        {
            return $('div[data-filename="' + escape(file.name) + '"][data-count="' + file._uploadCount + '"]');
        }

        $selector.each(function() {
            var $this = $(this);
            var options = $.extend({}, defaults);

            // default drag handlers
            options.dragenter = combine(function(e) {
                $this.addClass('drag-over');
            }, options.dragenter);
            options.dragover = combine(function(e) {
                $this.addClass('drag-over');
            }, options.dragover);
            options.dragleave = combine(function(e) {
                $this.removeClass('drag-over');
            }, options.dragleave);
            options.dragend = combine(function(e) {
                $this.removeClass('drag-over');
            }, options.dragend);
            options.drop = combine(function(e) {
                $this.removeClass('drag-over');
                $this.removeClass('drag-over');
            }, options.drop);

            // file upload handlers
            options.beforeHandle = combine(function(e, file) {
                var type = file.type.split('/');
                var $thumb = $(_.template($('#a-tmpl-media-thumb').text(), {}));
                $thumb.addClass(type[1]);
                $thumb.attr('data-filename', escape(file.name));
                $thumb.attr('data-count', file._uploadCount);

                $uploadList.append($thumb);
            }, options.beforeHandle);

            options.onload = combine(function(e, file) {
                var type = file.type.split('/');
                var $thumb = findThumb(file);

                // add thumbnail if available
                if (type[0] == 'image') {
                    if (window.FileReader) {
                        var $img = $(_.template($('#a-tmpl-media-upload-thumb').text(), {}));

                        var i = new Image;
                        i.src = e.target.result;
                        i.onload = function()
                        {
                            var width = i.width * 100 / i.height;
                            var height = i.height * width / i.width;
                            var canvas = document.createElement("canvas");
                            var context = canvas.getContext("2d");

                            canvas.width = width;
                            canvas.height = height;
                            context.drawImage(i, 0, 0, i.width, i.height, 0, 0, width, height)

                            $img.attr('src', canvas.toDataURL('image/png'));

                            $thumb.each(function() {
                               // Prevent duplicate thumbs
                               if ($(this).find('img').length == 0)
                               {
                                    $(this).append($img);
                               }
                            });
                        }
                    }
                }
            }, options.onload);

            options.ajaxTransferSuccess = combine(function(data, file) {
               var $thumb = findThumb(file);
               data = $.parseJSON(data);

               if (data.status == 'success') {
                    // Change thumbnail class
                    $thumb.addClass('done');
                    $thumb.attr('data-item-id', data.id);

                    // Add title text
                    var params = {};
                    params.item_title = data.item.title;
                    params.view_url = data.viewUrl;
                    params.edit_url = data.editUrl;
                    params.delete_url = data.deleteUrl;
                    var $title = $(_.template($('#a-tmpl-media-upload-title').text(), params));
                    $thumb.append($title);
               }

            }, options.ajaxTransferSuccess);

            $this.aFileUploader(options);
        });
    }
}