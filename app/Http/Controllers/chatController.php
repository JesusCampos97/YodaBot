<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Session;
use Illuminate\Support\Facades\Http;
use App\Traits\Utils;


class chatController extends Controller
{
    use Utils;

    /**
     * FUNCTION: 
     *  Es la funcion root para la vista del chat y,
     *  obtiene el primer ACCESS TOKEN que guarda en una variable de sesión.
     *  Además, borra las sesiones si el parametro es el adecuado.
     *  En principio, por temas de seguridad y como no es dinámico, API KEY y API KEY SECRET
     *  se guardan en Variables de entorno.
     *  En el caso de que no tenga que resetar, muestro el historial de la conversación
     * 
     * PARAMETERS:
     *  "new" reseta las variables de sesión
     * 
     * RETURNS:
     *  Devuelve la vista del chat
     * 
     * Created: 19/06/2021
     * Last Modified: 22/06/2021
     * User: JCampos
     */
    public function showChat(Request $request){

        $new=$request->new;
        
        //getting environments vars
        $API_KEY=env('API_YODABOT_KEY', '');
        $API_KEY_SECRET=env('API_YODABOT_SECRET', '');
        $message="";
        $botMessages=[];
        $userMessages=[];

        //Reset session vars
        if($new==1){
            session::forget('conversacionIniciada');
            session::forget('ACCESS_TOKEN');
            session::forget('sessionToken');
            session::forget('API_URL_CHATBOT');
            session::forget('errors');

            $this->getAccessToken($API_KEY,$API_KEY_SECRET);
        }else if(session('conversacionIniciada')!=null){
            $headers = [
                'x-inbenta-key' => $API_KEY,
                'x-inbenta-session' => 'Bearer '. session('sessionToken'),
                'Authorization' => 'Bearer '.session('ACCESS_TOKEN'),
            ];

            $body = [
                
            ];
               
            $response = Http::withHeaders($headers)->get(session('API_URL_CHATBOT')."/conversation/history",$body);
            $response = json_decode($response);

            $errors=0;
            //We need to store in arrays the conversations to show when the page was reloaded
            foreach($response as $r){
                if($r->user=='bot'){
                    $botM= new \stdClass();
                    $botM->message=$r->message;

                    if(str_contains(strtoupper($botM->message),'FORCE')){
                        $queryFilms="{ allFilms{ films{ title } } }";
                        $response=$this->queryGraphQL($queryFilms);
                        $films=$response->data->allFilms->films;
            
                        //getting a list of films in html code
                        $listOfFilms=$this->getListIn_UL_Tag($films,'title');
                        $finalResponse="The force is this in <strong>movies</strong>: ".$listOfFilms;
                        $botM->message=$finalResponse;
                    }            

                    if(in_array($botM->message,Config('constants.answersErrors')))
                        $errors++;

                    if($errors==2){
                        $errors=0;
                        $listCharacters=[];
                        $queryCharacters="{ allFilms{ films{ characterConnection{ characters{ name } } } } }";
                        $responseGraphQL=$this->queryGraphQL($queryCharacters);

                        //getting a list of films with characters
                        $films=$responseGraphQL->data->allFilms->films;

                        //store all characters on a list
                        foreach($films as $f){
                            $characters=$f->characterConnection->characters;
                            $listCharacters=array_merge($listCharacters,$characters);
                        }
                        //deleting duplicates
                        $listCharacters = array_map("unserialize", array_unique(array_map("serialize", $listCharacters)));
                        //shuffle to get random characters
                        shuffle($listCharacters);
                        //showing the first 10 by the way
                        $listCharacters=array_slice($listCharacters,0,9);
                        //getting a list of characters in html code
                        $listOfCharacters=$this->getListIn_UL_Tag($listCharacters,'name');
                        $finalResponse="I haven't found any results, but here is a list of some Star Wars characters: ".$listOfCharacters;
                        $botM->message=$finalResponse;
                    }
                    //datetime is in TZ. We need to parse it
                    $date = new \DateTime($r->datetime, new \DateTimeZone("Europe/Madrid"));
                    $botM->hour=date_format($date,"H:i");
                    array_push($botMessages,$botM);
                }else{
                    $userM= new \stdClass();
                    $userM->message=$r->message;
                    //datetime is in TZ. We need to parse it
                    $date = new \DateTime($r->datetime, new \DateTimeZone("Europe/Madrid"));
                    $userM->hour=date_format($date,"H:i");
                    array_push($userMessages,$userM);
                }
            }
        }else{
            $this->getAccessToken($API_KEY,$API_KEY_SECRET);
        }

        return view('app.yodaChat',compact('userMessages','botMessages'));
    }

