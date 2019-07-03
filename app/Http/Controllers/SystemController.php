<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Generado;
class SystemController extends Controller
{
    public function sendNotification(Request $request){
        $title = 'Stake Service';
        $body = 'Tienes un nuevo STAKE';

        $tokens = DB::table('usuario')->where('id', '=', 1)->pluck('code_notify');

        $notification = array(
            "title" => $title,
            "body" => $body
        );

        $message = array("message" => "Message from serve", "customKey" => "customValue");

        $url = "https://fcm.googleapis.com/fcm/send ";
        $fields = array(
            "registration_ids" => $tokens,
            "notification" => $notification,
            "data" => $message
        );
        $header = array(
            'Content-Type: application/json',
            'Authorization: key=AAAAFAPR0Oc:APA91bG-erQyGsb14ITv8WifHVv3tiVUMdP7w__9JAZ4DsCkMeNw_5qIIrM6iVS8yvqm5GEeiOjA5rIAECbGy9fmI4vWcT1vSel9RXDhWbgTxhJPCoVtYT-aCQDcFJhVY2ZQybC9xOff'
        );

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        $result = curl_exec($ch);

        if($result == false){
            die('Curl failed : ' . curl_error($ch));
        } else {
            echo 'envio con exito';
        }
        curl_close($ch);
    }
    public function generate(Request $request){
            $nro = rand(1,4);
            $usuarios = DB::table('usuario')->select('*')->get();
            $balances = DB::table('balance')->where('moneda_id', $nro)->select('cantidad')->get();
            $generado = 0;
            switch ($nro) {
                case '1':
                    $generado = 110.25;
                    break;
                case '2':
                    $generado = 33.31;
                    break;
                case '3':
                    $generado = 1000;
                    break;    
                case '4':
                    $generado = 1350;
                    break;    
            }
            $pool = DB::table('pool')->where('id', $nro)->select('participantes')->get()[0];
            $cantidad = ($generado / $pool->participantes);
            $cantidad = substr($cantidad, 0,10);
            $i = 0;
            $txid= str_random(64);
            foreach ($usuarios as $user) {
                DB::table('notificacion')
                    ->where('usuario_id', $user->id)
                    ->update(['nuevo' =>1]);
                 DB::table('notificacion')
                    ->where('usuario_id', $user->id)
                    ->update(['mensaje' => 'Stake generado, ve a la pestaña "Generado"']);    
               $balancenuevo = $balances[$i]->cantidad + $cantidad;
               $balancenuevo = substr($balancenuevo, 0,10);     
                DB::table('balance')
                    ->where([
                        ['usuario_id', '=', $user->id],
                        ['moneda_id', '=', $nro],
                    ])
                    ->update(['cantidad' =>$balancenuevo]);
                Generado::create([
                    'cantidad' => $cantidad,
                    'txid' => 'Generated',
                    'estado' => 'Acreditado',
                    'moneda_id' => $nro,
                    'user_id' => $user->id,
                    'usuario_id' => $user->id,
                ]);    
                    $i++;
            }
            DB::table('pool')
                    ->where('id', $nro)
                    ->update(['ultimo_generado' =>$generado]);
            DB::table('pool')
                    ->where('id', $nro)
                    ->update(['ultimo_txid' => $txid]);

            //$this->sendNotification($request);
            return redirect()->to('gen');        
    }
}
