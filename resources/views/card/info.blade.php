@include('card/common/header')
@include('card/common/backTitle')

<div class="weui-cell">
    <div class="weui-cell__hd"></div>
    <div class="weui-cell__bd">
        <p>额度:</p>
    </div>
    <div class="weui-cell__ft">{{number_format($info->quota,2)}}</div>
</div>
<div class="weui-cell">
    <div class="weui-cell__hd"></div>
    <div class="weui-cell__bd">
        <p>出账日:</p>
    </div>
    <div class="weui-cell__ft">{{$info->account_day}}日</div>
</div>
<div class="weui-cell">
    <div class="weui-cell__hd"></div>
    <div class="weui-cell__bd">
        <p>还款日:</p>
    </div>
    <div class="weui-cell__ft">{{$info->pay_day}}日</div>
</div>
<div class="page__bd" style="border-top: 1px solid #e5e5e5;">
    <article class="weui-article">
        <section>
            <section>
                <h3>备注:</h3>
                <p>
                    {{$info->desc??'暂无备注'}}
                </p>
                <p>
                    <img src="./images/pic_article.png" alt="">
                    <img src="./images/pic_article.png" alt="">
                </p>
            </section>
        </section>
    </article>
</div>

@include('card/common/footer')