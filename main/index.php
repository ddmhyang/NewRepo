<?php
require_once './includes/db.php';
?>
<!DOCTYPE html>
<html lang="ko">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>title</title>
        <style>
            @font-face {
                font-family: 'Fre1';
                src: url("assets/fonts/Freesentation-1Thin.ttf") format('truetype');
                font-weight: normal;
                font-style: normal;
            }

            @font-face {
                font-family: 'Fre3';
                src: url("assets/fonts/Freesentation-3Light.ttf") format('truetype');
                font-weight: normal;
                font-style: normal;
            }

            @font-face {
                font-family: 'Fre5';
                src: url("assets/fonts/Freesentation-5Medium.ttf") format('truetype');
                font-weight: normal;
                font-style: normal;
            }

            @font-face {
                font-family: 'Fre9';
                src: url("assets/fonts/Freesentation-9Black.ttf") format('truetype');
                font-weight: normal;
                font-style: normal;
            }

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
                font-family: 'S-CoreDream-3Light', sans-serif;
            }

            .container {
                width: 1440px;
                height: 960px;
                flex-shrink: 0;
                aspect-ratio: 3/2;
                background: url("/assets/img/back.jpg") no-repeat center center;
                background-size: cover;
                background-color: #000000;
                transform-origin: top left;
                position: absolute;
                transform: scale(0);
            }

            .container,
            body,
            html {
                transition: background-color 1s ease-in-out;
            }

            .logo{
                position: absolute;
                top: 58px;
                left: 121px;
                width: 230px;
                height: 91px;
                flex-shrink: 0;
                aspect-ratio: 230/91;
                background: url('assets/img/logo.png') center center / cover no-repeat;
                cursor: pointer;
            }

            .index_box{
                position: absolute;
                top: 199px;
                left: 50%;
                transform: translateX(-50%);
                width: 926px;
                height: 569px;
                flex-shrink: 0;
                border-radius: 30px;
                border: 5px solid #7078A7;
                background: rgba(255, 255, 255, 0.70);
            }

            .index_box > a{
                position: absolute;
                top: 460px;
                left: 50%;
                transform: translateX(-50%);
                color: #7078A7;
                text-align: center;
                font-family: 'Fre9';
                font-size: 32px;
                font-style: normal;
                line-height: normal;
            }

            .index_button{
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                width: 90%;
                height: 90%;
                background: url("/assets/img/back.jpg") no-repeat center center;
                background-size: contain;
            }

            @media (max-width: 768px) {
                .container {
                    width: 720px;
                    height: 1280px;
                    flex-shrink: 0;
                    background: url("/assets/img/back.jpg") no-repeat center center;
                    background-size: cover;
                    background-color: #000000;
                    transform-origin: top left;
                    position: absolute;
                    transform: scale(0);
                }

                .container,
                body,
                html {
                    transition: background-color 1s ease-in-out;
                }

                .logo{
                    position: absolute;
                    top: 46px;
                    left: 171px;
                    width: 371px;
                    height: 147px;
                    flex-shrink: 0;
                    aspect-ratio: 53/21;
                    background: url('/assets/img/logo.png') center center / cover no-repeat;
                    cursor: pointer;
                }

                .mobile_border{
                    position: absolute;
                    top:222px;
                    left: 50%;
                    transform: translateX(-50%);
                    width: 600px;
                    height: 990px;
                    flex-shrink: 0;
                    border-radius: 30px;
                    background: #111948;
                    box-shadow: 0 4px 4px 0 rgba(0, 0, 0, 0.25);
                }

                .index_box{
                    border-radius: 30px;
                    background: #111948;
                    box-shadow: 0 4px 4px 0 rgba(0, 0, 0, 0.25);
                    border-radius: 5px;
                    background: linear-gradient(0deg, rgba(255, 255, 255, 0.80) 0%, rgba(255, 255, 255, 0.80) 100%), url('/assets/img/phone_back.png') no-repeat center center;
                    background-size: cover;

                    position: absolute;
                    top: 85px;
                    left: 50%;
                    transform: translateX(-50%);
                    width: 570px;
                    height: 800px;
                    border: 0px solid;
                }

                .index_box > a{
                    position: absolute;
                    top: 460px;
                    left: 50%;
                    transform: translateX(-50%);
                    color: #7078A7;
                    text-align: center;
                    font-family: 'Fre9';
                    font-size: 32px;
                    font-style: normal;
                    line-height: normal;
                }

                .index_button{
                    position: absolute;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                    width: 90%;
                    height: 90%;
                    background: url("/assets/img/logo.jpg") no-repeat center center;
                    background-size: contain;
                }
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
                
                let containerWidth,
                    containerHeight;
                const windowWidth = window.innerWidth;
                const windowHeight = window.innerHeight;

                if (windowWidth <= 768) {
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
            });
            window.addEventListener('resize', adjustScale);
        </script>
    </body>
</html>