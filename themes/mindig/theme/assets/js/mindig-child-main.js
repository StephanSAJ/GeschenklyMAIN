;( function( $, window, document, undefined ) {
	var accessToken = "acb08d046002492b93cade4516e17684";
		var baseUrl = "https://api.api.ai/v1/";
		$(document).ready(function() {
			$("#input").on('keypress', function(event) {
				if (event.keyCode == 13) {
					send();
				}
			});
			$("#rec").click(function(event) {
				switchRecognition();
			});
		});
		var recognition;
		function startRecognition() {
			recognition = new webkitSpeechRecognition();
			recognition.onstart = function(event) {
				updateRec();
			};
			recognition.onresult = function(event) {
				var text = "";
			    for (var i = event.resultIndex; i < event.results.length; ++i) {
			    	text += event.results[i][0].transcript;
			    }
			    setInput(text);
				stopRecognition();
			};
			recognition.onend = function() {
				stopRecognition();
			};
			recognition.lang = "en-US";
			recognition.start();
		}

		function stopRecognition() {
			if (recognition) {
				recognition.stop();
				recognition = null;
			}
			updateRec();
		}
		function switchRecognition() {
			if (recognition) {
				stopRecognition();
			} else {
				startRecognition();
			}
		}
		function setInput(text) {
			$("#input").val(text);
			send()
		}
		function updateRec() {
			$("#rec").text(recognition ? "Stop" : "Speak");
		}
		function send() {
			var text = $("#input").val();
			$.ajax({
				type: "POST",
				url: baseUrl + "query?v=20150910",
				contentType: "application/json; charset=utf-8",
				dataType: "json",
				headers: {
					"Authorization": "Bearer " + accessToken
				},
				data: JSON.stringify({ query: text, lang: "en", sessionId: "somerandomthing" }),
				success: function(data) {
					setResponse(JSON.stringify(data, undefined, 2));
				},
				error: function() {
					setResponse("Internal Server Error");
				}
			});
			setResponse("Loading...");
		}

    function convert(speech){
      var exp = /(\b(https?|ftp|file):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/ig;
  	  var text1= speech.replace(exp, "<a target='_blank' href='$1'>$1</a>");
  	  var exp2 =/(^|[^\/])(www\.[\S]+(\b|$))/gim;
  	  var text2 =  text1.replace(exp2, '$1<a target="_blank" href="http://$2">$2</a>');

  	  //time
  	  var d = new Date();
			var time = d.getHours() + ":" + d.getMinutes();

  	  //storing messages
  	  var clientMessage = $("#input").val();
      localStorage.setItem('clientMessage', JSON.stringify(clientMessage));

  	  var clientMessageText = JSON.parse(localStorage.getItem('clientMessage'));
      $('.chat').append('<div class="client-message message"><div class="message-header"><span class="person"> Du </span><span class="time">' + time + '</span></div><div class="message-body">' + clientMessageText + '</div></div>');


  	  var supportMessage = text2;
  	  localStorage.setItem('supportMessage', JSON.stringify(supportMessage));

  	  var supportMessageText = JSON.parse(localStorage.getItem('supportMessage'));
      $('.chat').append('<div class="support-message message"><div class="message-header"><span class="time">' + time + '</span><span class="person"> Anna </span></div><div class="message-body">' + supportMessageText + '</div></div>');

      localStorage["chatHistory"] = JSON.stringify($('.chat').html());

      //clear input field
      $("#input").val('');

      //scroll window to bottom
      $('.chat').animate({scrollTop: $('.chat').prop("scrollHeight")}, 500)
    }
		function setResponse(val) {
      var json = JSON.parse(val);
       var speech = json["result"].fulfillment.speech
      var text_with_link = convert(speech)

		}

		$(function() {
		   if (localStorage["chatHistory"] != null) {
		      var contentOfOldDiv = JSON.parse(localStorage["chatHistory"]);    
		      $(".chat").html(contentOfOldDiv);
		     } 
		});
} )( jQuery, window, document );
