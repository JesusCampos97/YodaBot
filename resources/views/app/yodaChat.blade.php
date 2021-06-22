@extends('./layouts/principalLayout')

@section('yodaChat')

    <section class="msger">
        <div id="chat">
                <header class="msger-header">
                    <div class="msger-header-title" onclick="reset(0);">
                        <i class="fas fa-comment-alt"></i> Yoda Bot
                    </div>
                    <div class="msger-header-options" onclick="reset(1);">
                        <span><i class="fas fa-trash-alt"></i></span>
                    </div>
                </header>
            
                <main class="msger-chat" id="chatWindows" style="heigth:100%; min-height: 90vh; background-image: url('./dist/img/pattern.png'); background-size: cover;">
                </main>
        
        </div>
    </section>

@endSection

@section('footer')


    <div class="msger-inputarea-div">
        <label id="isWriting" class="writingLabel" hidden>Yoda bot is writing </label>
        <form class="msger-inputarea" id="formSendMessage" method="POST" action="{{route('sendMessage')}}" onsubmit="return false;">
            @csrf
            <input type="text" name="message" id="message" class="msger-input" placeholder="Enter your message..." required>
            <button type="submit" class="msger-send-btn">Send</button>
        </form>
    </div>

@endsection

@section('scripts')
    <script>


        var botMessages= <?= json_encode($botMessages); ?>;
        var userMessages= <?= json_encode($userMessages); ?>;
        console.log(botMessages)
        console.log(botMessages.length)

        loadHistory();
        function loadHistory(){
            console.log(botMessages)
            console.log(userMessages)

            console.log('dale '+botMessages.length)

            if(botMessages.length>0 || userMessages.length>0){
                for(i=0;i<userMessages.length;i++){
                    console.log('dale')
                    appendText(userMessages[i].message,'R',userMessages[i].hour);
                    appendText(botMessages[i].message,'L',botMessages[i].hour);

                }
            }
        }
       
    </script>
@endSection