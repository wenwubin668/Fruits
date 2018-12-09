<div class="headerbox">{{isset($title)?$title:'默认标题'}}
    @if(isset($left))
    <span class="return"><a href="{{$left['url']}}">{{$left['name']}}</a></span>
    @endif
    @if(isset($right))
        <span class="operation"><a href="{{$right['url']}}">{{$right['name']}}</a></span>
    @endif
</div>
<div style="height: 44px;width: 100%;"></div>