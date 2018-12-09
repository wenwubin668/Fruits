@include('card/common/header')
@include('card/common/backTitle')

@foreach($list as $value)
<a class="weui-cell weui-cell_access" href="{{route('CardAmountList',['id'=>$value->id])}}">
    <div class="weui-cell__bd">
        <p>{{$value->name}} / <span style="color: {{$value->account_day==$date?'red':'green'}}">{{$value->account_day}}日</span></p>
    </div>
    <div class="weui-cell__ft">{{number_format($value->quota,2)}}</div>
</a>
@endforeach

@include('card/common/footer')
