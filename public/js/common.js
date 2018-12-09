//页面提示
jQuery.extend({
    prompt:function(title,url) {
        var error = '<div class="error_content" style="background:#000;color:#fff;line-height:30px;padding:5px 0; border-radius:4px;word-wrap: break-word;position: fixed;left: 0;top: 0;width: 100%;margin:0;text-align: center;display:none;z-index:100000;font-size:15px;">手机号码不能为空</div>';
        $(".error_content").remove();
        $(document.body).append(error);
        $(".error_content").slideDown(500,function(){
            $(".error_content").show();
        });
        $(".error_content").html(title);
        setTimeout(function(){
            $(".error_content").slideUp(500,function(){
                $(".error_content").remove();
            });
            if(typeof(url)!='undefined'&&url!=""&&url!=null){
                window.location.href=url;
            }
        },2000);
    }
});

function mail_reply_delete(c){
    comfrimart('您确定要删除吗？',c,false);
    event.stopPropagation();
    return false;
}

//确认框
function comfrimart(titles,obj,flag,type){
    $('.ui-popup-show').remove();
    var d = dialog({
        id :obj,
        title: '提示',
        content:titles,
        okValue: '确定',
        ok: function () {
            this.title('提交中…');
            if(flag){

                //删除相册评论
                if(document.getElementById('classphoto')){
                    $.post('/index/album/removeComment/',{comment_id:$(obj).attr('data_id')},function(data){
                        json = JSON.parse(data);
                        if(json.type==1){
                            $(obj).slideUp(function(){
                                $(obj).remove();
                            });
                            //判断删除
                            if($(obj).parents('.comments').hasClass('com_none')){//无点赞
                                var lg = $(obj).parents('.comments').find('.del_comment').length;
                                if(lg==1){
                                    $(obj).parents('.guestbox').slideUp();
                                }
                            }else{
                                var lg = $(obj).parents('.comments').find('.del_comment').length;
                                if(lg==1){
                                    $(obj).parents('.comments').slideUp();
                                }
                            }
                        }
                    });
                }
                //删除通知评论
                if(document.getElementById('family_work')){
                    $.post('/index/homework/removeComment/',{comment_id:$(obj).attr('data_id')},function(data){
                        json = JSON.parse(data);
                        if(json.type==1){
                            $(obj).slideUp(function(){
                                $(obj).remove();
                            });
                            var c_n = $(obj).parents('.del_div').find('.comments_num');
                            var num = c_n.html();
                            c_n.html(num-1);

                            //判断删除
                            if($(obj).parents('.comments').hasClass('com_none')){//无点赞
                                var lg = $(obj).parents('.comments').find('.del_comment').length;
                                if(lg==1){
                                    $(obj).parents('.guestbox').slideUp();
                                }
                            }else{
                                var lg = $(obj).parents('.comments').find('.del_comment').length;
                                if(lg==1){
                                    $(obj).parents('.comments').slideUp();
                                }
                            }

                        }
                    });
                }
                //删除食谱评论
                if(document.getElementById('recipe')){
                    $.post('/index/recipe/removeComment/',{comment_id:$(obj).attr('data_id')},function(data){
                        json = JSON.parse(data);
                        if(json.type==1){
                            $(obj).slideUp(function(){
                                $(obj).remove();
                            });
                            var c_n = $(obj).parents('.del_div').find('.comments_num');
                            var num = c_n.html();
                            c_n.html(num-1);

                            //判断删除
                            if($(obj).parents('.comments').hasClass('com_none')){//无点赞
                                var lg = $(obj).parents('.comments').find('.del_comment').length;
                                if(lg==1){
                                    $(obj).parents('.guestbox').slideUp();
                                }
                            }else{
                                var lg = $(obj).parents('.comments').find('.del_comment').length;
                                if(lg==1){
                                    $(obj).parents('.comments').slideUp();
                                }
                            }

                        }
                    });
                }



				if(document.getElementById('yz_classvideo')){
					var parents=$(obj).parents('.classphoto_comment');
                    var num=parseInt($(obj).parents('.bbebebeb').find('#comment_num').html());
                    $.post('/index/video/removeComment',{comment_id:$(obj).attr('comment_id')},function(data){
                        $(obj).parent('li').fadeOut(function(){
                            $(obj).parents('li').remove();
                        });
						
						//代码可优化
						$(obj).parent("div.clearfix").fadeOut();
                       
                        $(obj).parents('.bbebebeb').find('#comment_num').html(num-1);
                    });
				}
                if(document.getElementById('invest')){
                    $.post('/index/school/investdel/',{id:$(obj).attr('data_id')},function(data){
                        $(obj).parents('dl').fadeOut(function(){
                            $(obj).parents('dl').remove();                            
                        });                        
                    });
                }
                
            }else{
                //删除相册
                if(document.getElementById('classphoto')){
                    $.post('/index/album/remove/',{album_id:$(obj).attr('data_id')},function(data){
                        json = JSON.parse(data);
                        if(json.type==1){
                            $(obj).parents('.del_div').slideUp(function(){
                                $(obj).parents('.del_div').remove();
                            });
                        }
                    });
                }
                //家长通知
                if(document.getElementById('family_work')){
                    $.post('/index/homework/remove/',{data_id:$(obj).attr('data_id')},function(data){
                        json = JSON.parse(data);
                        $(obj).parents('.del_div').slideUp(function(){
                            $(obj).parents('.del_div').remove();
                        });
                    });
                }
                //家长通知
                if(document.getElementById('school_notice')){
                    $.post('/index/notice/remove/',{data_id:$(obj).attr('data_id')},function(data){
                        json = JSON.parse(data);
                        $(obj).parents('.del_div').slideUp(function(){
                            $(obj).parents('.del_div').remove();
                        });
                    });
                }
                //食谱
                if(document.getElementById('recipe')){
                    $.post('/index/recipe/remove/',{data_id:$(obj).attr('data_id')},function(data){
                        json = JSON.parse(data);
                        $(obj).parents('.del_div').slideUp(function(){
                            $(obj).parents('.del_div').remove();
                        });
                    });
                }






                if(document.getElementById('classvideo')){
                    $.post('/index/video/remove/',{album_id:$(obj).attr('data_id')},function(data){
                        json = JSON.parse(data);
                        $.prompt(json.message);
                        $(obj).parents('.posrel').fadeOut();
                        return true;
                    });
                }
                //删除班级
                if(document.getElementById('data')){
                        $.post('/index/data/check_del/',{class_id:$(obj).attr('data-id')},function(data){
                            json = JSON.parse(data);
                            if(json.type == 1){
                                $.post('/index/data/remove/',{class_id:$(obj).attr('data-id')},function(data){
                                    json = JSON.parse(data);
                                    $.prompt(json.message);
                                    $(obj).parents('article').fadeOut();
                                    return true;
                                });
                            }else{
                                $.prompt(json.message);
                            }
                        });
                    }

                //班级置为毕业
                if(document.getElementById('modify_class')){
                        //alert($(obj).attr('data-id'));
                        $.post('/index/data/grad/',{class_id:$(obj).attr('data-id')},function(data){
                            json = JSON.parse(data);
                            if(json.type == 1){
                                $.prompt(json.message);
                                window.location.href="/index/data/index/";
                                return true;
                            }else{
                                $.prompt(json.message);
                            }
                        });
                }

                        //删除学生
                if(document.getElementById('student_sum')){
                   $.post('/index/student/remove/',{student_id:$(obj).attr('data-id')},function(data){
                        json = JSON.parse(data);
                        $.prompt(json.message);
                        $(obj).parents('.hoverECECEC').fadeOut();
                        return true;
                    }); 
                }
                        //删除老师
                if(document.getElementById('teacher')){
                   $.post('/index/teacher/remove/',{teacher_id:$(obj).attr('teacher_id')},function(data){
                        json = JSON.parse(data);
                        $.prompt(json.message);
                        $(obj).parents('.hoverECECEC').fadeOut();
                        return true;
                    }); 
                }
                //班级通知
                if(document.getElementById('classinform')){
                    $.post('/index/cnotice/remove/',{data_id:$(obj).attr('data-id')},function(data){
                        json = JSON.parse(data);
                        $.prompt(json.message);
                        $(obj).parents('.cnoticeRemove').fadeOut();
                        return true;
                    });
                }

                //全园广播
                if(document.getElementById('radio')){
                    $.post('/index/notice/remove/',{data_id:$(obj).attr('data-id')},function(data){
                        json = JSON.parse(data);
                        $.prompt(json.message);
                        $(obj).parents('.teach_frame').fadeOut();
                        return true;
                    });
                }	
                        //教学计划		
                if(document.getElementById('plan')){
                    $.post('/index/plan/remove/',{data_id:$(obj).attr('data-id')},function(data){
                        json = JSON.parse(data);
                        $.prompt(json.message);
                        $(obj).parents('.teach_frame').fadeOut();
                        return true;
                    });
                }
                //教学计划推荐
                if(document.getElementById('recommend')){
                    $.post('/index/plan/delrecommend/',{id:$(obj).attr('data-id'),"recommend":0},function(data){
                        json = JSON.parse(data);
                        $.prompt(json.message);
                        $(obj).parents('.teach_frame').fadeOut();
                        return true;
                    });
                }

                        //站内信
                if(document.getElementById('message')){
                    $.post('/index/inmail/remove/',{data_id:$(obj).attr('data-id')},function(data){
                        json = JSON.parse(data);
                        $.prompt(json.message);
                        $(obj).parents('.letter').fadeOut();
                        return true;
                    });
                }
                //删除家长
                if(document.getElementById('student_family')){
                    $.post('/index/student/delete_parent/',{student_id:$(obj).attr('student_id'),parent_id:$(obj).attr('parent_id')},function(data){
                        json = JSON.parse(data);
                        $.prompt(json.message);
                        var student_id = $(obj).attr('student_id');
                        var num = parseInt($("#parent_num_"+student_id).html()) - 1;
                        $("#parent_num_"+student_id).html(num);
                        $(obj).parents('.parent_li').fadeOut();
                        return true;
                    });
                }
                //删除投票
                if(document.getElementById('vote_list')){
                    $.post('/index/vote/remove/',{data_id:$(obj).attr('data-id'),data_school:$(obj).attr('data-school'),data_class:$(obj).attr('data-class')},function(data){
                        json = JSON.parse(data);
                        $.prompt(json.message);
                        $(obj).parents('.aim_vote').fadeOut();
                        return true;
                    });
                }
                if(document.getElementById('mailbox')){
                    $.post('/index/mailbox/remove/',{id:$(obj).attr('data-id')},function(data){
                        json = JSON.parse(data);
                        $.prompt(json.message);
                        $(obj).parents('.reply_list').fadeOut();
                        return true;
                    });
                }
            }
        },
        cancelValue: '取消',
        cancel: function () {
            
        }
    });
    d.show();
}

