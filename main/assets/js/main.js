$(document).ready(function() {
    const contentContainer = $('#content');


    function loadPage(url) {
        $.ajax({
            url: url, type: 'GET',
            success: (response) => contentContainer.html(response),
            error: () => contentContainer.html('<h1>페이지를 불러올 수 없습니다.</h1>')
        });
    }
    
    function loadChat() {
        $.ajax({
            url: 'chat.php',
            type: 'GET',
            success: function(response) {
                chatOverlay.html(response).show();
            }
        });
    }

    function router() {
        const hash = window.location.hash.substring(2) || 'main_content';
        const [page, params] = hash.split('?');
        
        if (page === 'black') {
            $('nav').css('display', 'none');
            $('.main_title').css('display', 'none');
            $('.main_slog').css('display', 'none');
            $('.main_chat').css('display', 'none');
            $('.main_login').css('display', 'none');
            $('.container').css('background-color', '#0a0a0a');
            $('body').css('background-color', '#0a0a0a');
        } else {
            $('nav').css('display', 'flex');
            $('.main_title').css('display', 'flex');
            $('.main_slog').css('display', 'flex');
            $('.main_chat').css('display', 'flex');
            $('.main_login').css('display', 'flex');
            $('.container').css('background-color', '#FAF4E2');
            $('body').css('background-color', '#FAF4E2');
        }

        const url = `${page}.php${params ? '?' + params : ''}`;
        loadPage(url);
    }


    $(window).on('hashchange', router);
    router();

    $(document).on('click', '#chat-overlay .chat-header', function() {
        chatOverlay.hide();
        window.location.hash = '#/main_content';
    });


    window.uploadSummernoteImage = function(file, editor) {
        return new Promise((resolve, reject) => {
            let data = new FormData();
            data.append("file", file);
            
            $.ajax({
                url: 'ajax_upload_image.php',
                type: "POST", data: data,
                contentType: false, processData: false, dataType: 'json',
                success: function(response) {
                    if (response.success && response.url) {
                        let imgNode = document.createElement('img');
                        imgNode.src = response.url;
                        imgNode.style.width = '100%';
                        
                        $(editor).summernote('insertNode', imgNode);
                        
                        let br = document.createElement('br');
                        $(editor).summernote('insertNode', br);
                        
                        resolve(response.url); 
                    } else {
                        alert('이미지 업로드 실패: ' + (response.message || '알 수 없는 오류'));
                        reject(response.message);
                    }
                },
                error: function() {
                    alert('이미지 업로드 중 서버 오류가 발생했습니다.');
                    reject('error');
                }
            });
        });
    };

    //전송
    $(document).on('submit', 'form.ajax-form', function(e) {
        e.preventDefault();
        const form = $(this);
        const formData = new FormData(this);

        if (form.find('.summernote').length) {
            formData.set('content', form.find('.summernote').summernote('code'));
        }

        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: formData, 
            processData: false,
            contentType: false,
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    // alert(response.message || '성공적으로 처리되었습니다.');
                    if (response.redirect_url === 'reload') {
                        window.location.reload();
                    } else if (response.redirect_url) {
                        window.location.hash = response.redirect_url;
                    } else {
                        router();
                    }
                } else {
                    alert('오류: ' + response.message);
                }
            },
            error: () => alert('요청 처리 중 오류가 발생했습니다.')
        });
    });
    
//삭제
    $(document).on('click', '.delete-btn', function() {
        if (!confirm('정말로 삭제하시겠습니까?')) return;

        const id = $(this).data('id');
        const tableName = $(this).data('table');
        const returnPage = $(this).data('return');

        $.ajax({
            url: 'ajax_delete_gallery.php',
            type: 'POST',
            data: { id: id, table_name: tableName },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    alert('삭제되었습니다.');
                    window.location.hash = `#/${returnPage}`;
                } else {
                    alert('삭제 실패: ' + response.message);
                }
            },
            error: function() {
                alert('삭제 요청 중 서버 오류가 발생했습니다.');
            }
        });
    });
    


});


//크기조절!
function adjustScale() {
    const container = document.querySelector('.container');
        if (!container) 
            return;
        const windowWidth = window.innerWidth,
            windowHeight = window.innerHeight;
        let containerWidth,
            containerHeight;
            containerWidth = 1440;
            containerHeight = 900;
        const scale = Math.min(
            windowWidth / containerWidth,
            windowHeight / containerHeight
        );
        container.style.transform = `scale(${scale})`;
        container.style.left = `${ (windowWidth - containerWidth * scale) / 2}px`;
        container.style.top = `${ (windowHeight - containerHeight * scale) / 2}px`;
    }

window.addEventListener('load', () => {
    adjustScale();
    document.body.style.visibility = 'visible';
});
window.addEventListener('resize', adjustScale);