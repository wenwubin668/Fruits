@include('card/common/header')
@include('card/common/backTitle')

<form action="" class="myform">

    <div class="weui-cells__title">名称</div>
    <div class="weui-cells">
        <div class="weui-cell">
            <div class="weui-cell__bd">
                <input class="weui-input required" data_value="名称" name="name" type="text" placeholder="请填写名称">
            </div>
        </div>
    </div>
    <div class="weui-cells__title">额度</div>
    <div class="weui-cells">
        <div class="weui-cell">
            <div class="weui-cell__bd">
                <input class="weui-input required" data_value="额度" name="quota" type="number" placeholder="请填写额度">
            </div>
        </div>
    </div>
    <div class="weui-cells__title">账单日</div>
    <div class="weui-cells">
        <div class="weui-cell">
            <div class="weui-cell__bd">
                <input class="weui-input required" data_value="账单日" name="account_day" type="number" placeholder="请填写账单日">
            </div>
        </div>
    </div>
    <div class="weui-cells__title">还款日</div>
    <div class="weui-cells">
        <div class="weui-cell">
            <div class="weui-cell__bd">
                <input class="weui-input required" data_value="还款日" name="pay_day" type="number" placeholder="请填写还款日">
            </div>
        </div>
    </div>
    <div class="weui-cells__title">还款日类型</div>
    <div class="weui-cells weui-cells_radio">
        <label class="weui-cell weui-check__label" for="x11">
            <div class="weui-cell__bd">
                <p>默认还款日</p>
            </div>
            <div class="weui-cell__ft">
                <input type="radio" class="weui-check" value="1" name="pay_type" id="x11" checked="checked">
                <span class="weui-icon-checked"></span>
            </div>
        </label>
        <label class="weui-cell weui-check__label" for="x12">
            <div class="weui-cell__bd">
                <p>出账日延后还款日</p>
            </div>
            <div class="weui-cell__ft">
                <input type="radio" name="pay_type" value="2" class="weui-check" id="x12" >
                <span class="weui-icon-checked"></span>
            </div>
        </label>
    </div>
    <div class="weui-cells__title">备注</div>
    <div class="weui-cells weui-cells_form">
        <div class="weui-cell">
            <div class="weui-cell__bd">
                <textarea class="weui-textarea" name="desc" placeholder="可填写备注" rows="3"></textarea>
                <div class="weui-textarea-counter"></div>
            </div>
        </div>
    </div>
    <div class="weui-btn-area">
        <input type="submit" class="weui-btn weui-btn_primary" value="确定">
    </div>
</form>

@include('card/common/footer')