$(document).ready(function(){
    imgLoad();
    //页面提交
    $(".myform").submit(function(){
        var error=0;
        var url=$(this).attr('action');
        $('.required').each(function() {
            if($(this).val() == ""||$(this).val() == 0){			
                $.prompt($(this).attr("data_value")+"不能为空");
                error++;
                return false;
            }
    	});
        if(error==0){
            $('input[type="submit"]').attr('disabled',true);
            $.post(url,$(this).serialize(),function(data){
                console.log(data);
				if(data.code == 0){
                    $.prompt(data.msg,data.url);
                }else{
                    $.prompt(data.msg);
                    $('input[type="submit"]').removeAttr('disabled');
                }
            });
        }
        return false;
    });
    //分页加载绑定
    if(document.getElementById('yzCollege')) {
        $(document).bind('scroll',onScroll);
    }
});

//分页加载
var isLoading = false;
var page=2;
var win_height = $(window).height();
var w = $(window).width();
//页面滚动事件
function onScroll(event) {
    if(!isLoading) {
        var closeToBottom = ($(document).scrollTop() + win_height-114 > $(document).height()- 160);
        if(closeToBottom) {
            loadData();
        }			
    }
}
//拉取数据
function loadData() {
    isLoading = true;
    $.ajax({
        type:"get",
        url:document.URL,
        data:{page:page},
        dataType:"",
        async:true,
        success: onLoadData
    });
}
//页面加载分页数据
function onLoadData(data) {
    isLoading = false;
    if (data.type == 1){
        dataArray=data.list;
        if (document.getElementById("yzCollege")) {
            $(".list").append(dataArray);
            page = parseInt(data.data.pageNum) + 1;
        }
    }else{
        isLoading = true;
    }
}

//加载图片
function imgLoad(){
    $('img').load(function() {
        var _this = $(this);
        var url = _this.attr('data');
        var src = _this.attr('src');
        if ( typeof(url) == 'undefined' || url == '' || url == src ) {
            return;
        }
        var img = new Image();
        img.src = url;
        if (img.complete) {
            _this.attr('src', url);
            return;
        }
        img.onload = function() {
            _this.attr('src', url);
        };
    });
};






