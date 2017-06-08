// 表单验证js
$(function() {
    $('#form-edit').validate({
        rules: {
            title: {
                required: true,
                remote: {
                    url:  $('#input-title').data('url'),
                    data: {
                        id: $('#form-edit').data('id'),
                    }
                }
            },
            site: {
                url: true,
            },
            sort_number: {
                digits: true,
            }
        },
        messages: {
            title: {
                required: '名称必须',
                remote: '名称已经存在'
            },
            site: {
                url: 'URL地址不正确'
            },
            sort_number: {
                digits: '请使用数值排序',
            }
        },
        errorClass: 'text-danger',
    });
});
