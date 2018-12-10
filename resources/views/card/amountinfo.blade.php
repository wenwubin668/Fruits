@include('card/common/header')
@include('card/common/backTitle')

<form action="" class="myform">

    <div class="weui-cells__title">还款时间</div>
    <div class="weui-cells">
        <div class="weui-cell">
            <div class="weui-cell__bd">
                <input class="weui-input required" data_value="还款时间" name="pay_time" type="date" placeholder="请填写还款时间" value="{{$info->pay_time}}">
            </div>
        </div>
    </div>
    <div class="weui-cells__title">还款金额</div>
    <div class="weui-cells">
        <div class="weui-cell">
            <div class="weui-cell__bd">
                <input class="weui-input" data_value="还款金额" name="pay_money" type="number" placeholder="请填写还款金额" value="{{$info->pay_money}}">
            </div>
        </div>
    </div>

    <div class="weui-btn-area">
        <input type="hidden" name="cid" value="{{$info->cid}}">
        <input type="submit" class="weui-btn weui-btn_primary" value="确定">
    </div>
</form>

@include('card/common/footer')