$("#formSendMessage").submit(function(e) {
    e.preventDefault(); // avoid to execute the actual submit of the form.
    var form = $(this);
    var url = form.attr('action');


    //Append the text send to the chat window
    text=document.getElementById('message').value;
    
    var today = new Date();
    var time = checkTime(today.getHours()) + ":" + checkTime(today.getMinutes());
    appendText(text,'R',time);
    document.getElementById('isWriting').style.display="block";
    //reset the message on input
    $.ajax({
        type: "POST",
        url: url/*.replace("http","https")*/,
        data: form.serialize(), // serializes the form's elements.
        success: function(data)
        {
            //Now we need to write yoda's reponse
            response=data;
            document.getElementById('isWriting').style.display="none";
            var today = new Date();
            var time = checkTime(today.getHours()) + ":" + checkTime(today.getMinutes());
            appendText(response,'L',time);
        },
        error: function(e){
            console.log('Ocurrió un error inesperado ');
            console.log(e);
        },
        complete: function (){
            //reset the message sent
            document.getElementById('message').value="";
        }
    });
});

function checkTime(i) {
    return (i < 10) ? "0" + i : i;
}

function appendText(text,side,time){

    clase="right-msg";
    who='Jesús';
    img="./dist/img/jesus.png";

    if(side=='L'){
        clase="left-msg";
        who='Yoda'
        img="./dist/img/yoda.png";
    }

    textToAppend='<div class="msg '+clase+'">'+
                    '<div class="msg-img" style="background-image: url('+img+')" ></div>'+
            
                    '<div class="msg-bubble">'+
                        '<div class="msg-info">'+
                            '<div class="msg-info-name">'+who+'</div>'+
                            '<div class="msg-info-time">'+time+'</div>'+
                        '</div>'+
                
                        '<div class="msg-text">'+
                            text+
                        '</div>'+
                    '</div>'+
                '</div>';
    
    $('#chatWindows').append(textToAppend);
    window.scrollTo(0,document.body.scrollHeight);

}

function reset(tipo){
    if(tipo==1){
        window.location.search = window.location.search.replace('?new=1','')+'?new=1';
    }else{
        window.location.search = window.location.search.replace('?new=1','');
    }
}
