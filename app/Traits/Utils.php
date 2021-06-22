<?php 

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Session;



trait Utils
{

    /**
     * 
     * FUNCTION:
     *  Nos porporciona la API URL CHATBOT actual
     * 
     * PARAMETERS:
     *  Recibe la API KEY y el API KEY SECRET
     *  En principio, por temas de seguridad y como no es dinámico, API KEY y API KEY SECRET
     *  se guardan en Variables de entorno
     * 
     * RETURNS:
     *  No devuelve nada, pues se guarda la API URL CHATBOT en una sesión
     * 
     * Created: 19/06/2021
     * Last Modified: 21/06/2021
     * User: JCampos
     */
    public function getApiURLChatBot($API_KEY,$API_KEY_SECRET){

        //We need to get the api url to obtain yodas response
        $headers = [
            'x-inbenta-key' => $API_KEY,
            'Authorization' => 'Bearer '.session('ACCESS_TOKEN')
        ];
        $response = Http::withHeaders($headers)->get('https://api-eu.inbenta.io/v1/apis');
        $response = json_decode($response);
        $chatbotApiUrl = $response->apis->chatbot;
        session::put('API_URL_CHATBOT',$chatbotApiUrl."/v1"); //Where is the version??? is always 1??
    }


    /**
     * 
     * FUNCTION:
     *  Nos porporciona el ACCESS TOKEN para poder realizar las peticiones post a la API
     * 
     * PARAMETERS:
     *  Recibe la API KEY y el API KEY SECRET
     *  En principio, por temas de seguridad y como no es dinámico, API KEY y API KEY SECRET
     *  se guardan en Variables de entorno
     * 
     * RETURNS:
     *  No devuelve nada, pues se guarda el ACCESS TOKEN en una sesión
     * 
     * Created: 19/06/2021
     * Last Modified: 20/06/2021
     * User: JCampos
     */
    public function getAccessToken($API_KEY,$API_KEY_SECRET){

        $headers = [
            'x-inbenta-key' => $API_KEY,
            'Content-Type' => 'application/json'
        ];

        $body = [
            'secret' => $API_KEY_SECRET
        ];

        $response = Http::withHeaders($headers)->post('https://api-eu.inbenta.io/v1/auth/', $body);
        $response = json_decode($response);
        $accessToken = $response->accessToken;
        $expiration = $response->expiration;  
        session::put('expiration',$expiration);
        session::put('ACCESS_TOKEN',$accessToken);

    }

    /**
     * 
     * FUNCTION:
     *  Nos porporciona la capacidad de refrescar el ACCESS TOKEN
     * 
     * PARAMETERS:
     *  Recibe la API KEY, el API KEY SECRET y el ACCESS TOKEN para poder resetar este mismo
     *  En principio, por temas de seguridad y como no es dinámico, API KEY y API KEY SECRET
     *  se guardan en Variables de entorno, sin embargo, el access token es variable por lo que,
     *  estará almacenado en una sesión
     * 
     * RETURNS:
     *  No devuelve nada, pues se guarda el ACCESS TOKEN en una sesión
     * 
     * Created:  21/06/2021
     * Last Modified: 21/06/2021
     * User: JCampos
     */
    public function refreshToken($API_KEY,$API_KEY_SECRET,$accessToken){
        $headers = [
            'x-inbenta-key' => $API_KEY,
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer '.$accessToken
        ];

        $body = [
            'secret' => $API_KEY_SECRET
        ];

        $response = Http::withHeaders($headers)->post('https://api-eu.inbenta.io/v1/refreshToken', $body);
        $response = json_decode($response);
        $accessToken = $response->accessToken;
        $expiration = $response->expiration;
        session::put('expiration',$expiration);
        session::put('ACCESS_TOKEN',$accessToken);
    }

     /**
     * PARAMETERS:
     *  Recibe el propio string con la query
     * 
     * RETURNS:
     *  Devuelve la respuesta de la API en formato JSON
     * 
     * Created: 20/06/2021
     * Last Modified: 20/06/2021
     * User: JCampos
     */
    public function queryGraphQL($query){
        $headers = [
            'Content-Type' => 'application/json'
        ];

        $body = [
            'query' => $query,
            'variables' => []
        ];

        $response = Http::withHeaders($headers)->post(env('URL_GRAPHQL'), $body);
        $response = json_decode($response);

        return $response;
    }

    /**
     * PARAMETERS:
     *  Recibe el un array para iterar y una variable a la que acceder
     * 
     * RETURNS:
     *  Devuelve un response que es un string con el formato de una lista
     * 
     * Created: 20/06/2021
     * Last Modified: 20/06/2021
     * User: JCampos
     */
    public function getListIn_UL_Tag($elements,$var){
        
        $response="<ul style='margin-bottom: 0rem; margin-left: 1rem;'>";
        foreach($elements as $e){
            $response.="<li>".$e->$var."</li>";
        }
        $response.="</ul>";

        return $response;
    }


}

