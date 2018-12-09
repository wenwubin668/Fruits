<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0;" name="viewport"/>
    <title>列表</title>
    <link href="//res.wx.qq.com/open/libs/weui/1.1.3/weui.min.css" rel="stylesheet" type="text/css">
    <script type="text/javascript" src="/js/jquery-2.0.0.min.js"></script>
    <script type="text/javascript" src="/js/common.js?v={{time()}}"></script>
    <script src="https://res.wx.qq.com/open/js/jweixin-1.2.0.js"></script>
    {{--<script>
        wx.config({
            debug: false,
            appId: '{{$WeChatJDK['appId']}}',
            timestamp:'{{$WeChatJDK['timestamp']}}',
            nonceStr: '{{$WeChatJDK['nonceStr']}}',
            signature: '{{$WeChatJDK['signature']}}',
            jsApiList: ['chooseImage','previewImage','uploadImage','downloadImage','hideOptionMenu','showOptionMenu','onMenuShareTimeline','onMenuShareAppMessage',
                'translateVoice',
                'startRecord',
                'stopRecord',
                'onVoiceRecordEnd',
                'playVoice',
                'onVoicePlayEnd',
                'pauseVoice',
                'stopVoice',
                'uploadVoice',
                'downloadVoice',
                'onMenuShareQQ',
                'onMenuShareWeibo',
                'onMenuShareQZone',
                'getLocation'
            ]
        });
        $(document).ready(function(){
            wx.ready(function () {
                wx.hideOptionMenu();
            });
        });
    </script>--}}
    <style>
        .headerbox {
            z-index: 99999;
            position: fixed;
            width: 100%;
            height: 44px;
            line-height: 44px;
            font-size: 14px;
            text-align: center;
            font-weight: bold;
            background: #fff;
            border-bottom: 1px rgba(0,0,0,0.06) solid;
        }
        .headerbox .return {
            position: absolute;
            left: 4%;
            width: 20%;
            font-weight: normal;
            color: #a8a8a8;
            text-align: left;
        }
        .headerbox .operation {
            float: none;
            width: auto;
            position: absolute;
            right: 4%;
            font-weight: normal;
            color: #FF7100;
            text-align: right;
        }
    </style>
</head>
<body>
