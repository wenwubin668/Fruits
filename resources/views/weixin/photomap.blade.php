<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <title>房佳斌&朱真真</title>
    <link rel="stylesheet" type="text/css" href="/marry/css/polaroid-gallery.css?v={{time()}}"/>
    <script type="text/javascript" src="/js/jquery-2.0.0.min.js"></script>
</head>
<body class="fullscreen">
<div id="gallery" class="fullscreen"></div>
<div id="nav" class="navbar">
    <button id="preview">&lt; 前一张</button>
    <button id="next">下一张 &gt;</button>
    <button id="start">开始播放</button>
    <button id="stop">停止播放</button>
</div>
<script type="text/javascript" src="/marry/js/polaroid-gallery.js?v={{time()}}"></script>
<script>
    window.onload = function () {
        new polaroidGallery();
    }
</script>


</body>
</html>