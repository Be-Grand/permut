<?php

namespace App\Http\Controllers;

use App\User;
use App\Models\Favorite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Password;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Auth\Events\PasswordReset;

use Illuminate\Auth\Events\Registered;



class AdminController extends Controller
{
   
    use SendsPasswordResetEmails, ResetsPasswords {
        SendsPasswordResetEmails::broker insteadof ResetsPasswords;
        ResetsPasswords::credentials insteadof SendsPasswordResetEmails;
    }

    public function sendPasswordResetLink(Request $request)
    {
        return $this->sendResetLinkEmail($request);
    }
   
    protected function sendResetLinkResponse(Request $request, $response)
    {
        return response()->json([
            'success'=>true,
            'message' => 'Link para recuperação de conta foi enviada.',
            'data' => $response
        ]);
    }
    
    protected function sendResetLinkFailedResponse(Request $request, $response)
    {
        return response()->json([
            'success'=>false,
            'message' => 'Não foi possível enviar e-mail para este endereço de e-mail.']);
    }

    public function callResetPassword(Request $request)
    {
        return $this->reset($request);
    }
 
    protected function resetPassword($user, $password)
    {
        $user->password = Hash::make($password);
        $user->save();
        event(new PasswordReset($user));
    }
    
    protected function sendResetResponse(Request $request, $response)
    {
        return response()->json([
            'success'=>false,
            'message' => 'Conta recuperada com sucesso.']);
    }
   
    protected function sendResetFailedResponse(Request $request, $response)
    {
        return response()->json([
            'success'=>false,
            'message' => 'Token inválido.']);
    }
    public function login(Request $request){
        $credentials = $request->only('email', 'password');
        try {
            if (! $token = JWTAuth::attempt($credentials)) {
                return response()->json(['message' => 'E-mail ou senha errada.', 'success'=>false]);
            }
        } catch (JWTException $e) {
            return response()->json(['message' => 'Não foi possivel criar um token', 'success'=>false], 500);
        }
        $user = User::where('email', $request->email)->first();
        if ($user->email_verified_at == null){
            return response()->json(['message' => 'Verifique a sua caixa de entrada de e-mail para activar a sua conta', 'success'=>false]);
        }
        if ($user->type != 1){
            return response()->json(['message' => 'Está conta não pode iniciar a sessão neste módulo.', 'success'=>false]);
        }
        else {
            return response()->json(['message'=> 'Login efectuado com sucesso', 'success'=> true, 'token'=>$token, 'user'=>$user]);
        }
      
    }

    public function logout(Request $request) {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return response()->json(['message'=> "Você foi deslogado com sucesso!", 'success' => true]);
        } catch (JWTException $e) {
            return response()->json(['message' => 'Falha ao fazer ao terminar a sessão.', 'success' => false], 500);
        }
    }

    public function register(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:6|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',

        ]);

        if($validator->fails()){
            return response()->json([$validator->errors(), 'success' => false], 400);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'whatsapp' => $request->whatsapp,
            'city_id' => $request->city_id,
            'institution_id' => $request->institution_id,
            'position_id' => $request->position_id,
            'type'=> '1',
            'licensed'=> '0',
            'password' => Hash::make($request->password),
        ]);
        event(new Registered($user));
        $token = JWTAuth::fromUser($user);
        return response()->json(['message'=> 'Conta criada com sucesso.', 'success'=> true,  'user'=>$user], 201);

    }

    public function recover(Request $request){
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['success' => false, 'message' => "E-mail não encontrado."], 401);
        }
        try {
            Password::sendResetLink($request->only('email'), function (Message $message) {
                $message->subject('Foi enviado o link de recuperação');
            });
        } catch (\Exception $e) {
            //Return with error
            $error_message = $e->getMessage();
            return response()->json(['success' => false, 'message' => $error_message], 401);
        }
        return response()->json([
            'success' => true, 'message'=> 'Foi enviado um link de recuperação de conta no seu e-mail.'
        ]);
    }
    public function update(Request $request){
        $user = JWTAuth::parseToken()->authenticate();

        $user->name = $request->name;
        $user->city_id = $request->city_id;
        $user->institution_id = $request->institution_id;
        $user->position_id = $request->position_id;
        $user->whatsapp = $request->whatsapp;

        $name =null;
        $allowedExtensions = ['png', 'jpg', 'jpeg'];
        if (!empty($request->imageToUpload)) {
           
            $explode = explode(',', $request->imageToUpload);
            if(count($explode) == 2){
                
                $format = str_replace(
                    ['data:image/', ';', 'base64'], 
                    ['', '', '',], 
                    $explode[0]
                );
                if (in_array($format, $allowedExtensions)) {
                    $decoded = base64_decode($explode[1]);
                    if (str_contains($explode[0], 'jpeg')){
                        $extension ='jpg';
                    }
                    else{
                        $extension ='png';
                    }
                    if ($user->avatar){
                        $expl= explode('.', $user->avatar);
                        $name = $expl[0];
                    }
                    else {
                        $name = $user->id.kebab_case($user->name);
                    }
                    $fileName ="{$name}.{$extension}";
                    $path= public_path().'/storage/users/'. $fileName;
                    file_put_contents($path, $decoded);
                    $user->avatar = $fileName;
                }
            }
        }
        if ($user->save()){
            return response()->json(['success' => true, 'message' => "Dados actualizados com sucesso.", 'data' => $user]);
        }
        return response()->json(['success' => false, 'message' => "Dados não actualizados", 'data' => $user]);
    }
 
    public function delete(Request $request){
        $user = JWTAuth::parseToken()->authenticate();
        $user->delete();
        return response()->json(['success' => true, 'message' => "Conta Apagada com sucesso."]);
    }
    public function updatePassword(Request $request){
        $user = JWTAuth::parseToken()->authenticate();
     
        if(password_verify($request->old_password, $user->password)){
            $user->password = bcrypt($request->password);
            $user->save();
            return response()->json(['message' => 'Senha actualizada com sucesso!', 'success' => true]); 
        }
       
        return response()->json(['message' => 'Erro ao actualizar a senha!', 'success' => false]); 
    }
  
    public function get(Request $request){
    
        return response()->json(['success' => true, 'message' => "Operação realizada com sucesso.",'data' =>$request->user()]);
        
    }
    
}