    /**
     * FUNCTION: 
     *  Es la funcion a la que se llama por AJAX y a la que se envía el mensaje del usuario
     *  para que el bot le responda.
     *  Además: 
     *   -> Si el mensaje contiene la palabra "force" entonces enviamos una lista de peliculas obtenidas
     *    mediante una consulta en GraphQL a la API
     *   -> Else iniciamos la conversación si no esta iniciada y mandamos el mensaje. Si llevo dos errores muestro
     *    Una lista de personajes de películas mediante una consulta en GraphQL a la API
     * 
     * PARAMETERS:
     *  Recibe el propio request donde si encontramos un parametro "new" reseta las variables de sesión
     *  En principio, por temas de seguridad y como no es dinámico, API KEY y API KEY SECRET
     *  se guardan en Variables de entorno
     * 
     * RETURNS:
     *  Devuelve la vista del chat
     * 
     * Created: 19/06/2021
     * Last Modified: 22/06/2021
     * User: JCampos
     */
    public function sendMessage(Request $request){
        $message=$request->message;
        $finalResponse="";

        $API_KEY=env('API_YODABOT_KEY', '');
        $API_KEY_SECRET=env('API_YODABOT_SECRET', '');

        //String with "FORCE"
        if(str_contains(strtoupper($message),'FORCE')){
            $queryFilms="{ allFilms{ films{ title } } }";
            $response=$this->queryGraphQL($queryFilms);
            $films=$response->data->allFilms->films;

            //getting a list of films in html code
            $listOfFilms=$this->getListIn_UL_Tag($films,'title');
            $finalResponse="The force is this in <strong>movies</strong>: ".$listOfFilms;

            //We need to send a message to store it in bot and then show in the history
             //Sending message to API
             $headers = [
                'x-inbenta-key' => $API_KEY,
                'x-inbenta-session' => 'Bearer '. session('sessionToken'),
                'Authorization' => 'Bearer '.session('ACCESS_TOKEN'),
            ];
            $body = [
                "message" => $message
            ];
            $response = Http::withHeaders($headers)->post(session('API_URL_CHATBOT')."/conversation/message",$body);
            //we dont need the response

        }else{
           
    
            //Check session to avoid call in each message and exceed rate limits
            if(session('API_URL_CHATBOT')=="" || session('API_URL_CHATBOT')==null){
                $this->getApiURLChatBot($API_KEY,$API_KEY_SECRET); 
            }
    
            //The first time we need to start a conversation
            if(session('conversacionIniciada')==null){
               
                $headers = [
                    'x-inbenta-key' => $API_KEY,
                    'Authorization' => 'Bearer '.session('ACCESS_TOKEN'),
                ];
    
                $body = [
                    
                ];
                   
                $response = Http::withHeaders($headers)->post(session('API_URL_CHATBOT')."/conversation",$body);
                $response = json_decode($response);

                $sessionToken = $response->sessionToken;
                //storing sessionToken for next request without reset
                session::put('sessionToken',$sessionToken);
                $sessionId = $response->sessionId;

                //Now we have a initied conversation
                session::put('conversacionIniciada',true);
    
            }

            //Sending message to API
            $headers = [
                'x-inbenta-key' => $API_KEY,
                'x-inbenta-session' => 'Bearer '. session('sessionToken'),
                'Authorization' => 'Bearer '.session('ACCESS_TOKEN'),
            ];
            $body = [
                "message" => $message
            ];
            $response = Http::withHeaders($headers)->post(session('API_URL_CHATBOT')."/conversation/message",$body);
            $response = json_decode($response);
            //checking properties to avoid errors
            if(property_exists($response->answers[0],'message')){
                $yodaMessage=$response->answers[0]->message;
            }else{
                //is an unknown error 
                $yodaMessage='An error have been ocurred';
            }

            //If I have problems 2 times, show a list of characters
            if(in_array($yodaMessage,Config('constants.answersErrors')))
                session::put('errors',session('errors')+1);

            if(session('errors')==2){
                session::put('errors',0);
                $listCharacters=[];
                $queryCharacters="{ allFilms{ films{ characterConnection{ characters{ name } } } } }";
                $responseGraphQL=$this->queryGraphQL($queryCharacters);

                //getting a list of films with characters
                $films=$responseGraphQL->data->allFilms->films;

                //store all characters on a list
                foreach($films as $f){
                    $characters=$f->characterConnection->characters;
                    $listCharacters=array_merge($listCharacters,$characters);
                }
                //deleting duplicates
                $listCharacters = array_map("unserialize", array_unique(array_map("serialize", $listCharacters)));
                //shuffle to get random characters
                shuffle($listCharacters);
                //showing the first 10 by the way
                $listCharacters=array_slice($listCharacters,0,9);
                //getting a list of characters in html code
                $listOfCharacters=$this->getListIn_UL_Tag($listCharacters,'name');
                $finalResponse="I haven't found any results, but here is a list of some Star Wars characters: ".$listOfCharacters;

            }else{
                $finalResponse= $response->answers[0]->message;
            }
        }

        return $finalResponse;
    }

}
