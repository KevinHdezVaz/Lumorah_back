<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Notifications\WelcomeEmailNotification;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Auth as FirebaseAuth;

class AuthController extends Controller
{
 
    protected $firebaseAuth;


    public function __construct()
    {
        // Configuración de Firebase
        $serviceAccountPath = storage_path('app/firebase/lumorah-765ad-firebase-adminsdk-fbsvc-8c924161ee.json');
        
        if (!file_exists($serviceAccountPath)) {
            throw new \RuntimeException("Archivo de configuración de Firebase no encontrado");
        }

        $this->firebaseAuth = (new Factory)
            ->withServiceAccount($serviceAccountPath)
            ->createAuth();
    }


    
    public function facebookLogin(Request $request)
{
    try {
        $validator = Validator::make($request->all(), [
            'id_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $idToken = $request->input('id_token');
        
        \Log::info("Token Facebook recibido: " . substr($idToken, 0, 30) . "...");

        $verifiedIdToken = $this->firebaseAuth->verifyIdToken($idToken, true);
        $claims = $verifiedIdToken->claims();
        
        $firebaseUid = $claims->get('sub');
        $email = $claims->get('email');
        $name = $claims->get('name') ?? 'Usuario Facebook';

        // Verificar que el proveedor sea Facebook
        $signInProvider = $claims->get('firebase')['sign_in_provider'] ?? '';
        if ($signInProvider !== 'facebook.com') {
            throw new \Exception("Token no emitido por Facebook");
        }

        $user = Usuario::firstOrCreate(
            ['email' => $email],
            [
                'nombre' => $name,
                'password' => Hash::make(uniqid()),
                'firebase_uid' => $firebaseUid,
                'auth_provider' => 'facebook'
            ]
        );

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'user' => $user,
            'token' => $token,
        ]);

    } catch (\Exception $e) {
        \Log::error("Error en facebookLogin: " . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Error en autenticación con Facebook',
            'error' => $e->getMessage(),
        ], 401);
    }
}


    public function googleLogin(Request $request)
    {
        try {
            // Validación del token
            $validator = Validator::make($request->all(), [
                'id_token' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $idToken = $request->input('id_token');
            
            \Log::info("Token recibido: " . substr($idToken, 0, 30) . "...");

            try {
                // Verificación del token con Firebase
                $verifiedIdToken = $this->firebaseAuth->verifyIdToken($idToken, true);
                $claims = $verifiedIdToken->claims();
                
                // Información del usuario
                $firebaseUid = $claims->get('sub');
                $email = $claims->get('email');
                $name = $claims->get('name') ?? 'Usuario Google';
                $audience = $claims->get('aud');

                // Debug: información completa del token
                \Log::info("Token decodificado:", [
                    'issuer' => $claims->get('iss'),
                    'audience' => $audience,
                    'email' => $email,
                    'firebase_uid' => $firebaseUid
                ]);

                // Verificación del audience
                $serviceAccount = json_decode(file_get_contents(
                    storage_path('app/firebase/lumorah-765ad-firebase-adminsdk-fbsvc-8c924161ee.json')
                ), true);
                
                $expectedAudience = $serviceAccount['project_id'] ?? null;
                
                if (!$expectedAudience) {
                    throw new \Exception("Project ID no configurado en service account");
                }

                // Manejo de audience como array o string
                $audienceMatch = false;
                if (is_array($audience)) {
                    $audienceMatch = in_array($expectedAudience, $audience);
                } else {
                    $audienceMatch = ($audience === $expectedAudience);
                }

                if (!$audienceMatch) {
                    throw new \Exception(sprintf(
                        "Audience no coincide. Esperado: %s, Recibido: %s",
                        $expectedAudience,
                        is_array($audience) ? json_encode($audience) : $audience
                    ));
                }

                // Buscar o crear usuario
                $user = Usuario::firstOrCreate(
                    ['email' => $email],
                    [
                        'nombre' => $name,
                        'password' => Hash::make(uniqid()),
                        'firebase_uid' => $firebaseUid,
                    ]
                );

                // Generar token de acceso
                $token = $user->createToken('auth_token')->plainTextToken;

                return response()->json([
                    'success' => true,
                    'user' => [
                        'id' => $user->id,
                        'nombre' => $user->nombre,
                        'email' => $user->email,
                    ],
                    'token' => $token,
                ]);

            } catch (\Throwable $e) {
                \Log::error("Error al verificar token:", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'token_sample' => substr($idToken, 0, 50)
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Error en verificación de token',
                    'error' => $e->getMessage(),
                ], 401);
            }

        } catch (\Exception $e) {
            \Log::error("Error general en googleLogin: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error en autenticación con Google',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    
private function decodeTokenHeader($token)
{
    try {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return ['error' => 'Estructura de token inválida'];
        }
        $header = json_decode(base64_decode($parts[0]), true);
        return $header;
    } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
    }
}


    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:usuarios',
            'password' => 'required|string|min:6',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
    
        try {
            $user = Usuario::create([
                'nombre' => $request->nombre,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);
            
            \Log::info('Enviando correo de bienvenida a ' . $user->email);

            $user->notify(new WelcomeEmailNotification());

            
            
            // Cambia esta parte para obtener solo el token plain text
            $tokenResult = $user->createToken('auth_token');
            $plainTextToken = $tokenResult->plainTextToken; // Esta es la clave
    
            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'nombre' => $user->nombre,
                    'email' => $user->email
                ],
                'token' => $plainTextToken, // Envía solo el string del token
            ], 201);
    
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error en el servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function profile(Request $request)
    {
        try {
            $user = $request->user();
            return response()->json([
                'id' => $user->id,
                'nombre' => $user->nombre,
                'email' => $user->email,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener el perfil',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }
    
        $user = Usuario::where('email', $request->email)->first();
    
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Credenciales inválidas'], 401);
        }
    
        $tokenResult = $user->createToken('auth_token');
        $plainTextToken = $tokenResult->plainTextToken;
    
        return response()->json([
            'token' => $plainTextToken,
            'user' => $user,
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->token()->revoke();
        return response()->json(['message' => 'Sesión cerrada correctamente'], 200);
    }
}