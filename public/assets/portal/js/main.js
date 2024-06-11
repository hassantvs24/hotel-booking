var current_fs, next_fs, previous_fs; //fieldsets
var left, opacity, scale; //fieldset properties which we will animate
var animating; //flag to prevent quick multi-click glitches

$(".next").click(function() {
    if (animating) return false;
    animating = true;

    current_fs = $(this).parent();
    next_fs = $(this).parent().next();

    //activate next step on progressbar using the index of next_fs
    $("#progressbar li").eq($("fieldset").index(next_fs)).addClass("active");

    //show the next fieldset
    next_fs.show();
    //hide the current fieldset with style
    current_fs.animate({
        opacity: 0
    }, {
        step: function(now, mx) {
            //as the opacity of current_fs reduces to 0 - stored in "now"
            //1. scale current_fs down to 80%
            scale = 1 - (1 - now) * 0.2;
            //2. bring next_fs from the right(50%)
            left = (now * 50) + "%";
            //3. increase opacity of next_fs to 1 as it moves in
            opacity = 1 - now;
            current_fs.css({
                'transform': 'scale(' + scale + ')',
                'position': 'absolute'
            });
            next_fs.css({
                'left': left,
                'opacity': opacity
            });
        },
        duration: 800,
        complete: function() {
            current_fs.hide();
            animating = false;
        },
        //this comes from the custom easing plugin
        easing: 'easeInOutBack'
    });
});

$(".previous").click(function() {
    if (animating) return false;
    animating = true;

    current_fs = $(this).parent();
    previous_fs = $(this).parent().prev();

    //de-activate current step on progressbar
    $("#progressbar li").eq($("fieldset").index(current_fs)).removeClass("active");

    //show the previous fieldset
    previous_fs.show();
    //hide the current fieldset with style
    current_fs.animate({
        opacity: 0
    }, {
        step: function(now, mx) {
            //as the opacity of current_fs reduces to 0 - stored in "now"
            //1. scale previous_fs from 80% to 100%
            scale = 0.8 + (1 - now) * 0.2;
            //2. take current_fs to the right(50%) - from 0%
            left = ((1 - now) * 50) + "%";
            //3. increase opacity of previous_fs to 1 as it moves in
            opacity = 1 - now;
            current_fs.css({
                'left': left
            });
            previous_fs.css({
                'transform': 'scale(' + scale + ')',
                'opacity': opacity
            });
        },
        duration: 800,
        complete: function() {
            current_fs.hide();
            animating = false;
        },
        //this comes from the custom easing plugin
        easing: 'easeInOutBack'
    });
});


document.addEventListener('readystatechange', event => {
    if (event.target.readyState === "complete") {
        var clockdiv = document.getElementsByClassName("room-search-timer");
        var countDownDate = new Array();
        for (var i = 0; i < clockdiv.length; i++) {
            countDownDate[i] = new Array();
            countDownDate[i]['el'] = clockdiv[i];
            countDownDate[i]['time'] = new Date(clockdiv[i].getAttribute('data-date')).getTime();
            countDownDate[i]['hours'] = 0;
            countDownDate[i]['minutes'] = 0;
        }

        var countdownfunction = setInterval(function() {
            for (var i = 0; i < countDownDate.length; i++) {
                var now = new Date().getTime();
                var distance = countDownDate[i]['time'] - now;
                countDownDate[i]['days'] = Math.floor(distance / (1000 * 60 * 60 * 24));
                countDownDate[i]['hours'] = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                countDownDate[i]['minutes'] = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                countDownDate[i]['seconds'] = Math.floor((distance % (1000 * 60)) / 1000);

                if (distance < 0) {
                    countDownDate[i]['el'].querySelector('.days').innerHTML = 0;
                    countDownDate[i]['el'].querySelector('.hours').innerHTML = 0;
                    countDownDate[i]['el'].querySelector('.minutes').innerHTML = 0;
                    countDownDate[i]['el'].querySelector('.seconds').innerHTML = 0;
                } else {
                    countDownDate[i]['el'].querySelector('.days').innerHTML = countDownDate[i]['days'];
                    countDownDate[i]['el'].querySelector('.hours').innerHTML = countDownDate[i]['hours'];
                    countDownDate[i]['el'].querySelector('.minutes').innerHTML = countDownDate[i]['minutes'];
                    countDownDate[i]['el'].querySelector('.seconds').innerHTML = countDownDate[i]['seconds'];
                }

            }
        }, 1000);
    }
});


//FORM DATA

$(".date-picker").datepicker({
	dateFormat: 'd M',
	minDate: new Date()
});
$("#submitBtn").click(function() {
	var spaces = $("#spaces").val();
	var parkingPrice = $("#parkingPrice").val();
	var cashParkers = $("#cashParkers").val();
	var util = $("#util").val();
	var count = $("#count").val();
	var hours = $("#hours").val();
	var laborRate = $("#laborRate").val();
	var eventLabor = $("#eventLabor").val();
	var vname = $("#full_name").val();
	var company = $("#company").val();
	var vemail = $("#email").val();
	if (vname == "" && vemail == "") {
		alert("Please fill out the form");
	} else if (vname == "" && vemail !== "") {
		alert("Name field is required");
	} else if (vemail == "" && vname !== "") {
		alert("Email field is required");
	} else {
		$.post(
			"https://parkhub-bosh-poc.bubbleapps.io/version-test/api/1.1/wf/calc?", //Required URL of the page on server
			{
				// Data Sending With Request To Server
				name: vname,
				email: vemail,
				company: company,
				spaces: spaces,
				parkingPrice: parkingPrice,
				cashPay: cashParkers,
				util: util,
				laborRate: laborRate,
				eventLabor: eventLabor,
				eventCount: count,
				eventHours: hours
			},
			function(response, status) {
				// Required Callback Function
				alert(
					"*----Received Data----*nnResponse : " +
					response +
					"nnStatus : " +
					status
				); //"response" receives - whatever written in echo of above PHP script.
				$("#form")[0].reset();
			}
		);
	}
});


var div_top = $('.filter, .room-search-map').offset().top;

$(window).scroll(function() {
    var window_top = $(window).scrollTop() - 0;
    if (window_top > div_top) {
        if (!$('.filter, .room-search-map').is('.sticky')) {
            $('.filter, .room-search-map').addClass('sticky');
        }
    } else {
        $('.filter, .room-search-map').removeClass('sticky');
    }
});