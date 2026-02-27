<?php
require_once './includes/db.php';
?>
<!DOCTYPE html>
<html lang="ko">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>GRUA</title>
        <style>
            body,
            html {
                margin: 0;
                padding: 0;
                width: 100%;
                height: 100%;
                background-color: rgb(0, 0, 0);
                overflow: hidden;
                position: relative;
                visibility: hidden;
            }
        </style>
    </head>
    <body>
        
        <div class="container">
            <div class="button" onclick="location.href='pages/main.php#/'"></div>
        </div>
        <script>
                        function adjustScale() {
                const container = document.querySelector('.container');
                    if (!container) 
                        return;
                    const windowWidth = window.innerWidth,
                        windowHeight = window.innerHeight;
                    let containerWidth,
                        containerHeight;
                    if (windowWidth <= 784) {
                        containerWidth = 720;
                        containerHeight = 1280;
                    } else {
                        containerWidth = 1440;
                        containerHeight = 900;
                    }
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

                setTimeout(() => {
                    document.querySelector('.container').style.opacity = '0';
                }, 1);

                setTimeout(() => {
                    window.location.href = 'pages/main.php';
                }, 2);
            });
            window.addEventListener('resize', adjustScale);
        </script>
    </body>
</html>