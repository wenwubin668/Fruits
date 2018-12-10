@include('card/common/header')
@include('card/common/backTitle')

@foreach($list as $value)
<a class="weui-cell weui-cell_access" href="{{route('CardAmountInfo',['id'=>$value->id])}}">
    <div class="weui-cell__bd">
        <p>#<span style="color: {{$value->pay_time==$date?'red':'green'}}">{{$value->pay_time}}</span>#账单#</p>
    </div>
    <div class="weui-cell__ft">{{intval($value->pay_money)?number_format($value->pay_money,2):'---'}}</div>
</a>
@endforeach

@include('card/common/footer')
